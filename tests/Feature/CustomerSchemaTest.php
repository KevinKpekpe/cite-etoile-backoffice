<?php

use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

uses(LazilyRefreshDatabase::class);

test('customer tables match the validated MySQL schema', function () {
    expect(Schema::hasColumns('customers', [
        'id', 'customer_number', 'user_id', 'first_name', 'last_name', 'middle_name',
        'gender', 'birth_date', 'phone', 'whatsapp', 'email', 'address', 'city',
        'country', 'nationality', 'status', 'created_by', 'created_at', 'updated_at',
    ]))->toBeTrue();
    expect(Schema::hasColumns('customer_documents', [
        'id', 'customer_id', 'document_type', 'name', 'file_path', 'uploaded_by', 'created_at',
    ]))->toBeTrue();
    expect(Schema::hasIndex('customers', ['customer_number'], 'unique'))->toBeTrue();
    expect(Schema::hasIndex('customers', ['user_id'], 'unique'))->toBeTrue();
    expect(Schema::hasIndex('customers', ['last_name', 'first_name']))->toBeTrue();
    expect(Schema::hasIndex('customers', 'idx_customers_phone'))->toBeTrue();
    expect(Schema::hasIndex('customers', 'idx_customers_status'))->toBeTrue();
    expect(Schema::hasIndex('customer_documents', 'idx_customer_documents_customer'))->toBeTrue();

    $tables = collect(DB::select(<<<'SQL'
        SELECT TABLE_NAME AS name, ENGINE AS engine, TABLE_COLLATION AS collation
        FROM information_schema.TABLES
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME IN ('customers', 'customer_documents')
        SQL));

    expect($tables)->toHaveCount(2);

    foreach ($tables as $table) {
        expect($table->engine)->toBe('InnoDB');
        expect($table->collation)->toBe('utf8mb4_unicode_ci');
    }
});

test('customers enforce unique references and portal accounts', function () {
    $user = User::factory()->create();
    $customer = [
        'customer_number' => 'CLI-0001',
        'user_id' => $user->id,
        'first_name' => 'Amina',
        'last_name' => 'Mukendi',
        'phone' => '+243810000001',
    ];

    DB::table('customers')->insert($customer);

    expect(fn () => DB::table('customers')->insert([...$customer, 'user_id' => null]))
        ->toThrow(QueryException::class);
    expect(fn () => DB::table('customers')->insert([...$customer, 'customer_number' => 'CLI-0002']))
        ->toThrow(QueryException::class);
});

test('customers and documents reject unknown status values', function () {
    expect(fn () => DB::table('customers')->insert([
        'customer_number' => 'CLI-0001',
        'first_name' => 'Amina',
        'last_name' => 'Mukendi',
        'phone' => '+243810000001',
        'status' => 'deleted',
    ]))->toThrow(QueryException::class);

    $customerId = DB::table('customers')->insertGetId([
        'customer_number' => 'CLI-0002',
        'first_name' => 'David',
        'last_name' => 'Ilunga',
        'phone' => '+243810000002',
    ]);

    expect(fn () => DB::table('customer_documents')->insert([
        'customer_id' => $customerId,
        'document_type' => 'executable',
        'name' => 'Document',
        'file_path' => 'customers/private/document.pdf',
    ]))->toThrow(QueryException::class);
});

test('foreign keys preserve customers and cascade their documents deliberately', function () {
    $portalUser = User::factory()->create();
    $creator = User::factory()->create();
    $uploader = User::factory()->create();
    $customerId = DB::table('customers')->insertGetId([
        'customer_number' => 'CLI-0001',
        'user_id' => $portalUser->id,
        'first_name' => 'Amina',
        'last_name' => 'Mukendi',
        'phone' => '+243810000001',
        'created_by' => $creator->id,
    ]);
    $documentId = DB::table('customer_documents')->insertGetId([
        'customer_id' => $customerId,
        'document_type' => 'identity',
        'name' => 'Pièce d’identité',
        'file_path' => 'customers/private/identity.pdf',
        'uploaded_by' => $uploader->id,
    ]);

    $portalUser->delete();
    $creator->delete();
    $uploader->delete();

    $this->assertDatabaseHas('customers', [
        'id' => $customerId,
        'user_id' => null,
        'created_by' => null,
        'status' => 'active',
    ]);
    $this->assertDatabaseHas('customer_documents', ['id' => $documentId, 'uploaded_by' => null]);

    DB::table('customers')->where('id', $customerId)->delete();

    $this->assertDatabaseMissing('customer_documents', ['id' => $documentId]);
});
