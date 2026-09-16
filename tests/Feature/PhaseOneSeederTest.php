<?php

use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

test('reference and demonstration data are reproducible', function () {
    $this->seed(DatabaseSeeder::class);
    $this->seed(DatabaseSeeder::class);

    expect(DB::table('roles')->count())->toBe(7)
        ->and(DB::table('payment_plans')->count())->toBe(5)
        ->and(DB::table('settings')->count())->toBe(14)
        ->and(DB::table('neighborhoods')->where('code', 'DEMO-Q1')->count())->toBe(1)
        ->and(DB::table('customers')->where('customer_number', 'DEMO-CLI-0001')->count())->toBe(1)
        ->and(DB::table('subscriptions')->where('subscription_number', 'DEMO-SUB-0001')->count())->toBe(1);

    $cashPlan = DB::table('payment_plans')->where('code', 'CASH')->first();

    expect($cashPlan)->not->toBeNull()
        ->and($cashPlan->total_price)->toBe('2500.00')
        ->and($cashPlan->frequency)->toBe('once');
});
