<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CatalogController extends Controller
{
    public function index(Request $request): View
    {
        $query = trim((string) $request->query('q', ''));

        $products = Product::query()
            ->active()
            ->search($query)
            ->orderBy('name')
            ->get();

        return view('catalog.index', [
            'products' => $products,
            'query' => $query,
        ]);
    }

    public function show(Product $product): View
    {
        abort_unless($product->is_active, 404);

        return view('catalog.show', [
            'product' => $product,
        ]);
    }
}
