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
            'customized' => ['nullable', 'boolean'],
            'custom_color' => ['nullable', 'required_if:customized,1', 'in:black,white,gray,navy,red,beige'],
            'custom_text' => ['nullable', 'string', 'max:40'],
            'custom_location' => ['nullable', 'required_if:customized,1', 'in:chest,back,left_sleeve,right_sleeve'],
            'custom_image' => ['nullable', 'image', 'mimes:png,jpg,jpeg,webp', 'max:5120'],
        ]);

        $customization = null;
        if (($data['customized'] ?? null) === '1' || ($data['customized'] ?? false) === true) {
            $customization = ['color' => $data['custom_color'], 'text' => $data['custom_text'] ?? null, 'location' => $data['custom_location']];
            if ($request->hasFile('custom_image')) {
                $customization['image_path'] = $request->file('custom_image')->store('customizations/temp', 'local');
            }
        }

        try {
            $this->cart->add($productId, (int) $data['quantity'], $data['size'] ?? null, $customization);

            return back()->with('status', 'Producto añadido al carrito.');
        } catch (\RuntimeException $e) {
            if (isset($customization['image_path'])) {
                Storage::disk('local')->delete($customization['image_path']);
            }

            return back()->withErrors(['cart' => $e->getMessage()]);
        }
    }

    /** Actualiza la cantidad de un ítem. */
    public function update(Request $request, int $productId): RedirectResponse
    {
        $data = $request->validate([
            'quantity' => ['required', 'integer', 'min:1', 'max:99'],
            'size'     => ['nullable', 'string', 'max:10'],
            'item_key' => ['nullable', 'string', 'max:128'],
        ]);

        try {
            $this->cart->update($productId, $data['size'] ?? null, (int) $data['quantity'], $data['item_key'] ?? null);

            return back()->with('status', 'Carrito actualizado.');
        } catch (\RuntimeException $e) {
            return back()->withErrors(['cart' => $e->getMessage()]);
        }
    }

    /** Elimina un ítem del carrito. */
    public function destroy(Request $request, int $productId): RedirectResponse
    {
        $size = $request->query('size');
        $this->cart->remove($productId, $size ?: null, $request->query('item_key'));

        return back()->with('status', 'Producto eliminado del carrito.');
    }
}
