<?php

namespace Database\Factories;

use App\Models\Customer;
use App\Models\PaymentPlan;
use App\Models\Plot;
use App\Models\Subscription;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Subscription>
 */
class SubscriptionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'subscription_number' => fake()->unique()->bothify('SUB-#####'),
            'customer_id' => Customer::factory(),
            'plot_id' => Plot::factory(),
            'payment_plan_id' => PaymentPlan::factory(),
            'subscription_date' => now()->toDateString(),
            'start_date' => now()->toDateString(),
            'expected_end_date' => now()->addYear()->toDateString(),
            'contract_total' => '3600.00',
            'monthly_amount' => '300.00',
            'duration_months' => 12,
        ];
    }
}
