<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\Product;
use App\Models\User;
use Database\Seeders\ProductSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->admin()->create();
    }

    private function customer(): User
    {
        return User::factory()->customer()->create();
    }

    public function test_admin_can_view_dashboard_with_real_metrics(): void
    {
        $this->seed(ProductSeeder::class);
        $admin = $this->admin();
        $customer = $this->customer();

        // Crear una orden pendiente
        Order::factory()->create([
            'user_id' => $customer->id,
            'status' => OrderStatus::PendingApproval,
            'order_number' => 'ZYR-DASH-PEND',
            'total' => 150.00,
        ]);

        // Crear una orden pagada con su pago aprobado
        $paidOrder = Order::factory()->create([
            'user_id' => $customer->id,
            'status' => OrderStatus::Paid,
            'order_number' => 'ZYR-DASH-PAID',
            'total' => 299.99,
        ]);

        Payment::create([
            'order_id' => $paidOrder->id,
            'method' => PaymentMethod::TestCard,
            'status' => PaymentStatus::Approved,
            'amount' => 299.99,
            'reference' => 'PAY-SIM-12345',
            'processed_at' => now(),
        ]);

        $response = $this->actingAs($admin)->get('/admin');

        $response->assertOk();
        $response->assertSee('Panel administrativo', false);
        $response->assertSee('Dashboard', false);
        $response->assertSee('Resumen operativo de ZYRO.', false);
        $response->assertSee('Pedidos pendientes de aprobación', false);
        $response->assertSee('Pedidos pagados', false);
        $response->assertSee('Ingresos simulados', false);
        $response->assertSee('Productos activos', false);
        $response->assertSee('ZYR-DASH-PEND', false);
        $response->assertSee('ZYR-DASH-PAID', false);
    }

    public function test_admin_can_poll_updates_json_endpoint(): void
    {
        $admin = $this->admin();
        $customer = $this->customer();

        $paidOrder = Order::factory()->create([
            'user_id' => $customer->id,
            'status' => OrderStatus::Paid,
            'total' => 79.99,
        ]);

        Payment::create([
            'order_id' => $paidOrder->id,
            'method' => PaymentMethod::TestCard,
            'status' => PaymentStatus::Approved,
            'amount' => 79.99,
            'reference' => 'PAY-UPD-001',
            'processed_at' => now(),
        ]);

        $response = $this->actingAs($admin)->getJson('/admin/actualizaciones');

        $response->assertOk();
        $response->assertJsonStructure([
            'pending_orders',
            'paid_orders',
            'simulated_revenue',
            'total_products',
            'low_stock_products',
        ]);
        $response->assertJson([
            'paid_orders' => 1,
            'simulated_revenue' => '79.99',
        ]);
    }

    public function test_admin_can_view_orders_index_and_filter_by_status(): void
    {
        $admin = $this->admin();
        $customer = $this->customer();

        $pending = Order::factory()->create([
            'user_id' => $customer->id,
            'status' => OrderStatus::PendingApproval,
            'order_number' => 'ZYR-ORD-PEND01',
        ]);

        $approved = Order::factory()->create([
            'user_id' => $customer->id,
            'status' => OrderStatus::Approved,
            'order_number' => 'ZYR-ORD-APPR02',
        ]);

        // Listar todos
        $this->actingAs($admin)
            ->get('/admin/pedidos')
            ->assertOk()
            ->assertSee('ZYR-ORD-PEND01', false)
            ->assertSee('ZYR-ORD-APPR02', false);

        // Filtrar por pendiente
        $this->actingAs($admin)
            ->get('/admin/pedidos?status=pending_approval')
            ->assertOk()
            ->assertSee('ZYR-ORD-PEND01', false)
            ->assertDontSee('ZYR-ORD-APPR02', false);

        // Filtrar por aprobado
        $this->actingAs($admin)
            ->get('/admin/pedidos?status=approved')
            ->assertOk()
            ->assertSee('ZYR-ORD-APPR02', false)
            ->assertDontSee('ZYR-ORD-PEND01', false);
    }

    public function test_admin_can_view_order_detail_page(): void
    {
        $this->seed(ProductSeeder::class);
        $admin = $this->admin();
        $customer = $this->customer();
        $product = Product::query()->where('slug', 'zyro-lunar-tech')->first();

        $order = Order::factory()->create([
            'user_id' => $customer->id,
            'status' => OrderStatus::PendingApproval,
            'order_number' => 'ZYR-DETAIL-001',
            'subtotal' => 299.99,
            'tax_amount' => 36.00,
            'shipping_amount' => 0.00,
            'total' => 335.99,
            'customer_name' => 'Cliente Detalle',
            'address' => 'Av. Principal 123',
            'city' => 'Quito',
        ]);

        OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'product_name' => $product->name,
            'size' => 'M',
            'quantity' => 1,
            'unit_price' => 299.99,
            'line_total' => 299.99,
        ]);

        $response = $this->actingAs($admin)->get("/admin/pedidos/{$order->id}");

        $response->assertOk();
        $response->assertSee('ZYR-DETAIL-001', false);
        $response->assertSee('Cliente Detalle', false);
        $response->assertSee('Av. Principal 123', false);
        $response->assertSee('ZYRO Lunar Tech', false);
        $response->assertSee('335.99', false);
        $response->assertSee('Aprobar Pedido', false);
        $response->assertSee('Rechazar Pedido', false);
    }

    public function test_admin_can_view_inventory_page_and_filter_by_line(): void
    {
        $this->seed(ProductSeeder::class);
        $admin = $this->admin();

        // Inventario completo
        $response = $this->actingAs($admin)->get('/admin/inventario');
        $response->assertOk();
        $response->assertSee('Inventario de Productos', false);
        $response->assertSee('ZYRO Lunar Tech', false);
        $response->assertSee('ZYRO Essential', false);

        // Filtrar por Línea Tech
        $responseTech = $this->actingAs($admin)->get('/admin/inventario?line=tech');
        $responseTech->assertOk();
        $responseTech->assertSee('ZYRO Lunar Tech', false);
        $responseTech->assertDontSee('ZYRO Essential', false);

        // Filtrar por Línea Esencial
        $responseEsencial = $this->actingAs($admin)->get('/admin/inventario?line=esencial');
        $responseEsencial->assertOk();
        $responseEsencial->assertSee('ZYRO Essential', false);
        $responseEsencial->assertDontSee('ZYRO Lunar Tech', false);
    }

    public function test_customer_cannot_access_any_admin_endpoint(): void
    {
        $customer = $this->customer();

        $this->actingAs($customer)->get('/admin')->assertForbidden();
        $this->actingAs($customer)->get('/admin/pedidos')->assertForbidden();
        $this->actingAs($customer)->get('/admin/inventario')->assertForbidden();
        $this->actingAs($customer)->get('/admin/actualizaciones')->assertForbidden();
    }
}
