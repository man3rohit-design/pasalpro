<?php

declare(strict_types=1);

namespace App\Filament\Resources\StockTransactions;

use App\Enums\StockTransactionType;
use App\Filament\Resources\StockTransactions\Pages\ManageStockTransactions;
use App\Models\PurchaseOrder;
use App\Models\Sale;
use App\Models\StockTransaction;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use UnitEnum;

class StockTransactionResource extends Resource
{
    protected static ?string $model = StockTransaction::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedArrowsRightLeft;

    protected static string|UnitEnum|null $navigationGroup = 'Inventory';

    protected static ?string $navigationLabel = 'Stock movements';

    protected static ?string $modelLabel = 'stock movement';

    protected static ?int $navigationSort = 3;

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('created_at')
                    ->label('Date')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('product.name')
                    ->searchable()
                    ->wrap(),
                TextColumn::make('type')
                    ->badge(),
                TextColumn::make('quantity')
                    ->formatStateUsing(fn (int $state): string => $state > 0 ? '+'.$state : (string) $state)
                    ->color(fn (int $state): string => $state > 0 ? 'success' : 'danger')
                    ->weight('bold'),
                TextColumn::make('balance_after')
                    ->label('Balance'),
                TextColumn::make('source_reference')
                    ->label('Reference')
                    ->state(fn (StockTransaction $record): ?string => match (true) {
                        $record->source instanceof Sale, $record->source instanceof PurchaseOrder => $record->source->reference,
                        default => null,
                    })
                    ->placeholder('-'),
                TextColumn::make('performedBy.name')
                    ->label('By')
                    ->placeholder('System'),
                TextColumn::make('note')
                    ->limit(40)
                    ->toggleable(),
            ])
            ->modifyQueryUsing(fn ($query) => $query->with('source'))
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('type')
                    ->options(StockTransactionType::class),
                SelectFilter::make('product')
                    ->relationship('product', 'name')
                    ->searchable()
                    ->preload(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageStockTransactions::route('/'),
        ];
    }
}
