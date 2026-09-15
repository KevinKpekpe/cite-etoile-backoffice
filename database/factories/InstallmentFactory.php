<?php

namespace Database\Factories;

use App\Models\Installment;
use App\Models\Subscription;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Installment>
 */
class InstallmentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'subscription_id' => Subscription::factory(),
            'installment_number' => 1,
            'due_date' => now()->addMonth()->toDateString(),
            'amount_due' => '300.00',
            'amount_paid' => '0.00',
        ];
    }
}
