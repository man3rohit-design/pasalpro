<?php

declare(strict_types=1);

use App\Actions\RecordStockMovement;
use App\Enums\AlertType;
use App\Enums\StockTransactionType;
use App\Enums\UserRole;
use App\Models\Product;
use App\Models\User;
use Illuminate\Validation\ValidationException;

beforeEach(function (): void {
    $this->recordStockMovement = resolve(RecordStockMovement::class);
});

it('creates an empty inventory record for every new product', function (): void {
    $product = Product::factory()->create();

    expect($product->inventory)
        ->quantity->toBe(0)
        ->reorder_level->toBe(10);
});

it('updates the on hand quantity and records the movement in the ledger', function (): void {
    $product = Product::factory()->withStock(20)->create();
    $storekeeper = User::factory()->create();

    $transaction = $this->recordStockMovement->handle($product, -5, StockTransactionType::Adjustment, performedBy: $storekeeper, note: 'Damaged packet');

    expect($product->inventory->fresh()->quantity)->toBe(15)
        ->and($transaction)
        ->quantity->toBe(-5)
        ->balance_after->toBe(15)
        ->note->toBe('Damaged packet')
        ->performed_by_id->toBe($storekeeper->id);
});

it('rejects a movement that would take stock below zero', function (): void {
    $product = Product::factory()->withStock(2)->create();

    expect(fn () => $this->recordStockMovement->handle($product, -3, StockTransactionType::Sale))
        ->toThrow(ValidationException::class, sprintf('Only 2 of %s left in stock.', $product->name));

    expect($product->inventory->fresh()->quantity)->toBe(2)
        ->and($product->stockTransactions()->count())->toBe(0);
});

it('stamps the restock time only when goods are purchased', function (): void {
    $product = Product::factory()->create();

    $this->recordStockMovement->handle($product, 10, StockTransactionType::Adjustment);
    expect($product->inventory->fresh()->last_restocked_at)->toBeNull();

    $this->recordStockMovement->handle($product, 10, StockTransactionType::Purchase);
    expect($product->inventory->fresh()->last_restocked_at)->not->toBeNull();
});

it('raises a single low stock alert while stock stays at or below the reorder level', function (): void {
    $product = Product::factory()->withStock(15, reorderLevel: 10)->create();

    $this->recordStockMovement->handle($product, -5, StockTransactionType::Sale);
    $this->recordStockMovement->handle($product, -2, StockTransactionType::Sale);

    expect($product->alerts()->open()->get())
        ->toHaveCount(1)
        ->first()->type->toBe(AlertType::LowStock)
        ->first()->quantity_at_alert->toBe(10);
});

it('escalates to an out of stock alert when the last unit is sold', function (): void {
    $product = Product::factory()->withStock(12, reorderLevel: 10)->create();

    $this->recordStockMovement->handle($product, -4, StockTransactionType::Sale);
    $this->recordStockMovement->handle($product, -8, StockTransactionType::Sale);

    expect($product->alerts()->open()->sole()->type)->toBe(AlertType::OutOfStock)
        ->and($product->alerts()->whereNotNull('resolved_at')->sole()->type)->toBe(AlertType::LowStock);
});

it('resolves the open alert once stock is replenished above the reorder level', function (): void {
    $product = Product::factory()->withStock(12, reorderLevel: 10)->create();
    $storekeeper = User::factory()->create();

    $this->recordStockMovement->handle($product, -4, StockTransactionType::Sale);
    $this->recordStockMovement->handle($product, 30, StockTransactionType::Purchase, performedBy: $storekeeper);

    expect($product->alerts()->open()->exists())->toBeFalse()
        ->and($product->alerts()->sole()->resolved_by_id)->toBe($storekeeper->id);
});

it('notifies the stock team when a product runs low', function (): void {
    $storekeeper = User::factory()->role(UserRole::Storekeeper)->create();
    $cashier = User::factory()->role(UserRole::Cashier)->create();
    $product = Product::factory()->withStock(12, reorderLevel: 10)->create(['name' => 'DDC Paneer 200 g']);

    $this->recordStockMovement->handle($product, -3, StockTransactionType::Sale);
    $this->recordStockMovement->handle($product, -1, StockTransactionType::Sale);

    expect($storekeeper->notifications()->sole()->data['title'])->toBe('DDC Paneer 200 g: Low Stock')
        ->and($cashier->notifications()->count())->toBe(0);
});
