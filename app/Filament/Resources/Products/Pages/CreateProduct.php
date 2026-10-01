<?php

declare(strict_types=1);

namespace App\Filament\Resources\Products\Pages;

use App\Actions\RecordStockMovement;
use App\Enums\StockTransactionType;
use App\Filament\Resources\Products\ProductResource;
use App\Models\Product;
use Filament\Resources\Pages\CreateRecord;

class CreateProduct extends CreateRecord
{
    protected static string $resource = ProductResource::class;

    protected function afterCreate(): void
    {
        $openingStock = (int) ($this->data['opening_stock'] ?? 0);

        if ($openingStock > 0 && $this->record instanceof Product) {
            resolve(RecordStockMovement::class)->handle(
                $this->record,
                $openingStock,
                StockTransactionType::OpeningStock,
                performedBy: auth()->user(),
                note: 'Opening stock count',
            );
        }
    }
}
