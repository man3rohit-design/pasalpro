<?php

declare(strict_types=1);

use App\Enums\UserRole;
use App\Filament\Resources\Alerts\AlertResource;
use App\Filament\Resources\Categories\CategoryResource;
use App\Filament\Resources\Customers\CustomerResource;
use App\Filament\Resources\LoginHistories\LoginHistoryResource;
use App\Filament\Resources\Products\ProductResource;
use App\Filament\Resources\PurchaseOrders\PurchaseOrderResource;
use App\Filament\Resources\Sales\SaleResource;
use App\Filament\Resources\StockTransactions\StockTransactionResource;
use App\Filament\Resources\Suppliers\SupplierResource;
use App\Filament\Resources\Users\UserResource;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\Sale;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Support\Facades\Storage;

it('renders every admin page with seeded data for an administrator', function (): void {
    Storage::fake('public');
    $this->seed(DatabaseSeeder::class);
    $this->actingAs(User::where('role', UserRole::Admin)->first());

    $sale = Sale::first();
    $purchaseOrder = PurchaseOrder::first();
    $product = Product::first();

    $urls = [
        '/admin',
        ProductResource::getUrl(),
        ProductResource::getUrl('create'),
        ProductResource::getUrl('view', ['record' => $product]),
        ProductResource::getUrl('edit', ['record' => $product]),
        CategoryResource::getUrl(),
        StockTransactionResource::getUrl(),
        AlertResource::getUrl(),
        PurchaseOrderResource::getUrl(),
        PurchaseOrderResource::getUrl('create'),
        PurchaseOrderResource::getUrl('view', ['record' => $purchaseOrder]),
        SupplierResource::getUrl(),
        SaleResource::getUrl(),
        SaleResource::getUrl('view', ['record' => $sale]),
        CustomerResource::getUrl(),
        UserResource::getUrl(),
        LoginHistoryResource::getUrl(),
        route('sales.invoice', $sale),
    ];

    foreach ($urls as $url) {
        $this->get($url)->assertOk();
    }
});

it('hides management pages from roles that do not need them', function (UserRole $role, string $resource): void {
    $this->actingAs(User::factory()->role($role)->create())
        ->get($resource::getUrl())
        ->assertForbidden();
})->with([
    'cashier on purchase orders'   => [UserRole::Cashier, PurchaseOrderResource::class],
    'cashier on suppliers'         => [UserRole::Cashier, SupplierResource::class],
    'cashier on stock movements'   => [UserRole::Cashier, StockTransactionResource::class],
    'manager on users'             => [UserRole::Manager, UserResource::class],
    'storekeeper on login history' => [UserRole::Storekeeper, LoginHistoryResource::class],
]);
