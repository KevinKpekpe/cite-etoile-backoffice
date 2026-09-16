<?php

use App\Models\Customer;
use App\Models\Payment;
use App\Models\Role;
use App\Models\Setting;
use App\Models\Subscription;
use App\Models\User;
use App\Services\PaymentService;
use App\Services\ReferenceGenerator;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Database\Seeders\SettingSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed([RoleSeeder::class, PermissionSeeder::class, SettingSeeder::class]);
    $this->admin = User::factory()->create();
    $this->admin->roles()->attach(Role::query()->where('name', 'admin')->firstOrFail());
});

function settingsPayload(array $overrides = []): array
{
    return array_replace_recursive([
        'company' => ['name' => 'MJIC Test', 'logo' => 'https://example.test/logo.png', 'phone' => '+243000000000', 'email' => 'contact@example.test', 'address' => 'Kinshasa'],
        'project' => ['name' => 'Étoile Test'],
        'finance' => ['currency' => 'CDF', 'payment_methods' => ['cash', 'mobile_money']],
        'customer' => ['prefix' => 'CUS'], 'payment' => ['prefix' => 'PMT'], 'receipt' => ['prefix' => 'RCP'], 'contract' => ['prefix' => 'CNT'],
        'subscription' => ['allow_partial_payment' => true, 'allow_advance_payment' => false],
    ], $overrides);
}

it('allows authorized administrators to update all setting groups and audits each change', function () {
    $this->actingAs($this->admin)->put(route('settings.update'), settingsPayload())->assertRedirect()->assertSessionHasNoErrors();

    $this->assertDatabaseHas('settings', ['setting_group' => 'finance', 'setting_key' => 'currency', 'value' => 'CDF']);
    expect(Setting::query()->where('updated_by', $this->admin->id)->count())->toBe(14);
    $this->assertDatabaseHas('audit_logs', ['action' => 'setting.updated', 'user_id' => $this->admin->id]);
});

it('rejects duplicate prefixes and invalid finance configuration', function () {
    $payload = settingsPayload(['payment' => ['prefix' => 'CUS'], 'finance' => ['currency' => 'US', 'payment_methods' => ['cheque']]]);

    $this->actingAs($this->admin)->put(route('settings.update'), $payload)
        ->assertSessionHasErrors(['customer.prefix', 'payment.prefix', 'finance.currency', 'finance.payment_methods.0']);
});

it('restricts settings to users with the settings permission', function () {
    $direction = User::factory()->create();
    $direction->roles()->attach(Role::query()->where('name', 'direction')->firstOrFail());

    $this->actingAs($direction)->get(route('settings.index'))->assertForbidden();
    $this->put(route('settings.update'), settingsPayload())->assertForbidden();
});

it('uses configured unique prefixes for generated references', function () {
    Setting::query()->where('setting_group', 'customer')->where('setting_key', 'prefix')->update(['value' => 'CUS']);
    $generator = app(ReferenceGenerator::class);
    $first = $generator->generate(Customer::class, 'customer_number', 'customer', 'CLI');
    Customer::factory()->create(['customer_number' => $first]);
    $second = $generator->generate(Customer::class, 'customer_number', 'customer', 'CLI');

    expect($first)->toStartWith('CUS-')->and($second)->toStartWith('CUS-')->and($second)->not->toBe($first);
});

it('enforces disabled partial and advance payment rules in the service', function () {
    Setting::query()->where('setting_group', 'subscription')->where('setting_key', 'allow_partial_payment')->update(['value' => 'false']);
    $cashSubscription = Subscription::factory()->create(['duration_months' => 0, 'monthly_amount' => null]);
    expect(fn () => app(PaymentService::class)->record($cashSubscription, $this->admin, ['idempotency_key' => (string) Str::uuid(), 'payment_date' => now(), 'amount' => '100.00', 'currency' => 'USD', 'payment_method' => 'cash']))->toThrow(ValidationException::class);

    Setting::query()->where('setting_group', 'subscription')->where('setting_key', 'allow_partial_payment')->update(['value' => 'true']);
    Setting::query()->where('setting_group', 'subscription')->where('setting_key', 'allow_advance_payment')->update(['value' => 'false']);
    $creditSubscription = Subscription::factory()->create();
    expect(fn () => app(PaymentService::class)->record($creditSubscription, $this->admin, ['idempotency_key' => (string) Str::uuid(), 'payment_date' => now(), 'amount' => '300.00', 'currency' => 'USD', 'payment_method' => 'cash']))->toThrow(ValidationException::class);
    expect(Payment::query()->count())->toBe(0);
});

it('validates payments against configured currency and methods', function () {
    Setting::query()->where('setting_group', 'finance')->where('setting_key', 'currency')->update(['value' => 'CDF']);
    Setting::query()->where('setting_group', 'finance')->where('setting_key', 'payment_methods')->update(['value' => '["mobile_money"]']);
    $subscription = Subscription::factory()->create();

    $this->actingAs($this->admin)->post(route('payments.store'), ['subscription_id' => $subscription->id, 'idempotency_key' => (string) Str::uuid(), 'payment_date' => now(), 'amount' => 100, 'currency' => 'USD', 'payment_method' => 'cash'])
        ->assertSessionHasErrors(['currency', 'payment_method']);
});
