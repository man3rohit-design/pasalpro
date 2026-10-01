<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Category;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Category>
 */
class CategoryFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->unique()->randomElement([
            'Rice, Dal & Grains', 'Instant Noodles', 'Biscuits & Snacks', 'Dairy & Eggs', 'Beverages',
            'Cooking Oil & Ghee', 'Spices & Masala', 'Personal Care', 'Household Cleaning', 'Tea & Coffee',
        ]);

        return [
            'name'        => $name,
            'slug'        => str($name)->slug(),
            'description' => fake()->sentence(),
            'is_active'   => true,
        ];
    }
}
