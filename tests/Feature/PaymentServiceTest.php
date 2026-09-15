<?php

use App\Models\Payment;
use App\Models\Subscription;
use App\Models\User;
use App\Services\InstallmentScheduleService;
use App\Services\PaymentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

uses(RefreshDatabase::class);

function paymentData(string $amount, ?string $key = null): array
{
    return ['idempotency_key' => $key ?? (string) Str::uuid(), 'payment_date' => '2026-09-15 10:00:00', 'amount' => $amount, 'currency' => 'USD', 'payment_method' => 'cash'];
}

it('allocates a partial payment to the oldest installment', function () {
    $subscription = Subscription::factory()->create();
    app(InstallmentScheduleService::class)->generate($subscription);

    $payment = app(PaymentService::class)->record($subscription, User::factory()->create(), paymentData('150.00'));

    expect($payment->allocations)->toHaveCount(1)
        ->and($payment->allocations->first()->installment->installment_number)->toBe(1)
        ->and($subscription->refresh()->amount_paid)->toBe('150.00')
        ->and($subscription->financial_status)->toBe('partially_paid');
});

it('allocates across multiple installments oldest first including future installments', function () {
    $subscription = Subscription::factory()->create();
    app(InstallmentScheduleService::class)->generate($subscription);

    $payment = app(PaymentService::class)->record($subscription, User::factory()->create(), paymentData('750.00'));

    expect($payment->allocations->pluck('amount')->all())->toBe(['300.00', '300.00', '150.00'])
        ->and($payment->allocations->pluck('installment.installment_number')->all())->toBe([1, 2, 3]);
});

it('recalculates subscription and plot financial status when fully paid', function () {
    $subscription = Subscription::factory()->create(['contract_total' => '3600.00', 'monthly_amount' => '300.00', 'duration_months' => 12]);
    app(InstallmentScheduleService::class)->generate($subscription);

    app(PaymentService::class)->record($subscription, User::factory()->create(), paymentData('3600.00'));

    expect($subscription->refresh()->amount_paid)->toBe('3600.00')->and($subscription->balance)->toBe('0.00')->and($subscription->financial_status)->toBe('paid')->and($subscription->plot->financial_status)->toBe('paid');
});

it('rolls back a payment that exceeds the subscription balance', function () {
    $subscription = Subscription::factory()->create();

    expect(fn () => app(PaymentService::class)->record($subscription, User::factory()->create(), paymentData('4000.00')))->toThrow(ValidationException::class);
    $this->assertDatabaseCount('payments', 0);
});

it('is idempotent for repeated submissions', function () {
    $subscription = Subscription::factory()->create();
    app(InstallmentScheduleService::class)->generate($subscription);
    $key = (string) Str::uuid();

    $first = app(PaymentService::class)->record($subscription, User::factory()->create(), paymentData('300.00', $key));
    $second = app(PaymentService::class)->record($subscription, User::factory()->create(), paymentData('300.00', $key));

    expect($second->id)->toBe($first->id)->and(Payment::query()->count())->toBe(1)->and($subscription->refresh()->amount_paid)->toBe('300.00');
});

it('reverses without deleting financial records and restores balances', function () {
    $subscription = Subscription::factory()->create();
    app(InstallmentScheduleService::class)->generate($subscription);
    $user = User::factory()->create();
    $payment = app(PaymentService::class)->record($subscription, $user, paymentData('450.00'));
    $allocationCount = $payment->allocations()->count();

    app(PaymentService::class)->reverse($payment, $user, 'Erreur de saisie du montant');

    expect($payment->refresh()->status)->toBe('reversed')->and($subscription->refresh()->amount_paid)->toBe('0.00')->and($subscription->financial_status)->toBe('unpaid')->and($payment->allocations()->count())->toBe($allocationCount);
    $this->assertDatabaseHas('audit_logs', ['action' => 'payment.reversed', 'entity_id' => $payment->id]);
});
