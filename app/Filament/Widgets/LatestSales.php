<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Filament\Resources\Sales\SaleResource;
use App\Models\Sale;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

class LatestSales extends TableWidget
{
    protected static ?int $sort = 4;

    protected static ?string $heading = 'Latest sales';

    public function table(Table $table): Table
    {
        return $table
            ->query(fn () => Sale::query()->with('customer')->latest('sold_at'))
            ->columns([
                TextColumn::make('reference')
                    ->description(fn (Sale $record): string => $record->sold_at->diffForHumans()),
                TextColumn::make('customer.name')
                    ->placeholder('Walk-in customer'),
                TextColumn::make('total')
                    ->money(),
                TextColumn::make('status')
                    ->badge(),
            ])
            ->recordUrl(fn (Sale $record): string => SaleResource::getUrl('view', ['record' => $record]))
            ->paginated([5]);
    }
}
