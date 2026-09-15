<?php

namespace Database\Factories;

use App\Models\Payment;
use App\Models\Receipt;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Receipt>
 */
class ReceiptFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'receipt_number' => fake()->unique()->bothify('REC-#####'),
            'payment_id' => Payment::factory(),
            'customer_id' => fn (array $attributes) => Payment::query()->findOrFail($attributes['payment_id'])->customer_id,
            'subscription_id' => fn (array $attributes) => Payment::query()->findOrFail($attributes['payment_id'])->subscription_id,
            'amount' => fn (array $attributes) => Payment::query()->findOrFail($attributes['payment_id'])->amount,
            'issued_at' => now(),
            'verification_code' => fake()->unique()->uuid(),
        ];
    }
}
