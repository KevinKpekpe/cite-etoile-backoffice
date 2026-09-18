<?php

use App\Models\Customer;
use App\Models\Installment;
use App\Models\Payment;
use App\Models\PaymentPlan;
use App\Models\Plot;
use App\Models\Role;
use App\Models\Subscription;
use App\Models\User;
use App\Services\InstallmentScheduleService;
use App\Services\PaymentService;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Database\Seeders\SettingSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

/**
 * Phase 17 – QA / Recette
 *
 * End-to-end workflow test that walks the full customer lifecycle:
 *   1. Commercial creates a customer and subscription.
 *   2. Cashier records a payment → receipt is generated automatically.
 *   3. Direction reverses the payment → receipt is cancelled.
 *   4. Finance manager consults reports and customer statement.
 *   5. Customer user accesses their portal and sees only their own data.
 *   6. Admin updates settings; super_admin accesses all back-office sections.
 */
beforeEach(function () {
    $this->seed([RoleSeeder::class, PermissionSeeder::class, SettingSeeder::class]);

    // Bootstrap actors
    $this->commercial = User::factory()->create(['first_name' => 'Commercial', 'last_name' => 'Test']);
    $this->commercial->roles()->attach(Role::query()->where('name', 'commercial')->firstOrFail());

    $this->cashier = User::factory()->create(['first_name' => 'Cashier', 'last_name' => 'Test']);
    $this->cashier->roles()->attach(Role::query()->where('name', 'cashier')->firstOrFail());

    $this->direction = User::factory()->create(['first_name' => 'Direction', 'last_name' => 'Test']);
    $this->direction->roles()->attach(Role::query()->where('name', 'direction')->firstOrFail());

    $this->finance = User::factory()->create(['first_name' => 'Finance', 'last_name' => 'Test']);
    $this->finance->roles()->attach(Role::query()->where('name', 'finance_manager')->firstOrFail());

    $this->admin = User::factory()->create(['first_name' => 'Admin', 'last_name' => 'Test']);
    $this->admin->roles()->attach(Role::query()->where('name', 'admin')->firstOrFail());

    $this->superAdmin = User::factory()->create(['first_name' => 'SuperAdmin', 'last_name' => 'Test']);
    $this->superAdmin->roles()->attach(Role::query()->where('name', 'super_admin')->firstOrFail());
});

// ---------------------------------------------------------------------------
// Step 1 – Commercial creates customer and subscription
// ---------------------------------------------------------------------------

it('allows commercial to create a customer and subscribe them to a plot', function () {
    $plot = Plot::factory()->create(['reference' => 'LOT-E12', 'commercial_status' => 'available']);
    $plan = PaymentPlan::factory()->create([
        'name' => 'Crédit 24 mois',
        'total_price' => '2400.00',
        'monthly_amount' => '100.00',
        'duration_months' => 24,
        'active' => true,
    ]);

    // Create customer
    $this->actingAs($this->commercial)
        ->post(route('customers.store'), [
            'first_name' => 'Jean', 'last_name' => 'Mbeki', 'middle_name' => null,
            'phone' => '+243900000001', 'secondary_phone' => null, 'whatsapp' => null,
            'email' => 'jean.mbeki@example.test', 'status' => 'active',
            'commune' => 'Mont-Ngafula', 'city' => 'Kinshasa', 'country' => 'RDC', 'address' => '12 Av. de la Paix',
        ])
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    $customer = Customer::query()->where('email', 'jean.mbeki@example.test')->firstOrFail();
    $this->assertDatabaseHas('audit_logs', ['action' => 'customer.created', 'entity_id' => $customer->id]);

    // Create subscription — always starts as pending, plot becomes reserved.
    $this->actingAs($this->commercial)
        ->post(route('subscriptions.store'), [
            'customer_id' => $customer->id, 'plot_id' => $plot->id, 'payment_plan_id' => $plan->id,
            'subscription_date' => '2026-09-01', 'start_date' => '2026-10-01',
        ])
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    $subscription = Subscription::query()->firstOrFail();
    expect($subscription->contract_total)->toBe('2400.00')
        ->and($subscription->monthly_amount)->toBe('100.00')
        ->and($subscription->commercial_status)->toBe('pending')
        ->and($plot->refresh()->commercial_status)->toBe('reserved');

    $this->assertDatabaseHas('audit_logs', ['action' => 'subscription.created', 'entity_id' => $subscription->id]);
});

