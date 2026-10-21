<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderTest extends TestCase
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
     * Pone ítems en la sesión del carrito y devuelve el customer autenticado.
     */
    private function withCartItems(array $products): array
    {
        $cartItems = [];
        foreach ($products as [$product, $qty, $size]) {
            $key = $product->id.'_'.($size ?? 'no-size');
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

    // ─── Acceso ───────────────────────────────────────────────────────────────

    public function test_guest_cannot_access_checkout(): void
    {
        $this->get(route('checkout.create'))->assertRedirect(route('login'));
    }

    public function test_guest_cannot_submit_order(): void
    {
        $this->post(route('orders.store'), [])->assertRedirect(route('login'));
    }

    public function test_customer_cannot_access_admin_orders(): void
    {
        $this->actingAs($this->customer())
            ->get(route('admin.orders.index'))
            ->assertForbidden();
    }

    // ─── Crear pedido ─────────────────────────────────────────────────────────

    public function test_customer_can_create_order_and_it_becomes_pending_approval(): void
    {
        $product  = $this->product(['stock' => 5]);
        $customer = $this->customer();

        $cartItems = $this->withCartItems([[$product, 2, 'M']]);

        $response = $this->actingAs($customer)
            ->withSession(['cart.items' => $cartItems])
            ->post(route('orders.store'), [
                'customer_name'   => 'Cliente Test',
                'phone'           => '0991234567',
                'address'         => 'Av. Principal 123',
                'city'            => 'Quito',
                'delivery_method' => 'standard',
                'delivery_notes'  => '',
            ]);

        $order = Order::query()->where('user_id', $customer->id)->first();
        $this->assertNotNull($order);
        $response->assertRedirect(route('orders.payment', $order));

        $this->assertSame(OrderStatus::PendingApproval, $order->status);
        $this->assertCount(1, $order->items);
        $this->assertSame(2, $order->items->first()->quantity);
    }

    public function test_checkout_persists_a_customization_snapshot_without_changing_price(): void
    {
        $product = $this->product(['price' => 370.00]);
        $customer = $this->customer();
        $items = $this->withCartItems([[$product, 1, 'L']]);
        $items[array_key_first($items)]['customization'] = [
            'color' => 'navy', 'text' => 'ANA', 'location' => 'chest',
        ];
        $items[array_key_first($items)]['price'] = 0.01;

        $this->actingAs($customer)->withSession(['cart.items' => $items])->post(route('orders.store'), [
            'customer_name' => 'Cliente Test', 'phone' => '0991234567', 'address' => 'Av. Principal 123',
            'city' => 'Quito', 'delivery_method' => 'standard',
        ])->assertRedirect();

        $item = Order::query()->where('user_id', $customer->id)->firstOrFail()->items()->firstOrFail();
        $this->assertSame('370.00', $item->unit_price);
        $this->assertSame('navy', $item->customization['color']);
        $this->assertSame('ANA', $item->customization['text']);
    }

    public function test_server_recalculates_price_ignoring_cart_values(): void
    {
        // El precio en BD es 49.90; el carrito podría tener cualquier valor
        // pero el CheckoutService siempre reconsulta desde MySQL.
        $product  = $this->product(['price' => 49.90, 'stock' => 10]);
        $customer = $this->customer();

        $cartItems = $this->withCartItems([[$product, 2, 'M']]);
        // Manipular el precio en la sesión (debería ignorarse)
        $key = $product->id.'_M';
        $cartItems[$key]['price'] = 0.01;

        $this->actingAs($customer)
            ->withSession(['cart.items' => $cartItems])
            ->post(route('orders.store'), [
                'customer_name'   => 'Test',
                'phone'           => '099',
                'address'         => 'Calle 1',
                'city'            => 'Quito',
                'delivery_method' => 'pickup',
            ]);

        $order = Order::query()->where('user_id', $customer->id)->first();
        // unit_price debe ser el de BD (49.90), no el manipulado (0.01)
        $this->assertSame('49.90', $order->items->first()->unit_price);
    }

    public function test_order_with_empty_cart_is_rejected(): void
    {
        $customer = $this->customer();

        $this->actingAs($customer)
            ->withSession(['cart.items' => []])
            ->post(route('orders.store'), [
                'customer_name'   => 'Test',
                'phone'           => '099',
                'address'         => 'Calle 1',
                'city'            => 'Quito',
                'delivery_method' => 'pickup',
            ])
            ->assertRedirect(route('cart.index'));
    }

    public function test_order_with_insufficient_stock_is_rejected(): void
    {
        $product  = $this->product(['stock' => 1]);
        $customer = $this->customer();

        // Carrito pide 5 pero stock es 1
        $cartItems = $this->withCartItems([[$product, 5, 'M']]);

        $this->actingAs($customer)
            ->withSession(['cart.items' => $cartItems])
            ->post(route('orders.store'), [
                'customer_name'   => 'Test',
                'phone'           => '099',
                'address'         => 'Calle 1',
                'city'            => 'Quito',
                'delivery_method' => 'pickup',
            ])
            ->assertSessionHasErrors('checkout');

        $this->assertSame(0, Order::query()->count());
    }

    public function test_cart_is_cleared_after_successful_order(): void
    {
        $product  = $this->product();
        $customer = $this->customer();
        $cartItems = $this->withCartItems([[$product, 1, 'S']]);

        $this->actingAs($customer)
            ->withSession(['cart.items' => $cartItems])
            ->post(route('orders.store'), [
                'customer_name'   => 'Test',
                'phone'           => '099',
                'address'         => 'Calle 1',
                'city'            => 'Quito',
                'delivery_method' => 'pickup',
            ]);

        $this->assertEmpty(session('cart.items', []));
    }

    public function test_customer_can_see_own_orders(): void
    {
        $customer = $this->customer();
        Order::factory()->create(['user_id' => $customer->id, 'status' => OrderStatus::PendingApproval->value, 'order_number' => 'ZYR-TEST001', 'subtotal' => 49.90, 'tax_amount' => 5.99, 'shipping_amount' => 3.00, 'total' => 58.89]);

        $this->actingAs($customer)
            ->get(route('orders.index'))
            ->assertOk()
            ->assertSee('ZYR-TEST001', false);
    }

    public function test_customer_cannot_see_other_customer_orders(): void
    {
        $customer1 = $this->customer();
        $customer2 = $this->customer();
        $order     = Order::factory()->create([
            'user_id'         => $customer2->id,
            'status'          => OrderStatus::PendingApproval->value,
            'order_number'    => 'ZYR-NOTMINE',
            'subtotal'        => 49.90,
            'tax_amount'      => 5.99,
            'shipping_amount' => 3.00,
            'total'           => 58.89,
        ]);

        $this->actingAs($customer1)
            ->get(route('orders.show', $order))
            ->assertForbidden();
    }

    // ─── Admin: Aprobar / Rechazar ────────────────────────────────────────────

    public function test_admin_can_approve_pending_order(): void
    {
        $admin    = $this->admin();
        $customer = $this->customer();
        $order    = Order::factory()->create([
            'user_id'         => $customer->id,
            'status'          => OrderStatus::PendingApproval->value,
            'order_number'    => 'ZYR-APP01',
            'subtotal'        => 49.90,
            'tax_amount'      => 5.99,
            'shipping_amount' => 3.00,
            'total'           => 58.89,
        ]);

        $this->actingAs($admin)
            ->post(route('admin.orders.approve', $order))
            ->assertRedirect();

        $order->refresh();
        $this->assertSame(OrderStatus::Approved, $order->status);
        $this->assertSame($admin->id, $order->approved_by);
        $this->assertNotNull($order->approved_at);
    }

    public function test_admin_can_reject_pending_order(): void
    {
        $admin    = $this->admin();
        $customer = $this->customer();
        $order    = Order::factory()->create([
            'user_id'         => $customer->id,
            'status'          => OrderStatus::PendingApproval->value,
            'order_number'    => 'ZYR-REJ01',
            'subtotal'        => 49.90,
            'tax_amount'      => 5.99,
            'shipping_amount' => 3.00,
            'total'           => 58.89,
        ]);

        $this->actingAs($admin)
            ->post(route('admin.orders.reject', $order))
            ->assertRedirect();

        $order->refresh();
        $this->assertSame(OrderStatus::Rejected, $order->status);
        $this->assertSame($admin->id, $order->rejected_by);
    }

    public function test_admin_cannot_approve_already_approved_order(): void
    {
        $admin = $this->admin();
        $order = Order::factory()->create([
            'user_id'         => $this->customer()->id,
            'status'          => OrderStatus::Approved->value,
            'order_number'    => 'ZYR-DUP01',
            'subtotal'        => 49.90,
            'tax_amount'      => 5.99,
            'shipping_amount' => 3.00,
            'total'           => 58.89,
        ]);

        $this->actingAs($admin)
            ->post(route('admin.orders.approve', $order))
            ->assertSessionHasErrors('order');

        $order->refresh();
        $this->assertSame(OrderStatus::Approved, $order->status); // No cambió
    }

    public function test_customer_cannot_approve_orders(): void
    {
        $order = Order::factory()->create([
            'user_id'         => $this->customer()->id,
            'status'          => OrderStatus::PendingApproval->value,
            'order_number'    => 'ZYR-CUST01',
            'subtotal'        => 49.90,
            'tax_amount'      => 5.99,
            'shipping_amount' => 3.00,
            'total'           => 58.89,
        ]);

        $this->actingAs($this->customer())
            ->post(route('admin.orders.approve', $order))
            ->assertForbidden();
    }
}
