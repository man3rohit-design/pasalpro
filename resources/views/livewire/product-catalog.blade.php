<div class="space-y-6">
    <div class="flex flex-wrap items-end justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold">{{ $heading }}</h1>
            <p class="text-sm text-gray-500">
                {{ $products->total() }} {{ str('product')->plural($products->total()) }}
                @if (filled($search))
                    matching "{{ $search }}"
                @endif
            </p>
        </div>

        <div class="flex w-full gap-3 sm:w-auto">
            <x-filament::input.wrapper prefix-icon="heroicon-m-magnifying-glass" class="flex-1 sm:w-72">
                <x-filament::input type="search" wire:model.live.debounce.400ms="search" placeholder="Search noodles, rice, tea..." aria-label="Search products" />
            </x-filament::input.wrapper>

            <x-filament::input.wrapper class="w-44">
                <x-filament::input.select wire:model.live="sort" aria-label="Sort products">
                    <option value="name">Name A to Z</option>
                    <option value="price_low">Price, low to high</option>
                    <option value="price_high">Price, high to low</option>
                </x-filament::input.select>
            </x-filament::input.wrapper>
        </div>
    </div>

    <div class="grid gap-8 lg:grid-cols-4">
        <aside>
            <nav class="-mx-4 flex gap-2 overflow-x-auto px-4 pb-1 text-sm lg:sticky lg:top-24 lg:mx-0 lg:flex-col lg:gap-1 lg:overflow-visible lg:rounded-xl lg:border lg:border-gray-200 lg:bg-white lg:p-3">
                <p class="hidden px-3 pb-2 pt-1 text-xs font-semibold uppercase tracking-wide text-gray-500 lg:block">Categories</p>
                @foreach (collect([['slug' => '', 'name' => 'All products']])->concat($categories->map->only(['slug', 'name'])) as $item)
                    <button type="button" wire:click="$set('category', '{{ $item['slug'] }}')" wire:key="category-{{ $item['slug'] ?: 'all' }}"
                        @class([
                            'shrink-0 whitespace-nowrap rounded-full border px-4 py-1.5 text-left transition lg:rounded-lg lg:border-0 lg:px-3 lg:py-2',
                            'border-primary-600 bg-primary-50 font-semibold text-primary-700' => $category === $item['slug'],
                            'border-gray-200 bg-white text-gray-700 hover:bg-gray-100' => $category !== $item['slug'],
                        ])>
                        {{ $item['name'] }}
                    </button>
                @endforeach
            </nav>
        </aside>

        <div class="space-y-6 lg:col-span-3">
            @if ($products->isEmpty())
                <x-filament::empty-state icon="heroicon-o-magnifying-glass" heading="No products found">
                    <x-slot name="description">Try another search or browse a different category.</x-slot>
                </x-filament::empty-state>
            @else
                <div class="grid grid-cols-2 gap-4 md:grid-cols-3" wire:loading.class="opacity-60">
                    @foreach ($products as $product)
                        <x-product-card :product="$product" wire:key="product-{{ $product->id }}" />
                    @endforeach
                </div>

                <x-filament::pagination :paginator="$products" />
            @endif
        </div>
    </div>
</div>
