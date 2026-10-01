<?php

declare(strict_types=1);

namespace App\Filament\Resources\Products\Pages;

use App\Filament\Resources\Products\Actions\AdjustStockAction;
use App\Filament\Resources\Products\ProductResource;
use App\Models\Product;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;
use Filament\Support\Icons\Heroicon;

class ViewProduct extends ViewRecord
{
    protected static string $resource = ProductResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('printBarcodes')
                ->label('Print barcodes')
                ->icon(Heroicon::OutlinedQrCode)
                ->color('gray')
                ->authorize('update')
                ->url(fn (Product $record): string => route('products.barcode-labels', $record), shouldOpenInNewTab: true),
            AdjustStockAction::make(),
            EditAction::make(),
        ];
    }
}
