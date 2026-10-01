<?php

declare(strict_types=1);

namespace App\Filament\Resources\Sales\Tables;

use App\Enums\PaymentMethod;
use App\Enums\SaleStatus;
use App\Models\Sale;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class SalesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('reference')
                    ->searchable()
                    ->weight('medium')
                    ->description(fn (Sale $record): string => $record->sold_at->format('M j, Y g:i A')),
                TextColumn::make('channel')
                    ->badge(),
                TextColumn::make('customer.name')
                    ->searchable()
                    ->placeholder('Walk-in customer'),
                TextColumn::make('items_count')
                    ->counts('items')
                    ->label('Items'),
                TextColumn::make('total')
                    ->money()
                    ->sortable(),
                TextColumn::make('payment_method')
                    ->label('Payment')
                    ->description(fn (Sale $record): string => $record->payment_status->getLabel()),
                TextColumn::make('status')
                    ->badge(),
                TextColumn::make('soldBy.name')
                    ->label('Cashier')
                    ->placeholder('-')
                    ->toggleable(),
                TextColumn::make('sold_at')
                    ->label('Date')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('sold_at', 'desc')
            ->filters([
                SelectFilter::make('status')
                    ->options(SaleStatus::class),
                SelectFilter::make('payment_method')
                    ->options(PaymentMethod::class),
                Filter::make('sold_at')
                    ->schema([
                        DatePicker::make('from'),
                        DatePicker::make('until'),
                    ])
                    ->query(fn (Builder $query, array $data): Builder => $query
                        ->when($data['from'], fn (Builder $query, string $date) => $query->whereDate('sold_at', '>=', $date))
                        ->when($data['until'], fn (Builder $query, string $date) => $query->whereDate('sold_at', '<=', $date))),
            ])
            ->recordActions([
                ViewAction::make(),
            ]);
    }
}
