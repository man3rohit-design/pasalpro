<x-filament-panels::page>
    <div class="grid gap-6 lg:grid-cols-3">
        <div class="space-y-6 lg:col-span-2">
            <x-filament::section>
                <div class="grid gap-4 md:grid-cols-2">
                    <form wire:submit="addScannedBarcode">
                        <x-filament::input.wrapper prefix-icon="heroicon-o-qr-code">
                            <x-filament::input
                                type="text"
                                wire:model="barcode"
                                placeholder="Scan barcode and press Enter"
                                autofocus
                                autocomplete="off"
                            />
                        </x-filament::input.wrapper>
                    </form>

                    <div>
                        {{ $this->searchForm }}
                    </div>
                </div>
            </x-filament::section>

            <x-filament::section heading="Cart" :description="count($cart) . ' ' . str('item')->plural(count($cart))">
                <x-slot name="afterHeader">
                    @if (count($cart))
                        <x-filament::button color="gray" size="sm" wire:click="clearCart">
                            Clear cart
                        </x-filament::button>
                    @endif
                </x-slot>

                @if ($this->cartProducts->isEmpty())
                    <div class="py-10 text-center text-sm text-gray-500 dark:text-gray-400">
                        Scan a barcode or search for a product to start a sale.
                    </div>
                @else
                    <div class="divide-y divide-gray-100 dark:divide-white/10">
                        @foreach ($this->cartProducts as $product)
                            <div wire:key="cart-{{ $product->id }}" class="flex flex-wrap items-center gap-4 py-3">
                                <div class="min-w-0 flex-1">
                                    <p class="font-medium text-gray-950 dark:text-white">{{ $product->name }}</p>
                                    <p class="text-sm text-gray-500 dark:text-gray-400">
                                        Rs. {{ number_format((float) $product->selling_price, 2) }} · {{ $product->inventory->quantity }} in stock
                                    </p>
                                </div>

                                <div class="flex items-center gap-2">
                                    <x-filament::icon-button
                                        icon="heroicon-m-minus"
                                        color="gray"
                                        label="Decrease quantity"
                                        wire:click="updateQuantity({{ $product->id }}, {{ $cart[$product->id] - 1 }})"
                                    />
                                    <span class="w-8 text-center font-semibold">{{ $cart[$product->id] }}</span>
                                    <x-filament::icon-button
                                        icon="heroicon-m-plus"
                                        color="gray"
                                        label="Increase quantity"
                                        wire:click="updateQuantity({{ $product->id }}, {{ $cart[$product->id] + 1 }})"
                                    />
                                </div>

                                <p class="w-28 text-right font-semibold text-gray-950 dark:text-white">
                                    Rs. {{ number_format($cart[$product->id] * (float) $product->selling_price, 2) }}
                                </p>

                                <x-filament::icon-button
                                    icon="heroicon-m-trash"
                                    color="danger"
                                    label="Remove {{ $product->name }}"
                                    wire:click="removeFromCart({{ $product->id }})"
                                />
                            </div>
                        @endforeach
                    </div>
                @endif
            </x-filament::section>
        </div>

        <div class="space-y-6">
            <x-filament::section heading="Checkout">
                {{ $this->checkoutForm }}

                <dl class="mt-6 space-y-2 border-t border-gray-100 pt-4 text-sm dark:border-white/10">
                    <div class="flex justify-between">
                        <dt class="text-gray-500 dark:text-gray-400">Subtotal</dt>
                        <dd>Rs. {{ number_format($this->subtotal, 2) }}</dd>
                    </div>
                    <div class="flex justify-between text-lg font-bold text-gray-950 dark:text-white">
                        <dt>Total</dt>
                        <dd>Rs. {{ number_format($this->total, 2) }}</dd>
                    </div>
                    @if ($this->changeDue !== null)
                        <div @class([
                            'flex justify-between font-semibold',
                            'text-success-600' => $this->changeDue >= 0,
                            'text-danger-600' => $this->changeDue < 0,
                        ])>
                            <dt>{{ $this->changeDue >= 0 ? 'Change due' : 'Still to pay' }}</dt>
                            <dd>Rs. {{ number_format(abs($this->changeDue), 2) }}</dd>
                        </div>
                    @endif
                </dl>

                <x-filament::button
                    class="mt-6 w-full"
                    size="xl"
                    icon="heroicon-o-check-circle"
                    wire:click="completeSale"
                    :disabled="empty($cart)"
                >
                    Complete sale
                </x-filament::button>

                @if ($lastSaleId)
                    <x-filament::link
                        class="mt-4 w-full justify-center"
                        icon="heroicon-o-printer"
                        :href="route('sales.invoice', $lastSaleId)"
                        target="_blank"
                    >
                        Print last receipt
                    </x-filament::link>
                @endif
            </x-filament::section>
        </div>
    </div>
</x-filament-panels::page>
