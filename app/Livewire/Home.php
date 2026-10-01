<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Enums\SaleStatus;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Component;

class Home extends Component
{
    public function render(): View
    {
        return view('livewire.home', [
            'categories' => Category::active()
                ->withCount(['products' => fn (Builder $query) => $query->where('is_active', true)])
                ->orderBy('name')
                ->get(),
            'featuredProducts' => Product::active()
                ->where('is_featured', true)
                ->with(['inventory', 'category'])
                ->orderBy('name')
                ->limit(8)
                ->get(),
            'bestSellers' => Product::active()
                ->with(['inventory', 'category'])
                ->withSum(['saleItems as units_sold' => fn (Builder $query) => $query->whereHas(
                    'sale',
                    fn (Builder $sale) => $sale->where('status', SaleStatus::Completed)->where('sold_at', '>=', now()->subDays(30)),
                )], 'quantity')
                ->orderByDesc('units_sold')
                ->limit(4)
                ->get(),
        ])->title('Groceries delivered in Kathmandu');
    }
}
