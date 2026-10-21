<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Pruebas dedicadas para la tarifa de envío de $6.00 USD,
 * cálculo de totales, consistencia entre carrito y checkout,
 * idempotencia y preservación de pedidos históricos.
 */
class ShippingTest extends TestCase
{
    use RefreshDatabase;

    private function customer(): User
    {
        return User::factory()->customer()->create();
    }

    private function product(float $price = 80.00, int $stock = 10): Product
    {
        return Product::factory()->create([
            'price' => $price,
            'stock' => $stock,
        ]);
    }

    private function cartItems(Product $product, int $qty = 1, string $size = 'M'): array
    {
        $key = $product->id.'_'.$size;
        return [
            $key => [
                'product_id' => $product->id,
                'name'       => $product->name,
                'price'      => (float) $product->price,
                'image_path' => $product->image_path,
                'size'       => $size,
                'quantity'   => $qty,
                'stock'      => $product->stock,
            ],
        ];
    }

    // ─── 1. Tarifa configurada ────────────────────────────────────────────────

    /** La tarifa de envío por defecto configurada debe ser 6.00 USD. */
    public function test_default_shipping_cost_is_6_usd(): void
    {
        $this->assertEqualsWithDelta(6.00, (float) config('shop.shipping'), 0.001);
    }

    // ─── 2. Cálculo en Backend e Inserción en Base de Datos ────────────────────

    /** Al confirmar un pedido, el servidor asigna exactamente 6.00 USD como shipping_amount. */
    public function test_order_creation_applies_6_usd_shipping(): void
    {
        $customer = $this->customer();
        $product  = $this->product(price: 80.00); // 1 chompa de $80

        $response = $this->actingAs($customer)
            ->withSession(['cart.items' => $this->cartItems($product, qty: 1)])
            ->post(route('orders.store'), [
                'customer_name'   => 'Cliente Envío',
                'phone'           => '0991122334',
                'address'         => 'Av. Amazonas 500',
                'city'            => 'Quito',
                'delivery_method' => 'standard',
            ]);

        $response->assertRedirect();

        $order = Order::query()->where('user_id', $customer->id)->firstOrFail();

        // Subtotal: 80.00
        // IVA 15%: 12.00
        // Envío: 6.00
        // Total: 98.00
        $this->assertEqualsWithDelta(80.00, (float) $order->subtotal, 0.001);
        $this->assertEqualsWithDelta(12.00, (float) $order->tax_amount, 0.001);
        $this->assertEqualsWithDelta(6.00,  (float) $order->shipping_amount, 0.001, 'El costo de envío debe ser $6.00');
        $this->assertEqualsWithDelta(98.00, (float) $order->total, 0.001, 'TOTAL debe ser 80 + 12 + 6 = 98.00');
    }

    /** Múltiples productos no multiplican el costo de envío; la tarifa es fija por pedido ($6.00). */
    public function test_shipping_is_not_charged_per_item(): void
    {
        $customer = $this->customer();
        $product1 = $this->product(price: 370.00);
        $product2 = $this->product(price: 80.00);

        $cart = array_merge(
            $this->cartItems($product1, qty: 1, size: 'L'),
            $this->cartItems($product2, qty: 2, size: 'M')
        );

        $this->actingAs($customer)
            ->withSession(['cart.items' => $cart])
            ->post(route('orders.store'), [
                'customer_name'   => 'Cliente Multi Items',
                'phone'           => '0991122334',
                'address'         => 'Av. República 100',
                'city'            => 'Guayaquil',
                'delivery_method' => 'express',
            ])
            ->assertRedirect();

        $order = Order::query()->where('user_id', $customer->id)->firstOrFail();

        // Subtotal: 370 + (80 * 2) = 530.00
        // IVA (15%): 79.50
        // Envío fijo: 6.00 (no 6 * 3)
        // Total: 615.50
        $this->assertEqualsWithDelta(530.00, (float) $order->subtotal, 0.001);
        $this->assertEqualsWithDelta(79.50,  (float) $order->tax_amount, 0.001);
        $this->assertEqualsWithDelta(6.00,   (float) $order->shipping_amount, 0.001, 'El envío debe ser $6.00 único por pedido');
        $this->assertEqualsWithDelta(615.50, (float) $order->total, 0.001);
    }

