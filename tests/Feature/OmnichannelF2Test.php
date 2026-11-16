<?php

namespace Tests\Feature;

use App\Enums\InteractionChannel;
use App\Enums\OrderChannel;
use App\Enums\OrderStatus;
use App\Enums\ReturnInspectedCondition;
use App\Enums\ReturnRequestedSolution;
use App\Enums\ReturnRequestStatus;
use App\Models\Order;
use App\Models\OrderInteraction;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ReturnRequest;
use App\Models\ReturnRequestHistory;
use App\Models\User;
use App\Services\WhatsAppLinkService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class OmnichannelF2Test extends TestCase
{
    use RefreshDatabase;

    public function test_new_migrations_apply_and_rollback_cleanly(): void
    {
        // 1. Verificar que las tablas y columnas nuevas existen tras RefreshDatabase
        $this->assertTrue(\Illuminate\Support\Facades\Schema::hasColumn('orders', 'channel_origin'));
        $this->assertTrue(\Illuminate\Support\Facades\Schema::hasColumn('orders', 'created_by'));
        $this->assertTrue(\Illuminate\Support\Facades\Schema::hasTable('order_interactions'));
        $this->assertTrue(\Illuminate\Support\Facades\Schema::hasTable('return_requests'));
        $this->assertTrue(\Illuminate\Support\Facades\Schema::hasTable('return_request_histories'));

        // 2. Ejecutar rollback de las migraciones omnicanal (calculando los pasos hasta la primera migración de F2)
        $latestId = \Illuminate\Support\Facades\DB::table('migrations')->max('id');
        $f2Start = \Illuminate\Support\Facades\DB::table('migrations')
            ->where('migration', '2026_11_16_100001_add_channel_origin_and_created_by_to_orders_table')
            ->first();
        $stepsToRollback = $f2Start ? ($latestId - $f2Start->id + 1) : 4;

        $exitCode = Artisan::call('migrate:rollback', ['--step' => $stepsToRollback]);
        $this->assertSame(0, $exitCode);

        // 3. Verificar que las tablas nuevas fueron removidas y orders preservada
        $this->assertFalse(\Illuminate\Support\Facades\Schema::hasTable('return_request_histories'));
        $this->assertFalse(\Illuminate\Support\Facades\Schema::hasTable('return_requests'));
        $this->assertFalse(\Illuminate\Support\Facades\Schema::hasTable('order_interactions'));
        $this->assertFalse(\Illuminate\Support\Facades\Schema::hasColumn('orders', 'channel_origin'));
        $this->assertFalse(\Illuminate\Support\Facades\Schema::hasColumn('orders', 'created_by'));
        $this->assertTrue(\Illuminate\Support\Facades\Schema::hasTable('orders'));
        $this->assertTrue(\Illuminate\Support\Facades\Schema::hasTable('products'));

        // 4. Re-aplicar migraciones para los siguientes tests
        Artisan::call('migrate');
        $this->assertTrue(\Illuminate\Support\Facades\Schema::hasColumn('orders', 'channel_origin'));
    }

    public function test_historical_orders_default_to_web_channel(): void
    {
        $customer = User::factory()->create();

        // Crear pedido sin especificar channel_origin (emula pedido histórico)
        $order = Order::create([
            'order_number'    => 'ZYR-HIST-001',
            'user_id'         => $customer->id,
            'customer_name'   => 'Cliente Histórico',
            'phone'           => '0991234567',
            'address'         => 'Centro Guayaquil',
            'city'            => 'Guayaquil',
            'delivery_method' => 'standard',
            'subtotal'        => 100.00,
            'tax_amount'      => 15.00,
            'shipping_amount' => 6.00,
            'total'           => 121.00,
            'status'          => OrderStatus::PendingApproval,
        ]);

        $order->refresh();

        $this->assertSame(OrderChannel::Web, $order->channel_origin);
        $this->assertNull($order->created_by);
        $this->assertNull($order->creator);
    }

    public function test_orders_can_be_registered_for_assisted_and_pos_channels(): void
    {
        $admin = User::factory()->create(['role' => \App\Enums\UserRole::Admin]);
        $customer = User::factory()->create();

        // Pedido originado en WhatsApp
        $waOrder = Order::create([
            'order_number'    => 'ZYR-WA-0001',
            'user_id'         => $customer->id,
            'created_by'      => $admin->id,
            'customer_name'   => 'Cliente WhatsApp',
            'phone'           => '0998887777',
            'address'         => 'Samborondón',
            'city'            => 'Guayaquil',
            'delivery_method' => 'express',
            'channel_origin'  => OrderChannel::WhatsApp,
            'subtotal'        => 50.00,
            'tax_amount'      => 7.50,
            'shipping_amount' => 8.00,
            'total'           => 65.50,
            'status'          => OrderStatus::PendingApproval,
        ]);

        // Pedido originado en Punto Físico
        $posOrder = Order::create([
            'order_number'    => 'ZYR-POS-0001',
            'user_id'         => $customer->id,
            'created_by'      => $admin->id,
            'customer_name'   => 'Cliente Mostrador',
            'phone'           => '0991112222',
            'address'         => 'Sucursal Centro',
            'city'            => 'Guayaquil',
            'delivery_method' => 'pickup',
            'channel_origin'  => OrderChannel::PuntoFisico,
            'subtotal'        => 80.00,
            'tax_amount'      => 12.00,
            'shipping_amount' => 0.00,
            'total'           => 92.00,
            'status'          => OrderStatus::Paid,
            'paid_at'         => now(),
        ]);

        $this->assertSame(OrderChannel::WhatsApp, $waOrder->channel_origin);
        $this->assertSame($admin->id, $waOrder->creator->id);

        $this->assertSame(OrderChannel::PuntoFisico, $posOrder->channel_origin);
        $this->assertSame($admin->id, $posOrder->creator->id);

        // Probar scope de filtro por canal
        $this->assertSame(1, Order::byChannel('whatsapp')->count());
        $this->assertSame(1, Order::byChannel('punto_fisico')->count());
    }

    public function test_order_interactions_can_be_logged_and_queried(): void
    {
        $admin = User::factory()->create(['role' => \App\Enums\UserRole::Admin]);
        $customer = User::factory()->create();

        $order = Order::factory()->create(['user_id' => $customer->id]);

        $interaction = OrderInteraction::create([
            'order_id'         => $order->id,
            'user_id'          => $admin->id,
            'channel'          => InteractionChannel::WhatsApp,
            'interaction_type' => 'coordinacion_entrega',
            'description'      => 'Cliente confirmó que retirará personalmente el viernes por la tarde.',
            'follow_up_notes'  => 'Tener paquete empaquetado en caja 2.',
        ]);

        $this->assertDatabaseHas('order_interactions', [
            'id'               => $interaction->id,
            'order_id'         => $order->id,
            'interaction_type' => 'coordinacion_entrega',
        ]);

        $this->assertCount(1, $order->interactions);
        $this->assertSame($admin->name, $order->interactions->first()->user->name);
        $this->assertSame(InteractionChannel::WhatsApp, $order->interactions->first()->channel);
    }

    public function test_return_request_identifies_order_item_and_manages_inspection(): void
    {
        $customer = User::factory()->create();
        $admin = User::factory()->create(['role' => \App\Enums\UserRole::Admin]);

        $product = Product::factory()->create([
            'name'         => 'Chompa Tech Test',
            'stock'        => 10,
            'size_options' => ['S', 'M', 'L'],
        ]);

        $order = Order::factory()->create(['user_id' => $customer->id]);

        $orderItem = OrderItem::create([
            'order_id'     => $order->id,
            'product_id'   => $product->id,
            'product_name' => $product->name,
            'size'         => 'M',
            'quantity'     => 1,
            'unit_price'   => 85.00,
            'line_total'   => 85.00,
        ]);

        // Crear solicitud de cambio vinculada al order_item
        $requestNumber = ReturnRequest::generateRequestNumber();

        $returnRequest = ReturnRequest::create([
            'request_number'      => $requestNumber,
            'order_id'            => $order->id,
            'user_id'             => $customer->id,
            'order_item_id'       => $orderItem->id, // Identificación garantizada del item comprado
            'product_id'          => $product->id,
            'quantity'            => 1,
            'size'                => 'M',
            'reason'              => 'Cambio de talla por L',
            'problem_description' => 'Me queda un poco ajustada en los hombros.',
            'requested_solution'  => ReturnRequestedSolution::CambioTalla,
            'status'              => ReturnRequestStatus::Recibida,
            'inspected_condition' => ReturnInspectedCondition::Pendiente,
            'is_restocked'        => false,
        ]);

        // Registrar entrada en historial
        ReturnRequestHistory::create([
            'return_request_id' => $returnRequest->id,
            'changed_by'        => $customer->id,
            'from_status'       => 'ninguno',
            'to_status'         => ReturnRequestStatus::Recibida->value,
            'note'              => 'Solicitud inicial creada por el cliente.',
        ]);

        $this->assertDatabaseHas('return_requests', [
            'id'             => $returnRequest->id,
            'order_item_id'  => $orderItem->id,
            'status'         => 'recibida',
            'is_restocked'   => false,
        ]);

        $this->assertSame($orderItem->id, $returnRequest->orderItem->id);
        $this->assertSame($product->id, $returnRequest->product->id);
        $this->assertCount(1, $returnRequest->histories);

        // Comprobar que en estado inicial NO se altera el stock
        $product->refresh();
        $this->assertSame(10, $product->stock);

        // Operador toma el caso para inspección física
        $returnRequest->update([
            'status'      => ReturnRequestStatus::EnRevision,
            'reviewer_id' => $admin->id,
        ]);

        ReturnRequestHistory::create([
            'return_request_id' => $returnRequest->id,
            'changed_by'        => $admin->id,
            'from_status'       => ReturnRequestStatus::Recibida->value,
            'to_status'         => ReturnRequestStatus::EnRevision->value,
            'note'              => 'Prenda recibida en mostrador; se procede con examen físico.',
        ]);

        $this->assertSame(ReturnRequestStatus::EnRevision, $returnRequest->refresh()->status);
        $this->assertSame($admin->id, $returnRequest->reviewer->id);

        // Concluir inspección favorable y aprobar reingreso
        $this->assertTrue(ReturnInspectedCondition::OptimoConEtiquetas->isRestockable());
        $this->assertFalse(ReturnInspectedCondition::DefectoFabrica->isRestockable());
        $this->assertFalse(ReturnInspectedCondition::NoApto->isRestockable());

        $returnRequest->update([
            'status'              => ReturnRequestStatus::Resuelta,
            'inspected_condition' => ReturnInspectedCondition::OptimoConEtiquetas,
            'admin_decision'      => 'Prenda en estado nuevo impecable. Se aprueba reingreso a inventario.',
            'is_restocked'        => true,
            'restocked_at'        => now(),
            'resolved_at'         => now(),
        ]);

        // Simular reingreso seguro
        $product->increment('stock', $returnRequest->quantity);

        $this->assertSame(11, $product->fresh()->stock);
        $this->assertTrue($returnRequest->refresh()->is_restocked);
    }

    public function test_product_aggregated_stock_limitation_and_size_validation(): void
    {
        $product = Product::factory()->create([
            'stock'        => 5,
            'size_options' => ['S', 'M', 'L'],
        ]);

        // 1. Talla válida y stock disponible
        $this->assertTrue($product->hasStockForSize('M', 1));
        $this->assertTrue($product->hasStockForSize('S', 5));

        // 2. Cantidad excede stock total agregado
        $this->assertFalse($product->hasStockForSize('M', 6));

        // 3. Talla inválida (no presente en size_options)
        $this->assertFalse($product->hasStockForSize('XXL', 1));
    }

    public function test_whatsapp_link_service_generates_dynamic_urls(): void
    {
        config(['shop.whatsapp_number' => '+593 99 123 4567']);

        $service = new WhatsAppLinkService();

        $product = Product::factory()->create([
            'name' => 'Chompa Lunar Tech',
            'slug' => 'chompa-lunar-tech',
        ]);

        $order = Order::factory()->create([
            'order_number'  => 'ZYR-998877',
            'customer_name' => 'Juan Pérez',
            'phone'         => '0987654321',
        ]);

        // 1. Enlace de producto con talla
        $productUrl = $service->generateProductLink($product, 'M');
        $this->assertStringStartsWith('https://wa.me/593991234567?text=', $productUrl);
        $this->assertStringContainsString(rawurlencode('Chompa Lunar Tech talla M'), $productUrl);
        $this->assertStringContainsString(rawurlencode('chompa-lunar-tech'), $productUrl);

        // 2. Enlace de consulta de pedido del cliente
        $orderUrl = $service->generateOrderInquiryLink($order);
        $this->assertStringStartsWith('https://wa.me/593991234567?text=', $orderUrl);
        $this->assertStringContainsString(rawurlencode('ZYR-998877'), $orderUrl);

        // 3. Enlace para que el asesor contacte al cliente
        $agentUrl = $service->generateAgentContactLink($order);
        $this->assertStringStartsWith('https://wa.me/0987654321?text=', $agentUrl);
        $this->assertStringContainsString(rawurlencode('Juan Pérez'), $agentUrl);
    }
}
