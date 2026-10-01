<div class="space-y-12">
    <x-filament::breadcrumbs :breadcrumbs="[
        route('products.index') => 'All products',
        route('products.index', ['category' => $product->category->slug]) => $product->category->name,
        $product->name,
    ]" />

    <div class="grid gap-8 md:grid-cols-2">
        <x-product-image :product="$product" class="aspect-square rounded-2xl" />

        <div class="space-y-5">
            <div>
                <p class="text-sm font-medium uppercase tracking-wide text-gray-500">{{ $product->category->name }}</p>
                <h1 class="mt-1 text-3xl font-bold">{{ $product->name }}</h1>
            </div>

            <p class="text-3xl font-bold text-primary-700">
                Rs. {{ number_format((float) $product->selling_price, 2) }}
                <span class="text-base font-normal text-gray-500">per {{ str($product->unit->getLabel())->lower() }}</span>
            </p>

            @if ($product->inventory->quantity <= 0)
                <x-filament::badge color="danger" size="lg">Out of stock</x-filament::badge>
            @elseif ($product->inventory->isLowStock())
                <x-filament::badge color="warning" size="lg">Hurry, only {{ $product->inventory->quantity }} left</x-filament::badge>
            @else
                <x-filament::badge color="success" size="lg">In stock</x-filament::badge>
            @endif

            @if ($product->description)
                <p class="text-gray-600">{{ $product->description }}</p>
            @endif

            <div class="max-w-sm">
                <livewire:add-to-cart-button :product-id="$product->id" with-quantity />
            </div>

            <dl class="grid grid-cols-2 gap-y-2 border-t border-gray-200 pt-5 text-sm">
                <dt class="text-gray-500">SKU</dt>
                <dd>{{ $product->sku }}</dd>
                @if ($product->expiry_date)
                    <dt class="text-gray-500">Best before</dt>
                    <dd>{{ $product->expiry_date->format('M j, Y') }}</dd>
                @endif
            </dl>
        </div>
    </div>

    @if ($relatedProducts->isNotEmpty())
        <section>
            <h2 class="text-xl font-bold">More from {{ $product->category->name }}</h2>
            <div class="mt-4 grid grid-cols-2 gap-4 md:grid-cols-4">
                @foreach ($relatedProducts as $related)
                    <x-product-card :product="$related" wire:key="related-{{ $related->id }}" />
                @endforeach
            </div>
        </section>
    @endif
</div>
