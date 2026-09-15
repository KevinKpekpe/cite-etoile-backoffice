<?php

namespace Database\Seeders;

use App\Models\Avenue;
use App\Models\Customer;
use App\Models\Neighborhood;
use App\Models\PaymentPlan;
use App\Models\Plot;
use App\Models\Subscription;
use Illuminate\Database\Seeder;

class DemoDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $neighborhood = Neighborhood::query()->updateOrCreate(
            ['code' => 'DEMO-Q1'],
            ['name' => 'Quartier Démonstration', 'description' => 'Données fictives locales', 'status' => 'active'],
        );

        $avenue = Avenue::query()->updateOrCreate(
            ['neighborhood_id' => $neighborhood->id, 'code' => 'DEMO-AV1'],
            ['name' => 'Avenue Démonstration', 'status' => 'active'],
        );

        $plot = Plot::query()->updateOrCreate(
            ['reference' => 'DEMO-LOT-0001'],
            [
                'plot_number' => 'DEMO-P001',
                'avenue_id' => $avenue->id,
                'surface_area' => '300.00',
                'width' => '15.00',
                'length' => '20.00',
                'base_price' => '2500.00',
                'commercial_status' => 'subscribed',
            ],
        );

        $customer = Customer::query()->updateOrCreate(
            ['customer_number' => 'DEMO-CLI-0001'],
            [
                'first_name' => 'Client',
                'last_name' => 'Démonstration',
                'phone' => '+243000000000',
                'email' => 'demo@example.test',
            ],
        );

        $paymentPlan = PaymentPlan::query()->where('code', 'CASH')->firstOrFail();

        Subscription::query()->updateOrCreate(
            ['subscription_number' => 'DEMO-SUB-0001'],
            [
                'customer_id' => $customer->id,
                'plot_id' => $plot->id,
                'payment_plan_id' => $paymentPlan->id,
                'subscription_date' => '2026-01-01',
                'start_date' => '2026-01-01',
                'expected_end_date' => '2026-01-01',
                'contract_total' => '2500.00',
                'monthly_amount' => null,
                'duration_months' => 0,
                'commercial_status' => 'active',
            ],
        );
    }
}
