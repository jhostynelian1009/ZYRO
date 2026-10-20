<?php

namespace Database\Seeders;

use App\Models\Product;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ProductSeeder extends Seeder
{
    /**
     * Sembrar exactamente ocho diseños oficiales de chompas ZYRO:
     * 4 con tecnología (Línea Tech — $370.00)
     * 4 sin tecnología (Línea Esencial — $80.00)
     *
     * Idempotente: actualiza o crea según slug oficial.
     * Desactiva productos demo previos sin eliminarlos de la base de datos.
     */
    public function run(): void
    {
        $sizes = ['S', 'M', 'L', 'XL'];

        $products = [
            // ─── LÍNEA TECH — $370.00 ──────────────────────────────────────────
            [
                'name' => 'ZYRO Lunar Tech',
                'description' => 'Chompa inteligente en tono crudo lunar con micro-filamentos de termorregulación activa y cuello ergonómico. Tejido bio-reactivo de alta respuesta térmica.',
                'price' => '370.00',
                'image_path' => 'images/products/lunar-tech-source.png',
                'category' => 'tech',
                'stock' => 15,
            ],
            [
                'name' => 'ZYRO Eclipse Tech',
                'description' => 'Chompa tecnológica en negro carbón profundo con micro-fibras inteligentes de retención de calor dinámico y acabado mate hidrófugo.',
                'price' => '370.00',
                'image_path' => 'images/products/eclipse-tech-source.png',
                'category' => 'tech',
                'stock' => 12,
            ],
            [
                'name' => 'ZYRO Nova Tech',
                'description' => 'Tejido técnico de compresión graduada en tono grafito con acentos reflectantes y canales de disipación térmica. Diseño de vanguardia para clima extremo.',
                'price' => '370.00',
                'image_path' => 'images/products/tech/nova-tech.jpg',
                'category' => 'tech',
                'stock' => 10,
            ],
            [
                'name' => 'ZYRO Orbit Tech',
                'description' => 'Silueta cinética con panel térmico dorsal y tejido transpirable bio-reactivo en tono azul orbital profundo. Corte estilizado de alta ingeniería.',
                'price' => '370.00',
                'image_path' => 'images/products/tech/orbit-tech.jpg',
                'category' => 'tech',
                'stock' => 8,
            ],

            // ─── LÍNEA ESENCIAL — $80.00 ───────────────────────────────────────
            [
                'name' => 'ZYRO Essential',
                'description' => 'Chompa de punto compacto en tono beige arena natural. Silueta minimalista de caída impecable confeccionada en algodón orgánico de fibra larga.',
                'price' => '80.00',
                'image_path' => 'images/products/essential-beige-source.png',
                'category' => 'esencial',
                'stock' => 20,
            ],
            [
                'name' => 'ZYRO Pure',
                'description' => 'Chompa en suave tono rosa con motivos florales de cerezo (sakura) en delantero y espalda. Algodón puro de alta densidad con acabado editorial contemporáneo.',
                'price' => '80.00',
                'image_path' => 'images/products/essential/pure.jpg',
                'category' => 'esencial',
                'stock' => 18,
            ],
            [
                'name' => 'ZYRO Classic',
                'description' => 'Gris melange clásico con hombro caído y canalé reforzado en puños y pretina. Una pieza esencial de tacto ultrasuave para el día a día.',
                'price' => '80.00',
                'image_path' => 'images/products/classic-gray-source.png',
                'category' => 'esencial',
                'stock' => 25,
            ],
            [
                'name' => 'ZYRO Soft',
                'description' => 'Chompa en verde bosque profundo con estampado artístico de cordillera y luna dorada. Confort térmico natural en fibras ultrasuaves de algodón orgánico.',
                'price' => '80.00',
                'image_path' => 'images/products/essential/soft.jpg',
                'category' => 'esencial',
                'stock' => 14,
            ],
        ];

        $officialSlugs = [];

        foreach ($products as $product) {
            $slug = Str::slug($product['name']);
            $officialSlugs[] = $slug;

            Product::query()->updateOrCreate(
                ['slug' => $slug],
                [
                    'name' => $product['name'],
                    'description' => $product['description'],
                    'price' => $product['price'],
                    'image_path' => $product['image_path'],
                    'category' => $product['category'],
                    'size_options' => $sizes,
                    'stock' => $product['stock'],
                    'is_active' => true,
                ]
            );
        }

        // Desactivar de forma segura productos anteriores para que no aparezcan en catálogo
        Product::query()->whereNotIn('slug', $officialSlugs)->update(['is_active' => false]);
    }
}
