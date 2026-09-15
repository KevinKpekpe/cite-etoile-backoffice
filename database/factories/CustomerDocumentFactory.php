<?php

namespace Database\Factories;

use App\Models\Customer;
use App\Models\CustomerDocument;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CustomerDocument>
 */
class CustomerDocumentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'customer_id' => Customer::factory(),
            'document_type' => 'identity',
            'name' => 'Pièce d’identité',
            'file_path' => 'customers/private/'.fake()->uuid().'.pdf',
        ];
    }
}
