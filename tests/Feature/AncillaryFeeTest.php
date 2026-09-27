<?php

use App\Models\AncillaryFee;
use App\Models\Contract;
use App\Models\Payment;
use App\Models\Role;
use App\Models\Subscription;
use App\Models\User;
use App\Services\AncillaryFeeService;
use App\Services\PaymentService;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

uses(RefreshDatabase::class);

it('creates cadastral fees with the formula-specific due date', function (int $duration, string $endDate, string $subscriptionDate, string $expectedDueDate) {
    $subscription = Subscription::factory()->create([
        'duration_months' => $duration,
        'expected_end_date' => $endDate,
        'subscription_date' => $subscriptionDate,
        'development_payment_mode' => 'total',
    ]);
    $contract = Contract::query()->create([
        'contract_number' => 'CTR-'.Str::upper(Str::random(8)),
        'subscription_id' => $subscription->id,
        'signed_at' => '2026-01-15',
        'status' => 'signed',
    ]);

    app(AncillaryFeeService::class)->createForSignedContract($contract);

    $cadastralFees = $subscription->ancillaryFees()->whereIn('fee_type', [
        'cadastral_number', 'occupancy_certificate', 'registration_certificate',
    ])->get();

    expect($cadastralFees)->toHaveCount(3)
        ->and($cadastralFees->pluck('amount_due')->sort()->values()->all())->toBe(['30.00', '400.00', '800.00'])
        ->and($cadastralFees->pluck('due_date')->map(fn ($date) => $date->toDateString())->unique()->first())->toBe($expectedDueDate);
})->with([
    'cash due after purchase' => [0, '2026-01-15', '2026-01-15', '2026-01-15'],
    'one-year formula due three months before end' => [12, '2027-01-15', '2026-01-15', '2026-10-15'],
    'three-year formula due six months before end' => [36, '2029-01-15', '2026-01-15', '2028-07-15'],
    'five-year formula due one year before end' => [60, '2031-01-15', '2026-01-15', '2030-01-15'],
    'ten-year formula due two years before end' => [120, '2036-01-15', '2026-01-15', '2034-01-15'],
]);

it('creates a single development charge one month after signature when total payment is selected', function () {
    $subscription = Subscription::factory()->create([
        'duration_months' => 36,
        'development_payment_mode' => 'total',
    ]);
    $contract = Contract::query()->create([
        'contract_number' => 'CTR-'.Str::upper(Str::random(8)),
        'subscription_id' => $subscription->id,
        'signed_at' => '2026-01-31',
        'status' => 'signed',
    ]);

    app(AncillaryFeeService::class)->createForSignedContract($contract);

    $developmentFees = $subscription->ancillaryFees()->where('fee_type', 'development')->get();

    expect($developmentFees)->toHaveCount(1)
        ->and($developmentFees->first()->amount_due)->toBe('1080.00')
        ->and($developmentFees->first()->due_date->toDateString())->toBe('2026-02-28');
});

it('creates 36 monthly development fees and does not duplicate fees when a signed contract is saved again', function () {
    $subscription = Subscription::factory()->create([
        'duration_months' => 12,
        'development_payment_mode' => 'monthly',
    ]);
    $contract = Contract::query()->create([
        'contract_number' => 'CTR-'.Str::upper(Str::random(8)),
        'subscription_id' => $subscription->id,
        'signed_at' => '2026-01-31',
        'status' => 'signed',
    ]);

    $service = app(AncillaryFeeService::class);
    $service->createForSignedContract($contract);
    $service->createForSignedContract($contract);

    $developmentFees = $subscription->ancillaryFees()->where('fee_type', 'development')->orderBy('installment_number')->get();

    expect($developmentFees)->toHaveCount(36)
        ->and($developmentFees->first()->amount_due)->toBe('40.00')
        ->and($developmentFees->first()->due_date->toDateString())->toBe('2026-02-28')
        ->and($developmentFees->last()->due_date->toDateString())->toBe('2029-01-31')
        ->and($subscription->ancillaryFees()->count())->toBe(39);
});

it('creates the bornage fee only once when its completion is recorded', function () {
    $subscription = Subscription::factory()->create();
    $user = User::factory()->create();
    $service = app(AncillaryFeeService::class);

    $first = $service->realizeSurvey($subscription, $user);
    $second = $service->realizeSurvey($subscription, $user);

    expect($second->id)->toBe($first->id)
        ->and($first->fee_type)->toBe('survey')
        ->and($first->amount_due)->toBe('50.00')
        ->and($first->due_date->toDateString())->toBe(now()->toDateString());
    $this->assertDatabaseHas('audit_logs', ['action' => 'ancillary_fee.survey_realized', 'entity_id' => $first->id]);
});

