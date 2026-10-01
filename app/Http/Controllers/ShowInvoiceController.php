<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Sale;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;

class ShowInvoiceController extends Controller
{
    public function __invoke(Sale $sale): View
    {
        Gate::authorize('view', $sale);

        abort_unless($sale->invoice()->exists(), 404);

        return view('invoices.show', [
            'sale' => $sale->load(['items', 'invoice', 'customer', 'soldBy', 'payments']),
        ]);
    }
}
