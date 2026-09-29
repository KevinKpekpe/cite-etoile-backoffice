<?php

namespace Database\Factories;

use App\Models\AncillaryFee;
use App\Models\Subscription;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AncillaryFee>
 */
class AncillaryFeeFactory extends Factory
{
    protected $model = AncillaryFee::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'subscription_id' => Subscription::factory(),
            'fee_type' => 'cadastral_number',
            'installment_number' => 1,
            'due_date' => now()->toDateString(),
            'amount_due' => '30.00',
            'amount_paid' => '0.00',
            'status' => 'due',
        ];
    }
}
