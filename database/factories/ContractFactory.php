<?php

namespace Database\Factories;

use App\Models\Contract;
use App\Models\Subscription;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Contract>
 */
class ContractFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'contract_number' => fake()->unique()->bothify('CTR-#####'),
            'subscription_id' => Subscription::factory(),
            'status' => 'draft',
        ];
    }
}
