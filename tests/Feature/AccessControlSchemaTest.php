<?php

use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

uses(LazilyRefreshDatabase::class);

test('access control tables match the validated MySQL schema', function () {
    expect(Schema::hasColumns('users', [
        'id',
        'first_name',
        'last_name',
        'email',
        'phone',
        'password',
        'remember_token',
        'status',
        'last_login_at',
        'created_at',
        'updated_at',
    ]))->toBeTrue();
    expect(Schema::hasColumn('users', 'name'))->toBeFalse();
    expect(Schema::hasColumn('users', 'email_verified_at'))->toBeFalse();
    expect(Schema::hasTable('roles'))->toBeTrue();
    expect(Schema::hasTable('permissions'))->toBeTrue();
    expect(Schema::hasTable('user_roles'))->toBeTrue();
    expect(Schema::hasTable('role_permissions'))->toBeTrue();
    expect(Schema::hasIndex('users', 'idx_users_status'))->toBeTrue();
    expect(Schema::hasIndex('roles', ['name'], 'unique'))->toBeTrue();
    expect(Schema::hasIndex('permissions', ['name'], 'unique'))->toBeTrue();
    expect(Schema::hasIndex('user_roles', ['user_id', 'role_id'], 'primary'))->toBeTrue();
    expect(Schema::hasIndex('role_permissions', ['role_id', 'permission_id'], 'primary'))->toBeTrue();

    $tables = collect(DB::select(<<<'SQL'
        SELECT TABLE_NAME AS name, ENGINE AS engine, TABLE_COLLATION AS collation
        FROM information_schema.TABLES
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME IN ('users', 'roles', 'permissions', 'user_roles', 'role_permissions')
        SQL))->keyBy('name');

    expect($tables)->toHaveCount(5);

    foreach ($tables as $table) {
        expect($table->engine)->toBe('InnoDB');
        expect($table->collation)->toBe('utf8mb4_unicode_ci');
    }
});

test('users enforce unique email and use an active status by default', function () {
    $user = User::factory()->create(['email' => 'agent@example.test']);

    expect($user->refresh()->status)->toBe('active');
    expect(fn () => User::factory()->create(['email' => 'agent@example.test']))
        ->toThrow(QueryException::class);
});

test('users reject statuses outside the validated lifecycle', function () {
    expect(fn () => User::factory()->create(['status' => 'pending']))
        ->toThrow(QueryException::class);
});

test('roles and permissions enforce unique names', function () {
    DB::table('roles')->insert(['name' => 'admin']);
    DB::table('permissions')->insert(['name' => 'customers.view']);

    expect(fn () => DB::table('roles')->insert(['name' => 'admin']))
        ->toThrow(QueryException::class);
    expect(fn () => DB::table('permissions')->insert(['name' => 'customers.view']))
        ->toThrow(QueryException::class);
});

test('pivot tables prevent duplicates and cascade deleted assignments', function () {
    $user = User::factory()->create();
    $roleId = DB::table('roles')->insertGetId(['name' => 'admin']);
    $permissionId = DB::table('permissions')->insertGetId(['name' => 'customers.view']);

    DB::table('user_roles')->insert(['user_id' => $user->id, 'role_id' => $roleId]);
    DB::table('role_permissions')->insert(['role_id' => $roleId, 'permission_id' => $permissionId]);

    expect(fn () => DB::table('user_roles')->insert([
        'user_id' => $user->id,
        'role_id' => $roleId,
    ]))->toThrow(QueryException::class);

    DB::table('roles')->where('id', $roleId)->delete();

    $this->assertDatabaseMissing('user_roles', ['user_id' => $user->id, 'role_id' => $roleId]);
    $this->assertDatabaseMissing('role_permissions', ['role_id' => $roleId, 'permission_id' => $permissionId]);
});
