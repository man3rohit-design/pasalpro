<?php

declare(strict_types=1);

use App\Actions\CompleteSale;
use App\Actions\CreateSale;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\SaleChannel;
use App\Enums\SaleStatus;
use App\Models\Customer;
use App\Models\Product;
use App\Models\User;

it('collects payment and issues an invoice when an online order is delivered', function (): void {
    $product = Product::factory()->withStock(10)->create(['selling_price' => 120]);
    $manager = User::factory()->create();
    $sale = resolve(CreateSale::class)->handle([$product->id => 2], SaleChannel::Online, PaymentMethod::CashOnDelivery, customer: Customer::factory()->create());

    resolve(CompleteSale::class)->handle($sale, $manager);

    expect($sale->fresh())
        ->status->toBe(SaleStatus::Completed)
        ->payment_status->toBe(PaymentStatus::Paid)
        ->and($sale->payments()->sole())
        ->amount->toBe('240.00')
        ->method->toBe(PaymentMethod::CashOnDelivery)
        ->and($sale->invoice->issued_by_id)->toBe($manager->id);
});

it('numbers invoices sequentially', function (): void {
    $product = Product::factory()->withStock(10)->create();

    $first = resolve(CreateSale::class)->handle([$product->id => 1], SaleChannel::Counter, PaymentMethod::Cash);
    $second = resolve(CreateSale::class)->handle([$product->id => 1], SaleChannel::Counter, PaymentMethod::Cash);

    expect($first->invoice->number)->toBe('INV-000001')
        ->and($second->invoice->number)->toBe('INV-000002');
});
