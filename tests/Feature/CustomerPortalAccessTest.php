<?php

use App\Mail\UserCredentialsMail;
use App\Models\Customer;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed([RoleSeeder::class, PermissionSeeder::class]);

    $this->admin = User::factory()->create(['must_change_password' => false]);
    $this->admin->roles()->attach(Role::query()->where('name', 'admin')->firstOrFail());

    $this->commercial = User::factory()->create(['must_change_password' => false]);
    $this->commercial->roles()->attach(Role::query()->where('name', 'commercial')->firstOrFail());
});

// ---------------------------------------------------------------------------
// createAccess
// ---------------------------------------------------------------------------

test('admin can create portal access for a customer without a linked user account', function () {
    Mail::fake();

    $customer = Customer::factory()->create(['email' => 'client@example.cd', 'user_id' => null]);

    $response = $this->actingAs($this->admin)
        ->post(route('customers.portal.create-access', $customer));

    $response->assertRedirect(route('customers.show', $customer));
    $response->assertSessionHas('status');
    $response->assertSessionHas('user_credentials_markdown');

    $customer->refresh();
    expect($customer->user_id)->not->toBeNull();

    $portalUser = $customer->user;
    expect($portalUser->email)->toBe('client@example.cd')
        ->and($portalUser->must_change_password)->toBeTrue()
        ->and($portalUser->status)->toBe('active')
        ->and($portalUser->hasRole('customer'))->toBeTrue();

    Mail::assertSent(UserCredentialsMail::class, fn ($mail) => $mail->hasTo('client@example.cd'));
});

test('create portal access uses a generated email when customer has no email', function () {
    Mail::fake();

    $customer = Customer::factory()->create(['email' => null, 'user_id' => null, 'customer_number' => 'CLI-2026-001']);

    $this->actingAs($this->admin)
        ->post(route('customers.portal.create-access', $customer))
        ->assertRedirect();

    $customer->refresh();
    expect($customer->user)->not->toBeNull()
        ->and($customer->user->email)->toContain('@client.cite-etoile.cd');
});

test('create portal access is rejected when customer already has a portal account', function () {
    $existingUser = User::factory()->create();
    $customer = Customer::factory()->create(['user_id' => $existingUser->id]);

    $this->actingAs($this->admin)
        ->post(route('customers.portal.create-access', $customer))
        ->assertStatus(422);
});

test('non-admin without customers.update permission cannot create portal access', function () {
    $cashier = User::factory()->create(['must_change_password' => false]);
    $cashier->roles()->attach(Role::query()->where('name', 'cashier')->firstOrFail());

    $customer = Customer::factory()->create(['user_id' => null]);

    $this->actingAs($cashier)
        ->post(route('customers.portal.create-access', $customer))
        ->assertForbidden();
});

// ---------------------------------------------------------------------------
// resetPassword
// ---------------------------------------------------------------------------

test('admin can reset the portal password and forces password change on next login', function () {
    Mail::fake();

    $portalUser = User::factory()->create([
        'email' => 'portal@example.cd',
        'password' => Hash::make('old-password'),
        'must_change_password' => false,
    ]);
    $customer = Customer::factory()->create(['user_id' => $portalUser->id]);

    $this->actingAs($this->admin)
        ->post(route('customers.portal.reset-password', $customer))
        ->assertRedirect(route('customers.show', $customer))
        ->assertSessionHas('status')
        ->assertSessionHas('user_credentials_markdown');

    $portalUser->refresh();
    expect($portalUser->must_change_password)->toBeTrue()
        ->and(Hash::check('old-password', $portalUser->password))->toBeFalse();

    Mail::assertSent(UserCredentialsMail::class, fn ($mail) => $mail->hasTo('portal@example.cd'));
});

test('reset password is rejected when customer has no portal account', function () {
    $customer = Customer::factory()->create(['user_id' => null]);

    $this->actingAs($this->admin)
        ->post(route('customers.portal.reset-password', $customer))
        ->assertStatus(422);
});

// ---------------------------------------------------------------------------
// resendCredentials
// ---------------------------------------------------------------------------

test('admin can resend credentials email to a customer who already has an account', function () {
    Mail::fake();

    $portalUser = User::factory()->create(['email' => 'resend@example.cd']);
    $customer = Customer::factory()->create(['user_id' => $portalUser->id]);

    $this->actingAs($this->admin)
        ->post(route('customers.portal.resend-credentials', $customer))
        ->assertRedirect(route('customers.show', $customer))
        ->assertSessionHas('status')
        ->assertSessionHas('user_credentials_markdown');

    $portalUser->refresh();
    expect($portalUser->must_change_password)->toBeTrue();

    Mail::assertSent(UserCredentialsMail::class, fn ($mail) => $mail->hasTo('resend@example.cd'));
});

test('resend credentials is rejected when customer has no portal account', function () {
    $customer = Customer::factory()->create(['user_id' => null]);

    $this->actingAs($this->admin)
        ->post(route('customers.portal.resend-credentials', $customer))
        ->assertStatus(422);
});

// ---------------------------------------------------------------------------
// toggleStatus
// ---------------------------------------------------------------------------

test('admin can suspend an active portal account', function () {
    $portalUser = User::factory()->create(['status' => 'active']);
    $customer = Customer::factory()->create(['user_id' => $portalUser->id]);

    $this->actingAs($this->admin)
        ->patch(route('customers.portal.toggle-status', $customer))
        ->assertRedirect(route('customers.show', $customer))
        ->assertSessionHas('status');

    expect($portalUser->refresh()->status)->toBe('suspended');
});

test('admin can reactivate a suspended portal account', function () {
    $portalUser = User::factory()->create(['status' => 'suspended']);
    $customer = Customer::factory()->create(['user_id' => $portalUser->id]);

    $this->actingAs($this->admin)
        ->patch(route('customers.portal.toggle-status', $customer))
        ->assertRedirect(route('customers.show', $customer));

    expect($portalUser->refresh()->status)->toBe('active');
});

test('toggle status is rejected when customer has no portal account', function () {
    $customer = Customer::factory()->create(['user_id' => null]);

    $this->actingAs($this->admin)
        ->patch(route('customers.portal.toggle-status', $customer))
        ->assertStatus(422);
});
