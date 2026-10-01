<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\PaymentStatus;
use App\Enums\SaleStatus;
use App\Models\Sale;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class CompleteSale
{
    public function handle(Sale $sale, ?User $completedBy = null, ?string $paymentReference = null): Sale
    {
        return DB::transaction(function () use ($sale, $completedBy, $paymentReference): Sale {
            $sale->payments()->create([
                'method'                => $sale->payment_method,
                'amount'                => $sale->total,
                'transaction_reference' => $paymentReference,
                'paid_at'               => now(),
                'received_by_id'        => $completedBy?->id,
            ]);

            $sale->invoice()->create(['issued_by_id' => $completedBy?->id]);

            $sale->update([
                'status'         => SaleStatus::Completed,
                'payment_status' => PaymentStatus::Paid,
                'completed_at'   => now(),
            ]);

            return $sale;
        });
    }
}