// ---------------------------------------------------------------------------
// Step 2 – Cashier records a payment → receipt is generated automatically
// ---------------------------------------------------------------------------

it('allows cashier to record a payment and generates a receipt automatically', function () {
    Storage::fake('local');

    $subscription = Subscription::factory()->create(['contract_total' => '1200.00', 'monthly_amount' => '100.00', 'duration_months' => 12]);

    $this->actingAs($this->cashier)
        ->post(route('payments.store'), [
            'subscription_id' => $subscription->id,
            'idempotency_key' => (string) Str::uuid(),
            'payment_date' => now()->format('Y-m-d'),
            'amount' => '100.00',
            'currency' => 'USD',
            'payment_method' => 'cash',
        ])
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    $payment = Payment::query()->firstOrFail();
    expect($payment->status)->toBe('validated')
        ->and($payment->amount)->toBe('100.00');

    // Receipt must be auto-generated
    $this->assertDatabaseHas('receipts', ['payment_id' => $payment->id, 'status' => 'valid']);
    $this->assertDatabaseHas('audit_logs', ['action' => 'payment.created', 'entity_id' => $payment->id]);

    // At least one installment must have been allocated
    $this->assertDatabaseHas('payment_allocations', ['payment_id' => $payment->id]);
});

// ---------------------------------------------------------------------------
// Step 3 – Direction reverses the payment
// ---------------------------------------------------------------------------

it('allows finance_manager to reverse a validated payment and cancels the receipt', function () {
    Storage::fake('local');

    $subscription = Subscription::factory()->create(['contract_total' => '800.00', 'duration_months' => 0, 'monthly_amount' => null]);
    $payment = app(PaymentService::class)->record(
        $subscription,
        $this->cashier,
        ['idempotency_key' => (string) Str::uuid(), 'payment_date' => now(), 'amount' => '800.00', 'currency' => 'USD', 'payment_method' => 'cash'],
    );

    // finance_manager has 'payments.cancel'; direction is read-only for payments.
    $this->actingAs($this->finance)
        ->patch(route('payments.reverse', $payment), ['reason' => 'Erreur de saisie corrective'])
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    expect($payment->refresh()->status)->toBe('reversed');
    $this->assertDatabaseHas('audit_logs', ['action' => 'payment.reversed', 'entity_id' => $payment->id]);
    $this->assertDatabaseHas('receipts', ['payment_id' => $payment->id, 'status' => 'cancelled']);
});

// ---------------------------------------------------------------------------
// Step 4 – Finance manager consults reports and statement
// ---------------------------------------------------------------------------

it('allows finance manager to view reports and download customer statement', function () {
    $customer = Customer::factory()->create(['first_name' => 'ReportClient']);
    Subscription::factory()->for($customer)->create(['amount_paid' => '0.00']);

    $this->actingAs($this->finance)
        ->get(route('reports.index'))
        ->assertOk()
        ->assertSee('ReportClient');

    $response = $this->actingAs($this->finance)
        ->get(route('reports.customers.statement', $customer));

    $response->assertOk()->assertHeader('content-type', 'application/pdf');
    expect($response->getContent())->toStartWith('%PDF');
});

// ---------------------------------------------------------------------------
// Step 5 – Customer portal isolation
// ---------------------------------------------------------------------------

