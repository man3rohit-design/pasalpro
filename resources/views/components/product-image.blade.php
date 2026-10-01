@use('Illuminate\Support\Facades\Storage')

@props(['product'])

@if ($product->image_path)
    <img src="{{ Storage::disk('public')->url($product->image_path) }}" alt="{{ $product->name }}" {{ $attributes->class('w-full bg-white object-contain p-3') }}>
@else
    <div {{ $attributes->class('flex w-full items-center justify-center bg-gradient-to-br from-primary-50 to-primary-100') }}>
        <span class="px-4 text-center text-2xl font-bold text-primary-700">
            {{ str($product->name)->explode(' ')->take(2)->map(fn (string $word): string => mb_substr($word, 0, 1))->implode('') }}
        </span>
    </div>
@endif
