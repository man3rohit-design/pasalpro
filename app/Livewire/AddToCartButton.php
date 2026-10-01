<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Models\Product;
use App\Support\Cart;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Illuminate\Contracts\View\View;
use Illuminate\Validation\ValidationException;
use Livewire\Component;

class AddToCartButton extends Component
{
    public int $productId;

    public bool $withQuantity = false;

    public int $quantity = 1;

    public function add(Cart $cart): void
    {
        $product = Product::with('inventory')->findOrFail($this->productId);

        try {
            $cart->add($product, max($this->quantity, 1));
        } catch (ValidationException $validationException) {
            Notification::make()->title($validationException->getMessage())->warning()->send();

            return;
        }

        $this->quantity = 1;
        $this->dispatch('cart-updated');

        Notification::make()
            ->title($product->name.' added to your cart')
            ->success()
            ->actions([
                Action::make('viewCart')
                    ->label('View cart')
                    ->url(route('cart')),
            ])
            ->send();
    }

    public function render(): View
    {
        return view('livewire.add-to-cart-button');
    }
}
