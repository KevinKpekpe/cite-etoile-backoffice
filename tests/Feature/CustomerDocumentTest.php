<?php

use App\Models\Customer;
use App\Models\CustomerDocument;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

beforeEach(function () {
    Storage::fake('local');
    $this->seed([RoleSeeder::class, PermissionSeeder::class]);
    $this->agent = User::factory()->create();
    $this->agent->roles()->attach(Role::query()->where('name', 'commercial')->firstOrFail());
});

it('stores validated customer documents on the private disk', function () {
    $customer = Customer::factory()->create();

    $this->actingAs($this->agent)->post(route('customers.documents.store', $customer), [
        'document_type' => 'identity', 'document' => UploadedFile::fake()->create('identite.pdf', 120, 'application/pdf'),
    ])->assertRedirect();

    $document = CustomerDocument::query()->firstOrFail();
    Storage::disk('local')->assertExists($document->file_path);
    $this->assertDatabaseHas('audit_logs', ['action' => 'customer.document_uploaded', 'entity_id' => $document->id]);
});

it('rejects unsafe document types', function () {
    $customer = Customer::factory()->create();

    $this->actingAs($this->agent)->post(route('customers.documents.store', $customer), [
        'document_type' => 'other', 'document' => UploadedFile::fake()->create('script.php', 10, 'text/x-php'),
    ])->assertSessionHasErrors('document');

    $this->assertDatabaseCount('customer_documents', 0);
});

it('prevents downloading a document through another customer route', function () {
    $owner = Customer::factory()->create();
    $otherCustomer = Customer::factory()->create();
    $document = CustomerDocument::factory()->for($owner)->create(['file_path' => 'customers/file.pdf']);
    Storage::disk('local')->put($document->file_path, 'private');

    $this->actingAs($this->agent)->get(route('customers.documents.download', [$otherCustomer, $document]))->assertNotFound();
});
