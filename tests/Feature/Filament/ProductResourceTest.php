<?php

declare(strict_types=1);

use App\Enums\ProductUnit;
use App\Enums\StockTransactionType;
use App\Enums\UserRole;
use App\Filament\Resources\Products\Pages\CreateProduct;
use App\Filament\Resources\Products\Pages\ListProducts;
use App\Filament\Resources\Products\Pages\ViewProduct;
use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Filament\Actions\Testing\TestAction;

beforeEach(function (): void {
    $this->storekeeper = User::factory()->role(UserRole::Storekeeper)->create();
    $this->actingAs($this->storekeeper);
});

it('creates a product with its reorder level and opening stock', function (): void {
    $category = Category::factory()->create();

    Livewire::test(CreateProduct::class)
        ->fillForm([
            'name'          => 'Wai Wai Chicken Noodles 75 g',
            'slug'          => 'wai-wai-chicken-noodles-75-g',
            'category_id'   => $category->id,
            'sku'           => 'NOO-0001',
            'barcode'       => '4291234567891',
            'unit'          => ProductUnit::Packet,
            'cost_price'    => 18,
            'selling_price' => 25,
            'opening_stock' => 120,
            'inventory'     => ['reorder_level' => 24],
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $product = Product::where('sku', 'NOO-0001')->sole();

    expect($product->inventory)
        ->quantity->toBe(120)
        ->reorder_level->toBe(24)
        ->and($product->stockTransactions()->sole())
        ->type->toBe(StockTransactionType::OpeningStock)
        ->performed_by_id->toBe($this->storekeeper->id);
});

it('rejects a selling price below the cost price', function (): void {
    Livewire::test(CreateProduct::class)
        ->fillForm([
            'cost_price'    => 300,
            'selling_price' => 250,
        ])
        ->call('create')
        ->assertHasFormErrors(['selling_price' => 'gte']);
});

it('generates a valid EAN-13 barcode by default', function (): void {
    $barcode = Livewire::test(CreateProduct::class)->get('data.barcode');

    expect($barcode)->toHaveLength(13)->toStartWith('429')
        ->and((int) substr((string) $barcode, -1))->toBe(Product::ean13CheckDigit(substr((string) $barcode, 0, 12)));
});

it('finds products by barcode', function (): void {
    $noodles = Product::factory()->create(['barcode' => '4290000000017']);
    $oil = Product::factory()->create();

    Livewire::test(ListProducts::class)
        ->searchTable('4290000000017')
        ->assertCanSeeTableRecords([$noodles])
        ->assertCanNotSeeTableRecords([$oil]);
});

it('filters the list down to low stock products', function (): void {
    $lowStock = Product::factory()->withStock(3, reorderLevel: 10)->create();
    $healthy = Product::factory()->withStock(50, reorderLevel: 10)->create();

    Livewire::test(ListProducts::class)
        ->filterTable('low_stock')
        ->assertCanSeeTableRecords([$lowStock])
        ->assertCanNotSeeTableRecords([$healthy]);
});

it('adjusts stock with a reason from the product list', function (): void {
    $product = Product::factory()->withStock(20)->create();

    Livewire::test(ListProducts::class)
        ->callAction(TestAction::make('adjustStock')->table($product), [
            'quantity' => -3,
            'note'     => 'Damaged in storage',
        ])
        ->assertHasNoFormErrors()
        ->assertNotified('Stock updated');

    expect($product->inventory->fresh()->quantity)->toBe(17)
        ->and($product->stockTransactions()->sole()->note)->toBe('Damaged in storage');
});

it('does not let a cashier adjust stock', function (): void {
    $product = Product::factory()->withStock(20)->create();
    $this->actingAs(User::factory()->role(UserRole::Cashier)->create());

    Livewire::test(ViewProduct::class, ['record' => $product->id])
        ->assertActionHidden('adjustStock');
});

it('prints a sheet of barcode labels for a product', function (): void {
    $product = Product::factory()->create(['name' => 'Tokla Tea 500 g', 'barcode' => '4291234567891']);

    $this->get(route('products.barcode-labels', ['product' => $product, 'copies' => 6]))
        ->assertOk()
        ->assertSee('Tokla Tea 500 g')
        ->assertSee('aria-label="Barcode 4291234567891"', escape: false);
});

it('does not print barcode labels for cashiers', function (): void {
    $this->actingAs(User::factory()->role(UserRole::Cashier)->create())
        ->get(route('products.barcode-labels', Product::factory()->create()))
        ->assertForbidden();
});
