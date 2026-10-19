<?php

namespace Database\Factories;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<OrderItem>
 */
class OrderItemFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $unitPrice = (float) fake()->randomFloat(2, 30, 150);
        $quantity  = fake()->numberBetween(1, 5);

        return [
            'order_id'     => Order::factory(),
            'product_id'   => Product::factory(),
            'product_name' => fake()->words(3, true),
            'size'         => fake()->randomElement(['S', 'M', 'L', 'XL']),
            'quantity'     => $quantity,
            'unit_price'   => $unitPrice,
            'line_total'   => round($unitPrice * $quantity, 2),
        ];
    }
}
