<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Product;
use App\Support\Ean13Barcode;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class PrintBarcodeLabelsController extends Controller
{
    public function __invoke(Request $request, Product $product): View
    {
        Gate::authorize('update', $product);

        return view('products.barcode-labels', [
            'product' => $product,
            'barcode' => (new Ean13Barcode($product->barcode))->toSvg(),
            'copies'  => $request->integer('copies', 24),
        ]);
    }
}
