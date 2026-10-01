<?php

declare(strict_types=1);

use App\Enums\PurchaseOrderStatus;
use App\Enums\UserRole;
use App\Filament\Resources\PurchaseOrders\Pages\CreatePurchaseOrder;
use App\Filament\Resources\PurchaseOrders\Pages\ViewPurchaseOrder;
use App\Filament\Resources\PurchaseOrders\PurchaseOrderResource;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\Supplier;
use App\Models\User;
use Filament\Forms\Components\Repeater;

it('raises a purchase order with a calculated total', function (): void {
    $storekeeper = User::factory()->role(UserRole::Storekeeper)->create();
    $supplier = Supplier::factory()->create();
    $tea = Product::factory()->create();
    $oil = Product::factory()->create();
    $this->actingAs($storekeeper);
    Repeater::fake();

    Livewire::test(CreatePurchaseOrder::class)
        ->fillForm([
            'supplier_id' => $supplier->id,
            'ordered_on'  => today(),
            'items'       => [
                ['product_id' => $tea->id, 'quantity' => 24, 'unit_cost' => 280],
                ['product_id' => $oil->id, 'quantity' => 12, 'unit_cost' => 320],
            ],
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    expect(PurchaseOrder::sole())
        ->status->toBe(PurchaseOrderStatus::Pending)
        ->created_by_id->toBe($storekeeper->id)
        ->total->toBe('10560.00')
        ->reference->toStartWith('PO-');
});

it('lets a manager approve a pending order', function (): void {
    $manager = User::factory()->role(UserRole::Manager)->create();
    $purchaseOrder = PurchaseOrder::factory()->create();
    $this->actingAs($manager);

    Livewire::test(ViewPurchaseOrder::class, ['record' => $purchaseOrder->id])
        ->callAction('approve')
        ->assertNotified('Purchase order approved');

    expect($purchaseOrder->fresh())
        ->status->toBe(PurchaseOrderStatus::Approved)
        ->approved_by_id->toBe($manager->id);
});

it('does not let a storekeeper approve their own order', function (): void {
    $this->actingAs(User::factory()->role(UserRole::Storekeeper)->create());

    Livewire::test(ViewPurchaseOrder::class, ['record' => PurchaseOrder::factory()->create()->id])
        ->assertActionHidden('approve');
});

it('lets the storekeeper receive an approved order into stock', function (): void {
    $product = Product::factory()->withStock(5)->create();
    $purchaseOrder = PurchaseOrder::factory()->approved()->create();
    $purchaseOrder->items()->create(['product_id' => $product->id, 'quantity' => 36, 'unit_cost' => 40]);
    $this->actingAs(User::factory()->role(UserRole::Storekeeper)->create());

    Livewire::test(ViewPurchaseOrder::class, ['record' => $purchaseOrder->id])
        ->assertActionHidden('approve')
        ->callAction('receive')
        ->assertNotified('Stock received');

    expect($purchaseOrder->fresh()->status)->toBe(PurchaseOrderStatus::Received)
        ->and($product->inventory->fresh()->quantity)->toBe(41);
});

it('only allows editing while an order is pending', function (): void {
    $this->actingAs(User::factory()->role(UserRole::Manager)->create());
    $approved = PurchaseOrder::factory()->approved()->create();

    $this->get(PurchaseOrderResource::getUrl('edit', ['record' => $approved]))
        ->assertForbidden();
});
