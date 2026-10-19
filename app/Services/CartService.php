<?php

namespace App\Services;

use App\Models\Product;
use Illuminate\Session\SessionManager;

/**
 * Gestiona el carrito de compra almacenado en sesión Laravel.
 *
 * Estructura de sesión:
 *   cart.items[product_id] => ['product_id', 'name', 'price', 'size', 'quantity', 'stock']
 */
class CartService
{
    private const SESSION_KEY = 'cart.items';

    public function __construct(private readonly SessionManager $session) {}

    /** Devuelve todos los ítems del carrito con precios confirmados desde BD. */
    public function items(): array
    {
        return $this->session->get(self::SESSION_KEY, []);
    }

    /**
     * Agrega o incrementa un producto en el carrito.
     * Recarga precio y stock desde BD; rechaza si no está activo o sin stock.
     *
     * @throws \RuntimeException
     */
    public function add(int $productId, int $quantity, ?string $size): void
    {
        if ($quantity < 1) {
            throw new \RuntimeException('La cantidad debe ser al menos 1.');
        }

        $product = Product::query()
            ->where('id', $productId)
            ->where('is_active', true)
            ->first();

        if (! $product) {
            throw new \RuntimeException('Producto no disponible.');
        }

        $items = $this->items();
        $key = $this->itemKey($productId, $size);
        $current = $items[$key]['quantity'] ?? 0;
        $newQty = $current + $quantity;

        if ($newQty > $product->stock) {
            throw new \RuntimeException(
                "Stock insuficiente. Disponible: {$product->stock}, solicitado: {$newQty}."
            );
        }

        $items[$key] = [
            'product_id' => $product->id,
            'name'       => $product->name,
            'price'      => (float) $product->price,
            'image_path' => $product->image_path,
            'size'       => $size,
            'quantity'   => $newQty,
            'stock'      => $product->stock,
        ];

        $this->session->put(self::SESSION_KEY, $items);
    }

    /**
     * Actualiza la cantidad de un ítem existente.
     *
     * @throws \RuntimeException
     */
    public function update(int $productId, ?string $size, int $quantity): void
    {
        if ($quantity < 1) {
            throw new \RuntimeException('La cantidad debe ser al menos 1.');
        }

        $product = Product::query()
            ->where('id', $productId)
            ->where('is_active', true)
            ->first();

        if (! $product) {
            throw new \RuntimeException('Producto no disponible.');
        }

        if ($quantity > $product->stock) {
            throw new \RuntimeException(
                "Stock insuficiente. Disponible: {$product->stock}."
            );
        }

        $items = $this->items();
        $key = $this->itemKey($productId, $size);

        if (! isset($items[$key])) {
            throw new \RuntimeException('Ítem no encontrado en el carrito.');
        }

        $items[$key]['quantity'] = $quantity;
        $items[$key]['stock']    = $product->stock;
        $items[$key]['price']    = (float) $product->price;

        $this->session->put(self::SESSION_KEY, $items);
    }

    /** Elimina un ítem del carrito. */
    public function remove(int $productId, ?string $size): void
    {
        $items = $this->items();
        $key = $this->itemKey($productId, $size);
        unset($items[$key]);
        $this->session->put(self::SESSION_KEY, $items);
    }

    /** Vacía el carrito completamente. */
    public function clear(): void
    {
        $this->session->forget(self::SESSION_KEY);
    }

    /** Total de ítems únicos en el carrito. */
    public function count(): int
    {
        return count($this->items());
    }

    /** Cantidad total de unidades en el carrito. */
    public function totalQuantity(): int
    {
        return array_sum(array_column($this->items(), 'quantity'));
    }

    /** Subtotal calculado desde sesión (no autoritativo; el servidor recalcula al pagar). */
    public function subtotal(): float
    {
        $total = 0.0;
        foreach ($this->items() as $item) {
            $total += $item['price'] * $item['quantity'];
        }

        return $total;
    }

    /** Clave única del ítem en sesión. */
    private function itemKey(int $productId, ?string $size): string
    {
        return $productId.'_'.($size ?? 'no-size');
    }
}
