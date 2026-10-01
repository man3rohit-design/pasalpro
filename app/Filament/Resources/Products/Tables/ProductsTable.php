<?php

declare(strict_types=1);

namespace App\Filament\Resources\Products\Tables;

use App\Filament\Resources\Products\Actions\AdjustStockAction;
use App\Models\Product;
use Filament\Actions\ActionGroup;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ProductsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with('inventory'))
            ->columns([
                ImageColumn::make('image_path')
                    ->label('')
                    ->disk('public')
                    ->imageHeight(44)
                    ->square(),
                TextColumn::make('name')
                    ->searchable(['name', 'sku', 'barcode'])
                    ->sortable()
                    ->wrap()
                    ->description(fn (Product $record): string => sprintf('%s · %s', $record->sku, $record->barcode)),
                TextColumn::make('category.name')
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('selling_price')
                    ->label('Price')
                    ->money()
                    ->sortable(),
                TextColumn::make('cost_price')
                    ->label('Cost')
                    ->money()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('inventory.quantity')
                    ->label('In stock')
                    ->badge()
                    ->color(fn (Product $record): string => match (true) {
                        $record->inventory->quantity <= 0 => 'danger',
                        $record->inventory->isLowStock()  => 'warning',
                        default                           => 'success',
                    })
                    ->suffix(fn (Product $record): string => ' '.$record->unit->value)
                    ->sortable(),
                TextColumn::make('supplier.name')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('expiry_date')
                    ->label('Expiry')
                    ->date()
                    ->color(fn (Product $record): ?string => $record->expiry_date?->isBefore(today()->addDays(7)) ? 'danger' : null)
                    ->placeholder('-')
                    ->sortable()
                    ->toggleable(),
                IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean(),
            ])
            ->defaultSort('name')
            ->filters([
                SelectFilter::make('category')
                    ->relationship('category', 'name')
                    ->preload(),
                SelectFilter::make('supplier')
                    ->relationship('supplier', 'name')
                    ->preload(),
                Filter::make('low_stock')
                    ->label('Low stock only')
                    ->query(fn (Builder $query): Builder => $query->scopes(['lowStock'])),
                TernaryFilter::make('is_active')
                    ->label('Available for sale'),
            ])
            ->recordActions([
                ActionGroup::make([
                    ViewAction::make(),
                    EditAction::make(),
                    AdjustStockAction::make(),
                ]),
            ]);
    }
}
