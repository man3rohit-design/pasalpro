<?php

declare(strict_types=1);

use App\Enums\PaymentMethod;
use App\Enums\SaleChannel;
use App\Enums\SaleStatus;
use App\Livewire\Checkout;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Sale;
use App\Models\User;
use App\Support\Cart;
use Livewire\Livewire;

it('sends guests to sign in before checkout', function (): void {
    $this->get(route('checkout'))->assertRedirect(route('filament.account.auth.login'));
});

it('sends shoppers with an empty cart back to the cart', function (): void {
    $this->actingAs(User::factory()->create());

    Livewire::test(Checkout::class)->assertRedirect(route('cart'));
});

it('places a cash on delivery order and empties the cart', function (): void {
    $user = User::factory()->create(['name' => 'Anisha Gurung', 'phone' => '9846612345']);
    $rice = Product::factory()->withStock(10)->create(['selling_price' => 850]);
    $this->actingAs($user);
    resolve(Cart::class)->add($rice, 2);

    Livewire::test(Checkout::class)
        ->assertSet('data.delivery_name', 'Anisha Gurung')
        ->fillForm([
            'delivery_phone'   => '9846612345',
            'delivery_address' => 'Jhamsikhel, Lalitpur',
            'payment_method'   => PaymentMethod::CashOnDelivery->value,
        ])
        ->call('placeOrder')
        ->assertHasNoFormErrors()
        ->assertRedirect();

    $sale = Sale::sole();

    expect($sale)
        ->channel->toBe(SaleChannel::Online)
        ->status->toBe(SaleStatus::Pending)
        ->total->toBe('1700.00')
        ->delivery_address->toBe('Jhamsikhel, Lalitpur')
        ->and($sale->customer->user_id)->toBe($user->id)
        ->and($rice->inventory->fresh()->quantity)->toBe(8)
        ->and(resolve(Cart::class)->count())->toBe(0);
});

it('reuses the customer record on later orders', function (): void {
    $customer = Customer::factory()->withAccount()->create();
    $product = Product::factory()->withStock(10)->create();
    $this->actingAs($customer->user);
    resolve(Cart::class)->add($product);

    Livewire::test(Checkout::class)
        ->fillForm(['delivery_phone' => '9801234567', 'delivery_address' => 'Boudha, Kathmandu'])
        ->call('placeOrder');

    expect(Customer::count())->toBe(1)
        ->and($customer->fresh()->address)->toBe('Boudha, Kathmandu');
});

it('rejects a mobile number that is not Nepali', function (): void {
    $this->actingAs(User::factory()->create());
    resolve(Cart::class)->add(Product::factory()->withStock(10)->create());

    Livewire::test(Checkout::class)
        ->fillForm(['delivery_phone' => '12345', 'delivery_address' => 'Koteshwor'])
        ->call('placeOrder')
        ->assertHasFormErrors(['delivery_phone' => 'regex']);
});
