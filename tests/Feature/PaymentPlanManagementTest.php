<?php

use App\Models\PaymentPlan;
use App\Models\Role;
use App\Models\Subscription;
use App\Models\User;
use Database\Seeders\PaymentPlanSeeder;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed([RoleSeeder::class, PermissionSeeder::class]);
    $this->admin = User::factory()->create();
    $this->admin->roles()->attach(Role::query()->where('name', 'admin')->firstOrFail());
});

it('lists the five official acquisition plans', function () {
    $this->seed(PaymentPlanSeeder::class);

    $this->actingAs($this->admin)->get(route('payment-plans.index'))->assertOk()
        ->assertSee('2 500')->assertSee('3 600')->assertSee('6 200')->assertSee('7 500')->assertSee('9 000');
});

it('validates validity periods and restricts management permission', function () {
    $commercial = User::factory()->create();
    $commercial->roles()->attach(Role::query()->where('name', 'commercial')->firstOrFail());
    $payload = ['code' => 'NEW', 'name' => 'Nouvelle', 'total_price' => 1000, 'duration_months' => 0, 'frequency' => 'once', 'valid_from' => '2026-12-31', 'valid_until' => '2026-01-01'];

    $this->actingAs($commercial)->post(route('payment-plans.store'), $payload)->assertForbidden();
    $this->actingAs($this->admin)->post(route('payment-plans.store'), $payload)->assertSessionHasErrors('valid_until');
});

it('preserves subscription snapshots when a plan changes', function () {
    $plan = PaymentPlan::factory()->create(['total_price' => '3600.00', 'monthly_amount' => '300.00', 'duration_months' => 12]);
    $subscription = Subscription::factory()->for($plan, 'paymentPlan')->create(['contract_total' => '3600.00', 'monthly_amount' => '300.00', 'duration_months' => 12]);

    $this->actingAs($this->admin)->put(route('payment-plans.update', $plan), ['code' => $plan->code, 'name' => $plan->name, 'total_price' => 5000, 'monthly_amount' => 400, 'duration_months' => 12, 'frequency' => 'monthly', 'active' => 1])->assertRedirect();

    expect($subscription->refresh()->contract_total)->toBe('3600.00')->and($subscription->monthly_amount)->toBe('300.00');
});

it('soft deletes a plan without deleting historical subscriptions', function () {
    $plan = PaymentPlan::factory()->create();
    $subscription = Subscription::factory()->for($plan, 'paymentPlan')->create();

    $this->actingAs($this->admin)->delete(route('payment-plans.destroy', $plan))->assertRedirect();

    expect(PaymentPlan::find($plan->id))->toBeNull()
        ->and(PaymentPlan::withTrashed()->find($plan->id))->not->toBeNull();
    $this->assertModelExists($subscription);
});
