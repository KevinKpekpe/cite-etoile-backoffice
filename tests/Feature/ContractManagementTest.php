<?php

use App\Models\AncillaryFee;
use App\Models\Contract;
use App\Models\Role;
use App\Models\Subscription;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

it('creates a unique contract dossier with a private PDF and audit record', function () {
    Storage::fake('local');
    $this->seed([RoleSeeder::class, PermissionSeeder::class]);
    $admin = User::factory()->create();
    $admin->roles()->attach(Role::query()->where('name', 'admin')->firstOrFail());
    $subscription = Subscription::factory()->create();

    $this->actingAs($admin)->post(route('subscriptions.contract.store', $subscription), ['signed_at' => '2026-09-15', 'status' => 'signed', 'document' => UploadedFile::fake()->create('contrat.pdf', 500, 'application/pdf')])->assertRedirect();

    $contract = Contract::query()->firstOrFail();
    expect($contract->contract_number)->toStartWith('CTR-')->and($contract->subscription_id)->toBe($subscription->id);
    Storage::disk('local')->assertExists($contract->document_path);
    $this->assertDatabaseHas('audit_logs', ['action' => 'contract.saved', 'entity_id' => $contract->id]);
    expect(AncillaryFee::query()->where('subscription_id', $subscription->id)->whereIn('fee_type', ['cadastral_number', 'occupancy_certificate', 'registration_certificate'])->count())->toBe(3);
});

it('requires a signature date before marking a contract signed', function () {
    $this->seed([RoleSeeder::class, PermissionSeeder::class]);
    $admin = User::factory()->create();
    $admin->roles()->attach(Role::query()->where('name', 'admin')->firstOrFail());
    $subscription = Subscription::factory()->create();

    $this->actingAs($admin)->post(route('subscriptions.contract.store', $subscription), [
        'status' => 'signed',
    ])->assertSessionHasErrors('signed_at');

    $this->assertDatabaseCount('contracts', 0);
    $this->assertDatabaseCount('ancillary_fees', 0);
});

it('rejects non PDF contract documents', function () {
    Storage::fake('local');
    $this->seed([RoleSeeder::class, PermissionSeeder::class]);
    $admin = User::factory()->create();
    $admin->roles()->attach(Role::query()->where('name', 'admin')->firstOrFail());

    $this->actingAs($admin)->post(route('subscriptions.contract.store', Subscription::factory()->create()), ['status' => 'draft', 'document' => UploadedFile::fake()->create('contrat.exe', 10, 'application/octet-stream')])->assertSessionHasErrors('document');
});
