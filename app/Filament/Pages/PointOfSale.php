<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use App\Actions\CreateSale;
use App\Enums\PaymentMethod;
use App\Enums\SaleChannel;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Sale;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\ToggleButtons;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Computed;
use UnitEnum;

/**
 * @property-read Schema $searchForm
 * @property-read Schema $checkoutForm
 * @property-read Collection<int, Product> $cartProducts
 * @property-read float $subtotal
 * @property-read float $total
 * @property-read float|null $changeDue
 */
class PointOfSale extends Page
{
    /** @var array<int, int> quantities keyed by product id */
    public array $cart = [];

    public string $barcode = '';

    /** @var array<string, mixed>|null */
    public ?array $search = [];

    /** @var array<string, mixed>|null */
    public ?array $checkout = [];

    public ?int $lastSaleId = null;

    protected string $view = 'filament.pages.point-of-sale';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCalculator;

    protected static string|UnitEnum|null $navigationGroup = 'Sales';

    protected static ?int $navigationSort = 1;

    protected static ?string $title = 'Point of sale';

    public static function canAccess(): bool
    {
        return auth()->user()?->can('create', Sale::class) ?? false;
    }

    public function mount(): void
    {
        $this->searchForm->fill();
        $this->checkoutForm->fill();
    }

    public function searchForm(Schema $schema): Schema
    {
        return $schema
            ->statePath('search')
            ->components([
                Select::make('product_id')
                    ->hiddenLabel()
                    ->placeholder('Search by product name or SKU')
                    ->searchable()
                    ->getSearchResultsUsing(fn (string $search): array => Product::active()
                        ->where(fn (Builder $query) => $query
                            ->where('name', 'like', sprintf('%%%s%%', $search))
                            ->orWhere('sku', 'like', sprintf('%%%s%%', $search))
                            ->orWhere('barcode', $search))
                        ->orderBy('name')
                        ->limit(15)
                        ->pluck('name', 'id')
                        ->all())
                    ->getOptionLabelUsing(fn (mixed $value): ?string => Product::find($value)?->name)
                    ->live()
                    ->afterStateUpdated(function (?string $state): void {
                        if (filled($state)) {
                            $this->addToCart((int) $state);
                            $this->searchForm->fill();
                        }
                    }),
            ]);
    }

    public function checkoutForm(Schema $schema): Schema
    {
        return $schema
            ->statePath('checkout')
            ->components([
                Select::make('customer_id')
                    ->label('Customer')
                    ->placeholder('Walk-in customer')
                    ->searchable()
                    ->getSearchResultsUsing(fn (string $search): array => Customer::query()
                        ->where('name', 'like', sprintf('%%%s%%', $search))
                        ->orWhere('phone', 'like', sprintf('%%%s%%', $search))
                        ->limit(10)
                        ->get()
                        ->mapWithKeys(fn (Customer $customer): array => [$customer->id => sprintf('%s (%s)', $customer->name, $customer->phone)])
                        ->all())
                    ->getOptionLabelUsing(fn (mixed $value): ?string => Customer::find($value)?->name)
                    ->createOptionForm([
                        TextInput::make('name')
                            ->required()
                            ->maxLength(255),
                        TextInput::make('phone')
                            ->tel()
                            ->required()
                            ->maxLength(20),
                    ])
                    ->createOptionUsing(fn (array $data): int => Customer::create($data)->id),
                ToggleButtons::make('payment_method')
                    ->label('Payment')
                    ->options(collect(PaymentMethod::cases())
                        ->reject(fn (PaymentMethod $method): bool => $method === PaymentMethod::CashOnDelivery)
                        ->mapWithKeys(fn (PaymentMethod $method): array => [$method->value => $method->getLabel()])
                        ->all())
                    ->default(PaymentMethod::Cash->value)
                    ->inline()
                    ->required()
                    ->live(),
                TextInput::make('payment_reference')
                    ->label('Transaction ID')
                    ->required()
                    ->maxLength(100)
                    ->visible(fn (Get $get): bool => $get('payment_method') !== PaymentMethod::Cash->value),
                TextInput::make('discount')
                    ->numeric()
                    ->prefix('Rs.')
                    ->minValue(0)
                    ->default(0)
                    ->live(onBlur: true),
                TextInput::make('amount_tendered')
                    ->label('Cash received')
                    ->numeric()
                    ->prefix('Rs.')
                    ->minValue(0)
                    ->live(onBlur: true)
                    ->visible(fn (Get $get): bool => $get('payment_method') === PaymentMethod::Cash->value),
            ]);
    }

