<?php

namespace Database\Factories;

use App\Models\Payment;
use App\Models\Subscription;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Payment>
 */
class PaymentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'payment_reference' => fake()->unique()->bothify('PAY-#####'),
            'subscription_id' => Subscription::factory(),
            'customer_id' => fn (array $attributes) => Subscription::query()->findOrFail($attributes['subscription_id'])->customer_id,
            'payment_date' => now(),
            'amount' => '300.00',
            'currency' => 'USD',
            'payment_method' => 'cash',
        ];
    }
}
