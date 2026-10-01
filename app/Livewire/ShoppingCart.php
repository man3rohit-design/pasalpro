<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Models\Product;
use App\Support\Cart;
use Filament\Notifications\Notification;
use Illuminate\Contracts\View\View;
use Illuminate\Validation\ValidationException;
use Livewire\Component;

class ShoppingCart extends Component
{
    public function updateQuantity(Cart $cart, int $productId, int $quantity): void
    {
        try {
            $cart->update(Product::with('inventory')->findOrFail($productId), $quantity);
        } catch (ValidationException $validationException) {
            Notification::make()->title($validationException->getMessage())->warning()->send();
        }

        $this->dispatch('cart-updated');
    }

    public function remove(Cart $cart, int $productId): void
    {
        $cart->remove($productId);
        $this->dispatch('cart-updated');
    }

    public function render(Cart $cart): View
    {
        return view('livewire.shopping-cart', [
            'products'   => $cart->products(),
            'quantities' => $cart->quantities(),
            'subtotal'   => $cart->subtotal(),
        ])->title('Your cart');
    }
}
