<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PaymentPlanSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $plans = [
            ['code' => 'CASH', 'name' => 'Formule Cash', 'total_price' => '2500.00', 'monthly_amount' => null, 'duration_months' => 0, 'frequency' => 'once', 'active' => true],
            ['code' => 'CREDIT_1Y', 'name' => 'Crédit 1 an', 'total_price' => '3600.00', 'monthly_amount' => '300.00', 'duration_months' => 12, 'frequency' => 'monthly', 'active' => true],
            ['code' => 'CREDIT_3Y', 'name' => 'Crédit 3 ans', 'total_price' => '6200.00', 'monthly_amount' => '175.00', 'duration_months' => 36, 'frequency' => 'monthly', 'active' => true],
            ['code' => 'CREDIT_5Y', 'name' => 'Crédit 5 ans', 'total_price' => '7500.00', 'monthly_amount' => '125.00', 'duration_months' => 60, 'frequency' => 'monthly', 'active' => true],
            ['code' => 'CREDIT_10Y', 'name' => 'Crédit 10 ans', 'total_price' => '9000.00', 'monthly_amount' => '75.00', 'duration_months' => 120, 'frequency' => 'monthly', 'active' => true],
        ];

        DB::table('payment_plans')->upsert($plans, ['code'], [
            'name', 'total_price', 'monthly_amount', 'duration_months', 'frequency', 'active',
        ]);
    }
}
