<?php

declare(strict_types=1);

use App\Models\Inventory;
use App\Models\Product;
use App\Models\Sale;
use App\Models\StockTransaction;
use Database\Seeders\CatalogSeeder;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Support\Facades\Storage;

it('seeds shop history whose stock ledger matches the inventory on hand', function (): void {
    Storage::fake('public');
    $this->seed(DatabaseSeeder::class);

    $ledgerBalances = StockTransaction::query()
        ->selectRaw('product_id, sum(quantity) as balance')
        ->groupBy('product_id')
        ->pluck('balance', 'product_id');

    expect(Sale::count())->toBeGreaterThan(500)
        ->and(Inventory::pluck('quantity', 'product_id')->all())->toEqual($ledgerBalances->all());
});

it('gives every seeded product a stored photo', function (): void {
    Storage::fake('public');
    $this->seed(CatalogSeeder::class);

    Product::all()->each(function (Product $product): void {
        expect($product->image_path)->toBe(sprintf('products/%s.jpg', $product->slug));
        Storage::disk('public')->assertExists($product->image_path);
    });
});
