<?php

use App\Models\Payment;
use App\Models\Role;
use App\Models\Subscription;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

it('lets a cashier find a dossier and record a payment', function () {
    $this->seed([RoleSeeder::class, PermissionSeeder::class]);
    $cashier = User::factory()->create();
    $cashier->roles()->attach(Role::query()->where('name', 'cashier')->firstOrFail());
    $subscription = Subscription::factory()->create(['subscription_number' => 'SUB-SEARCH-01']);

    $this->actingAs($cashier)->get(route('payments.index', ['search' => 'SUB-SEARCH-01']))->assertOk()->assertSee('SUB-SEARCH-01');
    $this->actingAs($cashier)->post(route('payments.store'), ['subscription_id' => $subscription->id, 'idempotency_key' => (string) Str::uuid(), 'payment_date' => '2026-09-15 10:00:00', 'amount' => '300.00', 'currency' => 'USD', 'payment_method' => 'cash'])->assertRedirect();

    $this->assertDatabaseHas('payments', ['subscription_id' => $subscription->id, 'status' => 'validated']);
});

it('forbids a cashier from reversing a payment', function () {
    $this->seed([RoleSeeder::class, PermissionSeeder::class]);
    $cashier = User::factory()->create();
    $cashier->roles()->attach(Role::query()->where('name', 'cashier')->firstOrFail());

    $this->actingAs($cashier)->patch(route('payments.reverse', Payment::factory()->create()), ['reason' => 'Erreur à corriger'])->assertForbidden();
});
