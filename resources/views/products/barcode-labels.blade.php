<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Barcode labels · {{ $product->name }}</title>
    @vite(['resources/css/app.css'])
</head>

<body class="bg-gray-100 p-6 print:bg-white print:p-0">
    <div class="mx-auto mb-6 flex max-w-4xl items-center justify-between print:hidden">
        <div>
            <h1 class="text-lg font-semibold">{{ $product->name }}</h1>
            <p class="text-sm text-gray-500">{{ $copies }} labels · A4 sheet</p>
        </div>
        <button type="button" onclick="window.print()"
            class="rounded-lg bg-emerald-600 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-500">
            Print labels
        </button>
    </div>

    <main class="mx-auto grid max-w-4xl grid-cols-3 gap-3 bg-white p-4 print:max-w-none print:p-0">
        @foreach (range(1, $copies) as $copy)
            <div class="flex flex-col items-center rounded border border-dashed border-gray-300 px-2 py-3 text-center">
                <p class="line-clamp-1 text-xs font-semibold">{{ $product->name }}</p>
                <div class="my-1 [&_svg]:h-12 [&_svg]:w-full">{!! $barcode !!}</div>
                <p class="font-mono text-xs tracking-widest">{{ $product->barcode }}</p>
                <p class="text-sm font-bold">Rs. {{ number_format((float) $product->selling_price, 2) }}</p>
            </div>
        @endforeach
    </main>
</body>

</html>
