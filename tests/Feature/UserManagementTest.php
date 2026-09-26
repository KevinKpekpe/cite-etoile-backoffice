<?php

use App\Models\Role;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed([RoleSeeder::class, PermissionSeeder::class]);
    $this->admin = User::factory()->create();
    $this->admin->roles()->attach(Role::query()->where('name', 'admin')->firstOrFail());
});

// ---------------------------------------------------------------------------
// Index
// ---------------------------------------------------------------------------

it('lists back-office users and excludes customer-only accounts', function () {
    $commercial = User::factory()->create(['first_name' => 'Agent', 'last_name' => 'Test']);
    $commercial->roles()->attach(Role::query()->where('name', 'commercial')->firstOrFail());

    $customerUser = User::factory()->create(['first_name' => 'Portal', 'last_name' => 'Client']);
    $customerUser->roles()->attach(Role::query()->where('name', 'customer')->firstOrFail());

    $this->actingAs($this->admin)
        ->get(route('users.index'))
        ->assertOk()
        ->assertSee('Agent Test')
        ->assertDontSee('Portal Client');
});

it('filters users by role and status', function () {
    $cashier = User::factory()->create(['first_name' => 'Caissier', 'last_name' => 'Actif', 'status' => 'active']);
    $cashier->roles()->attach(Role::query()->where('name', 'cashier')->firstOrFail());

    $suspended = User::factory()->create(['first_name' => 'Commercial', 'last_name' => 'Suspendu', 'status' => 'suspended']);
    $suspended->roles()->attach(Role::query()->where('name', 'commercial')->firstOrFail());

    $this->actingAs($this->admin)
        ->get(route('users.index', ['role' => 'cashier', 'status' => 'active']))
        ->assertOk()
        ->assertSee('Caissier Actif')
        ->assertDontSee('Commercial Suspendu');
});

it('blocks unauthorized roles from accessing user management', function () {
    $commercial = User::factory()->create();
    $commercial->roles()->attach(Role::query()->where('name', 'commercial')->firstOrFail());

    $this->actingAs($commercial)->get(route('users.index'))->assertForbidden();
    $this->actingAs($commercial)->get(route('users.create'))->assertForbidden();
});

// ---------------------------------------------------------------------------
// Create / Store
// ---------------------------------------------------------------------------

it('creates a staff user with a role and flashes a temporary password', function () {
    $role = Role::query()->where('name', 'cashier')->firstOrFail();

    $response = $this->actingAs($this->admin)->post(route('users.store'), [
        'first_name' => 'Nouveau',
        'last_name' => 'Caissier',
        'email' => 'caissier@example.test',
        'phone' => '+243900000099',
        'role_id' => $role->id,
    ]);

    $response->assertRedirect();
    $user = User::query()->where('email', 'caissier@example.test')->firstOrFail();
    expect($user->hasRole('cashier'))->toBeTrue();
    $this->assertDatabaseHas('audit_logs', ['action' => 'user.created', 'entity_id' => $user->id]);

    // Temporary password must have been flashed to the session.
    $response->assertSessionHas('temporary_password');
});

it('rejects creation with a duplicate email', function () {
    User::factory()->create(['email' => 'taken@example.test']);
    $role = Role::query()->where('name', 'commercial')->firstOrFail();

    $this->actingAs($this->admin)->post(route('users.store'), [
        'first_name' => 'Test', 'last_name' => 'User',
        'email' => 'taken@example.test', 'phone' => '+243000000000',
        'role_id' => $role->id,
    ])->assertSessionHasErrors('email');
});

it('rejects creation with an invalid role', function () {
    $this->actingAs($this->admin)->post(route('users.store'), [
        'first_name' => 'Test', 'last_name' => 'User',
        'email' => 'new@example.test', 'phone' => '+243000000000',
        'role_id' => 99999,
    ])->assertSessionHasErrors('role_id');
});

// ---------------------------------------------------------------------------
// Show
// ---------------------------------------------------------------------------

