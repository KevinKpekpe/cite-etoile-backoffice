<?php

use App\Models\Customer;
use App\Models\PaymentPlan;
use App\Models\Plot;
use App\Models\Role;
use App\Models\Subscription;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed([RoleSeeder::class, PermissionSeeder::class]);
    $this->commercial = User::factory()->create();
    $this->commercial->roles()->attach(Role::query()->where('name', 'commercial')->firstOrFail());
});

it('creates a subscription transactionally with a financial snapshot', function () {
    $customer = Customer::factory()->create();
    $plot = Plot::factory()->create(['commercial_status' => 'available']);
    $plan = PaymentPlan::factory()->create(['total_price' => '6200.00', 'monthly_amount' => '175.00', 'duration_months' => 36, 'active' => true]);

    $this->actingAs($this->commercial)->post(route('subscriptions.store'), ['customer_id' => $customer->id, 'plot_id' => $plot->id, 'payment_plan_id' => $plan->id, 'subscription_date' => '2026-09-15', 'start_date' => '2026-10-01', 'commercial_status' => 'active'])->assertRedirect();

    $subscription = Subscription::query()->firstOrFail();
    expect($subscription->contract_total)->toBe('6200.00')->and($subscription->monthly_amount)->toBe('175.00')->and($subscription->duration_months)->toBe(36)->and($plot->refresh()->commercial_status)->toBe('subscribed');
    $this->assertDatabaseHas('audit_logs', ['action' => 'subscription.created', 'entity_id' => $subscription->id]);
});

it('rejects unavailable plots and rolls back the subscription', function () {
    $plot = Plot::factory()->create(['commercial_status' => 'blocked']);

    $this->actingAs($this->commercial)->post(route('subscriptions.store'), ['customer_id' => Customer::factory()->create()->id, 'plot_id' => $plot->id, 'payment_plan_id' => PaymentPlan::factory()->create()->id, 'subscription_date' => '2026-09-15', 'start_date' => '2026-09-15', 'commercial_status' => 'active'])->assertUnprocessable();

    $this->assertDatabaseCount('subscriptions', 0);
});

it('preserves snapshots after the selected plan changes', function () {
    $subscription = Subscription::factory()->create(['contract_total' => '3600.00', 'monthly_amount' => '300.00']);

    $subscription->paymentPlan()->update(['total_price' => '5000.00', 'monthly_amount' => '400.00']);

    expect($subscription->refresh()->contract_total)->toBe('3600.00')->and($subscription->monthly_amount)->toBe('300.00');
});

it('enforces status transitions and audits successful changes', function () {
    $subscription = Subscription::factory()->create(['commercial_status' => 'active']);

    $this->actingAs($this->commercial)->patch(route('subscriptions.status', $subscription), ['commercial_status' => 'draft'])->assertSessionHasErrors('commercial_status');
    $this->actingAs($this->commercial)->patch(route('subscriptions.status', $subscription), ['commercial_status' => 'suspended'])->assertRedirect();

    expect($subscription->refresh()->commercial_status)->toBe('suspended');
    $this->assertDatabaseHas('audit_logs', ['action' => 'subscription.status_changed', 'entity_id' => $subscription->id]);
});

it('does not expose backoffice subscriptions to customer accounts', function () {
    $customerUser = User::factory()->create();
    $customerUser->roles()->attach(Role::query()->where('name', 'customer')->firstOrFail());

    $this->actingAs($customerUser)->get(route('subscriptions.index'))->assertForbidden();
});
