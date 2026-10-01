<?php

declare(strict_types=1);

namespace App\Filament\Resources\Alerts;

use App\Enums\AlertType;
use App\Filament\Resources\Alerts\Pages\ManageAlerts;
use App\Models\Alert;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class AlertResource extends Resource
{
    protected static ?string $model = Alert::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBellAlert;

    protected static string|UnitEnum|null $navigationGroup = 'Inventory';

    protected static ?string $navigationLabel = 'Stock alerts';

    protected static ?string $modelLabel = 'stock alert';

    protected static ?int $navigationSort = 4;

    public static function getNavigationBadge(): ?string
    {
        $openAlerts = Alert::open()->count();

        return $openAlerts > 0 ? (string) $openAlerts : null;
    }

    public static function getNavigationBadgeColor(): string
    {
        return 'danger';
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('product.name')
                    ->searchable()
                    ->wrap(),
                TextColumn::make('type')
                    ->badge(),
                TextColumn::make('quantity_at_alert')
                    ->label('Stock when raised'),
                TextColumn::make('product.inventory.quantity')
                    ->label('Stock now'),
                TextColumn::make('product.inventory.reorder_level')
                    ->label('Reorder level'),
                TextColumn::make('product.supplier.name')
                    ->label('Supplier')
                    ->toggleable(),
                TextColumn::make('created_at')
                    ->label('Raised')
                    ->since()
                    ->sortable(),
                TextColumn::make('resolved_at')
                    ->label('Resolved')
                    ->since()
                    ->placeholder('Open'),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                TernaryFilter::make('resolved')
                    ->placeholder('All alerts')
                    ->trueLabel('Resolved')
                    ->falseLabel('Open')
                    ->default(false)
                    ->queries(
                        true: fn (Builder $query) => $query->whereNotNull('resolved_at'),
                        false: fn (Builder $query) => $query->whereNull('resolved_at'),
                    ),
                SelectFilter::make('type')
                    ->options(AlertType::class),
            ])
            ->recordActions([
                Action::make('resolve')
                    ->label('Mark resolved')
                    ->icon(Heroicon::OutlinedCheck)
                    ->requiresConfirmation()
                    ->authorize('update')
                    ->action(fn (Alert $record) => $record->update([
                        'resolved_at'    => now(),
                        'resolved_by_id' => auth()->id(),
                    ])),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageAlerts::route('/'),
        ];
    }
}
