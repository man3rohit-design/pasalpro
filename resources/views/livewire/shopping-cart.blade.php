<div class="grid gap-8 lg:grid-cols-3">
    <div class="lg:col-span-2">
        <h1 class="mb-6 text-2xl font-bold">Your cart</h1>

        @if ($products->isEmpty())
            <x-filament::empty-state icon="heroicon-o-shopping-cart" heading="Your cart is empty">
                <x-slot name="description">Browse the shop and add a few things you need.</x-slot>
                <x-slot name="footer">
                    <x-filament::button tag="a" :href="route('products.index')">Browse products</x-filament::button>
                </x-slot>
            </x-filament::empty-state>
        @else
            <x-filament::section>
                <div class="divide-y divide-gray-100">
                    @foreach ($products as $product)
                        <div wire:key="line-{{ $product->id }}" class="flex flex-wrap items-center gap-4 py-4">
                            <x-product-image :product="$product" class="h-16 w-16 shrink-0 rounded-lg" />

                            <div class="min-w-0 flex-1">
                                <a href="{{ route('products.show', $product) }}" wire:navigate class="font-semibold hover:text-primary-600">{{ $product->name }}</a>
                                <p class="text-sm text-gray-500">Rs. {{ number_format((float) $product->selling_price, 2) }} each</p>
                            </div>

                            <div class="flex items-center gap-2">
                                <x-filament::icon-button icon="heroicon-m-minus" color="gray" label="Decrease quantity"
                                    wire:click="updateQuantity({{ $product->id }}, {{ $quantities[$product->id] - 1 }})" />
                                <span class="w-8 text-center font-semibold">{{ $quantities[$product->id] }}</span>
                                <x-filament::icon-button icon="heroicon-m-plus" color="gray" label="Increase quantity"
                                    wire:click="updateQuantity({{ $product->id }}, {{ $quantities[$product->id] + 1 }})" />
                            </div>

                            <p class="w-28 text-right font-semibold">Rs. {{ number_format($quantities[$product->id] * (float) $product->selling_price, 2) }}</p>

                            <x-filament::icon-button icon="heroicon-m-trash" color="danger" label="Remove {{ $product->name }}"
                                wire:click="remove({{ $product->id }})" />
                        </div>
                    @endforeach
                </div>
            </x-filament::section>
        @endif
    </div>

    @if ($products->isNotEmpty())
        <div>
            <x-filament::section heading="Order summary">
                <dl class="space-y-2 text-sm">
                    <div class="flex justify-between">
                        <dt class="text-gray-500">Subtotal</dt>
                        <dd>Rs. {{ number_format($subtotal, 2) }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-gray-500">Delivery</dt>
                        <dd class="text-success-600">Free</dd>
                    </div>
                    <div class="flex justify-between border-t border-gray-100 pt-2 text-lg font-bold">
                        <dt>Total</dt>
                        <dd>Rs. {{ number_format($subtotal, 2) }}</dd>
                    </div>
                </dl>

                <x-filament::button tag="a" :href="route('checkout')" size="xl" class="mt-6 w-full" icon="heroicon-m-lock-closed">
                    Proceed to checkout
                </x-filament::button>
            </x-filament::section>
        </div>
    @endif
</div>
