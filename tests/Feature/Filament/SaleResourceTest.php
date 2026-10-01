<?php

declare(strict_types=1);

use App\Actions\CompleteSale;
use App\Actions\CreateSale;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\SaleChannel;
use App\Enums\SaleStatus;
use App\Enums\UserRole;
use App\Filament\Resources\Sales\Pages\ListSales;
use App\Filament\Resources\Sales\Pages\ViewSale;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Sale;
use App\Models\User;

function placeOnlineOrder(Product $product, int $quantity = 2): Sale
{
    return resolve(CreateSale::class)->handle(
        [$product->id => $quantity],
        SaleChannel::Online,
        PaymentMethod::CashOnDelivery,
        customer: Customer::factory()->withAccount()->create(),
    );
}

it('takes an online order from pending through to delivered', function (): void {
    $this->actingAs(User::factory()->role(UserRole::Cashier)->create());
    $sale = placeOnlineOrder(Product::factory()->withStock(10)->create());

    Livewire::test(ViewSale::class, ['record' => $sale->id])
        ->assertActionHidden('dispatch')
        ->callAction('confirm')
        ->callAction('dispatch')
        ->callAction('complete')
        ->assertNotified('Order delivered and invoiced');

    expect($sale->fresh())
        ->status->toBe(SaleStatus::Completed)
        ->payment_status->toBe(PaymentStatus::Paid)
        ->and($sale->invoice()->exists())->toBeTrue();
});

it('lets a manager cancel an online order and restock it', function (): void {
    $product = Product::factory()->withStock(10)->create();
    $sale = placeOnlineOrder($product, 4);
    $this->actingAs(User::factory()->role(UserRole::Manager)->create());

    Livewire::test(ViewSale::class, ['record' => $sale->id])
        ->callAction('cancel');

    expect($sale->fresh()->status)->toBe(SaleStatus::Cancelled)
        ->and($product->inventory->fresh()->quantity)->toBe(10);
});

it('does not let a cashier cancel a sale', function (): void {
    $this->actingAs(User::factory()->role(UserRole::Cashier)->create());

    Livewire::test(ViewSale::class, ['record' => placeOnlineOrder(Product::factory()->withStock(10)->create())->id])
        ->assertActionHidden('cancel');
});

it('lists open online orders under the to fulfil tab', function (): void {
    $this->actingAs(User::factory()->role(UserRole::Cashier)->create());
    $product = Product::factory()->withStock(10)->create();
    $openOrder = placeOnlineOrder($product);
    $counterSale = resolve(CreateSale::class)->handle([$product->id => 1], SaleChannel::Counter, PaymentMethod::Cash);

    Livewire::test(ListSales::class)
        ->set('activeTab', 'to_fulfil')
        ->assertCanSeeTableRecords([$openOrder])
        ->assertCanNotSeeTableRecords([$counterSale]);
});

it('shows the invoice only to staff and the customer who placed the order', function (): void {
    $product = Product::factory()->withStock(10)->create();
    $sale = placeOnlineOrder($product);
    resolve(CompleteSale::class)->handle($sale);

    $this->actingAs($sale->customer->user)->get(route('sales.invoice', $sale))->assertOk()->assertSee($sale->invoice->number);
    $this->actingAs(User::factory()->role(UserRole::Cashier)->create())->get(route('sales.invoice', $sale))->assertOk();
    $this->actingAs(User::factory()->create())->get(route('sales.invoice', $sale))->assertForbidden();
});
