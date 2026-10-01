<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Models\Product;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class ShowProduct extends Component
{
    public Product $product;

    public function mount(Product $product): void
    {
        abort_unless($product->is_active, 404);

        $this->product = $product->load(['inventory', 'category']);
    }

    public function render(): View
    {
        return view('livewire.show-product', [
            'relatedProducts' => Product::active()
                ->whereBelongsTo($this->product->category)
                ->whereKeyNot($this->product->id)
                ->with(['inventory', 'category'])
                ->inRandomOrder()
                ->limit(4)
                ->get(),
        ])->title($this->product->name);
    }
}
