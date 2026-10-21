<?php

namespace Database\Factories;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Order>
 */
class OrderFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $subtotal      = (float) fake()->randomFloat(2, 30, 200);
        $taxAmount     = round($subtotal * (float) config('shop.iva_rate', 0.15), 2);
        $shippingAmount = (float) config('shop.shipping', 6.00);
        $total          = round($subtotal + $taxAmount + $shippingAmount, 2);

        return [
            'order_number'    => 'ZYR-'.strtoupper(Str::random(8)),
            'user_id'         => User::factory(),
            'customer_name'   => fake()->name(),
            'phone'           => fake()->phoneNumber(),
            'address'         => fake()->streetAddress(),
            'city'            => fake()->city(),
            'delivery_method' => fake()->randomElement(['pickup', 'standard', 'express']),
            'delivery_notes'  => null,
            'subtotal'        => $subtotal,
            'tax_amount'      => $taxAmount,
            'shipping_amount' => $shippingAmount,
            'total'           => $total,
            'status'          => OrderStatus::PendingApproval->value,
        ];
    }

    public function pendingApproval(): static
    {
        return $this->state(['status' => OrderStatus::PendingApproval->value]);
    }

    public function approved(): static
    {
        return $this->state([
            'status'      => OrderStatus::Approved->value,
            'approved_at' => now(),
        ]);
    }

    public function rejected(): static
    {
        return $this->state([
            'status'      => OrderStatus::Rejected->value,
            'rejected_at' => now(),
        ]);
    }

    public function paid(): static
    {
        return $this->state([
            'status'  => OrderStatus::Paid->value,
            'paid_at' => now(),
        ]);
    }
}
