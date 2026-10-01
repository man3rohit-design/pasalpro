<x-filament-panels::page>
    @if (method_exists($this, 'getSummary'))
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            @foreach ($this->getSummary() as $label => $value)
                <x-filament::section compact>
                    <p class="text-sm text-gray-500 dark:text-gray-400">{{ $label }}</p>
                    <p class="mt-1 text-xl font-semibold text-gray-950 dark:text-white">{{ $value }}</p>
                </x-filament::section>
            @endforeach
        </div>
    @endif

    {{ $this->table }}
</x-filament-panels::page>
