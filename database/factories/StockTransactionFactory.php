<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\StockTransactionType;
use App\Models\Product;
use App\Models\StockTransaction;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<StockTransaction>
 */
class StockTransactionFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $quantity = fake()->numberBetween(5, 50);

        return [
            'product_id'    => Product::factory(),
            'type'          => StockTransactionType::Adjustment,
            'quantity'      => $quantity,
            'balance_after' => $quantity,
        ];
    }
}
