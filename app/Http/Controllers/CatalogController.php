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
        $filter = trim((string) $request->query('line', $request->query('filtro', 'todos')));

        $products = Product::query()
            ->active()
            ->byLine($filter)
            ->search($query)
            ->orderByRaw("CASE WHEN category = 'tech' OR name LIKE '%Tech%' THEN 0 ELSE 1 END")
            ->orderBy('price', 'desc')
            ->orderBy('name')
            ->get();

        $techCount = Product::query()->active()->withTechnology()->count();
        $esencialCount = Product::query()->active()->withoutTechnology()->count();
        $totalCount = Product::query()->active()->count();

        return view('catalog.index', [
            'products' => $products,
            'query' => $query,
            'filter' => $filter ?: 'todos',
            'counts' => [
                'todos' => $totalCount,
                'tech' => $techCount,
                'esencial' => $esencialCount,
            ],
        ]);
    }

    public function show(Product $product): View
    {
        abort_unless($product->is_active, 404);

        $relatedProducts = Product::query()
            ->active()
            ->where('id', '!=', $product->id)
            ->byLine($product->hasTechnology() ? 'tech' : 'esencial')
            ->limit(3)
            ->get();

        return view('catalog.show', [
            'product' => $product,
            'relatedProducts' => $relatedProducts,
        ]);
    }
}
