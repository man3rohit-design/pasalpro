<?php

declare(strict_types=1);

namespace App\Filament\Resources\Products\Actions;

use App\Actions\RecordStockMovement;
use App\Enums\StockTransactionType;
use App\Models\Product;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;

class AdjustStockAction extends Action
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->label('Adjust stock')
            ->icon(Heroicon::OutlinedAdjustmentsHorizontal)
            ->color('gray')
            ->authorize('adjustStock')
            ->modalDescription(fn (Product $record): string => sprintf('%s has %s on hand.', $record->name, $record->inventory->quantity))
            ->schema([
                TextInput::make('quantity')
                    ->label('Change in quantity')
                    ->helperText('Use a negative number to remove stock, e.g. -2 for damaged packets.')
                    ->numeric()
                    ->integer()
                    ->notIn([0])
                    ->required(),
                TextInput::make('note')
                    ->label('Reason')
                    ->required()
                    ->maxLength(255),
            ])
            ->action(function (Product $record, array $data, RecordStockMovement $recordStockMovement): void {
                $recordStockMovement->handle(
                    $record,
                    (int) $data['quantity'],
                    StockTransactionType::Adjustment,
                    performedBy: auth()->user(),
                    note: $data['note'],
                );

                Notification::make()
                    ->title('Stock updated')
                    ->body(sprintf('%s now has %s on hand.', $record->name, $record->inventory->refresh()->quantity))
                    ->success()
                    ->send();
            });
    }

    public static function getDefaultName(): ?string
    {
        return 'adjustStock';
    }
}
