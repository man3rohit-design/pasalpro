<?php

declare(strict_types=1);

namespace App\Filament\Resources\Products\RelationManagers;

use App\Enums\StockTransactionType;
use App\Filament\Resources\Products\Actions\AdjustStockAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class StockTransactionsRelationManager extends RelationManager
{
    protected static string $relationship = 'stockTransactions';

    protected static ?string $title = 'Stock movements';

    public function isReadOnly(): bool
    {
        return true;
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('created_at')
                    ->label('Date')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('type')
                    ->badge(),
                TextColumn::make('quantity')
                    ->formatStateUsing(fn (int $state): string => $state > 0 ? '+'.$state : (string) $state)
                    ->color(fn (int $state): string => $state > 0 ? 'success' : 'danger')
                    ->weight('bold'),
                TextColumn::make('balance_after')
                    ->label('Balance'),
                TextColumn::make('performedBy.name')
                    ->label('By')
                    ->placeholder('System'),
                TextColumn::make('note')
                    ->placeholder('-'),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('type')
                    ->options(StockTransactionType::class),
            ])
            ->headerActions([
                AdjustStockAction::make()
                    ->record(fn (): mixed => $this->getOwnerRecord()),
            ]);
    }
}
