<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Support\Cart;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\On;
use Livewire\Component;

class CartCounter extends Component
{
    #[On('cart-updated')]
    public function render(Cart $cart): View
    {
        return view('livewire.cart-counter', [
            'count' => $cart->count(),
        ]);
    }
}
