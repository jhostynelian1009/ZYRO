<?php

namespace App\Services;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Crea pedidos en estado pending_approval.
 *
 * Reglas:
 * - Reconsulta productos y precios desde MySQL; nunca confía en valores del carrito.
 * - Valida stock, cantidad positiva y producto activo.
 * - Calcula subtotal, impuesto (12 %) y envío en servidor.
 * - Crea orders + order_items en transacción.
 * - No descuenta stock ni registra ingresos.
 */
class CheckoutService
{
    /** Tasa de impuesto configurable (IVA 12 %). */
    private const TAX_RATE = 0.12;

    /** Costo de envío fijo demo. */
    private const SHIPPING = 3.00;

    /**
     * @param  array<string, array{product_id:int, size:?string, quantity:int}>  $cartItems
     *
     * @throws \RuntimeException si el carrito está vacío, hay stock insuficiente o producto inactivo.
     */
    public function createOrder(User $user, array $cartItems, array $deliveryData): Order
    {
        if (empty($cartItems)) {
            throw new \RuntimeException('El carrito está vacío.');
        }

        return DB::transaction(function () use ($user, $cartItems, $deliveryData): Order {
            $lines    = [];
            $subtotal = 0.0;

            foreach ($cartItems as $item) {
                $product = Product::query()
                    ->where('id', $item['product_id'])
                    ->where('is_active', true)
                    ->lockForUpdate()
                    ->first();

                if (! $product) {
                    throw new \RuntimeException("Producto #{$item['product_id']} no disponible.");
                }

                $qty = (int) $item['quantity'];

                if ($qty < 1) {
                    throw new \RuntimeException("Cantidad inválida para «{$product->name}».");
                }

                if ($product->stock < $qty) {
                    throw new \RuntimeException(
                        "Stock insuficiente para «{$product->name}». ".
                        "Disponible: {$product->stock}, solicitado: {$qty}."
                    );
                }

                $unitPrice = (float) $product->price;
                $lineTotal = round($unitPrice * $qty, 2);
                $subtotal += $lineTotal;

                $lines[] = [
                    'product_id'   => $product->id,
                    'product_name' => $product->name,
                    'size'         => $item['size'] ?? null,
                    'quantity'     => $qty,
                    'unit_price'   => $unitPrice,
                    'line_total'   => $lineTotal,
                ];
            }

            $subtotal      = round($subtotal, 2);
            $taxAmount     = round($subtotal * self::TAX_RATE, 2);
            $shippingAmount = round(self::SHIPPING, 2);
            $total          = round($subtotal + $taxAmount + $shippingAmount, 2);

            $order = Order::query()->create([
                'order_number'    => $this->generateOrderNumber(),
                'user_id'         => $user->id,
                'customer_name'   => $deliveryData['customer_name'],
                'phone'           => $deliveryData['phone'],
                'address'         => $deliveryData['address'],
                'city'            => $deliveryData['city'],
                'delivery_method' => $deliveryData['delivery_method'],
                'delivery_notes'  => $deliveryData['delivery_notes'] ?? null,
                'subtotal'        => $subtotal,
                'tax_amount'      => $taxAmount,
                'shipping_amount' => $shippingAmount,
                'total'           => $total,
                'status'          => OrderStatus::PendingApproval,
            ]);

            $order->items()->createMany($lines);

            return $order;
        });
    }

    private function generateOrderNumber(): string
    {
        do {
            $number = 'ZYR-'.strtoupper(Str::random(8));
        } while (Order::query()->where('order_number', $number)->exists());

        return $number;
    }
}