    /**
     * @return Collection<int, Product>
     */
    #[Computed]
    public function cartProducts(): Collection
    {
        return Product::with('inventory')->findMany(array_keys($this->cart))->sortBy(
            fn (Product $product): int => array_search($product->id, array_keys($this->cart), true),
        );
    }

    #[Computed]
    public function subtotal(): float
    {
        return $this->cartProducts->sum(fn (Product $product): float => $this->cart[$product->id] * (float) $product->selling_price);
    }

    #[Computed]
    public function total(): float
    {
        return max($this->subtotal - (float) ($this->checkout['discount'] ?? 0), 0);
    }

    #[Computed]
    public function changeDue(): ?float
    {
        $tendered = $this->checkout['amount_tendered'] ?? null;

        return is_numeric($tendered) ? (float) $tendered - $this->total : null;
    }

    public function addScannedBarcode(): void
    {
        $product = Product::active()->where('barcode', str($this->barcode)->trim()->toString())->first();
        $this->barcode = '';

        if (! $product instanceof Product) {
            Notification::make()->title('No product matches that barcode')->warning()->send();

            return;
        }

        $this->addToCart($product->id);
    }

    public function addToCart(int $productId): void
    {
        $this->updateQuantity($productId, ($this->cart[$productId] ?? 0) + 1);
    }

    public function updateQuantity(int $productId, int $quantity): void
    {
        if ($quantity <= 0) {
            $this->removeFromCart($productId);

            return;
        }

        $product = Product::with('inventory')->findOrFail($productId);

        if ($quantity > $product->inventory->quantity) {
            Notification::make()
                ->title(sprintf('Only %s of %s in stock', $product->inventory->quantity, $product->name))
                ->warning()
                ->send();

            return;
        }

        $this->cart[$productId] = $quantity;
        unset($this->cartProducts);
    }

    public function removeFromCart(int $productId): void
    {
        unset($this->cart[$productId], $this->cartProducts);
    }

    public function clearCart(): void
    {
        $this->cart = [];
        $this->checkoutForm->fill();
        unset($this->cartProducts);
    }

    public function completeSale(CreateSale $createSale): void
    {
        $checkout = $this->checkoutForm->getState();
        $paymentMethod = PaymentMethod::from($checkout['payment_method']);

        if ($paymentMethod === PaymentMethod::Cash && filled($checkout['amount_tendered'] ?? null) && $this->changeDue < 0) {
            Notification::make()->title('Cash received is less than the total')->danger()->send();

            return;
        }

        try {
            $sale = $createSale->handle(
                $this->cart,
                SaleChannel::Counter,
                $paymentMethod,
                customer: Customer::find($checkout['customer_id'] ?? null),
                soldBy: auth()->user(),
                discount: (float) ($checkout['discount'] ?? 0),
                paymentReference: $checkout['payment_reference'] ?? null,
            );
        } catch (ValidationException $validationException) {
            Notification::make()->title('Sale not completed')->body($validationException->getMessage())->danger()->send();

            return;
        }

        $this->lastSaleId = $sale->id;
        $this->clearCart();

        Notification::make()
            ->title(sprintf('Sale %s completed', $sale->reference))
            ->body('Rs. '.number_format((float) $sale->total, 2).' received by '.$paymentMethod->getLabel().'.')
            ->success()
            ->actions([
                Action::make('printReceipt')
                    ->label('Print receipt')
                    ->url(route('sales.invoice', $sale), shouldOpenInNewTab: true),
            ])
            ->send();
    }
}
