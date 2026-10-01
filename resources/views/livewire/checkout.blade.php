<div class="grid gap-8 lg:grid-cols-3">
    <div class="lg:col-span-2">
        <h1 class="mb-6 text-2xl font-bold">Checkout</h1>

        <form wire:submit="placeOrder">
            <x-filament::section heading="Delivery details">
                {{ $this->form }}
            </x-filament::section>

            <x-filament::button type="submit" size="xl" class="mt-6" icon="heroicon-m-check-circle">
                Place order · Rs. {{ number_format($subtotal, 2) }}
            </x-filament::button>
        </form>
    </div>

    <div>
        <x-filament::section heading="Your order">
            <ul class="divide-y divide-gray-100 text-sm">
                @foreach ($products as $product)
                    <li class="flex justify-between gap-4 py-2" wire:key="summary-{{ $product->id }}">
                        <span>{{ $product->name }} <span class="text-gray-500">× {{ $quantities[$product->id] }}</span></span>
                        <span class="font-medium">Rs. {{ number_format($quantities[$product->id] * (float) $product->selling_price, 2) }}</span>
                    </li>
                @endforeach
            </ul>
            <div class="mt-4 flex justify-between border-t border-gray-100 pt-4 text-lg font-bold">
                <span>Total</span>
                <span>Rs. {{ number_format($subtotal, 2) }}</span>
            </div>
        </x-filament::section>
    </div>
</div>
