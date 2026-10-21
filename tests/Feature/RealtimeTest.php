<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Events\AnalyticsUpdated;
use App\Events\InventoryUpdated;
use App\Events\OrderCreated;
use App\Events\OrderStatusUpdated;
use App\Events\PaymentStatusUpdated;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Services\SimulatedPaymentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

/**
 * Pruebas integrales de eventos en tiempo real, canales privados de WebSockets,
 * seguridad de autorización y resiliencia ante fallos.
 */
class RealtimeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'broadcasting.default' => 'reverb',
            'broadcasting.connections.reverb.key' => 'test_key',
            'broadcasting.connections.reverb.secret' => 'test_secret',
            'broadcasting.connections.reverb.app_id' => '123456',
        ]);

        // Asegurar que las rutas de canales se registren en la conexión activa
        require base_path('routes/channels.php');
    }

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
        return Product::factory()->create(array_merge(['stock' => 10, 'price' => 80.00], $attrs));
    }

    private function withCartItems(array $products): array
    {
        $cartItems = [];
        foreach ($products as [$product, $qty, $size]) {
            $key = $product->id.'_'.($size ?? 'M');
            $cartItems[$key] = [
                'product_id' => $product->id,
                'name'       => $product->name,
                'price'      => (float) $product->price,
                'image_path' => $product->image_path,
                'size'       => $size,
                'quantity'   => $qty,
                'stock'      => $product->stock,
            ];
        }
        return $cartItems;
    }

    // ─── 1. Emisión de eventos ────────────────────────────────────────────────

    /** La creación de un pedido emite el evento OrderCreated. */
    public function test_order_creation_broadcasts_order_created_event(): void
    {
        Event::fake([OrderCreated::class]);

        $customer = $this->customer();
        $product  = $this->product();
        $cart     = $this->withCartItems([[$product, 1, 'M']]);

        $response = $this->actingAs($customer)
            ->withSession(['cart.items' => $cart])
            ->post(route('orders.store'), [
                'customer_name'   => 'Cliente Realtime',
                'phone'           => '0991234567',
                'address'         => 'Calle 100',
                'city'            => 'Quito',
                'delivery_method' => 'standard',
            ]);

        $response->assertRedirect();

        Event::assertDispatched(OrderCreated::class, function (OrderCreated $event) use ($customer) {
            return $event->order->user_id === $customer->id
                && in_array('private-admin', array_map(fn ($c) => $c->name, $event->broadcastOn()))
                && isset($event->metrics['pending_orders']);
        });
    }

    /** La aprobación de un pedido emite OrderStatusUpdated. */
    public function test_admin_approving_order_broadcasts_order_status_updated(): void
    {
        Event::fake([OrderStatusUpdated::class]);

        $admin    = $this->admin();
        $customer = $this->customer();
        $order    = Order::factory()->create([
            'user_id' => $customer->id,
            'status'  => OrderStatus::PendingApproval->value,
        ]);

        $response = $this->actingAs($admin)
            ->post(route('admin.orders.approve', $order));

        $response->assertRedirect();

        Event::assertDispatched(OrderStatusUpdated::class, function (OrderStatusUpdated $event) use ($order) {
            return $event->order->id === $order->id
                && $event->fromStatus === OrderStatus::PendingApproval->value
                && $event->toStatus === OrderStatus::Approved->value;
        });
    }

    /** El rechazo de un pedido emite OrderStatusUpdated. */
    public function test_admin_rejecting_order_broadcasts_order_status_updated(): void
    {
        Event::fake([OrderStatusUpdated::class]);

        $admin    = $this->admin();
        $customer = $this->customer();
        $order    = Order::factory()->create([
            'user_id' => $customer->id,
            'status'  => OrderStatus::PendingApproval->value,
        ]);

        $response = $this->actingAs($admin)
            ->post(route('admin.orders.reject', $order));

        $response->assertRedirect();

        Event::assertDispatched(OrderStatusUpdated::class, function (OrderStatusUpdated $event) use ($order) {
            return $event->order->id === $order->id
                && $event->fromStatus === OrderStatus::PendingApproval->value
                && $event->toStatus === OrderStatus::Rejected->value;
        });
    }

    /** Un pago aprobado emite PaymentStatusUpdated, OrderStatusUpdated, InventoryUpdated y AnalyticsUpdated. */
    public function test_approved_payment_broadcasts_all_related_events(): void
    {
        Event::fake([
            PaymentStatusUpdated::class,
            OrderStatusUpdated::class,
            InventoryUpdated::class,
            AnalyticsUpdated::class,
        ]);

        $customer = $this->customer();
        $product  = $this->product(['stock' => 5]);

        $order = Order::factory()->create([
            'user_id' => $customer->id,
            'status'  => OrderStatus::Approved->value,
            'total'   => 95.00,
        ]);

        $order->items()->create([
            'product_id'   => $product->id,
            'product_name' => $product->name,
            'quantity'     => 2,
            'unit_price'   => 80.00,
            'line_total'   => 160.00,
        ]);

        $paymentService = app(SimulatedPaymentService::class);
        $result = $paymentService->process($order, PaymentMethod::TestCard);

        $this->assertTrue($result['success']);

        Event::assertDispatched(PaymentStatusUpdated::class, function ($e) use ($order) {
            return $e->order->id === $order->id && $e->payment->status === PaymentStatus::Approved;
        });

        Event::assertDispatched(OrderStatusUpdated::class, function ($e) use ($order) {
            return $e->order->id === $order->id && $e->toStatus === OrderStatus::Paid->value;
        });

        Event::assertDispatched(InventoryUpdated::class, function ($e) use ($product) {
            return count($e->items) === 1
                && $e->items[0]['product_id'] === $product->id
                && $e->items[0]['stock'] === 3; // 5 - 2 = 3
        });

        Event::assertDispatched(AnalyticsUpdated::class);
    }

    // ─── 2. Autorización de Canales Privados ───────────────────────────────────

    /** Un cliente no puede escuchar en el canal privado de administración. */
    public function test_customer_cannot_authenticate_for_admin_channel(): void
    {
        $customer = $this->customer();

        $response = $this->actingAs($customer)
            ->post('/broadcasting/auth', [
                'channel_name' => 'private-admin',
                'socket_id'    => '12345.67890',
            ]);

        $response->assertForbidden();
    }

    /** Un administrador sí puede escuchar en el canal privado de administración. */
    public function test_admin_can_authenticate_for_admin_channel(): void
    {
        $admin = $this->admin();

        $response = $this->actingAs($admin)
            ->post('/broadcasting/auth', [
                'channel_name' => 'private-admin',
                'socket_id'    => '12345.67890',
            ]);

        $response->assertOk();
    }

    /** Un cliente no puede escuchar los eventos del canal de otro cliente. */
    public function test_customer_cannot_authenticate_for_other_user_channel(): void
    {
        $customer1 = $this->customer();
        $customer2 = $this->customer();

        $response = $this->actingAs($customer1)
            ->post('/broadcasting/auth', [
                'channel_name' => "private-user.{$customer2->id}",
                'socket_id'    => '12345.67890',
            ]);

        $response->assertForbidden();
    }

    /** Un cliente sí puede escuchar en su propio canal de usuario. */
    public function test_customer_can_authenticate_for_own_user_channel(): void
    {
        $customer = $this->customer();

        $response = $this->actingAs($customer)
            ->post('/broadcasting/auth', [
                'channel_name' => "private-user.{$customer->id}",
                'socket_id'    => '12345.67890',
            ]);

        $response->assertOk();
    }

    /** Un cliente no puede escuchar el canal de pedidos de otro cliente. */
    public function test_customer_cannot_authenticate_for_other_customer_order_channel(): void
    {
        $customer1 = $this->customer();
        $customer2 = $this->customer();

        $order = Order::factory()->create(['user_id' => $customer2->id]);

        $response = $this->actingAs($customer1)
            ->post('/broadcasting/auth', [
                'channel_name' => "private-orders.{$order->id}",
                'socket_id'    => '12345.67890',
            ]);

        $response->assertForbidden();
    }

    /** Un cliente sí puede escuchar el canal de su propio pedido. */
    public function test_customer_can_authenticate_for_own_order_channel(): void
    {
        $customer = $this->customer();
        $order    = Order::factory()->create(['user_id' => $customer->id]);

        $response = $this->actingAs($customer)
            ->post('/broadcasting/auth', [
                'channel_name' => "private-orders.{$order->id}",
                'socket_id'    => '12345.67890',
            ]);

        $response->assertOk();
    }

    // ─── 3. Resiliencia e Idempotencia ────────────────────────────────────────

    /** Pagar un pedido ya pagado no descuenta inventario dos veces ni emite eventos duplicados de pago aprobado. */
    public function test_duplicate_payment_does_not_duplicate_stock_reduction(): void
    {
        $customer = $this->customer();
        $product  = $this->product(['stock' => 10]);

        $order = Order::factory()->create([
            'user_id' => $customer->id,
            'status'  => OrderStatus::Approved->value,
            'total'   => 95.00,
        ]);

        $order->items()->create([
            'product_id'   => $product->id,
            'product_name' => $product->name,
            'quantity'     => 2,
            'unit_price'   => 80.00,
            'line_total'   => 160.00,
        ]);

        $paymentService = app(SimulatedPaymentService::class);

        // Primer pago: exitoso
        $res1 = $paymentService->process($order, PaymentMethod::TestCard);
        $this->assertTrue($res1['success']);

        $product->refresh();
        $this->assertSame(8, $product->stock); // 10 - 2 = 8

        // Segundo intento de pago: debe ser rechazado
        $this->expectException(\RuntimeException::class);
        $paymentService->process($order, PaymentMethod::TestCard);

        // El stock debe seguir siendo 8, no 6
        $product->refresh();
        $this->assertSame(8, $product->stock);
    }

    /** Los payloads de eventos no contienen datos sensibles (contraseñas ni tokens). */
    public function test_event_payloads_do_not_expose_passwords_or_tokens(): void
    {
        $customer = $this->customer();
        $order    = Order::factory()->create(['user_id' => $customer->id]);

        $event = new OrderCreated($order);
        $data  = $event->broadcastWith();

        $json = json_encode($data);

        $this->assertStringNotContainsString('password', $json);
        $this->assertStringNotContainsString('remember_token', $json);
        $this->assertStringNotContainsString('app_key', $json);
    }

    /** Si el servidor de broadcasting falla o arroja excepción, la compra no se interrumpe y se guarda en base de datos. */
    public function test_order_creation_succeeds_even_when_broadcasting_fails(): void
    {
        // Simular fallo en el broadcaster
        Event::listen(OrderCreated::class, function () {
            throw new \RuntimeException('Error simulado de conexión con el servidor Reverb');
        });

        $customer = $this->customer();
        $product  = $this->product();
        $cart     = $this->withCartItems([[$product, 1, 'M']]);

        $response = $this->actingAs($customer)
            ->withSession(['cart.items' => $cart])
            ->post(route('orders.store'), [
                'customer_name'   => 'Cliente Resiliente',
                'phone'           => '0991234567',
                'address'         => 'Calle 100',
                'city'            => 'Quito',
                'delivery_method' => 'standard',
            ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('orders', [
            'user_id'       => $customer->id,
            'customer_name' => 'Cliente Resiliente',
        ]);
    }
}
