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
    (new RoleSeeder)->run();
    (new PermissionSeeder)->run();
});

test('creating a customer automatically creates a user account with customer role and forces password change', function () {
    Mail::fake();

    $agent = User::factory()->create(['must_change_password' => false]);
    $roleCommercial = Role::query()->where('name', 'commercial')->firstOrFail();
    $agent->roles()->attach($roleCommercial);

    $response = $this->actingAs($agent)->post(route('customers.store'), [
        'first_name' => 'Nouveau',
        'last_name' => 'ClientTest',
        'phone' => '+243810009988',
        'email' => 'nouveau.client@test.cd',
        'status' => 'active',
    ]);

    $customer = Customer::query()->where('phone', '+243810009988')->firstOrFail();
    expect($customer->user_id)->not->toBeNull();

    $user = $customer->user;
    expect($user)->not->toBeNull()
        ->and($user->email)->toBe('nouveau.client@test.cd')
        ->and($user->must_change_password)->toBeTrue()
        ->and($user->hasRole('customer'))->toBeTrue();

    $response->assertSessionHas('user_credentials_markdown');

    Mail::assertSent(UserCredentialsMail::class, fn (UserCredentialsMail $mail) => $mail->hasTo('nouveau.client@test.cd'));
});

test('creating a staff user sets must_change_password to true and flashes markdown credentials', function () {
    Mail::fake();

    $admin = User::factory()->create(['must_change_password' => false]);
    $roleSuperAdmin = Role::query()->where('name', 'super_admin')->firstOrFail();
    $roleCashier = Role::query()->where('name', 'cashier')->firstOrFail();
    $admin->roles()->attach($roleSuperAdmin);

    $uniqueEmail = 'caissier.new.agent@cite-etoile.cd';

    $response = $this->actingAs($admin)->post(route('users.store'), [
        'first_name' => 'Agent',
        'last_name' => 'Caissier',
        'email' => $uniqueEmail,
        'phone' => '+243850009988',
        'role_id' => $roleCashier->id,
    ]);

    $createdUser = User::query()->where('email', $uniqueEmail)->firstOrFail();
    expect($createdUser->must_change_password)->toBeTrue();

    $response->assertSessionHas('user_credentials_markdown');

    Mail::assertSent(UserCredentialsMail::class, fn (UserCredentialsMail $mail) => $mail->hasTo($uniqueEmail));
});

test('user with must_change_password true is redirected to password change page when trying to access dashboard', function () {
    $user = User::factory()->mustChangePassword()->create();
    $role = Role::query()->where('name', 'commercial')->firstOrFail();
    $user->roles()->attach($role);

    $response = $this->actingAs($user)->get(route('dashboard'));
    $response->assertRedirect(route('password.change'));
});

test('user can successfully change password on first login and unlock access', function () {
    $user = User::factory()->mustChangePassword()->create([
        'password' => Hash::make('temp-password-123'),
    ]);
    $role = Role::query()->where('name', 'customer')->firstOrFail();
    $user->roles()->attach($role);

    Customer::factory()->create(['user_id' => $user->id]);

    $response = $this->actingAs($user)->post(route('password.change.store'), [
        'password' => 'NewStrongPassword123!',
        'password_confirmation' => 'NewStrongPassword123!',
    ]);

    $response->assertRedirect(route('portal.dashboard'));

    $user->refresh();
    expect($user->must_change_password)->toBeFalse()
        ->and(Hash::check('NewStrongPassword123!', $user->password))->toBeTrue();

    // Verification that user can now access portal dashboard without redirection
    $dashboardResponse = $this->actingAs($user)->get(route('portal.dashboard'));
    $dashboardResponse->assertOk();
});
