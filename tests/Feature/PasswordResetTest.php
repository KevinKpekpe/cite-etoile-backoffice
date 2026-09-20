<?php

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;

test('reset link screen can be rendered', function () {
    $response = $this->get('/forgot-password');

    $response->assertStatus(200);
});

test('reset link can be requested for valid user', function () {
    Notification::fake();

    $user = User::factory()->create();

    $response = $this->post('/forgot-password', [
        'email' => $user->email,
    ]);

    $response->assertSessionHas('status');
    Notification::assertSentTo($user, ResetPassword::class);
});

test('reset link request shows error for unknown email', function () {
    $response = $this->post('/forgot-password', [
        'email' => 'nonexistent@example.com',
    ]);

    $response->assertSessionHasErrors('email');
});

test('password can be reset with valid token', function () {
    Notification::fake();

    $user = User::factory()->create([
        'must_change_password' => true,
    ]);

    $token = Password::createToken($user);

    $response = $this->post('/reset-password', [
        'token' => $token,
        'email' => $user->email,
        'password' => 'Password123!',
        'password_confirmation' => 'Password123!',
    ]);

    $response->assertRedirect(route('login'));
    $response->assertSessionHas('status');

    $user->refresh();
    expect($user->must_change_password)->toBeFalse();
});
