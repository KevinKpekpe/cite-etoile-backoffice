<?php

use App\Models\AuditLog;
use App\Models\Customer;
use App\Models\Role;
use App\Models\Subscription;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed([RoleSeeder::class, PermissionSeeder::class]);
    $this->agent = User::factory()->create();
    $this->agent->roles()->attach(Role::query()->where('name', 'admin')->firstOrFail());
});

it('lists and searches customers by identity phone and number', function () {
    Customer::factory()->create(['first_name' => 'Amina', 'last_name' => 'Kalala', 'phone' => '+243811111111']);
    Customer::factory()->create(['first_name' => 'Jean', 'last_name' => 'Mbuyi']);

    $this->actingAs($this->agent)->get(route('customers.index', ['search' => '811111111']))
        ->assertOk()->assertSee('Amina')->assertDontSee('Jean');
});

it('creates a validated customer with an automatic number and audit record', function () {
    $response = $this->actingAs($this->agent)->post(route('customers.store'), [
        'first_name' => 'Sarah', 'last_name' => 'Ilunga', 'phone' => '+243820000001',
        'email' => 'sarah@example.test', 'status' => 'active',
    ]);

    $customer = Customer::query()->where('email', 'sarah@example.test')->firstOrFail();
    $response->assertRedirect(route('customers.show', $customer));
    expect($customer->customer_number)->toStartWith('CLI-');
    $this->assertDatabaseHas('audit_logs', ['action' => 'customer.created', 'entity_id' => $customer->id]);
});

it('rejects incomplete customer data', function () {
    $this->actingAs($this->agent)->post(route('customers.store'), ['first_name' => 'Sarah'])
        ->assertSessionHasErrors(['last_name', 'phone', 'status']);

    $this->assertDatabaseCount('customers', 0);
});

it('renders the customer 360 degree record', function () {
    $customer = Customer::factory()->create(['first_name' => 'Sarah']);
    Subscription::factory()->for($customer)->create(['subscription_number' => 'SUB-360-01']);

    $this->actingAs($this->agent)->get(route('customers.show', $customer))
        ->assertOk()->assertSee('Sarah')->assertSee('SUB-360-01')->assertSee('Situation financière');
});

it('audits updates and archives without deleting the customer', function () {
    $customer = Customer::factory()->create(['phone' => '+243810000001', 'status' => 'active']);
    $payload = $customer->only(['first_name', 'last_name', 'phone']);

    $this->actingAs($this->agent)->put(route('customers.update', $customer), [...$payload, 'phone' => '+243810000002', 'status' => 'active'])
        ->assertRedirect(route('customers.show', $customer));
    $this->actingAs($this->agent)->patch(route('customers.archive', $customer))->assertRedirect();

    expect($customer->refresh()->status)->toBe('archived')->and(AuditLog::query()->where('entity_id', $customer->id)->count())->toBe(2);
    $this->assertModelExists($customer);
});
