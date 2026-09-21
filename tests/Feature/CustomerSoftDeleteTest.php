<?php

use App\Models\Customer;
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

test('authorized user can soft delete customer', function () {
    $customer = Customer::factory()->create();

    $response = $this->actingAs($this->admin)->delete(route('customers.destroy', $customer));

    $response->assertRedirect(route('customers.index'));
    expect(Customer::find($customer->id))->toBeNull()
        ->and(Customer::withTrashed()->find($customer->id))->not->toBeNull();
});

test('trashed customers page can be rendered', function () {
    $customer = Customer::factory()->create();
    $customer->delete();

    $response = $this->actingAs($this->admin)->get(route('customers.trashed'));

    $response->assertStatus(200);
    $response->assertSee($customer->first_name);
});

test('authorized user can restore soft deleted customer', function () {
    $customer = Customer::factory()->create();
    $customer->delete();

    $response = $this->actingAs($this->admin)->post(route('customers.restore', $customer));

    $response->assertRedirect(route('customers.show', $customer));
    expect(Customer::find($customer->id))->not->toBeNull();
});

test('super admin can force delete customer', function () {
    $customer = Customer::factory()->create();
    $customer->delete();

    $response = $this->actingAs($this->superAdmin)->delete(route('customers.force-delete', $customer));

    $response->assertRedirect(route('customers.trashed'));
    expect(Customer::withTrashed()->find($customer->id))->toBeNull();
});
