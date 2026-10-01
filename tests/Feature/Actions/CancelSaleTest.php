<?php

declare(strict_types=1);

use App\Actions\CancelSale;
use App\Actions\CreateSale;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\SaleChannel;
use App\Enums\SaleStatus;
use App\Enums\StockTransactionType;
use App\Models\Customer;
use App\Models\Product;
use App\Models\User;

it('returns the items to stock and marks an unpaid order as cancelled', function (): void {
    $product = Product::factory()->withStock(10)->create();
    $manager = User::factory()->create();
    $sale = resolve(CreateSale::class)->handle([$product->id => 4], SaleChannel::Online, PaymentMethod::CashOnDelivery, customer: Customer::factory()->create());

    resolve(CancelSale::class)->handle($sale, $manager);

    expect($sale->fresh())
        ->status->toBe(SaleStatus::Cancelled)
        ->payment_status->toBe(PaymentStatus::Unpaid)
        ->cancelled_at->not->toBeNull()
        ->and($product->inventory->fresh()->quantity)->toBe(10)
        ->and($sale->stockTransactions()->where('type', StockTransactionType::SaleCancelled)->sole())
        ->quantity->toBe(4)
        ->performed_by_id->toBe($manager->id);
});

it('marks a paid sale as refunded when it is cancelled', function (): void {
    $product = Product::factory()->withStock(10)->create();
    $sale = resolve(CreateSale::class)->handle([$product->id => 1], SaleChannel::Counter, PaymentMethod::Cash);

    resolve(CancelSale::class)->handle($sale);

    expect($sale->fresh()->payment_status)->toBe(PaymentStatus::Refunded);
});