it('shows the user profile with role and audit history', function () {
    $cashier = User::factory()->create(['first_name' => 'Luc', 'last_name' => 'Mvuemba']);
    $cashier->roles()->attach(Role::query()->where('name', 'cashier')->firstOrFail());

    $this->actingAs($this->admin)
        ->get(route('users.show', $cashier))
        ->assertOk()
        ->assertSee('Luc Mvuemba')
        ->assertSee('Cashier');
});

// ---------------------------------------------------------------------------
// Edit / Update
// ---------------------------------------------------------------------------

it('updates user profile information and role with audit trail', function () {
    $user = User::factory()->create(['email' => 'old@example.test', 'phone' => '+243000000001']);
    $user->roles()->attach(Role::query()->where('name', 'commercial')->firstOrFail());
    $newRole = Role::query()->where('name', 'cashier')->firstOrFail();

    $this->actingAs($this->admin)->put(route('users.update', $user), [
        'first_name' => $user->first_name,
        'last_name' => $user->last_name,
        'email' => 'updated@example.test',
        'phone' => '+243000000002',
        'role_id' => $newRole->id,
        'status' => 'active',
    ])->assertRedirect(route('users.show', $user))->assertSessionHasNoErrors();

    $this->assertDatabaseHas('users', ['id' => $user->id, 'email' => 'updated@example.test']);
    expect($user->refresh()->hasRole('cashier'))->toBeTrue()
        ->and($user->hasRole('commercial'))->toBeFalse();
    $this->assertDatabaseHas('audit_logs', ['action' => 'user.updated', 'entity_id' => $user->id]);
});

it('resets the password when a valid password is provided on update', function () {
    $user = User::factory()->create(['password' => 'OldPassword1!']);
    $user->roles()->attach(Role::query()->where('name', 'commercial')->firstOrFail());
    $role = Role::query()->where('name', 'commercial')->firstOrFail();

    $this->actingAs($this->admin)->put(route('users.update', $user), [
        'first_name' => $user->first_name, 'last_name' => $user->last_name,
        'email' => $user->email, 'phone' => $user->phone,
        'role_id' => $role->id, 'status' => 'active',
        'password' => 'NewPassword1234!', 'password_confirmation' => 'NewPassword1234!',
    ])->assertRedirect()->assertSessionHasNoErrors();

    expect(Hash::check('NewPassword1234!', $user->refresh()->password))->toBeTrue();
});

it('does not change the password when the field is left blank on update', function () {
    $user = User::factory()->create(['password' => 'OriginalPassword1!']);
    $user->roles()->attach(Role::query()->where('name', 'commercial')->firstOrFail());
    $role = Role::query()->where('name', 'commercial')->firstOrFail();
    $originalHash = $user->password;

    $this->actingAs($this->admin)->put(route('users.update', $user), [
        'first_name' => $user->first_name, 'last_name' => $user->last_name,
        'email' => $user->email, 'phone' => $user->phone,
        'role_id' => $role->id, 'status' => 'active',
        'password' => '', 'password_confirmation' => '',
    ])->assertRedirect()->assertSessionHasNoErrors();

    expect($user->refresh()->password)->toBe($originalHash);
});

// ---------------------------------------------------------------------------
// Toggle status
// ---------------------------------------------------------------------------

it('toggles a user status between active and suspended with audit trail', function () {
    $user = User::factory()->create(['status' => 'active']);
    $user->roles()->attach(Role::query()->where('name', 'commercial')->firstOrFail());

    $this->actingAs($this->admin)
        ->patch(route('users.toggle-status', $user))
        ->assertRedirect(route('users.show', $user));

    expect($user->refresh()->status)->toBe('suspended');
    $this->assertDatabaseHas('audit_logs', ['action' => 'user.status_changed', 'entity_id' => $user->id]);

    $this->actingAs($this->admin)->patch(route('users.toggle-status', $user));
    expect($user->refresh()->status)->toBe('active');
});

it('prevents non-super_admin from suspending a super_admin account', function () {
    $superAdmin = User::factory()->create(['status' => 'active']);
    $superAdmin->roles()->attach(Role::query()->where('name', 'super_admin')->firstOrFail());

    $this->actingAs($this->admin)
        ->patch(route('users.toggle-status', $superAdmin))
        ->assertForbidden();

    expect($superAdmin->refresh()->status)->toBe('active');
});
