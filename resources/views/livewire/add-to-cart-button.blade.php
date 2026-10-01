<form wire:submit="add" class="flex gap-2">
    @if ($withQuantity)
        <x-filament::input.wrapper class="w-24">
            <x-filament::input type="number" min="1" wire:model="quantity" aria-label="Quantity" />
        </x-filament::input.wrapper>
    @endif

    <x-filament::button type="submit" icon="heroicon-m-plus" class="flex-1">
        Add to cart
    </x-filament::button>
</form>
