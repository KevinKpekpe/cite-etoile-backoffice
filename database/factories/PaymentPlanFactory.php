<?php

namespace Database\Factories;

use App\Models\PaymentPlan;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PaymentPlan>
 */
class PaymentPlanFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'code' => fake()->unique()->bothify('PLAN-####'),
            'name' => fake()->words(3, true),
            'total_price' => '3600.00',
            'monthly_amount' => '300.00',
            'duration_months' => 12,
            'frequency' => 'monthly',
            'active' => true,
        ];
    }
}
