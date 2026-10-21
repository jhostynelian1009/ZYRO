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

    // ─── 6. Logística y Tiempos de Entrega ────────────────────────────────────

    /** El envío estándar utiliza la tarifa configurada ($6.00) y registra 2–5 días hábiles. */
    public function test_standard_delivery_uses_configured_tariff_and_shows_2_to_5_business_days(): void
    {
        $customer = $this->customer();
        $product  = $this->product(price: 80.00);

        $this->actingAs($customer)
            ->withSession(['cart.items' => $this->cartItems($product, qty: 1)])
            ->post(route('orders.store'), [
                'customer_name'   => 'Cliente Estándar',
                'phone'           => '0991122334',
                'address'         => 'Av. Amazonas 500',
                'city'            => 'Quito',
                'delivery_method' => 'standard',
            ])
            ->assertRedirect();

        $order = Order::query()->where('user_id', $customer->id)->latest()->firstOrFail();

        $this->assertSame('standard', $order->delivery_method);
        $this->assertSame('2–5 días hábiles', $order->delivery_estimate);
        $this->assertEqualsWithDelta(6.00, (float) $order->shipping_amount, 0.001);

        $response = $this->actingAs($customer)->get(route('orders.show', $order));
        $response->assertOk();
        $response->assertSee('Envío estándar');
        $response->assertSee('2–5 días hábiles');
        $response->assertSee('$6.00');
    }

    /** El envío exprés conserva la tarifa configurada y registra 1–2 días hábiles. */
    public function test_express_delivery_preserves_configured_rate_and_shows_1_to_2_business_days(): void
    {
        $customer = $this->customer();
        $product  = $this->product(price: 80.00);

        $this->actingAs($customer)
            ->withSession(['cart.items' => $this->cartItems($product, qty: 1)])
            ->post(route('orders.store'), [
                'customer_name'   => 'Cliente Exprés',
                'phone'           => '0991122334',
                'address'         => 'Av. Amazonas 500',
                'city'            => 'Quito',
                'delivery_method' => 'express',
            ])
            ->assertRedirect();

        $order = Order::query()->where('user_id', $customer->id)->latest()->firstOrFail();

        $this->assertSame('express', $order->delivery_method);
        $this->assertSame('1–2 días hábiles', $order->delivery_estimate);
        $this->assertEqualsWithDelta(6.00, (float) $order->shipping_amount, 0.001);

        $response = $this->actingAs($customer)->get(route('orders.show', $order));
        $response->assertOk();
        $response->assertSee('Envío exprés');
        $response->assertSee('1–2 días hábiles');
        $response->assertSee('$6.00');
    }

    /** El retiro en tienda cobra $0.00 de envío e indica disponibilidad pendiente de confirmación en sucursal Centro. */
    public function test_pickup_delivery_charges_zero_shipping_and_shows_pending_confirmation(): void
    {
        $customer = $this->customer();
        $product  = $this->product(price: 80.00);

        $this->actingAs($customer)
            ->withSession(['cart.items' => $this->cartItems($product, qty: 1)])
            ->post(route('orders.store'), [
                'customer_name'   => 'Cliente Retiro',
                'phone'           => '0991122334',
                'address'         => 'Sucursal Centro',
                'city'            => 'Quito',
                'delivery_method' => 'pickup',
            ])
            ->assertRedirect();

        $order = Order::query()->where('user_id', $customer->id)->latest()->firstOrFail();

        // Subtotal: 80.00
        // IVA 15%: 12.00
        // Envío: 0.00
        // Total: 92.00
        $this->assertSame('pickup', $order->delivery_method);
        $this->assertSame('Pendiente de confirmación', $order->delivery_estimate);
        $this->assertEqualsWithDelta(0.00, (float) $order->shipping_amount, 0.001);
        $this->assertEqualsWithDelta(92.00, (float) $order->total, 0.001);

        $response = $this->actingAs($customer)->get(route('orders.show', $order));
        $response->assertOk();
        $response->assertSee('Retiro en tienda');
        $response->assertSee('Pendiente de confirmación');
        $response->assertSee('Sucursal Centro');
        $response->assertSee('GRATIS');
    }

    /** El backend no acepta tarifas de envío manipuladas desde el navegador ni desde la sesión. */
    public function test_backend_does_not_accept_manipulated_shipping_rate(): void
    {
        $customer = $this->customer();
        $product  = $this->product(price: 80.00);

        $this->actingAs($customer)
            ->withSession(['cart.items' => $this->cartItems($product, qty: 1)])
            ->post(route('orders.store'), [
                'customer_name'   => 'Cliente Manipulador',
                'phone'           => '0991122334',
                'address'         => 'Av. Amazonas 500',
                'city'            => 'Quito',
                'delivery_method' => 'standard',
                'shipping_amount' => 0.01, // Intento de manipulación en el payload
                'total'           => 1.00,
            ])
            ->assertRedirect();

        $order = Order::query()->where('user_id', $customer->id)->latest()->firstOrFail();

        // El servidor debió calcular estrictamente $6.00 de envío y $98.00 de total
        $this->assertEqualsWithDelta(6.00, (float) $order->shipping_amount, 0.001);
        $this->assertEqualsWithDelta(98.00, (float) $order->total, 0.001);
    }

    /** La vista de checkout muestra las tres tarjetas de modalidades con plazos en días hábiles. */
    public function test_checkout_view_displays_all_three_delivery_modalities(): void
    {
        $customer = $this->customer();
        $product  = $this->product(price: 80.00);

        $response = $this->actingAs($customer)
            ->withSession(['cart.items' => $this->cartItems($product, qty: 1)])
            ->get(route('checkout.create'));

        $response->assertOk();
        $response->assertSee('Método de entrega');
        $response->assertSee('Envío estándar');
        $response->assertSee('2–5 días hábiles');
        $response->assertSee('Envío exprés');
        $response->assertSee('1–2 días hábiles');
        $response->assertSee('Retiro en tienda');
        $response->assertSee('Pendiente de confirmación');
        $response->assertSee('GRATIS');
    }

    /** El administrador visualiza la modalidad, estimación y costo de envío en el listado y detalle. */
    public function test_admin_views_delivery_modality_and_estimate_in_index_and_detail(): void
    {
        $admin    = User::factory()->admin()->create();
        $customer = $this->customer();

        $order = Order::factory()->create([
            'user_id'           => $customer->id,
            'delivery_method'   => 'pickup',
            'delivery_estimate' => 'Pendiente de confirmación',
            'shipping_amount'   => 0.00,
            'subtotal'          => 80.00,
            'tax_amount'        => 12.00,
            'total'             => 92.00,
        ]);

        // 1. Listado administrativo
        $indexResponse = $this->actingAs($admin)->get(route('admin.orders.index'));
        $indexResponse->assertOk();
        $indexResponse->assertSee('Retiro en tienda');
        $indexResponse->assertSee('Pendiente de confirmación');

        // 2. Detalle administrativo
        $showResponse = $this->actingAs($admin)->get(route('admin.orders.show', $order));
        $showResponse->assertOk();
        $showResponse->assertSee('Retiro en tienda');
        $showResponse->assertSee('Pendiente de confirmación');
        $showResponse->assertSee('Sucursal Centro');
        $showResponse->assertSee('GRATIS ($0.00)');
    }

    /** Los pedidos históricos sin campo delivery_estimate en BD se muestran coherentemente mediante fallback. */
    public function test_historical_orders_without_estimate_render_via_fallback(): void
    {
        $customer = $this->customer();

        // Pedido sin estimate explícito en BD
        $order = Order::factory()->create([
            'user_id'           => $customer->id,
            'delivery_method'   => 'standard',
            'delivery_estimate' => null,
            'shipping_amount'   => 6.00,
            'subtotal'          => 80.00,
            'tax_amount'        => 12.00,
            'total'             => 98.00,
        ]);

        $this->assertNull($order->delivery_estimate);
        $this->assertSame('2–5 días hábiles', $order->delivery_estimate_label);

        $response = $this->actingAs($customer)->get(route('orders.show', $order));
        $response->assertOk();
        $response->assertSee('Envío estándar');
        $response->assertSee('2–5 días hábiles');
    }
}
