<?php

declare(strict_types=1);

use App\Livewire\ProductCatalog;
use App\Models\Category;
use App\Models\Product;
use Livewire\Livewire;

it('shows categories and featured products on the home page', function (): void {
    $category = Category::factory()->create(['name' => 'Instant Noodles']);
    Product::factory()->for($category)->withStock(20)->create(['name' => 'Wai Wai Chicken Noodles 75 g', 'is_featured' => true]);

    $this->get(route('home'))
        ->assertOk()
        ->assertSee('Instant Noodles')
        ->assertSee('Wai Wai Chicken Noodles 75 g');
});

it('filters the catalog by category and search term', function (): void {
    $noodles = Category::factory()->create(['slug' => 'instant-noodles']);
    $dairy = Category::factory()->create(['slug' => 'dairy-eggs']);
    Product::factory()->for($noodles)->withStock(10)->create(['name' => 'Rara Noodles 70 g']);
    Product::factory()->for($noodles)->withStock(10)->create(['name' => 'Wai Wai Veg Noodles 75 g']);
    Product::factory()->for($dairy)->withStock(10)->create(['name' => 'DDC Paneer 200 g']);

    Livewire::test(ProductCatalog::class)
        ->set('category', 'instant-noodles')
        ->assertSee('Rara Noodles 70 g')
        ->assertDontSee('DDC Paneer 200 g')
        ->set('search', 'wai')
        ->assertSee('Wai Wai Veg Noodles 75 g')
        ->assertDontSee('Rara Noodles 70 g');
});

it('hides inactive products from the shop', function (): void {
    $product = Product::factory()->inactive()->create(['name' => 'Discontinued Biscuit']);

    $this->get(route('products.show', $product))->assertNotFound();

    Livewire::test(ProductCatalog::class)->assertDontSee('Discontinued Biscuit');
});

it('shows a product with its price and stock status', function (): void {
    $product = Product::factory()->withStock(4, reorderLevel: 10)->create(['name' => 'DDC Pure Ghee 1 L', 'selling_price' => 1300]);

    $this->get(route('products.show', $product))
        ->assertOk()
        ->assertSee('DDC Pure Ghee 1 L')
        ->assertSee('Rs. 1,300.00')
        ->assertSee('only 4 left');
});

it('shows a single search box on the catalog page', function (): void {
    $this->get(route('products.index'))
        ->assertOk()
        ->assertSee('Search noodles, rice, tea...')
        ->assertDontSee('name="search"', escape: false);

    $this->get(route('home'))->assertSee('name="search"', escape: false);
});

it('names the page after the selected category', function (): void {
    Category::factory()->create(['name' => 'Dairy & Eggs', 'slug' => 'dairy-eggs']);

    Livewire::test(ProductCatalog::class)
        ->set('category', 'dairy-eggs')
        ->assertSee('Dairy &amp; Eggs', escape: false);
});
