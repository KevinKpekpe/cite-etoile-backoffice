<?php

use App\Models\Role;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/**
 * Phase 16 – Security Hardening
 * Verifies that every HTTP response carries the expected defence-in-depth
 * headers, regardless of authentication state or content type.
 */
it('sends X-Frame-Options DENY on every response', function () {
    $response = $this->get(route('login'));

    $response->assertHeader('X-Frame-Options', 'DENY');
});

it('sends X-Content-Type-Options nosniff on every response', function () {
    $response = $this->get(route('login'));

    $response->assertHeader('X-Content-Type-Options', 'nosniff');
});

it('sends a strict Referrer-Policy header', function () {
    $response = $this->get(route('login'));

    $response->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
});

it('sends a Permissions-Policy restricting unused browser features', function () {
    $response = $this->get(route('login'));

    $response->assertHeader('Permissions-Policy', 'camera=(), microphone=(), geolocation=(), payment=()');
});

it('sends a restrictive Content-Security-Policy on HTML pages', function () {
    $response = $this->get(route('login'));

    $csp = $response->headers->get('Content-Security-Policy');
    expect($csp)
        ->toContain("default-src 'self'")
        ->toContain("script-src 'self'")
        ->toContain("frame-ancestors 'none'")
        ->toContain("form-action 'self'")
        ->toContain("base-uri 'self'");
});

it('omits Content-Security-Policy on file download responses', function () {
    $this->seed([RoleSeeder::class, PermissionSeeder::class]);
    $admin = User::factory()->create();
    $admin->roles()->attach(Role::query()->where('name', 'finance_manager')->firstOrFail());

    // CSV export returns a streamed response with Content-Disposition attachment.
    $response = $this->actingAs($admin)->get(route('reports.export', 'customers'));

    $response->assertOk()->assertDownload();
    expect($response->headers->get('Content-Security-Policy'))->toBeNull();
});

it('applies security headers to authenticated back-office pages', function () {
    $user = User::factory()->create();

    // The login redirect is also a web response — headers must be present.
    $response = $this->actingAs($user)->get(route('login'));

    $response->assertHeader('X-Frame-Options', 'DENY');
    $response->assertHeader('X-Content-Type-Options', 'nosniff');
});

it('applies security headers to the public receipt verification endpoint', function () {
    $response = $this->get('/verify/receipts/nonexistent-code');

    // 404 responses must still carry the security headers.
    $response->assertHeader('X-Frame-Options', 'DENY');
    $response->assertHeader('X-Content-Type-Options', 'nosniff');
});
