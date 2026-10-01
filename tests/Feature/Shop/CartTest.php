<?php

declare(strict_types=1);

use App\Livewire\AddToCartButton;
use App\Livewire\CartCounter;
use App\Livewire\ShoppingCart;
use App\Models\Product;
use App\Support\Cart;
use Livewire\Livewire;

it('adds a product to the cart and refreshes the counter', function (): void {
    $product = Product::factory()->withStock(10)->create();

    Livewire::test(AddToCartButton::class, ['productId' => $product->id, 'withQuantity' => true])
        ->set('quantity', 3)
        ->call('add')
        ->assertDispatched('cart-updated')
        ->assertNotified($product->name.' added to your cart');

    expect(resolve(Cart::class)->quantities())->toBe([$product->id => 3]);
});

it('tells the shopper when there is not enough stock', function (): void {
    $product = Product::factory()->withStock(1)->create();

    Livewire::test(AddToCartButton::class, ['productId' => $product->id, 'withQuantity' => true])
        ->set('quantity', 2)
        ->call('add')
        ->assertNotDispatched('cart-updated')
        ->assertNotified(sprintf('Only 1 of %s available.', $product->name));
});

it('updates quantities and totals on the cart page', function (): void {
    $product = Product::factory()->withStock(10)->create(['selling_price' => 120]);
    resolve(Cart::class)->add($product);

    Livewire::test(ShoppingCart::class)
        ->call('updateQuantity', $product->id, 3)
        ->assertSee('Rs. 360.00')
        ->call('remove', $product->id)
        ->assertSee('Your cart is empty');
});

it('refreshes the header cart count when the cart changes', function (): void {
    $product = Product::factory()->withStock(10)->create();
    $counter = Livewire::test(CartCounter::class)->assertViewHas('count', 0);

    resolve(Cart::class)->add($product, 4);

    $counter->dispatch('cart-updated')->assertViewHas('count', 4);
});
