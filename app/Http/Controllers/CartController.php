<?php

namespace App\Http\Controllers;

use App\Services\CartService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CartController extends Controller
{
    public function __construct(private readonly CartService $cart) {}

    /** Muestra el carrito actual. */
    public function index(): View
    {
        return view('cart.index', [
            'items'    => $this->cart->items(),
            'subtotal' => $this->cart->subtotal(),
        ]);
    }

    /** Agrega un producto al carrito. */
    public function store(Request $request, int $productId): RedirectResponse
    {
        $data = $request->validate([
            'quantity' => ['required', 'integer', 'min:1', 'max:99'],
            'size'     => ['nullable', 'string', 'max:10'],
        ]);

        try {
            $this->cart->add($productId, (int) $data['quantity'], $data['size'] ?? null);

            return back()->with('status', 'Producto añadido al carrito.');
        } catch (\RuntimeException $e) {
            return back()->withErrors(['cart' => $e->getMessage()]);
        }
    }

    /** Actualiza la cantidad de un ítem. */
    public function update(Request $request, int $productId): RedirectResponse
    {
        $data = $request->validate([
            'quantity' => ['required', 'integer', 'min:1', 'max:99'],
            'size'     => ['nullable', 'string', 'max:10'],
        ]);

        try {
            $this->cart->update($productId, $data['size'] ?? null, (int) $data['quantity']);

            return back()->with('status', 'Carrito actualizado.');
        } catch (\RuntimeException $e) {
            return back()->withErrors(['cart' => $e->getMessage()]);
        }
    }

    /** Elimina un ítem del carrito. */
    public function destroy(Request $request, int $productId): RedirectResponse
    {
        $size = $request->query('size');
        $this->cart->remove($productId, $size ?: null);

        return back()->with('status', 'Producto eliminado del carrito.');
    }
}
