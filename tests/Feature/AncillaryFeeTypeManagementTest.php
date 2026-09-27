<?php

use App\Models\AncillaryFee;
use App\Models\AncillaryFeeType;
use App\Models\AuditLog;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('shows the fee type catalogue to authorized staff and links it in navigation', function () {
    $this->seed([RoleSeeder::class, PermissionSeeder::class]);
    $cashier = User::factory()->create();
    $cashier->roles()->attach(Role::query()->where('name', 'cashier')->firstOrFail());

    $this->actingAs($cashier)
        ->get(route('ancillary-fee-types.index'))
        ->assertSee('Types de frais connexes')
        ->assertSee('Certificat d’occupation')
        ->assertSee('Nouveau type');
});

it('creates and updates a custom fee type with audit entries', function () {
    $this->seed([RoleSeeder::class, PermissionSeeder::class]);
    $cashier = User::factory()->create();
    $cashier->roles()->attach(Role::query()->where('name', 'cashier')->firstOrFail());

    $this->actingAs($cashier)->post(route('ancillary-fee-types.store'), [
        'code' => 'water_connection',
        'name' => 'Raccordement en eau',
        'default_amount' => '125.00',
    ])->assertRedirect(route('ancillary-fee-types.index'));

    $feeType = AncillaryFeeType::query()->where('code', 'water_connection')->firstOrFail();
    $this->assertModelExists($feeType);

    $this->put(route('ancillary-fee-types.update', $feeType), [
        'name' => 'Branchement eau',
        'default_amount' => '150.00',
    ])->assertRedirect(route('ancillary-fee-types.index'));

    expect($feeType->refresh()->name)->toBe('Branchement eau')
        ->and($feeType->default_amount)->toBe('150.00');
    expect(AuditLog::query()->whereIn('action', ['ancillary_fee_type.created', 'ancillary_fee_type.updated'])->count())->toBe(2);
});

it('deletes an unused custom fee type', function () {
    $this->seed([RoleSeeder::class, PermissionSeeder::class]);
    $cashier = User::factory()->create();
    $cashier->roles()->attach(Role::query()->where('name', 'cashier')->firstOrFail());
    $feeType = AncillaryFeeType::query()->create([
        'code' => 'water_connection',
        'name' => 'Raccordement en eau',
        'default_amount' => '125.00',
    ]);

    $this->actingAs($cashier)->delete(route('ancillary-fee-types.destroy', $feeType))
        ->assertRedirect(route('ancillary-fee-types.index'));

    $this->assertModelMissing($feeType);
    $this->assertDatabaseHas('audit_logs', ['action' => 'ancillary_fee_type.deleted', 'entity_id' => $feeType->id]);
});

it('prevents deleting required or referenced fee types', function () {
    $this->seed([RoleSeeder::class, PermissionSeeder::class]);
    $cashier = User::factory()->create();
    $cashier->roles()->attach(Role::query()->where('name', 'cashier')->firstOrFail());
    $requiredType = AncillaryFeeType::query()->where('code', 'survey')->firstOrFail();
    $customType = AncillaryFeeType::query()->create([
        'code' => 'water_connection',
        'name' => 'Raccordement en eau',
        'default_amount' => '125.00',
    ]);
    AncillaryFee::factory()->create(['fee_type' => $customType->code, 'fee_label' => $customType->name]);

    $this->actingAs($cashier)->delete(route('ancillary-fee-types.destroy', $requiredType))
        ->assertSessionHasErrors('fee_type');
    $this->delete(route('ancillary-fee-types.destroy', $customType))
        ->assertSessionHasErrors('fee_type');

    $this->assertModelExists($requiredType);
    $this->assertModelExists($customType);
});

it('keeps the saved fee label when a type is renamed', function () {
    $feeType = AncillaryFeeType::query()->where('code', 'cadastral_number')->firstOrFail();
    $fee = AncillaryFee::factory()->create([
        'fee_type' => $feeType->code,
        'fee_label' => $feeType->name,
    ]);
    $feeType->update(['name' => 'Référence cadastrale']);

    expect($fee->fresh()->label())->toBe('Numéro cadastral');
});

it('forbids customers from managing the fee type catalogue', function () {
    $this->seed([RoleSeeder::class, PermissionSeeder::class]);
    $customer = User::factory()->create();
    $customer->roles()->attach(Role::query()->where('name', 'customer')->firstOrFail());

    $this->actingAs($customer)->get(route('ancillary-fee-types.index'))->assertForbidden();
});
