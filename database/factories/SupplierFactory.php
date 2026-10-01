<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Supplier;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Supplier>
 */
class SupplierFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name'           => fake()->lastName().' '.fake()->randomElement(['Traders', 'Distributors', 'Suppliers', 'Wholesale']),
            'contact_person' => fake()->name(),
            'phone'          => '01-'.fake()->numerify('4######'),
            'email'          => fake()->unique()->companyEmail(),
            'address'        => fake()->randomElement(['Kalimati', 'Teku', 'Asan', 'Balaju Industrial Area', 'Kuleshwor']).', Kathmandu',
            'pan_number'     => fake()->unique()->numerify('60#######'),
            'is_active'      => true,
        ];
    }
}
