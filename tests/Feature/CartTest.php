<?php

declare(strict_types=1);

use App\Models\Product;
use App\Support\Cart;
use Illuminate\Validation\ValidationException;

beforeEach(function (): void {
    $this->cart = resolve(Cart::class);
});

it('adds products and adds up repeated quantities', function (): void {
    $noodles = Product::factory()->withStock(30)->create(['selling_price' => 25]);
    $tea = Product::factory()->withStock(10)->create(['selling_price' => 330]);

    $this->cart->add($noodles, 2);
    $this->cart->add($noodles, 3);
    $this->cart->add($tea);

    expect($this->cart->quantities())->toBe([$noodles->id => 5, $tea->id => 1])
        ->and($this->cart->count())->toBe(6)
        ->and($this->cart->subtotal())->toBe(455.0);
});

it('refuses more than the shop has in stock', function (): void {
    $paneer = Product::factory()->withStock(3)->create();

    expect(fn () => $this->cart->add($paneer, 4))
        ->toThrow(ValidationException::class, sprintf('Only 3 of %s available.', $paneer->name));
});

it('refuses products that are out of stock', function (): void {
    $product = Product::factory()->withStock(0)->create();

    expect(fn () => $this->cart->add($product))
        ->toThrow(ValidationException::class, $product->name.' is out of stock.');
});

it('removes a product when its quantity is set to zero', function (): void {
    $product = Product::factory()->withStock(10)->create();

    $this->cart->add($product, 2);
    $this->cart->update($product, 0);

    expect($this->cart->quantities())->toBe([]);
});

it('leaves deactivated products out of the cart lines', function (): void {
    $product = Product::factory()->withStock(10)->create();
    $this->cart->add($product);

    $product->update(['is_active' => false]);

    expect($this->cart->products())->toBeEmpty();
});
