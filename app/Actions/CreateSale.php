<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\PaymentMethod;
use App\Enums\SaleChannel;
use App\Enums\SaleStatus;
use App\Enums\StockTransactionType;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Sale;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CreateSale
{
    public function __construct(
        private readonly RecordStockMovement $recordStockMovement,
        private readonly CompleteSale $completeSale,
    ) {}

    /**
     * @param array<int, int>                                                                                        $quantities keyed by product id
     * @param array{delivery_name?: string, delivery_phone?: string, delivery_address?: string, notes?: string|null} $details
     */
    public function handle(
        array $quantities,
        SaleChannel $channel,
        PaymentMethod $paymentMethod,
        ?Customer $customer = null,
        ?User $soldBy = null,
        float $discount = 0,
        array $details = [],
        ?string $paymentReference = null,
    ): Sale {
        $quantities = array_filter($quantities, fn (int $quantity): bool => $quantity > 0);

        throw_if($quantities === [], ValidationException::withMessages([
            'items' => 'Add at least one product to the sale.',
        ]));

        return DB::transaction(function () use ($quantities, $channel, $paymentMethod, $customer, $soldBy, $discount, $details, $paymentReference): Sale {
            $products = Product::active()->findMany(array_keys($quantities))->keyBy('id');

            throw_if($products->count() !== count($quantities), ValidationException::withMessages([
                'items' => 'One or more products are no longer available.',
            ]));

            $subtotal = $products->sum(fn (Product $product): float => $quantities[$product->id] * (float) $product->selling_price);
            $discount = min(max($discount, 0), $subtotal);

            $sale = Sale::create([
                ...$details,
                'channel'        => $channel,
                'status'         => SaleStatus::Pending,
                'customer_id'    => $customer?->id,
                'sold_by_id'     => $soldBy?->id,
                'subtotal'       => $subtotal,
                'discount'       => $discount,
                'total'          => round($subtotal - $discount, 2),
                'payment_method' => $paymentMethod,
            ]);

            foreach ($products as $product) {
                $sale->items()->create([
                    'product_id'   => $product->id,
                    'product_name' => $product->name,
                    'quantity'     => $quantities[$product->id],
                    'unit_price'   => $product->selling_price,
                    'unit_cost'    => $product->cost_price,
                ]);

                $this->recordStockMovement->handle($product, -$quantities[$product->id], StockTransactionType::Sale, $sale, $soldBy);
            }

            if ($channel === SaleChannel::Counter) {
                $this->completeSale->handle($sale, $soldBy, $paymentReference);
            }

            return $sale;
        });
    }
}
