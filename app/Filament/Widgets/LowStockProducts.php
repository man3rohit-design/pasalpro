<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Enums\UserRole;
use App\Filament\Resources\Products\ProductResource;
use App\Models\Product;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

class LowStockProducts extends TableWidget
{
    protected static ?int $sort = 3;

    protected static ?string $heading = 'Running low';

    public static function canView(): bool
    {
        return auth()->user()?->hasRole(UserRole::Admin, UserRole::Manager, UserRole::Storekeeper) ?? false;
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(fn () => Product::query()->lowStock()->with(['inventory', 'supplier']))
            ->columns([
                TextColumn::make('name')
                    ->wrap(),
                TextColumn::make('inventory.quantity')
                    ->label('Left')
                    ->badge()
                    ->color(fn (Product $record): string => $record->inventory->quantity <= 0 ? 'danger' : 'warning'),
                TextColumn::make('inventory.reorder_level')
                    ->label('Reorder at'),
                TextColumn::make('supplier.name')
                    ->label('Supplier')
                    ->wrap(),
            ])
            ->recordUrl(fn (Product $record): string => ProductResource::getUrl('view', ['record' => $record]))
            ->paginated([5])
            ->emptyStateHeading('All products are well stocked');
    }
}
