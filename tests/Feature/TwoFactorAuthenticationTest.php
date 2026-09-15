<?php

use App\Models\Role;
use App\Models\User;
use App\Security\Totp;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('lets an administrator enable two factor authentication', function () {
    $this->seed(RoleSeeder::class);
    $user = User::factory()->create();
    $user->roles()->attach(Role::query()->where('name', 'admin')->firstOrFail());
    $totp = app(Totp::class);
    $secret = $totp->generateSecret();

    $this->actingAs($user)->withSession(['auth.two_factor_setup_secret' => $secret])->post(route('two-factor.enable'), [
        'code' => $totp->code($secret, intdiv(time(), 30)),
    ])->assertRedirect(route('two-factor.setup'));

    expect($user->refresh()->hasTwoFactorAuthenticationEnabled())->toBeTrue()->and($user->two_factor_recovery_codes)->toHaveCount(8);
});

it('requires and accepts a valid second factor after login', function () {
    $user = User::factory()->create(['password' => 'Secret123!']);
    $secret = app(Totp::class)->generateSecret();
    $user->forceFill(['two_factor_secret' => $secret, 'two_factor_recovery_codes' => [], 'two_factor_confirmed_at' => now()])->save();

    $this->post(route('login.store'), ['email' => $user->email, 'password' => 'Secret123!'])->assertRedirect(route('two-factor.challenge'));
    $this->get(route('dashboard'))->assertRedirect(route('two-factor.challenge'));
    $this->post(route('two-factor.verify'), ['code' => app(Totp::class)->code($secret, intdiv(time(), 30))])->assertRedirect(route('dashboard'));
});

it('forbids two factor setup for non administrative accounts', function () {
    $this->actingAs(User::factory()->create())->get(route('two-factor.setup'))->assertForbidden();
});
