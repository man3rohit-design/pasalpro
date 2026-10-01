<a href="{{ route('cart') }}" wire:navigate class="relative inline-flex items-center gap-2 rounded-lg px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100">
    <x-filament::icon icon="heroicon-o-shopping-cart" class="h-6 w-6" />
    <span class="hidden sm:inline">Cart</span>
    @if ($count > 0)
        <x-filament::badge size="sm">{{ $count }}</x-filament::badge>
    @endif
</a>
