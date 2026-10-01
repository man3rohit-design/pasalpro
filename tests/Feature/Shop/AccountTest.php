<?php

declare(strict_types=1);

use App\Actions\CreateSale;
use App\Enums\PaymentMethod;
use App\Enums\SaleChannel;
use App\Enums\SaleStatus;
use App\Enums\UserRole;
use App\Filament\Account\Pages\Auth\Register;
use App\Filament\Account\Resources\Orders\OrderResource;
use App\Filament\Account\Resources\Orders\Pages\ListOrders;
use App\Filament\Account\Resources\Orders\Pages\ViewOrder;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Sale;
use App\Models\User;
use Filament\Facades\Filament;
use Livewire\Livewire;

beforeEach(function (): void {
    Filament::setCurrentPanel('account');
});

function orderFor(Customer $customer): Sale
{
    return resolve(CreateSale::class)->handle(
        [Product::factory()->withStock(10)->create()->id => 1],
        SaleChannel::Online,
        PaymentMethod::CashOnDelivery,
        customer: $customer,
    );
}

it('registers a shopper as a customer with a mobile number', function (): void {
    Livewire::test(Register::class)
        ->fillForm([
            'name'                 => 'Sabin Bhattarai',
            'phone'                => '9841450080',
            'email'                => 'sabin@example.com',
            'password'             => 'secret-pass',
            'passwordConfirmation' => 'secret-pass',
        ])
        ->call('register')
        ->assertHasNoFormErrors();

    $user = User::where('email', 'sabin@example.com')->sole();

    expect($user->role)->toBe(UserRole::Customer)
        ->and($user->customer)->name->toBe('Sabin Bhattarai')->phone->toBe('9841450080');
});

it('lists only the signed in customer orders', function (): void {
    $customer = Customer::factory()->withAccount()->create();
    $ownOrder = orderFor($customer);
    $someoneElsesOrder = orderFor(Customer::factory()->withAccount()->create());
    $this->actingAs($customer->user);

    Livewire::test(ListOrders::class)
        ->assertCanSeeTableRecords([$ownOrder])
        ->assertCanNotSeeTableRecords([$someoneElsesOrder]);

    $this->get(OrderResource::getUrl('view', ['record' => $someoneElsesOrder], panel: 'account'))->assertNotFound();
});

it('lets a customer cancel an order that has not been confirmed', function (): void {
    $customer = Customer::factory()->withAccount()->create();
    $order = orderFor($customer);
    $this->actingAs($customer->user);

    Livewire::test(ViewOrder::class, ['record' => $order->id])
        ->callAction('cancel');

    expect($order->fresh()->status)->toBe(SaleStatus::Cancelled);
});

it('does not let a customer cancel once the shop has confirmed', function (): void {
    $customer = Customer::factory()->withAccount()->create();
    $order = orderFor($customer);
    $order->update(['status' => SaleStatus::Confirmed]);
    $this->actingAs($customer->user);

    Livewire::test(ViewOrder::class, ['record' => $order->id])
        ->assertActionHidden('cancel');
});

it('lets customers into their account but not the admin panel', function (): void {
    $this->actingAs(User::factory()->create());

    $this->get('/account/orders')->assertOk();
    $this->get('/admin')->assertForbidden();
});
