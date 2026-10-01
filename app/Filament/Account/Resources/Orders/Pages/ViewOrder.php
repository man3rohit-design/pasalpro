<?php

declare(strict_types=1);

namespace App\Filament\Account\Resources\Orders\Pages;

use App\Actions\CancelSale;
use App\Enums\SaleStatus;
use App\Filament\Account\Resources\Orders\OrderResource;
use App\Models\Sale;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Filament\Support\Icons\Heroicon;

class ViewOrder extends ViewRecord
{
    protected static string $resource = OrderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('invoice')
                ->label('Download invoice')
                ->icon(Heroicon::OutlinedPrinter)
                ->color('gray')
                ->url(fn (Sale $record): string => route('sales.invoice', $record), shouldOpenInNewTab: true)
                ->visible(fn (Sale $record): bool => $record->status === SaleStatus::Completed),
            Action::make('cancel')
                ->label('Cancel order')
                ->icon(Heroicon::OutlinedXCircle)
                ->color('danger')
                ->requiresConfirmation()
                ->modalDescription('You can cancel until we confirm your order.')
                ->visible(fn (Sale $record): bool => $record->status === SaleStatus::Pending)
                ->action(function (Sale $record, CancelSale $cancelSale): void {
                    $cancelSale->handle($record, auth()->user());

                    Notification::make()->title('Your order has been cancelled')->send();
                }),
        ];
    }
}
