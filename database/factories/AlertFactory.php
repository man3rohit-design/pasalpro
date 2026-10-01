<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\AlertType;
use App\Models\Alert;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Alert>
 */
class AlertFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'product_id'        => Product::factory(),
            'type'              => AlertType::LowStock,
            'quantity_at_alert' => fake()->numberBetween(1, 9),
        ];
    }
}
