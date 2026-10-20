<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
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

    public function test_customizations_are_stored_as_distinct_cart_lines(): void
    {
        $product = $this->product(['size_options' => ['S', 'M', 'L', 'XL']]);
        $customer = $this->customer();

        foreach (['navy', 'red'] as $color) {
            $this->actingAs($customer)->post(route('cart.store', $product->id), [
                'quantity' => 1,
                'size' => 'M',
                'customized' => '1',
                'custom_color' => $color,
                'custom_text' => 'ZYRO',
                'custom_location' => 'chest',
                'price' => 0.01,
            ])->assertRedirect();
        }

        $items = session('cart.items');
        $this->assertCount(2, $items);
        $this->assertSame(['navy', 'red'], collect($items)->pluck('customization.color')->sort()->values()->all());
        $this->assertSame((float) $product->price * 2, collect($items)->sum('price'));
    }

    public function test_customizer_rejects_an_invalid_color(): void
    {
        $product = $this->product();

        $this->actingAs($this->customer())
            ->post(route('cart.store', $product->id), [
                'quantity' => 1,
                'size' => 'M',
                'customized' => '1',
                'custom_color' => 'gold',
                'custom_location' => 'chest',
            ])
            ->assertSessionHasErrors('custom_color');
    }

    public function test_customizer_rejects_images_larger_than_five_megabytes(): void
    {
        Storage::fake('local');
        $product = $this->product();

        $temp = tempnam(sys_get_temp_dir(), 'zyro_test');
        file_put_contents($temp, str_repeat('A', 5200 * 1024));
        $file = new UploadedFile($temp, 'design.png', 'image/png', null, true);

        $this->actingAs($this->customer())
            ->post(route('cart.store', $product->id), [
                'quantity' => 1, 'size' => 'M', 'customized' => '1', 'custom_color' => 'black',
                'custom_location' => 'chest', 'custom_image' => $file,
            ])
            ->assertSessionHasErrors('custom_image');

        if (file_exists($temp)) {
            @unlink($temp);
        }
    }
}
