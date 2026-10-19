<?php

namespace Tests\Feature;

use App\Models\Product;
use Database\Seeders\ProductSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CatalogTest extends TestCase
{
    use RefreshDatabase;

    public function test_catalog_displays_eight_seeded_products(): void
    {
        $this->seed(ProductSeeder::class);

        $response = $this->get('/productos');

        $response->assertOk();
        $response->assertSee('ZYRO Lunar Tech', false);
        $response->assertSee('ZYRO Essential', false);
        $this->assertSame(8, Product::query()->active()->count());
    }

    public function test_filter_by_technology_shows_only_four_tech_products(): void
    {
        $this->seed(ProductSeeder::class);

        $response = $this->get('/productos?line=tech');

        $response->assertOk();
        $response->assertSee('ZYRO Lunar Tech', false);
        $response->assertSee('ZYRO Eclipse Tech', false);
        $response->assertSee('ZYRO Nova Tech', false);
        $response->assertSee('ZYRO Orbit Tech', false);
        $response->assertDontSee('ZYRO Essential', false);
        $response->assertDontSee('ZYRO Soft', false);
    }

    public function test_filter_without_technology_shows_only_four_essential_products(): void
    {
        $this->seed(ProductSeeder::class);

        $response = $this->get('/productos?line=esencial');

        $response->assertOk();
        $response->assertSee('ZYRO Essential', false);
        $response->assertSee('ZYRO Pure', false);
        $response->assertSee('ZYRO Classic', false);
        $response->assertSee('ZYRO Soft', false);
        $response->assertDontSee('ZYRO Lunar Tech', false);
        $response->assertDontSee('ZYRO Eclipse Tech', false);
    }

    public function test_search_finds_products_by_name_and_description(): void
    {
        $this->seed(ProductSeeder::class);

        $this->get('/productos?q=Lunar')
            ->assertOk()
            ->assertSee('ZYRO Lunar Tech', false)
            ->assertDontSee('ZYRO Classic', false);

        $this->get('/productos?q=arena')
            ->assertOk()
            ->assertSee('ZYRO Essential', false);
    }

    public function test_search_shows_empty_state_when_there_are_no_matches(): void
    {
        $this->seed(ProductSeeder::class);

        $this->get('/productos?q=inexistente-xyz')
            ->assertOk()
            ->assertSee('No se encontraron productos que coincidan con la búsqueda', false);
    }

    public function test_inactive_products_are_hidden_from_the_catalog(): void
    {
        Product::factory()->create([
            'name' => 'Chompa Visible',
            'slug' => 'chompa-visible',
        ]);
        Product::factory()->inactive()->create([
            'name' => 'Chompa Oculta',
            'slug' => 'chompa-oculta',
        ]);

        $this->get('/productos')
            ->assertOk()
            ->assertSee('Chompa Visible', false)
            ->assertDontSee('Chompa Oculta', false);
    }

    public function test_product_detail_page_displays_line_and_technology_attributes(): void
    {
        $this->seed(ProductSeeder::class);

        $techProduct = Product::query()->where('slug', 'zyro-lunar-tech')->firstOrFail();
        $esencialProduct = Product::query()->where('slug', 'zyro-soft')->firstOrFail();

        $this->get(route('catalog.show', $techProduct))
            ->assertOk()
            ->assertSee('ZYRO Lunar Tech', false)
            ->assertSee('Línea Tech', false)
            ->assertSee('299.99', false);

        $this->get(route('catalog.show', $esencialProduct))
            ->assertOk()
            ->assertSee('ZYRO Soft', false)
            ->assertSee('Línea Esencial', false)
            ->assertSee('79.99', false);
    }
}
