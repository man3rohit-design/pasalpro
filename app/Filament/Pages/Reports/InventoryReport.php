<?php

declare(strict_types=1);

namespace App\Filament\Pages\Reports;

use App\Models\Category;
use App\Models\Product;
use BackedEnum;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Collection;
use Illuminate\Support\Number;

class InventoryReport extends ReportPage
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedArchiveBox;

    protected static ?int $navigationSort = 3;

    protected static ?string $title = 'Inventory report';

    public function table(Table $table): Table
    {
        return $table
            ->records(fn (?string $sortColumn, ?string $sortDirection): Collection => $this->rows()
                ->sortBy($sortColumn ?? 'product', SORT_REGULAR, $sortDirection === 'desc'))
            ->columns([
                TextColumn::make('product')
                    ->sortable()
                    ->wrap(),
                TextColumn::make('category'),
                TextColumn::make('on_hand')
                    ->label('On hand')
                    ->sortable(),
                TextColumn::make('reorder_level')
                    ->label('Reorder level'),
                TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Out of stock' => 'danger',
                        'Low stock'    => 'warning',
                        default        => 'success',
                    }),
                TextColumn::make('stock_value')
                    ->label('Value at cost')
                    ->money()
                    ->sortable(),
                TextColumn::make('retail_value')
                    ->label('Value at retail')
                    ->money()
                    ->sortable(),
                TextColumn::make('last_restocked')
                    ->label('Last restocked')
                    ->placeholder('Never'),
            ])
            ->filters([
                SelectFilter::make('category')
                    ->options(fn (): array => Category::orderBy('name')->pluck('name', 'id')->all()),
                SelectFilter::make('status')
                    ->options([
                        'Out of stock' => 'Out of stock',
                        'Low stock'    => 'Low stock',
                        'In stock'     => 'In stock',
                    ]),
            ])
            ->paginated(false);
    }

    /**
     * @return array<string, string>
     */
    public function getSummary(): array
    {
        $rows = $this->rows();

        return [
            'Products'        => (string) $rows->count(),
            'Units on hand'   => Number::format($rows->sum('on_hand'), locale: 'en_IN'),
            'Value at cost'   => 'Rs. '.Number::format((float) $rows->sum('stock_value'), 2, locale: 'en_IN'),
            'Value at retail' => 'Rs. '.Number::format((float) $rows->sum('retail_value'), 2, locale: 'en_IN'),
        ];
    }

    protected function rows(): Collection
    {
        $categoryId = $this->tableFilters['category']['value'] ?? null;
        $status = $this->tableFilters['status']['value'] ?? null;

        return Product::query()
            ->with(['category', 'inventory'])
            ->when(filled($categoryId), fn ($query) => $query->where('category_id', $categoryId))
            ->orderBy('name')
            ->get()
            ->mapWithKeys(fn (Product $product): array => [$product->id => [
                'product'       => $product->name,
                'category'      => $product->category->name,
                'on_hand'       => $product->inventory->quantity,
                'reorder_level' => $product->inventory->reorder_level,
                'status'        => match (true) {
                    $product->inventory->quantity <= 0 => 'Out of stock',
                    $product->inventory->isLowStock()  => 'Low stock',
                    default                            => 'In stock',
                },
                'stock_value'    => round($product->inventory->quantity * (float) $product->cost_price, 2),
                'retail_value'   => round($product->inventory->quantity * (float) $product->selling_price, 2),
                'last_restocked' => $product->inventory->last_restocked_at?->format('M j, Y'),
            ]])
            ->filter(fn (array $row): bool => blank($status) || $row['status'] === $status);
    }
}
