<?php

declare(strict_types=1);

namespace App\Filament\Pages\Reports;

use App\Models\Category;
use App\Models\SaleItem;
use BackedEnum;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Number;

class ProductSalesReport extends ReportPage
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedTrophy;

    protected static ?int $navigationSort = 2;

    protected static ?string $title = 'Product report';

    public function table(Table $table): Table
    {
        return $table
            ->records(fn (?string $sortColumn, ?string $sortDirection): Collection => $this->rows()
                ->sortBy($sortColumn ?? 'revenue', SORT_REGULAR, ($sortDirection ?? 'desc') === 'desc'))
            ->columns([
                TextColumn::make('product')
                    ->wrap(),
                TextColumn::make('category'),
                TextColumn::make('units_sold')
                    ->label('Units sold')
                    ->sortable(),
                TextColumn::make('revenue')
                    ->money()
                    ->sortable(),
                TextColumn::make('gross_profit')
                    ->money()
                    ->sortable(),
                TextColumn::make('in_stock')
                    ->label('In stock')
                    ->sortable(),
            ])
            ->filters([
                static::periodFilter(),
                SelectFilter::make('category')
                    ->options(fn (): array => Category::orderBy('name')->pluck('name', 'id')->all()),
            ])
            ->paginated(false);
    }

    /**
     * @return array<string, string>
     */
    public function getSummary(): array
    {
        $rows = $this->rows();
        $bestSeller = $rows->sortByDesc('units_sold')->first();

        return [
            'Products sold' => (string) $rows->count(),
            'Units sold'    => Number::format($rows->sum('units_sold'), locale: 'en_IN'),
            'Revenue'       => 'Rs. '.Number::format((float) $rows->sum('revenue'), 2, locale: 'en_IN'),
            'Best seller'   => $bestSeller['product'] ?? '-',
        ];
    }

    protected function rows(): Collection
    {
        [$from, $until] = $this->selectedPeriod();
        $categoryId = $this->tableFilters['category']['value'] ?? null;

        return SaleItem::query()
            ->join('sales', 'sales.id', '=', 'sale_items.sale_id')
            ->join('products', 'products.id', '=', 'sale_items.product_id')
            ->join('categories', 'categories.id', '=', 'products.category_id')
            ->join('inventories', 'inventories.product_id', '=', 'products.id')
            ->where('sales.status', 'completed')
            ->whereBetween('sales.sold_at', [$from, $until])
            ->when(filled($categoryId), fn ($query) => $query->where('products.category_id', $categoryId))
            ->groupBy('products.id', 'products.name', 'categories.name', 'inventories.quantity')
            ->select('products.id', 'products.name as product', 'categories.name as category', 'inventories.quantity as in_stock')
            ->selectRaw('sum(sale_items.quantity) as units_sold, sum(sale_items.line_total) as revenue')
            ->addSelect(DB::raw('sum(sale_items.line_total - sale_items.unit_cost * sale_items.quantity) as gross_profit'))
            ->get()
            ->mapWithKeys(fn (SaleItem $row): array => [$row->getAttribute('id') => [
                'product'      => $row->getAttribute('product'),
                'category'     => $row->getAttribute('category'),
                'units_sold'   => (int) $row->getAttribute('units_sold'),
                'revenue'      => round((float) $row->getAttribute('revenue'), 2),
                'gross_profit' => round((float) $row->getAttribute('gross_profit'), 2),
                'in_stock'     => (int) $row->getAttribute('in_stock'),
            ]]);
    }
}
