<?php

declare(strict_types=1);

namespace App\Filament\Resources\Sales\Pages;

use App\Actions\CancelSale;
use App\Actions\CompleteSale;
use App\Enums\SaleStatus;
use App\Filament\Resources\Sales\SaleResource;
use App\Models\Sale;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Filament\Support\Icons\Heroicon;

class ViewSale extends ViewRecord
{
    protected static string $resource = SaleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('confirm')
                ->label('Confirm order')
                ->icon(Heroicon::OutlinedCheck)
                ->authorize('fulfil')
                ->visible(fn (Sale $record): bool => $record->status === SaleStatus::Pending)
                ->action(fn (Sale $record) => $this->moveTo($record, SaleStatus::Confirmed)),
            Action::make('dispatch')
                ->label('Out for delivery')
                ->icon(Heroicon::OutlinedTruck)
                ->authorize('fulfil')
                ->visible(fn (Sale $record): bool => $record->status === SaleStatus::Confirmed)
                ->action(fn (Sale $record) => $this->moveTo($record, SaleStatus::OutForDelivery)),
            Action::make('complete')
                ->label('Mark delivered')
                ->icon(Heroicon::OutlinedCheckBadge)
                ->color('success')
                ->requiresConfirmation()
                ->modalDescription('Confirm the customer has received the order and paid in full.')
                ->authorize('fulfil')
                ->visible(fn (Sale $record): bool => $record->status === SaleStatus::OutForDelivery)
                ->action(function (Sale $record, CompleteSale $completeSale): void {
                    $completeSale->handle($record, auth()->user());

                    Notification::make()->title('Order delivered and invoiced')->success()->send();
                }),
            Action::make('invoice')
                ->label('Print invoice')
                ->icon(Heroicon::OutlinedPrinter)
                ->color('gray')
                ->url(fn (Sale $record): string => route('sales.invoice', $record), shouldOpenInNewTab: true)
                ->visible(fn (Sale $record): bool => $record->invoice()->exists()),
            Action::make('cancel')
                ->label(fn (Sale $record): string => $record->status === SaleStatus::Completed ? 'Refund sale' : 'Cancel order')
                ->icon(Heroicon::OutlinedXCircle)
                ->color('danger')
                ->requiresConfirmation()
                ->modalDescription('All items go back into stock. This cannot be undone.')
                ->authorize('cancel')
                ->action(function (Sale $record, CancelSale $cancelSale): void {
                    $cancelSale->handle($record, auth()->user());

                    Notification::make()->title('Sale cancelled and items restocked')->send();
                }),
        ];
    }

    private function moveTo(Sale $sale, SaleStatus $status): void
    {
        $sale->update(['status' => $status]);

        Notification::make()->title('Order marked as '.$status->getLabel())->success()->send();
    }
}