it('customer portal shows only their own data and blocks access to back-office', function () {
    $customerUser = User::factory()->create(['first_name' => 'Portal', 'last_name' => 'User']);
    $customerUser->roles()->attach(Role::query()->where('name', 'customer')->firstOrFail());
    $ownCustomer = Customer::factory()->create(['user_id' => $customerUser->id, 'first_name' => 'Portal']);
    $ownSub = Subscription::factory()->for($ownCustomer)->create(['subscription_number' => 'SUB-OWN-001']);
    $otherSub = Subscription::factory()->create(['subscription_number' => 'SUB-OTHER-001']);

    $this->actingAs($customerUser)
        ->get(route('portal.subscriptions.index'))
        ->assertOk()
        ->assertSee('SUB-OWN-001')
        ->assertDontSee('SUB-OTHER-001');

    // Back-office dashboard must be forbidden
    $this->actingAs($customerUser)
        ->get(route('dashboard'))
        ->assertForbidden();

    // Attempting to view a foreign subscription must return 404
    $this->actingAs($customerUser)
        ->get(route('portal.subscriptions.show', $otherSub))
        ->assertNotFound();
});

// ---------------------------------------------------------------------------
// Step 6 – Admin settings and super_admin bypass
// ---------------------------------------------------------------------------

it('admin can update settings and super_admin can access all back-office sections', function () {
    $payload = [
        'company' => ['name' => 'MJIC', 'logo' => 'https://example.test/logo.png', 'phone' => '+243000000000', 'email' => 'contact@example.test', 'address' => 'Kinshasa'],
        'project' => ['name' => 'Cité Étoile'],
        'finance' => ['currency' => 'USD', 'payment_methods' => ['cash', 'mobile_money']],
        'customer' => ['prefix' => 'CUS'], 'payment' => ['prefix' => 'PMT'], 'receipt' => ['prefix' => 'RCP'], 'contract' => ['prefix' => 'CNT'],
        'subscription' => ['allow_partial_payment' => true, 'allow_advance_payment' => true],
    ];

    $this->actingAs($this->admin)
        ->put(route('settings.update'), $payload)
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    $this->assertDatabaseHas('audit_logs', ['action' => 'setting.updated', 'user_id' => $this->admin->id]);

    // Super admin accesses all restricted back-office sections without restriction
    foreach (['dashboard', 'customers.index', 'plots.index', 'subscriptions.index', 'payments.index', 'reports.index', 'audit-logs.index', 'settings.index'] as $routeName) {
        $this->actingAs($this->superAdmin)
            ->get(route($routeName))
            ->assertOk("Route {$routeName} should be accessible to super_admin");
    }
});

// ---------------------------------------------------------------------------
// Step 7 – Role isolation: every non-admin role is blocked from settings
// ---------------------------------------------------------------------------

it('blocks non-admin roles from accessing settings and audit logs', function () {
    foreach ([$this->commercial, $this->cashier, $this->finance] as $actor) {
        $this->actingAs($actor)->get(route('settings.index'))->assertForbidden();
    }

    // Cashier and commercial cannot view audit logs
    foreach ([$this->commercial, $this->cashier] as $actor) {
        $this->actingAs($actor)->get(route('audit-logs.index'))->assertForbidden();
    }

    // Finance manager can view reports but not settings
    $this->actingAs($this->finance)
        ->get(route('reports.index'))
        ->assertOk();

    $this->actingAs($this->finance)
        ->get(route('settings.index'))
        ->assertForbidden();
});

// ---------------------------------------------------------------------------
// Step 8 – Installment schedule integrity
// ---------------------------------------------------------------------------

it('generates a mathematically correct installment schedule and reconciles overdue statuses', function () {
    $subscription = Subscription::factory()->create([
        'contract_total' => '1200.00',
        'monthly_amount' => '100.00',
        'duration_months' => 12,
        'start_date' => now()->subMonths(2),
    ]);

    $installments = app(InstallmentScheduleService::class)->generate($subscription);

    expect($installments)->toHaveCount(12);
    expect($installments->sum(fn (Installment $i) => (float) $i->amount_due))->toBe(1200.0);

    // Refresh statuses: the first two are past due → should be 'overdue'
    app(InstallmentScheduleService::class)->refreshStatuses($subscription);

    $overdue = $subscription->installments()->where('status', 'overdue')->count();
    expect($overdue)->toBeGreaterThanOrEqual(1);
});

