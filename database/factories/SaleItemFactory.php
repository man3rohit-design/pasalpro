<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SaleItem>
 */
class SaleItemFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'sale_id'      => Sale::factory(),
            'product_id'   => Product::factory(),
            'product_name' => fn (array $attributes): string => Product::find($attributes['product_id'])->name,
            'quantity'     => fake()->numberBetween(1, 4),
            'unit_price'   => fn (array $attributes): string => Product::find($attributes['product_id'])->selling_price,
            'unit_cost'    => fn (array $attributes): string => Product::find($attributes['product_id'])->cost_price,
        ];
    }
}
