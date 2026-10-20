<?php

namespace Tests\Feature;

use App\Models\AnalyticsEvent;
use App\Models\Product;
use App\Models\User;
use Database\Seeders\AdminUserSeeder;
use Database\Seeders\ProductSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

class AnalyticsTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $customer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(ProductSeeder::class);
        $this->seed(AdminUserSeeder::class);

        $this->admin = User::query()->where('role', 'admin')->firstOrFail();
        $this->customer = User::factory()->create(['role' => 'customer']);
    }

    public function test_admin_can_view_analytics_and_heatmaps_page(): void
    {
        $response = $this->actingAs($this->admin)->get('/admin/analitica');

        $response->assertOk();
        $response->assertSee('Analítica y Mapas de Calor', false);
        $response->assertSee('Embudo de Conversión Comercial', false);
        $response->assertSee('Rendimiento de los 8 Modelos ZYRO', false);
        $response->assertSee('Visualizador de Mapa de Calor de ZYRO', false);
    }

    public function test_customer_cannot_access_analytics_page(): void
    {
        $response = $this->actingAs($this->customer)->get('/admin/analitica');

        $response->assertForbidden();
    }

    public function test_guest_cannot_access_analytics_page(): void
    {
        $response = $this->get('/admin/analitica');

        $response->assertRedirect('/iniciar-sesion');
    }

    public function test_product_view_is_tracked_in_database(): void
    {
        $product = Product::query()->firstOrFail();

        $this->get(route('catalog.show', $product))->assertOk();

        $this->assertDatabaseHas('analytics_events', [
            'event_type' => 'product_view',
            'product_id' => $product->id,
        ]);
    }

    public function test_add_to_cart_is_tracked_in_database(): void
    {
        $product = Product::query()->firstOrFail();

        $this->actingAs($this->customer)->post(route('cart.store', $product->id), [
            'quantity' => 1,
            'size'     => 'M',
        ])->assertRedirect();

        $this->assertDatabaseHas('analytics_events', [
            'event_type' => 'add_to_cart',
            'product_id' => $product->id,
        ]);
    }

    public function test_begin_checkout_is_tracked_in_database(): void
    {
        $product = Product::query()->firstOrFail();

        // Agregar item al carrito para poder entrar al checkout
        $this->actingAs($this->customer)->post(route('cart.store', $product->id), [
            'quantity' => 1,
            'size'     => 'L',
        ]);

        $this->actingAs($this->customer)->get(route('checkout.create'))->assertOk();

        $this->assertDatabaseHas('analytics_events', [
            'event_type' => 'begin_checkout',
        ]);
    }

    public function test_interaction_beacon_endpoint_records_events(): void
    {
        $product = Product::query()->firstOrFail();

        $response = $this->postJson(route('analytics.interaction'), [
            'event'      => 'interaction_card_customize',
            'product_id' => $product->id,
            'metadata'   => ['action' => 'card_customize'],
        ]);

        $response->assertOk();
        $response->assertJson(['ok' => true]);

        $this->assertDatabaseHas('analytics_events', [
            'event_type' => 'interaction_card_customize',
            'product_id' => $product->id,
        ]);
    }

    public function test_clarity_script_renders_on_public_pages_when_project_id_configured(): void
    {
        Config::set('services.clarity.project_id', 'clarity_test_abc123');

        $response = $this->get('/productos');

        $response->assertOk();
        $response->assertSee('https://www.clarity.ms/tag/"+i', false);
        $response->assertSee('clarity_test_abc123', false);
    }

    public function test_clarity_script_does_not_render_when_project_id_is_empty(): void
    {
        Config::set('services.clarity.project_id', null);

        $response = $this->get('/productos');

        $response->assertOk();
        $response->assertDontSee('https://www.clarity.ms/tag/', false);
    }

    public function test_clarity_script_never_renders_on_sensitive_pages(): void
    {
        Config::set('services.clarity.project_id', 'clarity_test_abc123');

        // 1. Login
        $this->get('/iniciar-sesion')
            ->assertOk()
            ->assertDontSee('https://www.clarity.ms/tag/', false);

        // 2. Registro
        $this->get('/registro')
            ->assertOk()
            ->assertDontSee('https://www.clarity.ms/tag/', false);

        // 3. Panel de Administración
        $this->actingAs($this->admin)->get('/admin')
            ->assertOk()
            ->assertDontSee('https://www.clarity.ms/tag/', false);

        $this->actingAs($this->admin)->get('/admin/analitica')
            ->assertOk()
            ->assertDontSee('https://www.clarity.ms/tag/', false);
    }
}
