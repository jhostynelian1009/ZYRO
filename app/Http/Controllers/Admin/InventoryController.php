<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\View\View;

class InventoryController extends Controller
{
    /**
     * Muestra el catálogo de inventario con existencias y líneas comerciales.
     */
    public function index(Request $request): View
    {
        $q = trim((string) $request->query('q'));
        $line = $request->query('line');

        $query = Product::query()->orderByDesc('is_active')->orderBy('name');

        if ($q !== '') {
            $query->where(function ($sub) use ($q) {
                $sub->where('name', 'like', "%{$q}%")
                    ->orWhere('description', 'like', "%{$q}%")
                    ->orWhere('slug', 'like', "%{$q}%");
            });
        }

        if ($line === 'tech') {
            $query->where('price', '>=', 200);
        } elseif ($line === 'esencial') {
            $query->where('price', '<', 200);
        }

        $products = $query->paginate(15)->withQueryString();

        $stats = [
            'total'     => Product::query()->count(),
            'active'    => Product::query()->where('is_active', true)->count(),
            'inactive'  => Product::query()->where('is_active', false)->count(),
            'low_stock' => Product::query()->where('is_active', true)->where('stock', '<=', 3)->count(),
        ];

        return view('admin.inventory.index', compact('products', 'q', 'line', 'stats'));
    }
}
