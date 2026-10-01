<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\PurchaseOrderStatus;
use App\Enums\StockTransactionType;
use App\Models\PurchaseOrder;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class ReceivePurchaseOrder
{
    public function __construct(private readonly RecordStockMovement $recordStockMovement) {}

    public function handle(PurchaseOrder $purchaseOrder, ?User $receivedBy = null): PurchaseOrder
    {
        return DB::transaction(function () use ($purchaseOrder, $receivedBy): PurchaseOrder {
            foreach ($purchaseOrder->items()->with('product')->get() as $item) {
                $this->recordStockMovement->handle($item->product, $item->quantity, StockTransactionType::Purchase, $purchaseOrder, $receivedBy);
                $item->product->update(['cost_price' => $item->unit_cost]);
            }

            $purchaseOrder->update([
                'status'         => PurchaseOrderStatus::Received,
                'received_by_id' => $receivedBy?->id,
                'received_at'    => now(),
            ]);

            return $purchaseOrder;
        });
    }
}
