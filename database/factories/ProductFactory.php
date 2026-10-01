<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\ProductUnit;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Product>
 */
class ProductFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->randomElement(['Wai Wai', 'Rara', 'Mayos', 'Ruchee', 'Gyan', 'DDC', 'Sujal']).' '.fake()->randomElement(['Noodles', 'Biscuit', 'Masala', 'Ghee', 'Atta', 'Chiura']);
        $costPrice = fake()->numberBetween(20, 900);

        return [
            'category_id'   => Category::factory(),
            'name'          => $name,
            'slug'          => str($name)->slug().'-'.fake()->unique()->numberBetween(100, 99999),
            'sku'           => fake()->unique()->bothify('SKU-#####'),
            'barcode'       => Product::generateBarcode(),
            'description'   => fake()->sentence(),
            'unit'          => fake()->randomElement(ProductUnit::cases()),
            'cost_price'    => $costPrice,
            'selling_price' => round($costPrice * 1.18),
            'is_active'     => true,
            'is_featured'   => false,
        ];
    }

    public function inactive(): static
    {
        return $this->state(['is_active' => false]);
    }

    public function withStock(int $quantity, int $reorderLevel = 10): static
    {
        return $this->afterCreating(function (Product $product) use ($quantity, $reorderLevel): void {
            $product->inventory()->update([
                'quantity'      => $quantity,
                'reorder_level' => $reorderLevel,
            ]);
        });
    }
}
