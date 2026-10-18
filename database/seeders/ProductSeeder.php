<?php

namespace Database\Seeders;

use App\Models\Product;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ProductSeeder extends Seeder
{
    /**
     * Seed 10 demonstration sweaters. Images are local placeholders
     * and can be replaced by updating image_path without changing the schema.
     */
    public function run(): void
    {
        $sizes = ['S', 'M', 'L', 'XL'];

        $products = [
            [
                'name' => 'Chompa Essential Negro',
                'description' => 'Corte recto de punto compacto. Silueta limpia para uso diario.',
                'price' => '49.90',
                'stock' => 18,
            ],
            [
                'name' => 'Chompa Essential Crudo',
                'description' => 'Punto ligero en tono crudo. Cuello redondo y caída suave.',
                'price' => '49.90',
                'stock' => 16,
            ],
            [
                'name' => 'Chompa Oversize Grafito',
                'description' => 'Volumen relajado, hombros caídos y textura mate.',
                'price' => '59.90',
                'stock' => 12,
            ],
            [
                'name' => 'Chompa Cuello Alto Marfil',
                'description' => 'Cuello alto fino y cuerpo ajustado. Acabado editorial.',
                'price' => '64.90',
                'stock' => 10,
            ],
            [
                'name' => 'Chompa Lana Merino Carbón',
                'description' => 'Merino de tacto seco. Peso medio para clima templado.',
                'price' => '89.90',
                'stock' => 8,
            ],
            [
                'name' => 'Chompa Crop Estructura',
                'description' => 'Largo corto y canalé marcado. Pensada para superponer.',
                'price' => '54.90',
                'stock' => 14,
            ],
            [
                'name' => 'Chompa Relaxed Arena',
                'description' => 'Silueta amplia en tono arena. Costuras visibles y bajo recto.',
                'price' => '57.90',
                'stock' => 11,
            ],
            [
                'name' => 'Chompa Cable Knit Niebla',
                'description' => 'Trenzado clásico en gris niebla. Cuerpo cálido y elástico.',
                'price' => '69.90',
                'stock' => 9,
            ],
            [
                'name' => 'Chompa Bomber Punto',
                'description' => 'Cierre frontal y puños en canalé. Híbrido entre chompa y chaqueta.',
                'price' => '74.90',
                'stock' => 7,
            ],
            [
                'name' => 'Chompa Editorial Tinta',
                'description' => 'Negro tinta, hombro marcado y largo a la cadera.',
                'price' => '79.90',
                'stock' => 6,
            ],
        ];

        foreach ($products as $index => $product) {
            $slug = Str::slug($product['name']);

            Product::query()->updateOrCreate(
                ['slug' => $slug],
                [
                    'name' => $product['name'],
                    'description' => $product['description'],
                    'price' => $product['price'],
                    'image_path' => 'images/products/chompa-'.str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT).'.svg',
                    'category' => 'chompas',
                    'size_options' => $sizes,
                    'stock' => $product['stock'],
                    'is_active' => true,
                ]
            );
        }
    }
}
