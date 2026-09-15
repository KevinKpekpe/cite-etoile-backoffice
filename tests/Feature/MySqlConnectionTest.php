<?php

use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(LazilyRefreshDatabase::class);

test('tests connect to the dedicated MySQL database', function () {
    $database = DB::selectOne('SELECT DATABASE() AS name, VERSION() AS version, @@character_set_connection AS charset');

    expect($database->name)->toBe('cite_etoile_du_monde_testing');
    expect($database->version)->toStartWith('8.');
    expect($database->charset)->toBe('utf8mb4');
});

test('test migrations support factory records in an isolated transaction', function () {
    $user = User::factory()->create();

    $this->assertModelExists($user);
    expect(DB::transactionLevel())->toBeGreaterThan(0);
});
