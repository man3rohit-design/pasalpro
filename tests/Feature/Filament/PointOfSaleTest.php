<?php

declare(strict_types=1);

use App\Enums\PaymentMethod;
use App\Enums\SaleStatus;
use App\Enums\UserRole;
use App\Filament\Pages\PointOfSale;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Sale;
use App\Models\User;
use Livewire\Livewire;

beforeEach(function (): void {
    $this->cashier = User::factory()->role(UserRole::Cashier)->create();
    $this->actingAs($this->cashier);
});

it('adds a scanned product to the cart and increases the quantity on repeat scans', function (): void {
    $noodles = Product::factory()->withStock(30)->create(['barcode' => '4290000000017', 'selling_price' => 25]);

    Livewire::test(PointOfSale::class)
        ->set('barcode', '4290000000017')
        ->call('addScannedBarcode')
        ->set('barcode', '4290000000017')
        ->call('addScannedBarcode')
        ->assertSet('cart', [$noodles->id => 2])
        ->assertSet('barcode', '')
        ->assertSee('Rs. 50.00');
});

it('warns when the scanned barcode is unknown', function (): void {
    Livewire::test(PointOfSale::class)
        ->set('barcode', '0000000000000')
        ->call('addScannedBarcode')
        ->assertNotified('No product matches that barcode')
        ->assertSet('cart', []);
});

it('adds products picked from the search box', function (): void {
    $tea = Product::factory()->withStock(10)->create(['name' => 'Tokla Tea 500 g']);

    Livewire::test(PointOfSale::class)
        ->fillForm(['product_id' => $tea->id], 'searchForm')
        ->assertSet('cart', [$tea->id => 1])
        ->assertSet('search.product_id', null);
});

it('does not let the cart exceed the stock on hand', function (): void {
    $product = Product::factory()->withStock(2)->create();

    Livewire::test(PointOfSale::class)
        ->call('addToCart', $product->id)
        ->call('addToCart', $product->id)
        ->call('addToCart', $product->id)
        ->assertNotified()
        ->assertSet('cart', [$product->id => 2]);
});

it('completes a cash sale with change and clears the cart', function (): void {
    $oil = Product::factory()->withStock(10)->create(['selling_price' => 365]);
    $customer = Customer::factory()->create();

    Livewire::test(PointOfSale::class)
        ->call('addToCart', $oil->id)
        ->call('updateQuantity', $oil->id, 2)
        ->fillForm([
            'customer_id'     => $customer->id,
            'payment_method'  => PaymentMethod::Cash->value,
            'discount'        => 30,
            'amount_tendered' => 1000,
        ], 'checkoutForm')
        ->assertSee('Change due')
        ->assertSee('Rs. 300.00')
        ->call('completeSale')
        ->assertNotified()
        ->assertSet('cart', []);

    expect(Sale::sole())
        ->status->toBe(SaleStatus::Completed)
        ->total->toBe('700.00')
        ->customer_id->toBe($customer->id)
        ->sold_by_id->toBe($this->cashier->id)
        ->and($oil->inventory->fresh()->quantity)->toBe(8);
});

it('requires a transaction ID for digital wallet payments', function (): void {
    $product = Product::factory()->withStock(10)->create();

    Livewire::test(PointOfSale::class)
        ->call('addToCart', $product->id)
        ->fillForm(['payment_method' => PaymentMethod::Esewa->value, 'payment_reference' => ''], 'checkoutForm')
        ->call('completeSale')
        ->assertHasFormErrors(['payment_reference' => 'required'], 'checkoutForm');

    expect(Sale::count())->toBe(0);
});

it('refuses a cash sale when less cash is received than the total', function (): void {
    $product = Product::factory()->withStock(10)->create(['selling_price' => 500]);

    Livewire::test(PointOfSale::class)
        ->call('addToCart', $product->id)
        ->fillForm(['payment_method' => PaymentMethod::Cash->value, 'amount_tendered' => 400], 'checkoutForm')
        ->call('completeSale')
        ->assertNotified('Cash received is less than the total');

    expect(Sale::count())->toBe(0);
});

it('is not available to storekeepers', function (): void {
    $this->actingAs(User::factory()->role(UserRole::Storekeeper)->create())
        ->get(PointOfSale::getUrl())
        ->assertForbidden();
});
