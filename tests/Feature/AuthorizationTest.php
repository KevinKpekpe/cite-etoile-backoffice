<?php

use App\Models\Role;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed([RoleSeeder::class, PermissionSeeder::class]);
});

it('assigns the explicit permission matrix to all seven roles', function () {
    expect(Role::query()->count())->toBe(7)
        ->and(Role::query()->where('name', 'super_admin')->firstOrFail()->permissions()->count())->toBe(37)
        ->and(Role::query()->where('name', 'cashier')->firstOrFail()->permissions()->where('name', 'payments.create')->exists())->toBeTrue()
        ->and(Role::query()->where('name', 'commercial')->firstOrFail()->permissions()->where('name', 'payments.create')->exists())->toBeFalse();
});

it('allows and denies model actions through server policies', function () {
    $commercial = User::factory()->create();
    $commercial->roles()->attach(Role::query()->where('name', 'commercial')->firstOrFail());
    $cashier = User::factory()->create();
    $cashier->roles()->attach(Role::query()->where('name', 'cashier')->firstOrFail());

    expect(Gate::forUser($commercial)->allows('customers.create'))->toBeTrue()
        ->and(Gate::forUser($cashier)->allows('customers.create'))->toBeFalse();
});

it('returns 403 when the dashboard permission is missing', function () {
    $customer = User::factory()->create();
    $customer->roles()->attach(Role::query()->where('name', 'customer')->firstOrFail());

    $this->actingAs($customer)->get(route('dashboard'))->assertForbidden();
});
