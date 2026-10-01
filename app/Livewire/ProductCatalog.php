<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class ProductCatalog extends Component
{
    use WithPagination;

    #[Url]
    public string $search = '';

    #[Url]
    public string $category = '';

    #[Url]
    public string $sort = 'name';

    public function updated(string $property): void
    {
        if (in_array($property, ['search', 'category', 'sort'], true)) {
            $this->resetPage();
        }
    }

    public function render(): View
    {
        $products = Product::active()
            ->with(['inventory', 'category'])
            ->when(filled($this->search), fn (Builder $query) => $query->where('name', 'like', '%'.$this->search.'%'))
            ->when(filled($this->category), fn (Builder $query) => $query->whereRelation('category', 'slug', $this->category))
            ->when($this->sort === 'price_low', fn (Builder $query) => $query->orderBy('selling_price'))
            ->when($this->sort === 'price_high', fn (Builder $query) => $query->orderByDesc('selling_price'))
            ->orderBy('name')
            ->paginate(12);

        $categories = Category::active()->orderBy('name')->get();
        $heading = $categories->firstWhere('slug', $this->category)->name ?? 'All products';

        return view('livewire.product-catalog', [
            'products'   => $products,
            'categories' => $categories,
            'heading'    => $heading,
        ])->title($heading);
    }
}
