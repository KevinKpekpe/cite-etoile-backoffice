<?php

use App\Models\Receipt;
use App\Models\Role;
use App\Models\Subscription;
use App\Models\User;
use App\Services\PaymentService;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

beforeEach(function () {
    Storage::fake('local');
    $this->seed([RoleSeeder::class, PermissionSeeder::class]);
    $this->admin = User::factory()->create(['first_name' => 'Agent', 'last_name' => 'Test']);
    $this->admin->roles()->attach(Role::query()->where('name', 'admin')->firstOrFail());
});

it('creates one verifiable PDF receipt with complete payment data', function () {
    $subscription = Subscription::factory()->create(['contract_total' => '2500.00', 'monthly_amount' => null, 'duration_months' => 0]);

    $payment = app(PaymentService::class)->record($subscription, $this->admin, receiptPaymentData('500.00'));
    $receipt = $payment->receipt()->firstOrFail();

    expect($receipt->receipt_number)->toStartWith('REC-')->and($receipt->verification_code)->not->toBeEmpty()->and($receipt->amount)->toBe('500.00');
    Storage::disk('local')->assertExists($receipt->pdf_path);
    expect(Storage::disk('local')->get($receipt->pdf_path))->toStartWith('%PDF');
});

it('returns the same receipt for an idempotent payment submission', function () {
    $subscription = Subscription::factory()->create(['duration_months' => 0, 'monthly_amount' => null]);
    $key = (string) Str::uuid();

    app(PaymentService::class)->record($subscription, $this->admin, receiptPaymentData('100.00', $key));
    app(PaymentService::class)->record($subscription, $this->admin, receiptPaymentData('100.00', $key));

    expect(Receipt::query()->count())->toBe(1);
});

it('verifies a receipt publicly without exposing customer identity', function () {
    $subscription = Subscription::factory()->create(['duration_months' => 0, 'monthly_amount' => null]);
    $receipt = app(PaymentService::class)->record($subscription, $this->admin, receiptPaymentData('100.00'))->receipt()->firstOrFail();

    $this->get(route('receipts.verify', $receipt->verification_code))->assertOk()
        ->assertSee($receipt->receipt_number)->assertSee('Ce reçu est valide.')->assertDontSee($subscription->customer->first_name);
});

it('reprints the same PDF and audits the download', function () {
    $subscription = Subscription::factory()->create(['duration_months' => 0, 'monthly_amount' => null]);
    $receipt = app(PaymentService::class)->record($subscription, $this->admin, receiptPaymentData('100.00'))->receipt()->firstOrFail();

    $this->actingAs($this->admin)->get(route('receipts.download', $receipt))->assertDownload($receipt->receipt_number.'.pdf');

    expect(Receipt::query()->count())->toBe(1);
    $this->assertDatabaseHas('audit_logs', ['action' => 'receipt.reprinted', 'entity_id' => $receipt->id]);
});

it('cancels the linked receipt when its payment is reversed', function () {
    $subscription = Subscription::factory()->create(['duration_months' => 0, 'monthly_amount' => null]);
    $payment = app(PaymentService::class)->record($subscription, $this->admin, receiptPaymentData('100.00'));

    app(PaymentService::class)->reverse($payment, $this->admin, 'Paiement enregistré par erreur');

    expect($payment->receipt()->firstOrFail()->status)->toBe('cancelled');
});

function receiptPaymentData(string $amount, ?string $key = null): array
{
    return ['idempotency_key' => $key ?? (string) Str::uuid(), 'payment_date' => '2026-09-16 10:00:00', 'amount' => $amount, 'currency' => 'USD', 'payment_method' => 'cash'];
}
