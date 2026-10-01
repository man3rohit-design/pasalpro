@props(['product'])

<div class="flex flex-col overflow-hidden rounded-xl border border-gray-200 bg-white shadow-xs transition hover:shadow-md">
    <a href="{{ route('products.show', $product) }}" wire:navigate>
        <x-product-image :product="$product" class="aspect-square" />
    </a>

    <div class="flex flex-1 flex-col gap-2 p-4">
        <p class="text-xs font-medium uppercase tracking-wide text-gray-500">{{ $product->category->name }}</p>
        <a href="{{ route('products.show', $product) }}" wire:navigate class="font-semibold leading-snug hover:text-primary-600">
            {{ $product->name }}
        </a>

        <div class="mt-auto flex items-center justify-between pt-2">
            <p class="text-lg font-bold">Rs. {{ number_format((float) $product->selling_price) }}</p>

            @if ($product->inventory->quantity <= 0)
                <x-filament::badge color="danger">Out of stock</x-filament::badge>
            @elseif ($product->inventory->isLowStock())
                <x-filament::badge color="warning">Only {{ $product->inventory->quantity }} left</x-filament::badge>
            @endif
        </div>

        <livewire:add-to-cart-button :product-id="$product->id" :wire:key="'add-'.$product->id" />
    </div>
</div>