// ---------------------------------------------------------------------------
// Step 9 – Auto-activation on first payment
// ---------------------------------------------------------------------------

it('auto-activates a pending subscription and sets plot to subscribed on first payment', function () {
    Storage::fake('local');

    $plot = Plot::factory()->create(['commercial_status' => 'reserved']);
    $subscription = Subscription::factory()->for($plot, 'plot')->create([
        'commercial_status' => 'pending',
        'contract_total' => '2500.00',
        'duration_months' => 0,
        'monthly_amount' => null,
    ]);

    // First payment — cashier encaisse l'acompte
    $this->actingAs($this->cashier)
        ->post(route('payments.store'), [
            'subscription_id' => $subscription->id,
            'idempotency_key' => (string) Str::uuid(),
            'payment_date' => now()->format('Y-m-d'),
            'amount' => '500.00',
            'currency' => 'USD',
            'payment_method' => 'cash',
        ])
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    // Subscription must now be active.
    expect($subscription->refresh()->commercial_status)->toBe('active');

    // Plot must now be subscribed.
    expect($plot->refresh()->commercial_status)->toBe('subscribed');

    // Audit trail must record the activation.
    $this->assertDatabaseHas('audit_logs', [
        'action' => 'subscription.activated',
        'entity_id' => $subscription->id,
    ]);
});

it('creates a pending subscription with deposit and activates it immediately', function () {
    Storage::fake('local');

    $plot = Plot::factory()->create(['commercial_status' => 'available']);
    $plan = PaymentPlan::factory()->create([
        'total_price' => '2500.00', 'duration_months' => 0, 'monthly_amount' => null, 'active' => true,
    ]);
    $customer = Customer::factory()->create();

    $this->actingAs($this->commercial)
        ->post(route('subscriptions.store'), [
            'customer_id' => $customer->id, 'plot_id' => $plot->id, 'payment_plan_id' => $plan->id,
            'subscription_date' => now()->toDateString(), 'start_date' => now()->toDateString(),
            'deposit' => '250.00', 'deposit_method' => 'cash',
        ])
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    $subscription = Subscription::query()->where('customer_id', $customer->id)->firstOrFail();

    // With deposit → immediately active.
    expect($subscription->commercial_status)->toBe('active')
        ->and($plot->refresh()->commercial_status)->toBe('subscribed')
        ->and((float) $subscription->amount_paid)->toBe(250.0);

    $this->assertDatabaseHas('audit_logs', ['action' => 'subscription.activated', 'entity_id' => $subscription->id]);
    $this->assertDatabaseHas('payments', ['subscription_id' => $subscription->id, 'amount' => '250.00']);
});

it('creates customer and initial subscription seamlessly from customer store form', function () {
    Storage::fake('local');

    $plot = Plot::factory()->create(['commercial_status' => 'available']);
    $plan = PaymentPlan::factory()->create([
        'total_price' => '3000.00',
        'duration_months' => 6,
        'monthly_amount' => '500.00',
        'active' => true,
    ]);

    $response = $this->actingAs($this->commercial)
        ->post(route('customers.store'), [
            'first_name' => 'Jean',
            'last_name' => 'Kabila',
            'email' => 'jean.kabila@example.com',
            'phone' => '+243810000999',
            'status' => 'active',
            'plot_id' => $plot->id,
            'payment_plan_id' => $plan->id,
            'subscription_date' => now()->toDateString(),
            'start_date' => now()->toDateString(),
            'deposit' => '500.00',
            'deposit_method' => 'cash',
        ]);

    $customer = Customer::query()->where('email', 'jean.kabila@example.com')->firstOrFail();
    $subscription = Subscription::query()->where('customer_id', $customer->id)->firstOrFail();
    $payment = Payment::query()->where('subscription_id', $subscription->id)->firstOrFail();

    $response->assertRedirect(route('payments.show', $payment));

    expect($subscription->commercial_status)->toBe('active')
        ->and($plot->refresh()->commercial_status)->toBe('subscribed')
        ->and((float) $subscription->amount_paid)->toBe(500.0);
});
