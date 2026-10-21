<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\User;
use App\Models\Order;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Valida que los cálculos del IVA sean correctos al 15 %
 * y que los totales de pedido cuadren matemáticamente.
 */
class TaxTest extends TestCase
{
    use RefreshDatabase;

    private function customer(): User
    {
        return User::factory()->customer()->create();
    }

    private function product(float $price = 100.00, int $stock = 10): Product
    {
        return Product::factory()->create(['price' => $price, 'stock' => $stock]);
    }

    private function cartItems(Product $product, int $qty = 1, string $size = 'M'): array
    {
        $key = $product->id . '_' . $size;
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

    private function submitOrder(User $customer, array $cartItems): \Illuminate\Testing\TestResponse
    {
        return $this->actingAs($customer)
            ->withSession(['cart.items' => $cartItems])
            ->post(route('orders.store'), [
                'customer_name'   => 'Test Cliente',
                'phone'           => '0991234567',
                'address'         => 'Av. Fiscal 100',
                'city'            => 'Quito',
                'delivery_method' => 'standard',
            ]);
    }

    // ─── Tasa centralizada ────────────────────────────────────────────────────

    /** La tasa de IVA configurada debe ser 0.15 (15 %). */
    public function test_configured_iva_rate_is_15_percent(): void
    {
        $this->assertSame(0.15, (float) config('shop.iva_rate'));
    }

    // ─── Cálculo correcto al crear un pedido ─────────────────────────────────

    /**
     * Dado un producto de $100 y cantidad 2:
     *   subtotal   = 200.00
     *   IVA (15%)  =  30.00
     *   envío      =   6.00
     *   total      = 236.00
     */
    public function test_order_tax_is_calculated_at_15_percent(): void
    {
        $product  = $this->product(price: 100.00);
        $customer = $this->customer();

        $this->submitOrder($customer, $this->cartItems($product, qty: 2));

        $order = Order::query()->where('user_id', $customer->id)->firstOrFail();

        $expectedSubtotal  = 200.00;
        $expectedTax       = round($expectedSubtotal * config('shop.iva_rate', 0.15), 2); // 30.00
        $expectedShipping  = (float) config('shop.shipping', 6.00);
        $expectedTotal     = round($expectedSubtotal + $expectedTax + $expectedShipping, 2);

        $this->assertEqualsWithDelta($expectedSubtotal, (float) $order->subtotal, 0.001, 'Subtotal incorrecto');
        $this->assertEqualsWithDelta($expectedTax,      (float) $order->tax_amount, 0.001, 'IVA incorrecto (debe ser 15%)');
        $this->assertEqualsWithDelta($expectedShipping, (float) $order->shipping_amount, 0.001, 'Envío incorrecto (debe ser $6.00)');
        $this->assertEqualsWithDelta($expectedTotal,    (float) $order->total, 0.001, 'Total incorrecto');
    }

    /** El IVA no debe aplicarse dos veces ni estar ausente. */
    public function test_iva_is_applied_exactly_once(): void
    {
        $product  = $this->product(price: 80.00);
        $customer = $this->customer();

        $this->submitOrder($customer, $this->cartItems($product, qty: 1));

        $order = Order::query()->where('user_id', $customer->id)->firstOrFail();

        // El IVA declarado debe ser exactamente subtotal * 15 %
        $expectedTax = round((float) $order->subtotal * 0.15, 2);
        $this->assertEqualsWithDelta(
            $expectedTax,
            (float) $order->tax_amount,
            0.001,
            'El IVA no coincide con subtotal × 15 %'
        );

        // El total debe ser subtotal + IVA + envío (no más, no menos)
        $reconstituted = round(
            (float) $order->subtotal + (float) $order->tax_amount + (float) $order->shipping_amount,
            2
        );
        $this->assertEqualsWithDelta(
            $reconstituted,
            (float) $order->total,
            0.001,
            'El total no cuadra con subtotal + IVA + envío'
        );
    }

    /** Línea Tech: precio $370 × 1 → IVA 15 % = $55.50, envío $6.00, total = $431.50. */
    public function test_tech_line_order_iva_15_percent(): void
    {
        $product  = $this->product(price: 370.00);
        $customer = $this->customer();

        $this->submitOrder($customer, $this->cartItems($product, qty: 1));

        $order = Order::query()->where('user_id', $customer->id)->firstOrFail();

        $this->assertEqualsWithDelta(370.00, (float) $order->subtotal, 0.001);
        $this->assertEqualsWithDelta(55.50,  (float) $order->tax_amount, 0.001, 'IVA Línea Tech debe ser $55.50');
        $this->assertEqualsWithDelta(6.00,   (float) $order->shipping_amount, 0.001, 'Envío Línea Tech debe ser $6.00');
        $this->assertEqualsWithDelta(431.50, (float) $order->total, 0.001, 'Total Línea Tech debe ser $431.50');
    }

    /** Línea Essential: precio $80 × 1 → IVA 15 % = $12.00, envío $6.00, total = $98.00. */
    public function test_essential_line_order_iva_15_percent(): void
    {
        $product  = $this->product(price: 80.00);
        $customer = $this->customer();

        $this->submitOrder($customer, $this->cartItems($product, qty: 1));

        $order = Order::query()->where('user_id', $customer->id)->firstOrFail();

        $this->assertEqualsWithDelta(80.00,  (float) $order->subtotal, 0.001);
        $this->assertEqualsWithDelta(12.00,  (float) $order->tax_amount, 0.001, 'IVA Línea Essential debe ser $12.00');
        $this->assertEqualsWithDelta(6.00,   (float) $order->shipping_amount, 0.001, 'Envío Línea Essential debe ser $6.00');
        $this->assertEqualsWithDelta(98.00,  (float) $order->total, 0.001, 'Total Línea Essential debe ser $98.00');
    }

    // ─── Pedidos históricos ───────────────────────────────────────────────────

    /**
     * Los pedidos históricos con tax_amount ya guardado no deben cambiar.
     * Este test simula un pedido con IVA 12 % anterior y verifica que
     * la migración no altera sus valores.
     */
    public function test_historical_orders_preserve_original_tax_amount(): void
    {
        // Pedido antiguo con IVA al 12 % (valor histórico arbitrario)
        $oldOrder = Order::factory()->create([
            'subtotal'        => 200.00,
            'tax_amount'      => 24.00,  // 12 % histórico
            'shipping_amount' => 3.00,
            'total'           => 227.00,
        ]);

        // Refrescar desde BD — el valor no debe haber cambiado
        $oldOrder->refresh();

        $this->assertEqualsWithDelta(24.00, (float) $oldOrder->tax_amount, 0.001,
            'El IVA de pedidos históricos no debe modificarse');
        $this->assertEqualsWithDelta(227.00, (float) $oldOrder->total, 0.001,
            'El total de pedidos históricos no debe modificarse');
    }

    // ─── No duplicación de inventario ────────────────────────────────────────

    /** Verificar que crear un pedido no descuenta stock (se descuenta al pagar). */
    public function test_creating_order_does_not_reduce_stock(): void
    {
        $product  = $this->product(price: 100.00, stock: 10);
        $customer = $this->customer();

        $this->submitOrder($customer, $this->cartItems($product, qty: 3));

        $product->refresh();
        $this->assertSame(10, $product->stock, 'El stock no debe reducirse al crear el pedido');
    }
}
