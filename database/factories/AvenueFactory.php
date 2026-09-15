<?php

namespace Database\Factories;

use App\Models\Avenue;
use App\Models\Neighborhood;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Avenue>
 */
class AvenueFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'neighborhood_id' => Neighborhood::factory(),
            'code' => fake()->bothify('AV-###'),
            'name' => fake()->streetName(),
            'status' => 'active',
        ];
    }
}
