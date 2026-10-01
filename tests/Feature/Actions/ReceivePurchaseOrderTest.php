<?php

declare(strict_types=1);

use App\Actions\ReceivePurchaseOrder;
use App\Enums\PurchaseOrderStatus;
use App\Enums\StockTransactionType;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\User;

it('adds received goods to stock and records the latest cost price', function (): void {
    $product = Product::factory()->withStock(4)->create(['cost_price' => 300]);
    $storekeeper = User::factory()->create();
    $purchaseOrder = PurchaseOrder::factory()->approved()->create();
    $purchaseOrder->items()->create(['product_id' => $product->id, 'quantity' => 24, 'unit_cost' => 320]);

    resolve(ReceivePurchaseOrder::class)->handle($purchaseOrder, $storekeeper);

    expect($purchaseOrder->fresh())
        ->status->toBe(PurchaseOrderStatus::Received)
        ->received_by_id->toBe($storekeeper->id)
        ->received_at->not->toBeNull()
        ->and($product->fresh()->cost_price)->toBe('320.00')
        ->and($product->inventory->fresh()->quantity)->toBe(28)
        ->and($purchaseOrder->stockTransactions()->sole())
        ->type->toBe(StockTransactionType::Purchase)
        ->quantity->toBe(24);
});

it('calculates line totals and the order total from its items', function (): void {
    $purchaseOrder = PurchaseOrder::factory()->create();
    $purchaseOrder->items()->create(['product_id' => Product::factory()->create()->id, 'quantity' => 12, 'unit_cost' => 18]);
    $purchaseOrder->items()->create(['product_id' => Product::factory()->create()->id, 'quantity' => 6, 'unit_cost' => 1150.5]);

    $purchaseOrder->recalculateTotal();

    expect($purchaseOrder->items->pluck('line_total')->all())->toBe(['216.00', '6903.00'])
        ->and($purchaseOrder->total)->toBe('7119.00');
});
