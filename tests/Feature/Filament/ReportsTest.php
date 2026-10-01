<?php

declare(strict_types=1);

use App\Actions\CancelSale;
use App\Actions\CreateSale;
use App\Actions\ReceivePurchaseOrder;
use App\Enums\PaymentMethod;
use App\Enums\SaleChannel;
use App\Enums\UserRole;
use App\Filament\Pages\Reports\InventoryReport;
use App\Filament\Pages\Reports\ProductSalesReport;
use App\Filament\Pages\Reports\SalesReport;
use App\Filament\Pages\Reports\SupplierReport;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\User;
use Livewire\Livewire;

beforeEach(function (): void {
    $this->actingAs(User::factory()->role(UserRole::Manager)->create());
});

it('totals completed sales per day with cost and profit', function (): void {
    $tea = Product::factory()->withStock(20)->create(['cost_price' => 280, 'selling_price' => 330]);
    $createSale = resolve(CreateSale::class);
    $createSale->handle([$tea->id => 2], SaleChannel::Counter, PaymentMethod::Cash, discount: 10);
    $createSale->handle([$tea->id => 1], SaleChannel::Counter, PaymentMethod::Fonepay);

    resolve(CancelSale::class)->handle($createSale->handle([$tea->id => 5], SaleChannel::Counter, PaymentMethod::Cash));

    Livewire::test(SalesReport::class)
        ->assertSee(today()->format('D, M j Y'))
        ->assertSee('Rs. 980.00')
        ->assertSee('Rs. 140.00')
        ->assertSee('14.3%');
});

it('groups sales by month when asked', function (): void {
    $product = Product::factory()->withStock(20)->create();
    resolve(CreateSale::class)->handle([$product->id => 1], SaleChannel::Counter, PaymentMethod::Cash);

    Livewire::test(SalesReport::class)
        ->filterTable('group_by', 'month')
        ->assertSee(today()->format('F Y'));
});

it('exports the sales report as a CSV file', function (): void {
    $product = Product::factory()->withStock(20)->create(['selling_price' => 100]);
    resolve(CreateSale::class)->handle([$product->id => 3], SaleChannel::Counter, PaymentMethod::Cash);

    Livewire::test(SalesReport::class)
        ->callAction('exportCsv')
        ->assertFileDownloaded('sales-report-'.today()->format('Y-m-d').'.csv');
});

it('ranks products by revenue', function (): void {
    $rice = Product::factory()->withStock(20)->create(['name' => 'Jeera Masino Rice', 'selling_price' => 850]);
    $noodles = Product::factory()->withStock(50)->create(['name' => 'Wai Wai Noodles', 'selling_price' => 25]);
    resolve(CreateSale::class)->handle([$rice->id => 1, $noodles->id => 10], SaleChannel::Counter, PaymentMethod::Cash);

    Livewire::test(ProductSalesReport::class)
        ->assertSeeInOrder(['Jeera Masino Rice', 'Wai Wai Noodles']);
});

it('values stock and flags low stock products', function (): void {
    Product::factory()->withStock(4, reorderLevel: 10)->create(['name' => 'DDC Paneer 200 g', 'cost_price' => 160]);
    Product::factory()->withStock(40, reorderLevel: 10)->create(['name' => 'Tokla Tea 500 g']);

    Livewire::test(InventoryReport::class)
        ->filterTable('status', 'Low stock')
        ->assertSee('DDC Paneer 200 g')
        ->assertSee('Rs. 640.00')
        ->assertDontSee('Tokla Tea 500 g');
});

it('sums purchases received from each supplier in the period', function (): void {
    $purchaseOrder = PurchaseOrder::factory()->approved()->create();
    $purchaseOrder->items()->create(['product_id' => Product::factory()->create()->id, 'quantity' => 10, 'unit_cost' => 120]);
    $purchaseOrder->recalculateTotal();
    resolve(ReceivePurchaseOrder::class)->handle($purchaseOrder);

    Livewire::test(SupplierReport::class)
        ->assertSee($purchaseOrder->supplier->name)
        ->assertSee('Rs. 1,200.00');
});

it('keeps reports away from cashiers and storekeepers', function (UserRole $role): void {
    $this->actingAs(User::factory()->role($role)->create())
        ->get(SalesReport::getUrl())
        ->assertForbidden();
})->with([UserRole::Cashier, UserRole::Storekeeper]);
