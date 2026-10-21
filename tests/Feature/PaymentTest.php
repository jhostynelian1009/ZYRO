<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaymentTest extends TestCase
{
    use RefreshDatabase;

    // ─── Helpers ──────────────────────────────────────────────────────────────

    private function customer(): User
    {
        return User::factory()->customer()->create();
    }

    private function admin(): User
    {
        return User::factory()->admin()->create();
    }

    private function product(array $attrs = []): Product
    {
        return Product::factory()->create(array_merge(['stock' => 10, 'price' => 49.90], $attrs));
    }

    /**
     * Crea un pedido aprobado con una línea de ítem y devuelve [order, customer, product].
     */
    private function approvedOrderWithItem(int $qty = 2): array
    {
        $customer = $this->customer();
        $product  = $this->product(['stock' => 10]);

        $order = Order::factory()->approved()->create([
            'user_id'  => $customer->id,
            'subtotal' => 49.90 * $qty,
            'tax_amount' => round(49.90 * $qty * 0.15, 2),
            'shipping_amount' => 6.00,
            'total'    => round(49.90 * $qty * 1.15 + 6.00, 2),
        ]);

        OrderItem::factory()->create([
            'order_id'     => $order->id,
            'product_id'   => $product->id,
            'product_name' => $product->name,
            'size'         => 'M',
            'quantity'     => $qty,
            'unit_price'   => 49.90,
            'line_total'   => 49.90 * $qty,
        ]);

        return [$order, $customer, $product];
    }

    /**
     * Crea un pedido pendiente con una línea de ítem y devuelve [order, customer, product].
     */
    private function pendingOrderWithItem(int $qty = 2): array
    {
        $customer = $this->customer();
        $product  = $this->product(['stock' => 10]);

        $order = Order::factory()->pendingApproval()->create([
            'user_id'  => $customer->id,
            'subtotal' => 49.90 * $qty,
            'tax_amount' => round(49.90 * $qty * 0.15, 2),
            'shipping_amount' => 6.00,
            'total'    => round(49.90 * $qty * 1.15 + 6.00, 2),
        ]);

        OrderItem::factory()->create([
            'order_id'     => $order->id,
            'product_id'   => $product->id,
            'product_name' => $product->name,
            'size'         => 'M',
            'quantity'     => $qty,
            'unit_price'   => 49.90,
            'line_total'   => 49.90 * $qty,
        ]);

        return [$order, $customer, $product];
    }

    // ─── Acceso / IDOR ────────────────────────────────────────────────────────

    public function test_guest_cannot_access_payment_page(): void
    {
        [$order] = $this->approvedOrderWithItem();
        $this->get(route('orders.payment', $order))->assertRedirect(route('login'));
    }

    public function test_customer_cannot_pay_another_customer_order(): void
    {
        [$order] = $this->approvedOrderWithItem();
        $other   = $this->customer();

        $this->actingAs($other)
            ->get(route('orders.payment', $order))
            ->assertForbidden();
    }

    public function test_customer_can_access_payment_page_for_pending_order(): void
    {
        [$order, $customer] = $this->pendingOrderWithItem();

        $this->actingAs($customer)
            ->get(route('orders.payment', $order))
            ->assertOk()
            ->assertSee('Pago simulado')
            ->assertSee($order->order_number);
    }

    public function test_pending_order_can_be_paid_directly_without_admin_approval(): void
    {
        [$order, $customer, $product] = $this->pendingOrderWithItem(qty: 2);
        $stockBefore = $product->stock; // 10

        $this->actingAs($customer)
            ->post(route('orders.payment.store', $order), ['method' => 'test_card'])
            ->assertRedirect(route('orders.show', $order));

        $order->refresh();
        $product->refresh();

        // Estado del pedido actualizado directamente a Pagado
        $this->assertSame(OrderStatus::Paid, $order->status);
        $this->assertNotNull($order->paid_at);

        // Pago registrado como aprobado
        $payment = Payment::query()->where('order_id', $order->id)->first();
        $this->assertNotNull($payment);
        $this->assertSame(PaymentStatus::Approved, $payment->status);

        // Stock descontado correctamente
        $this->assertSame($stockBefore - 2, $product->stock);
    }

    public function test_customer_cannot_pay_already_paid_order(): void
    {
        [$order, $customer] = $this->approvedOrderWithItem();
        $order->update(['status' => OrderStatus::Paid, 'paid_at' => now()]);

        $this->actingAs($customer)
            ->get(route('orders.payment', $order))
            ->assertRedirect(route('orders.show', $order));

        $this->actingAs($customer)
            ->post(route('orders.payment.store', $order), ['method' => 'test_card'])
            ->assertRedirect(route('orders.show', $order));
    }

    public function test_customer_cannot_pay_rejected_order(): void
    {
        $customer = $this->customer();
        $order    = Order::factory()->rejected()->create(['user_id' => $customer->id]);

        $this->actingAs($customer)
            ->get(route('orders.payment', $order))
            ->assertRedirect(route('orders.show', $order));

        $this->actingAs($customer)
            ->post(route('orders.payment.store', $order), ['method' => 'test_card'])
            ->assertRedirect(route('orders.show', $order));
    }

    // ─── Pago exitoso ─────────────────────────────────────────────────────────

    public function test_approved_order_can_be_paid_successfully(): void
    {
        [$order, $customer, $product] = $this->approvedOrderWithItem(qty: 2);
        $stockBefore = $product->stock; // 10

        $this->actingAs($customer)
            ->post(route('orders.payment.store', $order), ['method' => 'test_card'])
            ->assertRedirect(route('orders.show', $order));

        $order->refresh();
        $product->refresh();

        // Estado del pedido
        $this->assertSame(OrderStatus::Paid, $order->status);
        $this->assertNotNull($order->paid_at);

        // Pago registrado
        $payment = Payment::query()->where('order_id', $order->id)->first();
        $this->assertNotNull($payment);
        $this->assertSame(PaymentStatus::Approved, $payment->status);

        // Stock descontado exactamente una vez
        $this->assertSame($stockBefore - 2, $product->stock);
    }

    public function test_payment_amount_comes_from_database_not_from_request(): void
    {
        [$order, $customer] = $this->approvedOrderWithItem();
        $expectedTotal      = $order->total;

        $this->actingAs($customer)
            ->post(route('orders.payment.store', $order), [
                'method' => 'test_card',
                'amount' => 0.01, // Intento de manipulación — ignorado
            ]);

        $payment = Payment::query()->where('order_id', $order->id)->first();
        $this->assertNotNull($payment);
        // El monto debe ser el del pedido, no el del request
        $this->assertSame((string) $expectedTotal, $payment->amount);
    }

    // ─── Idempotencia: no doble cobro ─────────────────────────────────────────

    public function test_paying_already_paid_order_does_not_duplicate_payment(): void
    {
        [$order, $customer, $product] = $this->approvedOrderWithItem(qty: 2);

        // Primer pago
        $this->actingAs($customer)
            ->post(route('orders.payment.store', $order), ['method' => 'test_card']);

        $stockAfterFirst = $product->fresh()->stock;
        $paymentCountAfterFirst = Payment::query()->where('order_id', $order->id)->count();

        // Segundo intento (pedido ya está paid)
        $this->actingAs($customer)
            ->post(route('orders.payment.store', $order), ['method' => 'test_card']);

        $product->refresh();

        // El stock no debe haber cambiado de nuevo
        $this->assertSame($stockAfterFirst, $product->stock);

        // No debe haber un segundo pago aprobado
        $approvedPayments = Payment::query()
            ->where('order_id', $order->id)
            ->where('status', PaymentStatus::Approved)
            ->count();
        $this->assertSame(1, $approvedPayments);
    }

    // ─── Rechazo no afecta inventario ─────────────────────────────────────────

    public function test_rejected_payment_does_not_change_stock_or_order_status(): void
    {
        // Para forzar un rechazo necesitamos un pedido approved sin stock.
        $customer = $this->customer();
        $product  = $this->product(['stock' => 0]); // Sin stock
        $order    = Order::factory()->approved()->create([
            'user_id'         => $customer->id,
            'subtotal'        => 49.90,
            'tax_amount'      => 5.99,
            'shipping_amount' => 3.00,
            'total'           => 58.89,
        ]);

        OrderItem::factory()->create([
            'order_id'     => $order->id,
            'product_id'   => $product->id,
            'product_name' => $product->name,
            'size'         => 'M',
            'quantity'     => 1,
            'unit_price'   => 49.90,
            'line_total'   => 49.90,
        ]);

        $this->actingAs($customer)
            ->post(route('orders.payment.store', $order), ['method' => 'test_card']);

        $order->refresh();
        $product->refresh();

        // El pedido sigue approved (no paid) porque falló por stock
        $this->assertSame(OrderStatus::Approved, $order->status);
        // El stock sigue en 0
        $this->assertSame(0, $product->stock);
        // Se registró un pago rechazado
        $rejectedPayment = Payment::query()
            ->where('order_id', $order->id)
            ->where('status', PaymentStatus::Rejected)
            ->first();
        $this->assertNotNull($rejectedPayment);
    }

    public function test_rejected_payment_on_pending_order_maintains_pending_status_and_allows_retry(): void
    {
        $customer = $this->customer();
        $product  = $this->product(['stock' => 0]); // Sin stock
        $order    = Order::factory()->pendingApproval()->create([
            'user_id'         => $customer->id,
            'subtotal'        => 49.90,
            'tax_amount'      => 7.49,
            'shipping_amount' => 6.00,
            'total'           => 63.39,
        ]);

        OrderItem::factory()->create([
            'order_id'     => $order->id,
            'product_id'   => $product->id,
            'product_name' => $product->name,
            'size'         => 'M',
            'quantity'     => 1,
            'unit_price'   => 49.90,
            'line_total'   => 49.90,
        ]);

        $this->actingAs($customer)
            ->post(route('orders.payment.store', $order), ['method' => 'test_card'])
            ->assertRedirect(route('orders.payment', $order));

        $order->refresh();
        $product->refresh();

        // El pedido sigue en estado pendiente (no paid)
        $this->assertSame(OrderStatus::PendingApproval, $order->status);
        $this->assertSame(0, $product->stock);

        $rejectedPayment = Payment::query()
            ->where('order_id', $order->id)
            ->where('status', PaymentStatus::Rejected)
            ->first();
        $this->assertNotNull($rejectedPayment);
    }

    // ─── Admin no puede pagar ─────────────────────────────────────────────────

    public function test_admin_cannot_access_payment_page(): void
    {
        [$order] = $this->approvedOrderWithItem();
        $admin   = $this->admin();

        // El admin no es dueño del pedido → 403
        $this->actingAs($admin)
            ->get(route('orders.payment', $order))
            ->assertForbidden();
    }
}