    // ─── 3. Consistencia entre Carrito y Checkout ──────────────────────────────

    /** El carrito y la vista de checkout muestran el desglose de envío de $6.00 y el mismo total estimado. */
    public function test_cart_and_checkout_display_same_shipping_and_total(): void
    {
        $customer = $this->customer();
        $product  = $this->product(price: 80.00);
        $cart     = $this->cartItems($product, qty: 1);

        // 1. Vista de Carrito
        $cartResponse = $this->actingAs($customer)
            ->withSession(['cart.items' => $cart])
            ->get(route('cart.index'));

        $cartResponse->assertOk();
        $cartResponse->assertSee('$6.00'); // Envío mostrado en el resumen
        $cartResponse->assertSee('$98.00'); // Total estimado mostrado (80 + 12 + 6)

        // 2. Vista de Checkout
        $checkoutResponse = $this->actingAs($customer)
            ->withSession(['cart.items' => $cart])
            ->get(route('checkout.create'));

        $checkoutResponse->assertOk();
        $checkoutResponse->assertSee('$6.00'); // Envío mostrado en el checkout
        $checkoutResponse->assertSee('$98.00'); // Total estimado coincidente
    }

    // ─── 4. No duplicación ante recargas o reintentos ──────────────────────────

    /** Recargar el checkout o volver a enviar con errores de validación no duplica el envío. */
    public function test_reloading_or_failed_validation_does_not_duplicate_shipping(): void
    {
        $customer = $this->customer();
        $product  = $this->product(price: 80.00);
        $cart     = $this->cartItems($product, qty: 1);

        // Intento con validación fallida (teléfono faltante)
        $this->actingAs($customer)
            ->withSession(['cart.items' => $cart])
            ->post(route('orders.store'), [
                'customer_name'   => 'Cliente',
                'phone'           => '', // Inválido
                'address'         => 'Calle 1',
                'city'            => 'Quito',
                'delivery_method' => 'standard',
            ])
            ->assertSessionHasErrors('phone');

        // El carrito sigue intacto y no hay órdenes creadas
        $this->assertSame(0, Order::query()->count());

        // Ahora reintento con datos válidos
        $this->actingAs($customer)
            ->withSession(['cart.items' => $cart])
            ->post(route('orders.store'), [
                'customer_name'   => 'Cliente',
                'phone'           => '0991234567',
                'address'         => 'Calle 1',
                'city'            => 'Quito',
                'delivery_method' => 'standard',
            ])
            ->assertRedirect();

        $this->assertSame(1, Order::query()->count());
        $order = Order::query()->first();
        $this->assertEqualsWithDelta(6.00, (float) $order->shipping_amount, 0.001);
        $this->assertEqualsWithDelta(98.00, (float) $order->total, 0.001);
    }

    // ─── 5. Preservación de Pedidos Históricos ─────────────────────────────────

    /** Los pedidos creados con la tarifa anterior (ej. $3.00) conservan exactamente su valor histórico. */
    public function test_historical_orders_preserve_previous_shipping_cost(): void
    {
        $customer = $this->customer();

        // Pedido histórico con envío de $3.00 y total histórico
        $historicalOrder = Order::factory()->create([
            'user_id'         => $customer->id,
            'subtotal'        => 80.00,
            'tax_amount'      => 9.60,
            'shipping_amount' => 3.00,
            'total'           => 92.60,
        ]);

        $historicalOrder->refresh();

        $this->assertEqualsWithDelta(3.00, (float) $historicalOrder->shipping_amount, 0.001);
        $this->assertEqualsWithDelta(92.60, (float) $historicalOrder->total, 0.001);

        // Comprobar que en la vista de detalle se muestra el costo histórico de $3.00
        $response = $this->actingAs($customer)->get(route('orders.show', $historicalOrder));
        $response->assertOk();
        $response->assertSee('$3.00');
        $response->assertSee('$92.60');
    }
}
