<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CartTest extends TestCase
{
    use RefreshDatabase;

    // ─── Helpers ──────────────────────────────────────────────────────────────

    private function customer(): User
    {
        return User::factory()->customer()->create();
    }

    private function product(array $attrs = []): Product
    {
        return Product::factory()->create(array_merge(['stock' => 10, 'is_active' => true], $attrs));
    }

    // ─── Tests ────────────────────────────────────────────────────────────────

    public function test_guest_cannot_view_cart(): void
    {
        $this->get(route('cart.index'))->assertRedirect(route('login'));
    }

    public function test_authenticated_customer_can_view_empty_cart(): void
    {
        $this->actingAs($this->customer())
            ->get(route('cart.index'))
            ->assertOk()
            ->assertSee('El carrito está vacío', false);
    }

    public function test_customer_can_add_product_to_cart(): void
    {
        $product = $this->product();

        $this->actingAs($this->customer())
            ->post(route('cart.store', $product->id), [
                'quantity' => 2,
                'size'     => 'M',
            ])
            ->assertRedirect();

        $this->assertSame(2, collect(session('cart.items'))->sum('quantity'));
    }

    public function test_adding_product_exceeding_stock_is_rejected(): void
    {
        $product = $this->product(['stock' => 3]);

        $this->actingAs($this->customer())
            ->post(route('cart.store', $product->id), [
                'quantity' => 10,
                'size'     => 'L',
            ])
            ->assertSessionHasErrors('cart');
    }

    public function test_quantity_zero_or_negative_is_rejected(): void
    {
        $product = $this->product();

        $this->actingAs($this->customer())
            ->post(route('cart.store', $product->id), [
                'quantity' => 0,
                'size'     => 'S',
            ])
            ->assertSessionHasErrors('quantity');
    }

    public function test_inactive_product_cannot_be_added_to_cart(): void
    {
        $product = $this->product(['is_active' => false]);

        $this->actingAs($this->customer())
            ->post(route('cart.store', $product->id), [
                'quantity' => 1,
            ])
            ->assertSessionHasErrors('cart');
    }

    public function test_customer_can_update_cart_item_quantity(): void
    {
        $product  = $this->product(['stock' => 10]);
        $customer = $this->customer();

        // Agregar primero
        $this->actingAs($customer)
            ->post(route('cart.store', $product->id), ['quantity' => 2, 'size' => 'M']);

        // Actualizar a 5
        $this->actingAs($customer)
            ->patch(route('cart.update', $product->id), ['quantity' => 5, 'size' => 'M'])
            ->assertRedirect();

        $items = session('cart.items');
        $this->assertSame(5, collect($items)->sum('quantity'));
    }

    public function test_customer_can_remove_item_from_cart(): void
    {
        $product  = $this->product();
        $customer = $this->customer();

        $this->actingAs($customer)
            ->post(route('cart.store', $product->id), ['quantity' => 1, 'size' => 'M']);

        $this->actingAs($customer)
            ->delete(route('cart.destroy', $product->id).'?size=M')
            ->assertRedirect();

        $this->assertEmpty(session('cart.items', []));
    }

    public function test_cart_price_is_read_from_database_not_from_request(): void
    {
        // El CartController nunca acepta el precio del request; lo lee de BD siempre.
        // Verificamos que el ítem en sesión tiene el precio de BD, no uno enviado.
        $product  = $this->product(['price' => 50.00]);
        $customer = $this->customer();

        $this->actingAs($customer)
            ->post(route('cart.store', $product->id), [
                'quantity' => 1,
                'size'     => 'M',
                'price'    => 0.01, // Intento de manipulación ignorado
            ]);

        $items = session('cart.items');
        $item  = collect($items)->first();
        $this->assertSame(50.0, (float) $item['price']);
    }
}
