<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\Product;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Validation\ValidationException;

class Cart
{
    private const SESSION_KEY = 'cart';

    /**
     * @return array<int, int> quantities keyed by product id
     */
    public function quantities(): array
    {
        return session(self::SESSION_KEY, []);
    }

    public function add(Product $product, int $quantity = 1): void
    {
        $this->update($product, ($this->quantities()[$product->id] ?? 0) + $quantity);
    }

    public function update(Product $product, int $quantity): void
    {
        if ($quantity <= 0) {
            $this->remove($product->id);

            return;
        }

        $inStock = $product->inventory->quantity;

        throw_if(! $product->is_active || $inStock <= 0, ValidationException::withMessages([
            'cart' => $product->name.' is out of stock.',
        ]));

        throw_if($quantity > $inStock, ValidationException::withMessages([
            'cart' => sprintf('Only %s of %s available.', $inStock, $product->name),
        ]));

        session()->put(self::SESSION_KEY.'.'.$product->id, $quantity);
    }

    public function remove(int $productId): void
    {
        session()->forget(self::SESSION_KEY.'.'.$productId);
    }

    public function clear(): void
    {
        session()->forget(self::SESSION_KEY);
    }

    public function count(): int
    {
        return array_sum($this->quantities());
    }

    /**
     * @return Collection<int, Product>
     */
    public function products(): Collection
    {
        return Product::active()
            ->with(['inventory', 'category'])
            ->findMany(array_keys($this->quantities()));
    }

    public function subtotal(): float
    {
        $quantities = $this->quantities();

        return $this->products()->sum(fn (Product $product): float => $quantities[$product->id] * (float) $product->selling_price);
    }
}
