<?php

namespace Database\Factories;

use App\Models\Avenue;
use App\Models\Plot;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Plot>
 */
class PlotFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'plot_number' => fake()->unique()->numerify('P-####'),
            'reference' => fake()->unique()->bothify('LOT-#####'),
            'avenue_id' => Avenue::factory(),
            'surface_area' => '300.00',
            'width' => '15.00',
            'length' => '20.00',
            'base_price' => '2500.00',
        ];
    }
}
