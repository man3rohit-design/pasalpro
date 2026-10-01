<?php

declare(strict_types=1);

use App\Http\Controllers\PrintBarcodeLabelsController;
use App\Http\Controllers\ShowInvoiceController;
use App\Livewire\Checkout;
use App\Livewire\Home;
use App\Livewire\ProductCatalog;
use App\Livewire\ShoppingCart;
use App\Livewire\ShowProduct;
use Illuminate\Support\Facades\Route;

Route::livewire('/', Home::class)->name('home');
Route::livewire('/products', ProductCatalog::class)->name('products.index');
Route::livewire('/products/{product:slug}', ShowProduct::class)->name('products.show');
Route::livewire('/cart', ShoppingCart::class)->name('cart');

Route::middleware('auth')->group(function (): void {
    Route::livewire('/checkout', Checkout::class)->name('checkout');

    Route::get('/sales/{sale}/invoice', ShowInvoiceController::class)->name('sales.invoice');
    Route::get('/products/{product}/barcode-labels', PrintBarcodeLabelsController::class)->name('products.barcode-labels');
});
