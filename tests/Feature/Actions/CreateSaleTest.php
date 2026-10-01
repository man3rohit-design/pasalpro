<?php

declare(strict_types=1);

use App\Actions\CreateSale;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\SaleChannel;
use App\Enums\SaleStatus;
use App\Enums\StockTransactionType;
use App\Enums\UserRole;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Sale;
use App\Models\User;
use Illuminate\Validation\ValidationException;

beforeEach(function (): void {
    $this->createSale = resolve(CreateSale::class);
});

it('completes a counter sale with payment, invoice and stock deduction', function (): void {
    $noodles = Product::factory()->withStock(50)->create(['selling_price' => 25]);
    $oil = Product::factory()->withStock(10)->create(['selling_price' => 365]);
    $cashier = User::factory()->role(UserRole::Cashier)->create();

    $sale = $this->createSale->handle(
        [$noodles->id => 4, $oil->id => 1],
        SaleChannel::Counter,
        PaymentMethod::Fonepay,
        soldBy: $cashier,
        discount: 15,
        paymentReference: 'FP123456',
    );

    expect($sale->fresh())
        ->status->toBe(SaleStatus::Completed)
        ->payment_status->toBe(PaymentStatus::Paid)
        ->subtotal->toBe('465.00')
        ->discount->toBe('15.00')
        ->total->toBe('450.00')
        ->completed_at->not->toBeNull()
        ->and($sale->items)->toHaveCount(2)
        ->and($sale->items->firstWhere('product_id', $oil->id)->unit_cost)->toBe($oil->cost_price)
        ->and($sale->payments()->sole())
        ->amount->toBe('450.00')
        ->method->toBe(PaymentMethod::Fonepay)
        ->transaction_reference->toBe('FP123456')
        ->received_by_id->toBe($cashier->id)
        ->and($sale->invoice->number)->toStartWith('INV-')
        ->and($noodles->inventory->fresh()->quantity)->toBe(46)
        ->and($oil->inventory->fresh()->quantity)->toBe(9)
        ->and($sale->stockTransactions()->where('type', StockTransactionType::Sale)->count())->toBe(2);
});

it('captures the product name and price at the time of sale', function (): void {
    $product = Product::factory()->withStock(5)->create(['name' => 'Tokla Tea 500 g', 'selling_price' => 330]);

    $sale = $this->createSale->handle([$product->id => 2], SaleChannel::Counter, PaymentMethod::Cash);
    $product->update(['name' => 'Tokla Gold Tea 500 g', 'selling_price' => 350]);

    expect($sale->items()->sole())
        ->product_name->toBe('Tokla Tea 500 g')
        ->unit_price->toBe('330.00')
        ->line_total->toBe('660.00');
});

it('never discounts more than the subtotal', function (): void {
    $product = Product::factory()->withStock(5)->create(['selling_price' => 40]);

    $sale = $this->createSale->handle([$product->id => 1], SaleChannel::Counter, PaymentMethod::Cash, discount: 100);

    expect($sale->total)->toBe('0.00');
});

it('keeps an online order pending and unpaid until it is delivered', function (): void {
    $product = Product::factory()->withStock(8)->create();
    $customer = Customer::factory()->withAccount()->create();

    $sale = $this->createSale->handle(
        [$product->id => 3],
        SaleChannel::Online,
        PaymentMethod::CashOnDelivery,
        customer: $customer,
        details: ['delivery_name' => $customer->name, 'delivery_phone' => '9841000000', 'delivery_address' => 'Chabahil, Kathmandu'],
    );

    expect($sale)
        ->status->toBe(SaleStatus::Pending)
        ->payment_status->toBe(PaymentStatus::Unpaid)
        ->delivery_address->toBe('Chabahil, Kathmandu')
        ->and($sale->payments()->exists())->toBeFalse()
        ->and($sale->invoice()->exists())->toBeFalse()
        ->and($product->inventory->fresh()->quantity)->toBe(5);
});

it('rejects an empty basket', function (): void {
    $this->createSale->handle([], SaleChannel::Counter, PaymentMethod::Cash);
})->throws(ValidationException::class, 'Add at least one product to the sale.');

it('rejects products that have been deactivated', function (): void {
    $product = Product::factory()->inactive()->withStock(5)->create();

    $this->createSale->handle([$product->id => 1], SaleChannel::Counter, PaymentMethod::Cash);
})->throws(ValidationException::class, 'One or more products are no longer available.');

it('rolls back the whole sale when one product is short of stock', function (): void {
    $inStock = Product::factory()->withStock(10)->create();
    $shortStock = Product::factory()->withStock(1)->create();

    expect(fn () => $this->createSale->handle([$inStock->id => 2, $shortStock->id => 2], SaleChannel::Counter, PaymentMethod::Cash))
        ->toThrow(ValidationException::class);

    expect($inStock->inventory->fresh()->quantity)->toBe(10)
        ->and(Sale::count())->toBe(0);
});