it('lets authorized subscription staff mark bornage as realized once', function () {
    $this->seed([RoleSeeder::class, PermissionSeeder::class]);
    $commercial = User::factory()->create();
    $commercial->roles()->attach(Role::query()->where('name', 'commercial')->firstOrFail());
    $subscription = Subscription::factory()->create();

    $this->actingAs($commercial)->post(route('subscriptions.bornage.realize', $subscription))->assertRedirect();
    $this->post(route('subscriptions.bornage.realize', $subscription))->assertRedirect();

    expect($subscription->ancillaryFees()->where('fee_type', 'survey')->count())->toBe(1);
    $this->assertDatabaseHas('ancillary_fees', [
        'subscription_id' => $subscription->id,
        'fee_type' => 'survey',
        'amount_due' => '50.00',
    ]);
});

it('records and reverses a fee payment with its own receipt without changing the contract balance', function () {
    Storage::fake('local');
    $subscription = Subscription::factory()->create(['amount_paid' => '0.00']);
    $fee = AncillaryFee::factory()->for($subscription)->create(['amount_due' => '30.00']);
    $user = User::factory()->create();
    $data = [
        'idempotency_key' => (string) Str::uuid(),
        'payment_date' => now()->toDateTimeString(),
        'amount' => '30.00',
        'currency' => 'USD',
        'payment_method' => 'cash',
    ];

    $payment = app(PaymentService::class)->recordAncillaryFee($fee, $user, $data);

    expect($fee->refresh()->status)->toBe('paid')
        ->and($fee->amount_paid)->toBe('30.00')
        ->and($subscription->refresh()->amount_paid)->toBe('0.00')
        ->and($payment->ancillary_fee_id)->toBe($fee->id)
        ->and($payment->receipt)->not->toBeNull();
    Storage::disk('local')->assertExists($payment->receipt->pdf_path);

    app(PaymentService::class)->reverse($payment, $user, 'Correction du versement du frais');

    expect($fee->refresh()->amount_paid)->toBe('0.00')
        ->and($fee->status)->toBe('due')
        ->and($payment->receipt->fresh()->status)->toBe('cancelled')
        ->and($subscription->refresh()->amount_paid)->toBe('0.00');
});

it('rejects a fee payment larger than the remaining fee balance', function () {
    $subscription = Subscription::factory()->create();
    $fee = AncillaryFee::factory()->for($subscription)->create(['amount_due' => '30.00']);
    $data = [
        'idempotency_key' => (string) Str::uuid(),
        'payment_date' => now()->toDateTimeString(),
        'amount' => '31.00',
        'currency' => 'USD',
        'payment_method' => 'cash',
    ];

    expect(fn () => app(PaymentService::class)->recordAncillaryFee($fee, User::factory()->create(), $data))
        ->toThrow(ValidationException::class);
    $this->assertDatabaseCount('payments', 0);
});

it('lets an authorized cashier record an individual fee payment and creates a receipt', function () {
    Storage::fake('local');
    $this->seed([RoleSeeder::class, PermissionSeeder::class]);
    $cashier = User::factory()->create();
    $cashier->roles()->attach(Role::query()->where('name', 'cashier')->firstOrFail());
    $subscription = Subscription::factory()->create();
    $fee = AncillaryFee::factory()->for($subscription)->create(['amount_due' => '30.00']);

    $this->actingAs($cashier)->post(route('payments.ancillary.store', $fee), [
        'idempotency_key' => (string) Str::uuid(),
        'payment_date' => now()->toDateTimeString(),
        'amount' => '30.00',
        'currency' => 'USD',
        'payment_method' => 'cash',
    ])->assertRedirect();

    expect($fee->refresh()->status)->toBe('paid');
    $payment = Payment::query()->where('ancillary_fee_id', $fee->id)->firstOrFail();
    expect($payment->receipt)->not->toBeNull();
    Storage::disk('local')->assertExists($payment->receipt->pdf_path);
});

it('denies a customer access to the staff fee-payment form', function () {
    $this->seed([RoleSeeder::class, PermissionSeeder::class]);
    $customer = User::factory()->create();
    $customer->roles()->attach(Role::query()->where('name', 'customer')->firstOrFail());
    $fee = AncillaryFee::factory()->create();

    $this->actingAs($customer)->get(route('payments.ancillary.create', $fee))->assertForbidden();
});
