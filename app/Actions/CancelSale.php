<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\PaymentStatus;
use App\Enums\SaleStatus;
use App\Enums\StockTransactionType;
use App\Models\Sale;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class CancelSale
{
    public function __construct(private readonly RecordStockMovement $recordStockMovement) {}

    public function handle(Sale $sale, ?User $cancelledBy = null): Sale
    {
        return DB::transaction(function () use ($sale, $cancelledBy): Sale {
            foreach ($sale->items()->with('product')->get() as $item) {
                $this->recordStockMovement->handle($item->product, $item->quantity, StockTransactionType::SaleCancelled, $sale, $cancelledBy);
            }

            $sale->update([
                'status'         => SaleStatus::Cancelled,
                'payment_status' => $sale->payment_status === PaymentStatus::Paid ? PaymentStatus::Refunded : $sale->payment_status,
                'cancelled_at'   => now(),
            ]);

            return $sale;
        });
    }
}
