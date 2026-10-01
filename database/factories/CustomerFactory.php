<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Customer;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Customer>
 */
class CustomerFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name'    => fake()->name(),
            'phone'   => fake()->unique()->numerify('98########'),
            'email'   => fake()->optional(0.6)->safeEmail(),
            'address' => fake()->randomElement([
                'New Baneshwor', 'Koteshwor', 'Chabahil', 'Maharajgunj', 'Kalanki', 'Lazimpat',
                'Baluwatar', 'Boudha', 'Tokha', 'Gongabu', 'Sitapaila', 'Thapathali',
            ]).', Kathmandu',
        ];
    }

    public function withAccount(): static
    {
        return $this->state(function (array $attributes): array {
            $email = $attributes['email'] ?? fake()->unique()->safeEmail();

            return [
                'email'   => $email,
                'user_id' => User::factory()->state([
                    'name'  => $attributes['name'],
                    'email' => $email,
                    'phone' => $attributes['phone'],
                ]),
            ];
        });
    }
}
