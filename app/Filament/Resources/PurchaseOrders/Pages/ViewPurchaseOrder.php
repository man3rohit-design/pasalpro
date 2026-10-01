<?php

declare(strict_types=1);

namespace App\Filament\Resources\PurchaseOrders\Pages;

use App\Actions\ReceivePurchaseOrder;
use App\Enums\PurchaseOrderStatus;
use App\Filament\Resources\PurchaseOrders\PurchaseOrderResource;
use App\Models\PurchaseOrder;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Filament\Support\Icons\Heroicon;

class ViewPurchaseOrder extends ViewRecord
{
    protected static string $resource = PurchaseOrderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('approve')
                ->icon(Heroicon::OutlinedCheckBadge)
                ->requiresConfirmation()
                ->modalDescription('The supplier can be asked to deliver once this order is approved.')
                ->authorize('approve')
                ->action(function (PurchaseOrder $record): void {
                    $record->update([
                        'status'         => PurchaseOrderStatus::Approved,
                        'approved_by_id' => auth()->id(),
                        'approved_at'    => now(),
                    ]);

                    Notification::make()->title('Purchase order approved')->success()->send();
                }),
            Action::make('receive')
                ->label('Receive stock')
                ->icon(Heroicon::OutlinedInboxArrowDown)
                ->color('success')
                ->requiresConfirmation()
                ->modalDescription('Every item on this order will be added to stock at the listed quantity.')
                ->authorize('receive')
                ->action(function (PurchaseOrder $record, ReceivePurchaseOrder $receivePurchaseOrder): void {
                    $receivePurchaseOrder->handle($record, auth()->user());

                    Notification::make()->title('Stock received')->success()->send();
                }),
            Action::make('cancel')
                ->label('Cancel order')
                ->icon(Heroicon::OutlinedXCircle)
                ->color('danger')
                ->requiresConfirmation()
                ->authorize('cancel')
                ->action(function (PurchaseOrder $record): void {
                    $record->update(['status' => PurchaseOrderStatus::Cancelled]);

                    Notification::make()->title('Purchase order cancelled')->send();
                }),
            EditAction::make(),
        ];
    }
}
