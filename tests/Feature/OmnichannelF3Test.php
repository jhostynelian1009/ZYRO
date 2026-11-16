<?php

namespace Tests\Feature;

use App\Contracts\WhatsAppLinkGeneratorInterface;
use App\Enums\InteractionChannel;
use App\Enums\OrderChannel;
use App\Enums\OrderStatus;
use App\Enums\ReturnInspectedCondition;
use App\Enums\ReturnRequestStatus;
use App\Enums\ReturnRequestedSolution;
use App\Enums\UserRole;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ReturnRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OmnichannelF3Test extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $customer;
    protected User $otherCustomer;
    protected Product $productTech;
    protected Product $productEssential;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->admin()->create([
            'name'  => 'Admin Operador',
            'email' => 'operador@zyro.local',
        ]);

        $this->customer = User::factory()->customer()->create([
            'name'  => 'Cliente Uno',
            'email' => 'cliente1@zyro.local',
        ]);

        $this->otherCustomer = User::factory()->customer()->create([
            'name'  => 'Cliente Dos',
            'email' => 'cliente2@zyro.local',
        ]);

        $this->productTech = Product::factory()->create([
            'name'         => 'ZYRO Lunar Tech',
            'slug'         => 'zyro-lunar-tech',
            'price'        => 370.00,
            'stock'        => 5,
            'category'     => 'tech',
            'is_active'    => true,
            'size_options' => ['S', 'M', 'L', 'XL'],
        ]);

        $this->productEssential = Product::factory()->create([
            'name'         => 'ZYRO Pure Black',
            'slug'         => 'zyro-pure-black',
            'price'        => 80.00,
            'stock'        => 10,
            'category'     => 'esencial',
            'is_active'    => true,
            'size_options' => ['S', 'M', 'L'],
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────────────
    // 1. K-001: REGISTRO DE VENTAS POR CANAL (PUNTO FÍSICO Y WHATSAPP)
    // ─────────────────────────────────────────────────────────────────────────────

    public function test_admin_can_register_physical_pos_sale_with_immediate_stock_decrement(): void
    {
        $this->actingAs($this->admin);

        $response = $this->post(route('admin.manual-orders.store'), [
            'channel_origin'  => OrderChannel::PuntoFisico->value,
            'customer_name'   => 'Cliente Presencial',
            'phone'           => '0987654321',
            'email'           => 'presencial@example.com',
            'address'         => 'Mostrador Tienda Centro',
            'city'            => 'Guayaquil',
            'delivery_method' => 'pickup',
            'payment_method'  => 'efectivo',
            'mark_as_paid'    => 1,
            'items'           => [
                [
                    'product_id' => $this->productTech->id,
                    'quantity'   => 2,
                    'size'       => 'M',
                ],
            ],
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('orders', [
            'customer_name'  => 'Cliente Presencial',
            'channel_origin' => OrderChannel::PuntoFisico->value,
            'created_by'     => $this->admin->id,
            'status'         => OrderStatus::Paid->value,
            'delivery_method'=> 'pickup',
        ]);

        // Verificación de descuento atómico del inventario: 5 - 2 = 3
        $this->assertEquals(3, $this->productTech->fresh()->stock);
    }

    public function test_admin_can_register_whatsapp_sale_and_reflects_in_catalog(): void
    {
        $this->actingAs($this->admin);

        $response = $this->post(route('admin.manual-orders.store'), [
            'channel_origin'  => OrderChannel::WhatsApp->value,
            'customer_name'   => 'Cliente WhatsApp',
            'phone'           => '0991234567',
            'email'           => 'wa@example.com',
            'address'         => 'Av. 9 de Octubre 100',
            'city'            => 'Guayaquil',
            'delivery_method' => 'standard',
            'payment_method'  => 'transferencia',
            'mark_as_paid'    => 0,
            'items'           => [
                [
                    'product_id' => $this->productEssential->id,
                    'quantity'   => 3,
                    'size'       => 'L',
                ],
            ],
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('orders', [
            'customer_name'  => 'Cliente WhatsApp',
            'channel_origin' => OrderChannel::WhatsApp->value,
            'created_by'     => $this->admin->id,
            'status'         => OrderStatus::PendingApproval->value,
        ]);

        // Stock original 10 - 3 = 7
        $this->assertEquals(7, $this->productEssential->fresh()->stock);

        // Comprobación de catálogo público
        $catalogResponse = $this->get(route('catalog.show', $this->productEssential));
        $catalogResponse->assertOk();
        $catalogResponse->assertSee('7 unidades disponibles');
    }

    public function test_sale_cannot_exceed_available_stock(): void
    {
        $this->actingAs($this->admin);

        // Stock es 5, intentamos vender 6
        $response = $this->post(route('admin.manual-orders.store'), [
            'channel_origin'  => OrderChannel::PuntoFisico->value,
            'customer_name'   => 'Comprador Excesivo',
            'phone'           => '0990000000',
            'address'         => 'Tienda',
            'city'            => 'Guayaquil',
            'delivery_method' => 'pickup',
            'payment_method'  => 'efectivo',
            'items'           => [
                [
                    'product_id' => $this->productTech->id,
                    'quantity'   => 6,
                    'size'       => 'M',
                ],
            ],
        ]);

        $response->assertSessionHasErrors(['order']);
        // El stock no debe alterarse
        $this->assertEquals(5, $this->productTech->fresh()->stock);
    }

    public function test_sale_rejects_inactive_product(): void
    {
        $this->actingAs($this->admin);

        $inactive = Product::factory()->inactive()->create(['stock' => 10]);

        $response = $this->post(route('admin.manual-orders.store'), [
            'channel_origin'  => OrderChannel::PuntoFisico->value,
            'customer_name'   => 'Cliente',
            'phone'           => '0990000000',
            'address'         => 'Tienda',
            'city'            => 'Guayaquil',
            'delivery_method' => 'pickup',
            'payment_method'  => 'efectivo',
            'items'           => [
                [
                    'product_id' => $inactive->id,
                    'quantity'   => 1,
                ],
            ],
        ]);

        $response->assertSessionHasErrors(['order']);
    }

    // ─────────────────────────────────────────────────────────────────────────────
    // 2. K-002: GESTIÓN UNIFICADA DE PEDIDOS Y CONTINUIDAD DE ATENCIÓN
    // ─────────────────────────────────────────────────────────────────────────────

    public function test_orders_from_all_channels_appear_in_unified_admin_list(): void
    {
        // 1. Pedido Web
        $orderWeb = Order::factory()->create([
            'order_number'   => 'ZYR-WEB-001',
            'channel_origin' => OrderChannel::Web->value,
            'user_id'        => $this->customer->id,
            'status'         => OrderStatus::PendingApproval,
        ]);

        // 2. Pedido WhatsApp
        $orderWa = Order::factory()->create([
            'order_number'   => 'ZYR-WA-002',
            'channel_origin' => OrderChannel::WhatsApp->value,
            'created_by'     => $this->admin->id,
            'status'         => OrderStatus::Approved,
        ]);

        // 3. Venta Presencial
        $orderPos = Order::factory()->create([
            'order_number'   => 'ZYR-POS-003',
            'channel_origin' => OrderChannel::PuntoFisico->value,
            'created_by'     => $this->admin->id,
            'status'         => OrderStatus::Paid,
        ]);

        $this->actingAs($this->admin);

        // Listado unificado sin filtro
        $response = $this->get(route('admin.orders.index'));
        $response->assertOk();
        $response->assertSee('ZYR-WEB-001');
        $response->assertSee('ZYR-WA-002');
        $response->assertSee('ZYR-POS-003');

        // Filtro por canal WhatsApp
        $filterWa = $this->get(route('admin.orders.index', ['channel' => 'whatsapp']));
        $filterWa->assertOk();
        $filterWa->assertSee('ZYR-WA-002');
        $filterWa->assertDontSee('ZYR-WEB-001');
        $filterWa->assertDontSee('ZYR-POS-003');

        // Filtro por canal Punto Físico
        $filterPos = $this->get(route('admin.orders.index', ['channel' => 'punto_fisico']));
        $filterPos->assertOk();
        $filterPos->assertSee('ZYR-POS-003');
        $filterPos->assertDontSee('ZYR-WA-002');
    }

    public function test_order_continuation_preserves_identifier_without_duplicating_order_or_stock(): void
    {
        $this->actingAs($this->admin);

        // Creamos un pedido iniciado en WhatsApp que queda pendiente de cobro en tienda
        $order = Order::factory()->create([
            'order_number'   => 'ZYR-CONT-099',
            'channel_origin' => OrderChannel::WhatsApp->value,
            'created_by'     => $this->admin->id,
            'status'         => OrderStatus::PendingApproval,
            'total'          => 370.00,
        ]);

        $initialOrderCount = Order::query()->count();

        // El cliente llega a la tienda y el operador continúa el pedido registrando el pago
        $response = $this->post(route('admin.orders.continue', $order), [
            'payment_method' => 'efectivo',
            'reference'      => 'POS-REC-12345',
            'notes'          => 'Cobro presencial en sucursal centro.',
        ]);

        $response->assertRedirect();
        $order->refresh();

        // Conserva el mismo identificador único
        $this->assertEquals('ZYR-CONT-099', $order->order_number);
        $this->assertEquals(OrderStatus::Paid, $order->status);
        $this->assertEquals($initialOrderCount, Order::query()->count()); // No se duplicó el pedido
    }

    // ─────────────────────────────────────────────────────────────────────────────
    // 3. K-003: COMUNICACIÓN Y BITÁCORA DE INTERACCIONES WHATSAPP
    // ─────────────────────────────────────────────────────────────────────────────

    public function test_dynamic_whatsapp_links_are_rendered_across_modules(): void
    {
        $waService = app(WhatsAppLinkGeneratorInterface::class);

        // 1. Enlace para producto
        $productLink = $waService->generateProductLink($this->productTech, 'M');
        $this->assertStringContainsString('https://wa.me/', $productLink);
        $this->assertStringContainsString(rawurlencode($this->productTech->name), $productLink);

        // 2. Enlace en detalle del catálogo
        $detailResponse = $this->actingAs($this->customer)->get(route('catalog.show', $this->productTech));
        $detailResponse->assertOk();
        $detailResponse->assertSee('wa.me', false);

        // 3. Enlace en pedido del cliente
        $order = Order::factory()->create([
            'user_id'      => $this->customer->id,
            'order_number' => 'ZYR-ORD-WA7',
        ]);
        $orderResponse = $this->actingAs($this->customer)->get(route('orders.show', $order));
        $orderResponse->assertOk();
        $orderResponse->assertSee('wa.me', false);
        $orderResponse->assertSee('ZYR-ORD-WA7');

        // 4. Enlace en vista administrativa
        $adminOrderResponse = $this->actingAs($this->admin)->get(route('admin.orders.show', $order));
        $adminOrderResponse->assertOk();
        $adminOrderResponse->assertSee('wa.me', false);
    }

    public function test_admin_can_log_and_view_order_interactions(): void
    {
        $this->actingAs($this->admin);

        $order = Order::factory()->create([
            'order_number'   => 'ZYR-INT-001',
            'channel_origin' => OrderChannel::WhatsApp->value,
        ]);

        $response = $this->post(route('admin.orders.interactions.store', $order), [
            'channel'          => 'whatsapp',
            'interaction_type' => 'confirmacion_talla',
            'description'      => 'Cliente confirmó que prefiere talla L en lugar de M.',
            'follow_up_notes'  => 'Preparar empaque para retiro el martes.',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('order_interactions', [
            'order_id'         => $order->id,
            'user_id'          => $this->admin->id,
            'channel'          => 'whatsapp',
            'interaction_type' => 'confirmacion_talla',
            'description'      => 'Cliente confirmó que prefiere talla L en lugar de M.',
        ]);

        // Verificación en la vista de detalle
        $showResponse = $this->get(route('admin.orders.show', $order));
        $showResponse->assertOk();
        $showResponse->assertSee('Cliente confirmó que prefiere talla L');
        $showResponse->assertSee($this->admin->name);
    }

    // ─────────────────────────────────────────────────────────────────────────────
    // 4. K-004: DEVOLUCIONES, CUARENTENA Y REINCORPORACIÓN CONTROLADA
    // ─────────────────────────────────────────────────────────────────────────────

    public function test_customer_can_create_return_request_and_item_enters_quarantine(): void
    {
        $order = Order::factory()->create([
            'user_id' => $this->customer->id,
            'status'  => OrderStatus::Paid,
        ]);

        $item = OrderItem::factory()->create([
            'order_id'   => $order->id,
            'product_id' => $this->productTech->id,
            'quantity'   => 1,
            'size'       => 'M',
        ]);

        $this->actingAs($this->customer);

        $response = $this->post(route('customer.returns.store', $order), [
            'order_item_id'       => $item->id,
            'quantity'            => 1,
            'reason'              => 'Cambio de talla por ajuste holgado',
            'problem_description' => 'La prenda queda ligeramente más ancha de lo esperado en hombros.',
            'requested_solution'  => ReturnRequestedSolution::CambioTalla->value,
        ]);

        $response->assertRedirect(route('customer.returns.index'));
        $this->assertDatabaseHas('return_requests', [
            'order_id'            => $order->id,
            'order_item_id'       => $item->id,
            'product_id'          => $this->productTech->id,
            'status'              => ReturnRequestStatus::Recibida->value,
            'is_restocked'        => false, // Cuarentena: NO reingresa a stock
        ]);

        // Stock original sigue siendo 5 (no aumentó)
        $this->assertEquals(5, $this->productTech->fresh()->stock);
    }

    public function test_admin_inspects_and_resolves_return_with_authorized_restock(): void
    {
        $order = Order::factory()->create([
            'user_id' => $this->customer->id,
            'status'  => OrderStatus::Paid,
        ]);

        $item = OrderItem::factory()->create([
            'order_id'   => $order->id,
            'product_id' => $this->productTech->id,
            'quantity'   => 1,
        ]);

        $return = ReturnRequest::query()->create([
            'request_number'      => ReturnRequest::generateRequestNumber(),
            'order_id'            => $order->id,
            'user_id'             => $this->customer->id,
            'order_item_id'       => $item->id,
            'product_id'          => $this->productTech->id,
            'quantity'            => 1,
            'size'                => 'M',
            'reason'              => 'Talla',
            'problem_description' => 'Talla no adecuada',
            'requested_solution'  => ReturnRequestedSolution::CambioTalla,
            'status'              => ReturnRequestStatus::Recibida,
            'is_restocked'        => false,
        ]);

        $this->actingAs($this->admin);

        // 1. Iniciar inspección física
        $inspectResponse = $this->post(route('admin.returns.start-inspection', $return), [
            'note' => 'Prenda recibida con etiquetas intactas para evaluación.',
        ]);
        $inspectResponse->assertRedirect();
        $this->assertEquals(ReturnRequestStatus::EnRevision, $return->fresh()->status);
        $this->assertEquals($this->admin->id, $return->fresh()->reviewer_id);

        // 2. Dictamen con autorización de reincorporación a stock
        $resolveResponse = $this->post(route('admin.returns.resolve', $return), [
            'status'              => 'resuelta',
            'inspected_condition' => ReturnInspectedCondition::OptimoConEtiquetas->value,
            'admin_decision'      => 'Aprobado cambio. Prenda en estado impecable reingresada a inventario.',
            'should_restock'      => 1,
        ]);

        $resolveResponse->assertRedirect();
        $return->refresh();

        $this->assertEquals(ReturnRequestStatus::Resuelta, $return->status);
        $this->assertTrue($return->is_restocked);

        // El stock vendible ahora incrementa en 1 (5 + 1 = 6)
        $this->assertEquals(6, $this->productTech->fresh()->stock);

        // Intentar doble reincorporación debe ser prevenido
        $this->expectException(\RuntimeException::class);
        app(\App\Contracts\ReturnRequestServiceInterface::class)->restockReturnedItem($return);
    }

    // ─────────────────────────────────────────────────────────────────────────────
    // 5. SEGURIDAD Y RBAC
    // ─────────────────────────────────────────────────────────────────────────────

    public function test_customer_cannot_view_or_return_another_customers_order(): void
    {
        $orderOther = Order::factory()->create([
            'user_id' => $this->otherCustomer->id,
        ]);

        $this->actingAs($this->customer);

        // No puede ver pedido ajeno
        $viewResponse = $this->get(route('orders.show', $orderOther));
        $viewResponse->assertForbidden();

        // No puede solicitar devolución sobre pedido ajeno
        $createReturnResponse = $this->get(route('customer.returns.create', $orderOther));
        $createReturnResponse->assertForbidden();
    }

    public function test_customer_cannot_access_admin_omnichannel_routes(): void
    {
        $this->actingAs($this->customer);

        $this->get(route('admin.orders.create'))->assertForbidden();
        $this->get(route('admin.returns.index'))->assertForbidden();
        $this->post(route('admin.manual-orders.store'), [])->assertForbidden();
    }
}
