<?php

use App\Models\Plot;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed([RoleSeeder::class, PermissionSeeder::class]);
    $this->admin = User::factory()->create();
    $this->admin->roles()->attach(Role::query()->where('name', 'admin')->firstOrFail());

    $this->superAdmin = User::factory()->create();
    $this->superAdmin->roles()->attach(Role::query()->where('name', 'super_admin')->firstOrFail());
});

test('authorized user can soft delete plot', function () {
    $plot = Plot::factory()->create();

    $response = $this->actingAs($this->admin)->delete(route('plots.destroy', $plot));

    $response->assertRedirect(route('plots.index'));
    expect(Plot::find($plot->id))->toBeNull()
        ->and(Plot::withTrashed()->find($plot->id))->not->toBeNull();
});

test('trashed plots page can be rendered', function () {
    $plot = Plot::factory()->create();
    $plot->delete();

    $response = $this->actingAs($this->admin)->get(route('plots.trashed'));

    $response->assertStatus(200);
    $response->assertSee($plot->reference);
});

test('authorized user can restore soft deleted plot', function () {
    $plot = Plot::factory()->create();
    $plot->delete();

    $response = $this->actingAs($this->admin)->post(route('plots.restore', $plot));

    $response->assertRedirect(route('plots.show', $plot));
    expect(Plot::find($plot->id))->not->toBeNull();
});

test('super admin can force delete plot without subscriptions', function () {
    $plot = Plot::factory()->create();
    $plot->delete();

    $response = $this->actingAs($this->superAdmin)->delete(route('plots.force-delete', $plot));

    $response->assertRedirect(route('plots.trashed'));
    expect(Plot::withTrashed()->find($plot->id))->toBeNull();
});
