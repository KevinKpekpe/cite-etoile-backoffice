<?php

use App\Models\AuditLog;
use App\Models\Avenue;
use App\Models\Neighborhood;
use App\Models\Plot;
use App\Models\Role;
use App\Models\Subscription;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed([RoleSeeder::class, PermissionSeeder::class]);
    $this->admin = User::factory()->create();
    $this->admin->roles()->attach(Role::query()->where('name', 'admin')->firstOrFail());
});

it('creates configurable neighborhoods and avenues', function () {
    $this->actingAs($this->admin)->post(route('neighborhoods.store'), ['code' => 'Q-NORD', 'name' => 'Nord', 'status' => 'commercializable'])->assertRedirect(route('neighborhoods.index'));
    $neighborhood = Neighborhood::query()->where('code', 'Q-NORD')->firstOrFail();
    $this->actingAs($this->admin)->post(route('avenues.store'), ['neighborhood_id' => $neighborhood->id, 'code' => 'SOLEIL', 'name' => 'Avenue Soleil', 'status' => 'active'])->assertRedirect(route('avenues.index'));

    expect(Avenue::query()->firstOrFail()->neighborhood_id)->toBe($neighborhood->id);
});

it('validates unique plot references and separate statuses', function () {
    $avenue = Avenue::factory()->create();
    $payload = ['plot_number' => 'P-001', 'reference' => 'LOT-UNIQUE', 'avenue_id' => $avenue->id, 'commercial_status' => 'available', 'financial_status' => 'unpaid', 'administrative_status' => 'not_started'];

    $this->actingAs($this->admin)->post(route('plots.store'), $payload)->assertRedirect();
    $this->actingAs($this->admin)->post(route('plots.store'), [...$payload, 'plot_number' => 'P-002'])->assertSessionHasErrors('reference');

    $this->assertDatabaseHas('plots', ['reference' => 'LOT-UNIQUE', 'commercial_status' => 'available', 'financial_status' => 'unpaid', 'administrative_status' => 'not_started']);
});

it('filters plots by neighborhood avenue and commercial status', function () {
    $targetAvenue = Avenue::factory()->create();
    $target = Plot::factory()->for($targetAvenue)->create(['reference' => 'LOT-CIBLE', 'commercial_status' => 'reserved']);
    Plot::factory()->create(['reference' => 'LOT-AUTRE', 'commercial_status' => 'available']);

    $this->actingAs($this->admin)->get(route('plots.index', ['neighborhood_id' => $targetAvenue->neighborhood_id, 'avenue_id' => $targetAvenue->id, 'commercial_status' => 'reserved']))
        ->assertOk()->assertSee($target->reference)->assertDontSee('LOT-AUTRE');
});

it('shows a controlled plot record with attribution and history', function () {
    $plot = Plot::factory()->create();
    $subscription = Subscription::factory()->for($plot)->create();
    AuditLog::factory()->create(['entity_type' => Plot::class, 'entity_id' => $plot->id, 'action' => 'plot.updated']);

    $this->actingAs($this->admin)->get(route('plots.show', $plot))->assertOk()
        ->assertSee($plot->reference)->assertSee($subscription->customer->first_name)->assertSee('plot.updated');
});

it('prevents concurrent active attribution at database level', function () {
    $plot = Plot::factory()->create();
    Subscription::factory()->for($plot)->create(['commercial_status' => 'active']);

    expect(fn () => Subscription::factory()->for($plot)->create(['commercial_status' => 'pending']))->toThrow(QueryException::class);
});
