<?php

namespace Tests\Feature;

use App\Models\Product;
use Database\Seeders\ProductSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CatalogTest extends TestCase
{
    use RefreshDatabase;

    public function test_catalog_displays_ten_seeded_products(): void
    {
        $this->seed(ProductSeeder::class);

        $response = $this->get('/productos');

        $response->assertOk();
        $response->assertSee('Chompa Essential Negro', false);
        $this->assertSame(10, Product::query()->count());
        $this->assertSame(10, Product::query()->active()->count());
    }

    public function test_search_finds_products_by_name_and_description(): void
    {
        $this->seed(ProductSeeder::class);

        $this->get('/productos?q=Merino')
            ->assertOk()
            ->assertSee('Chompa Lana Merino Carbón', false)
            ->assertDontSee('Chompa Essential Negro', false);

        $this->get('/productos?q=trenzado')
            ->assertOk()
            ->assertSee('Chompa Cable Knit Niebla', false);
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
}
