<?php

use App\Models\Installment;
use App\Models\Subscription;
use App\Services\InstallmentScheduleService;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('does not create monthly installments for a cash subscription', function () {
    $subscription = Subscription::factory()->create(['contract_total' => '2500.00', 'monthly_amount' => null, 'duration_months' => 0]);

    $installments = app(InstallmentScheduleService::class)->generate($subscription);

    expect($installments)->toBeEmpty();
});

it('generates the exact number dates and total for credit durations', function (int $months, string $total, string $monthly) {
    $subscription = Subscription::factory()->create(['start_date' => '2026-01-31', 'contract_total' => $total, 'monthly_amount' => $monthly, 'duration_months' => $months]);

    $installments = app(InstallmentScheduleService::class)->generate($subscription);

    expect($installments)->toHaveCount($months)
        ->and($installments->first()->due_date->toDateString())->toBe('2026-02-28')
        ->and(number_format((float) $installments->sum('amount_due'), 2, '.', ''))->toBe($total);
})->with([
    '12 mois' => [12, '3600.00', '300.00'], '36 mois' => [36, '6200.00', '175.00'],
    '60 mois' => [60, '7500.00', '125.00'], '120 mois' => [120, '9000.00', '75.00'],
]);

it('is idempotent when the schedule already exists', function () {
    $subscription = Subscription::factory()->create();
    $service = app(InstallmentScheduleService::class);

    $service->generate($subscription);
    $service->generate($subscription);

    expect($subscription->installments()->count())->toBe($subscription->duration_months);
});

it('calculates installment statuses consistently', function () {
    $subscription = Subscription::factory()->create();
    Installment::factory()->for($subscription)->create(['installment_number' => 1, 'due_date' => '2026-01-01', 'amount_paid' => '0.00']);
    Installment::factory()->for($subscription)->create(['installment_number' => 2, 'due_date' => '2026-02-01', 'amount_paid' => '100.00']);
    Installment::factory()->for($subscription)->create(['installment_number' => 3, 'due_date' => '2026-03-01', 'amount_paid' => '300.00']);
    Installment::factory()->for($subscription)->create(['installment_number' => 4, 'due_date' => '2026-04-01', 'amount_paid' => '0.00']);
    Installment::factory()->for($subscription)->create(['installment_number' => 5, 'due_date' => '2026-05-01', 'amount_paid' => '0.00']);

    app(InstallmentScheduleService::class)->refreshStatuses($subscription, CarbonImmutable::parse('2026-04-01'));

    expect($subscription->installments()->orderBy('installment_number')->pluck('status')->all())
        ->toBe(['overdue', 'partially_paid', 'paid', 'due', 'upcoming']);
});
