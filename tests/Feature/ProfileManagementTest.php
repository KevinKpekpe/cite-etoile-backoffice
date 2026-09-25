<?php

use App\Models\AuditLog;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed([RoleSeeder::class, PermissionSeeder::class]);
});

it('requires authentication to access the internal profile', function () {
    $this->get(route('profile.edit'))->assertRedirect(route('login'));
});

it('allows an internal user to view and update only their own profile', function () {
    $user = User::factory()->create(['email' => 'before@example.test']);
    $user->roles()->attach(Role::query()->where('name', 'admin')->firstOrFail());

    $this->actingAs($user)->get(route('profile.edit'))->assertOk()->assertSee('Mon profil');

    $this->actingAs($user)->put(route('profile.update'), [
        'first_name' => 'Aline',
        'last_name' => 'Mukendi',
        'email' => 'aline@example.test',
        'phone' => '+243900000001',
    ])->assertRedirect()->assertSessionHasNoErrors();

    expect($user->fresh())
        ->first_name->toBe('Aline')
        ->last_name->toBe('Mukendi')
        ->email->toBe('aline@example.test')
        ->phone->toBe('+243900000001');

    expect(AuditLog::query()->where('action', 'profile.updated')->where('entity_id', $user->id)->exists())->toBeTrue();
});

it('rejects invalid or duplicate profile information', function () {
    User::factory()->create(['email' => 'existing@example.test']);
    $user = User::factory()->create();
    $user->roles()->attach(Role::query()->where('name', 'commercial')->firstOrFail());

    $this->actingAs($user)->put(route('profile.update'), [
        'first_name' => '',
        'last_name' => '',
        'email' => 'existing@example.test',
        'phone' => '',
    ])->assertSessionHasErrors(['first_name', 'last_name', 'email', 'phone']);
});

it('keeps customer accounts on the dedicated portal profile', function () {
    $user = User::factory()->create();
    $user->roles()->attach(Role::query()->where('name', 'customer')->firstOrFail());

    $this->actingAs($user)->get(route('profile.edit'))->assertForbidden();
    $this->actingAs($user)->put(route('profile.update'), [])->assertForbidden();
});
