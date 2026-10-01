<div class="space-y-12">
    <section class="overflow-hidden rounded-2xl bg-gradient-to-r from-primary-700 to-primary-500 px-6 py-12 text-white sm:px-12">
        <p class="text-sm font-semibold uppercase tracking-widest text-primary-100">Your neighbourhood pasal, online</p>
        <h1 class="mt-3 max-w-2xl text-3xl font-bold leading-tight sm:text-4xl">
            Daily groceries from Chabahil, delivered to your door
        </h1>
        <p class="mt-4 max-w-xl text-primary-50">
            Rice, dal, noodles, dairy and household essentials at shop prices. Order before 5 pm for same day delivery inside the valley.
        </p>
        <div class="mt-8 flex flex-wrap gap-3">
            <x-filament::button tag="a" :href="route('products.index')" size="xl" color="gray">
                Start shopping
            </x-filament::button>
        </div>
    </section>

    <section>
        <h2 class="text-xl font-bold">Shop by category</h2>
        <div class="mt-4 grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-5">
            @foreach ($categories as $category)
                <a href="{{ route('products.index', ['category' => $category->slug]) }}" wire:navigate
                    class="rounded-xl border border-gray-200 bg-white p-4 transition hover:border-primary-500 hover:shadow-sm">
                    <p class="font-semibold">{{ $category->name }}</p>
                    <p class="text-sm text-gray-500">{{ $category->products_count }} products</p>
                </a>
            @endforeach
        </div>
    </section>

    <section>
        <div class="flex items-end justify-between">
            <h2 class="text-xl font-bold">Featured this week</h2>
            <x-filament::link :href="route('products.index')" icon="heroicon-m-arrow-right" icon-position="after">
                See all products
            </x-filament::link>
        </div>
        <div class="mt-4 grid grid-cols-2 gap-4 md:grid-cols-3 lg:grid-cols-4">
            @foreach ($featuredProducts as $product)
                <x-product-card :product="$product" wire:key="featured-{{ $product->id }}" />
            @endforeach
        </div>
    </section>

    <section>
        <h2 class="text-xl font-bold">Best sellers this month</h2>
        <div class="mt-4 grid grid-cols-2 gap-4 md:grid-cols-3 lg:grid-cols-4">
            @foreach ($bestSellers as $product)
                <x-product-card :product="$product" wire:key="best-{{ $product->id }}" />
            @endforeach
        </div>
    </section>
</div>
