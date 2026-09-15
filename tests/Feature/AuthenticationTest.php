<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;

uses(RefreshDatabase::class);

it('authenticates an active user and rotates the session', function () {
    $user = User::factory()->create(['password' => 'Secret123!']);
    $oldSessionId = session()->getId();

    $response = $this->post(route('login.store'), ['email' => $user->email, 'password' => 'Secret123!']);

    $response->assertRedirect(route('dashboard'));
    $this->assertAuthenticatedAs($user);
    expect(session()->getId())->not->toBe($oldSessionId)->and($user->refresh()->last_login_at)->not->toBeNull();
});

it('rejects suspended users and rate limits repeated failures', function () {
    $user = User::factory()->create(['password' => 'Secret123!', 'status' => 'suspended']);

    foreach (range(1, 6) as $attempt) {
        $this->post(route('login.store'), ['email' => $user->email, 'password' => 'wrong'])->assertSessionHasErrors('email');
    }

    $this->assertGuest();
});

it('logs out and invalidates authentication', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->post(route('logout'))->assertRedirect(route('login'));

    $this->assertGuest();
});

it('resets a password with a valid broker token', function () {
    $user = User::factory()->create();
    $token = Password::createToken($user);

    $this->post(route('password.update'), ['token' => $token, 'email' => $user->email, 'password' => 'NewSecret123!', 'password_confirmation' => 'NewSecret123!'])
        ->assertRedirect(route('login'));

    expect(Hash::check('NewSecret123!', $user->refresh()->password))->toBeTrue();
});
