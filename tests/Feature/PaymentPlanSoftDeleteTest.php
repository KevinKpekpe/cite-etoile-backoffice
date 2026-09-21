<?php

use App\Models\PaymentPlan;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed([RoleSeeder::class, PermissionSeeder::class]);
    $this->admin = User::factory()->create();
    $this->admin->roles()->attach(Role::query()->where('name', 'admin')->firstOrFail());

    $this->superAdmin = User::factory()->create();
    $this->superAdmin->roles()->attach(Role::query()->where('name', 'super_admin')->firstOrFail());
});

test('authorized user can soft delete payment plan', function () {
    $plan = PaymentPlan::factory()->create();

    $response = $this->actingAs($this->admin)->delete(route('payment-plans.destroy', $plan));

    $response->assertRedirect(route('payment-plans.index'));
    expect(PaymentPlan::find($plan->id))->toBeNull()
        ->and(PaymentPlan::withTrashed()->find($plan->id))->not->toBeNull();
});

test('trashed payment plans page can be rendered', function () {
    $plan = PaymentPlan::factory()->create();
    $plan->delete();

    $response = $this->actingAs($this->admin)->get(route('payment-plans.trashed'));

    $response->assertStatus(200);
    $response->assertSee($plan->name);
});

test('authorized user can restore soft deleted payment plan', function () {
    $plan = PaymentPlan::factory()->create();
    $plan->delete();

    $response = $this->actingAs($this->admin)->post(route('payment-plans.restore', $plan));

    $response->assertRedirect(route('payment-plans.index'));
    expect(PaymentPlan::find($plan->id))->not->toBeNull();
});

test('super admin can force delete payment plan without subscriptions', function () {
    $plan = PaymentPlan::factory()->create();
    $plan->delete();

    $response = $this->actingAs($this->superAdmin)->delete(route('payment-plans.force-delete', $plan));

    $response->assertRedirect(route('payment-plans.trashed'));
    expect(PaymentPlan::withTrashed()->find($plan->id))->toBeNull();
});
