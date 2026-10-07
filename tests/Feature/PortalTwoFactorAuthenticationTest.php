<?php

use App\Models\Customer;
use App\Models\Role;
use App\Models\User;
use App\Security\Totp;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed([RoleSeeder::class, PermissionSeeder::class]);

    $this->customer = Customer::factory()->create();
    $this->portalUser = User::factory()->create(['must_change_password' => false]);
    $this->portalUser->roles()->attach(Role::query()->where('name', 'customer')->firstOrFail());
    $this->customer->update(['user_id' => $this->portalUser->id]);
});

it('lets a customer view the portal two-factor setup page', function () {
    $this->actingAs($this->portalUser)
        ->get(route('portal.two-factor.setup'))
        ->assertOk();
});

it('lets a customer enable two-factor authentication on the portal', function () {
    $totp = app(Totp::class);
    $secret = $totp->generateSecret();

    $this->actingAs($this->portalUser)
        ->withSession(['auth.portal_two_factor_setup_secret' => $secret])
        ->post(route('portal.two-factor.enable'), [
            'code' => $totp->code($secret, intdiv(time(), 30)),
        ])
        ->assertRedirect(route('portal.two-factor.setup'));

    expect($this->portalUser->refresh()->hasTwoFactorAuthenticationEnabled())->toBeTrue()
        ->and($this->portalUser->two_factor_recovery_codes)->toHaveCount(8);
});

it('rejects an invalid TOTP code when enabling portal two-factor', function () {
    $totp = app(Totp::class);
    $secret = $totp->generateSecret();

    $this->actingAs($this->portalUser)
        ->withSession(['auth.portal_two_factor_setup_secret' => $secret])
        ->post(route('portal.two-factor.enable'), ['code' => '000000'])
        ->assertSessionHasErrors('code');

    expect($this->portalUser->refresh()->hasTwoFactorAuthenticationEnabled())->toBeFalse();
});

it('lets a customer disable two-factor authentication on the portal', function () {
    $secret = app(Totp::class)->generateSecret();
    $this->portalUser->forceFill([
        'two_factor_secret'         => $secret,
        'two_factor_recovery_codes' => [],
        'two_factor_confirmed_at'   => now(),
    ])->save();

    $this->actingAs($this->portalUser)
        ->delete(route('portal.two-factor.disable'), ['password' => 'password'])
        ->assertRedirect(route('portal.settings.index'));

    expect($this->portalUser->refresh()->hasTwoFactorAuthenticationEnabled())->toBeFalse();
});

it('forbids non-customer users from accessing portal two-factor setup', function () {
    $this->seed([RoleSeeder::class]);

    $admin = User::factory()->create();
    $admin->roles()->attach(Role::query()->where('name', 'admin')->firstOrFail());

    $this->actingAs($admin)
        ->get(route('portal.two-factor.setup'))
        ->assertForbidden();
});
