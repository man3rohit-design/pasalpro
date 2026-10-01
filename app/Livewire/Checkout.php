<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Actions\CreateSale;
use App\Enums\PaymentMethod;
use App\Enums\SaleChannel;
use App\Filament\Account\Resources\Orders\OrderResource;
use App\Models\Customer;
use App\Models\User;
use App\Support\Cart;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Schemas\Schema;
use Illuminate\Contracts\View\View;
use Illuminate\Validation\ValidationException;
use Livewire\Component;

/**
 * @property-read Schema $form
 */
class Checkout extends Component implements HasSchemas
{
    use InteractsWithSchemas;

    /** @var array<string, mixed>|null */
    public ?array $data = [];

    public function mount(Cart $cart): void
    {
        if ($cart->count() === 0) {
            $this->redirectRoute('cart');

            return;
        }

        $user = $this->user();

        $this->form->fill([
            'delivery_name'    => $user->customer->name ?? $user->name,
            'delivery_phone'   => $user->customer->phone ?? $user->phone,
            'delivery_address' => $user->customer?->address,
            'payment_method'   => PaymentMethod::CashOnDelivery->value,
        ]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->statePath('data')
            ->components([
                TextInput::make('delivery_name')
                    ->label('Full name')
                    ->required()
                    ->maxLength(255),
                TextInput::make('delivery_phone')
                    ->label('Mobile number')
                    ->tel()
                    ->required()
                    ->regex('/^9[678]\d{8}$/')
                    ->validationMessages(['regex' => 'Enter a 10 digit Nepali mobile number, e.g. 9841234567.']),
                TextInput::make('delivery_address')
                    ->label('Delivery address')
                    ->placeholder('Tole, ward and nearby landmark')
                    ->required()
                    ->maxLength(255),
                Textarea::make('notes')
                    ->label('Delivery notes')
                    ->placeholder('Call before delivery, leave with the guard, etc.')
                    ->rows(2)
                    ->maxLength(500),
                Radio::make('payment_method')
                    ->label('Pay when your order arrives')
                    ->options([
                        PaymentMethod::CashOnDelivery->value => 'Cash on delivery',
                        PaymentMethod::Fonepay->value        => 'Fonepay QR on delivery',
                    ])
                    ->required(),
            ]);
    }

    public function placeOrder(Cart $cart, CreateSale $createSale): void
    {
        $details = $this->form->getState();
        $user = $this->user();

        $customer = Customer::updateOrCreate(['user_id' => $user->id], [
            'name'    => $details['delivery_name'],
            'phone'   => $details['delivery_phone'],
            'email'   => $user->email,
            'address' => $details['delivery_address'],
        ]);

        try {
            $sale = $createSale->handle(
                $cart->quantities(),
                SaleChannel::Online,
                PaymentMethod::from($details['payment_method']),
                customer: $customer,
                details: collect($details)->only(['delivery_name', 'delivery_phone', 'delivery_address', 'notes'])->all(),
            );
        } catch (ValidationException $validationException) {
            Notification::make()->title('We could not place your order')->body($validationException->getMessage())->danger()->send();

            return;
        }

        $cart->clear();

        Notification::make()
            ->title(sprintf('Order %s placed', $sale->reference))
            ->body('We will call you to confirm delivery.')
            ->success()
            ->send();

        $this->redirect(OrderResource::getUrl('view', ['record' => $sale], panel: 'account'));
    }

    public function render(Cart $cart): View
    {
        return view('livewire.checkout', [
            'products'   => $cart->products(),
            'quantities' => $cart->quantities(),
            'subtotal'   => $cart->subtotal(),
        ])->title('Checkout');
    }

    private function user(): User
    {
        $user = auth()->user();
        assert($user instanceof User);

        return $user;
    }
}
