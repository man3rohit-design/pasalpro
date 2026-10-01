<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Invoice {{ $sale->invoice->number }}</title>
    @vite(['resources/css/app.css'])
</head>

<body class="bg-neutral-100 py-8 text-neutral-900 print:bg-white print:py-0">
    <main class="mx-auto max-w-md bg-white p-6 shadow-sm print:max-w-none print:shadow-none">
        <header class="border-b border-dashed border-neutral-300 pb-4 text-center">
            <h1 class="text-xl font-bold">{{ config('shop.name') }}</h1>
            <p class="text-sm text-neutral-600">{{ config('shop.address') }}</p>
            <p class="text-sm text-neutral-600">Phone {{ config('shop.phone') }} · PAN {{ config('shop.pan_number') }}</p>
            <p class="mt-3 text-sm font-semibold uppercase tracking-wide">Tax invoice</p>
        </header>

        <dl class="grid grid-cols-2 gap-y-1 border-b border-dashed border-neutral-300 py-3 text-sm">
            <dt class="text-neutral-500">Invoice</dt>
            <dd class="text-right font-medium">{{ $sale->invoice->number }}</dd>
            <dt class="text-neutral-500">Date</dt>
            <dd class="text-right">{{ $sale->invoice->issued_at->format('M j, Y g:i A') }}</dd>
            <dt class="text-neutral-500">Sale</dt>
            <dd class="text-right">{{ $sale->reference }}</dd>
            <dt class="text-neutral-500">Customer</dt>
            <dd class="text-right">{{ $sale->customer?->name ?? 'Walk-in customer' }}</dd>
            @if ($sale->soldBy)
                <dt class="text-neutral-500">Cashier</dt>
                <dd class="text-right">{{ $sale->soldBy->name }}</dd>
            @endif
        </dl>

        <table class="w-full border-b border-dashed border-neutral-300 text-sm">
            <thead>
                <tr class="text-left text-neutral-500">
                    <th class="py-2 font-normal">Item</th>
                    <th class="py-2 text-right font-normal">Qty</th>
                    <th class="py-2 text-right font-normal">Rate</th>
                    <th class="py-2 text-right font-normal">Amount</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($sale->items as $item)
                    <tr class="align-top">
                        <td class="py-1 pr-2">{{ $item->product_name }}</td>
                        <td class="py-1 text-right">{{ $item->quantity }}</td>
                        <td class="py-1 text-right">{{ number_format((float) $item->unit_price, 2) }}</td>
                        <td class="py-1 text-right">{{ number_format((float) $item->line_total, 2) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <dl class="grid grid-cols-2 gap-y-1 py-3 text-sm">
            <dt class="text-neutral-500">Subtotal</dt>
            <dd class="text-right">Rs. {{ number_format((float) $sale->subtotal, 2) }}</dd>
            @if ((float) $sale->discount > 0)
                <dt class="text-neutral-500">Discount</dt>
                <dd class="text-right">- Rs. {{ number_format((float) $sale->discount, 2) }}</dd>
            @endif
            <dt class="text-base font-bold">Total</dt>
            <dd class="text-right text-base font-bold">Rs. {{ number_format((float) $sale->total, 2) }}</dd>
            <dt class="text-neutral-500">Paid by</dt>
            <dd class="text-right">{{ $sale->payment_method->getLabel() }}</dd>
        </dl>

        <p class="border-t border-dashed border-neutral-300 pt-4 text-center text-sm text-neutral-600">
            Thank you for shopping with us. Goods once sold are exchanged within 7 days with this invoice.
        </p>

        <div class="mt-6 flex justify-center gap-3 print:hidden">
            <button type="button" onclick="window.print()"
                class="rounded-lg bg-emerald-600 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-500">
                Print invoice
            </button>
        </div>
    </main>
</body>

</html>
