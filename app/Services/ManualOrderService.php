<?php

namespace App\Services;

use App\Contracts\OmnichannelOrderServiceInterface;
use App\Enums\InteractionChannel;
use App\Enums\OrderChannel;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Enums\UserRole;
use App\Events\InventoryUpdated;
use App\Events\OrderStatusUpdated;
use App\Models\Order;
use App\Models\OrderInteraction;
use App\Models\OrderStatusHistory;
use App\Models\Payment;
use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class ManualOrderService implements OmnichannelOrderServiceInterface
{
    private function taxRate(): float
    {
        return (float) config('shop.iva_rate', 0.15);
    }

    public function createManualOrder(User $creator, array $data): Order
    {
        if (empty($data['items'])) {
            throw new \RuntimeException('El pedido debe contener al menos un producto.');
        }

        $channel = $data['channel_origin'] instanceof OrderChannel
            ? $data['channel_origin']
            : OrderChannel::tryFrom((string) $data['channel_origin']);

        if (! $channel || $channel === OrderChannel::Web) {
            throw new \InvalidArgumentException('El canal de venta manual debe ser WhatsApp o Punto Físico.');
        }

        return DB::transaction(function () use ($creator, $data, $channel): Order {
            $lines = [];
            $subtotal = 0.0;
            $updatedProducts = [];

            // 1. Validar productos, tallas y descontar inventario con bloqueo pesimista
            foreach ($data['items'] as $item) {
                $qty = (int) ($item['quantity'] ?? 1);
                if ($qty < 1) {
                    throw new \RuntimeException('La cantidad solicitada debe ser mayor o igual a 1.');
                }

                $product = Product::query()
                    ->where('id', $item['product_id'])
                    ->where('is_active', true)
                    ->lockForUpdate()
                    ->first();

                if (! $product) {
                    throw new \RuntimeException("Producto #{$item['product_id']} no encontrado o inactivo.");
                }

                $size = $item['size'] ?? null;
                if (filled($size) && ! empty($product->size_options) && ! in_array($size, $product->size_options, true)) {
                    throw new \RuntimeException("La talla «{$size}» no es válida para «{$product->name}».");
                }

                if ($product->stock < $qty) {
                    throw new \RuntimeException(
                        "Stock insuficiente para «{$product->name}». Disponible: {$product->stock}, solicitado: {$qty}."
                    );
                }

                // Descuento atómico del inventario central
                $product->decrement('stock', $qty);
                $freshStock = (int) $product->fresh()->stock;

                $updatedProducts[] = [
                    'product_id' => $product->id,
                    'name'       => $product->name,
                    'stock'      => $freshStock,
                    'is_low'     => $freshStock <= 3 && $freshStock > 0,
                    'is_out'     => $freshStock <= 0,
                ];

                $unitPrice = (float) $product->price;
                $lineTotal = round($unitPrice * $qty, 2);
                $subtotal += $lineTotal;

                $lines[] = [
                    'product_id'    => $product->id,
                    'product_name'  => $product->name,
                    'size'          => $size,
                    'customization' => $item['customization'] ?? null,
                    'quantity'      => $qty,
                    'unit_price'    => $unitPrice,
                    'line_total'    => $lineTotal,
                ];
            }

            // 2. Resolver o registrar al cliente
            $customer = $this->resolveCustomer($data);

            // 3. Cálculos de entrega e impuestos
            $subtotal        = round($subtotal, 2);
            $taxAmount       = round($subtotal * $this->taxRate(), 2);
            $deliveryMethod  = $data['delivery_method'] ?? 'pickup';
            $shippingAmount  = round(DeliveryService::costFor($deliveryMethod), 2);
            $deliveryEstimate= DeliveryService::estimateFor($deliveryMethod);
            $total           = round($subtotal + $taxAmount + $shippingAmount, 2);

            $isPaid = ! empty($data['mark_as_paid']);
            $initialStatus = $isPaid ? OrderStatus::Paid : OrderStatus::PendingApproval;

            // 4. Crear la orden unificada
            $order = Order::query()->create([
                'order_number'      => $this->generateOrderNumber(),
                'user_id'           => $customer->id,
                'created_by'        => $creator->id,
                'customer_name'     => $data['customer_name'] ?? $customer->name,
                'phone'             => $data['phone'] ?? $customer->phone ?? '0999999999',
                'address'           => $data['address'] ?? 'Entrega en mostrador',
                'city'              => $data['city'] ?? 'Guayaquil',
                'delivery_method'   => $deliveryMethod,
                'channel_origin'    => $channel,
                'delivery_estimate' => $deliveryEstimate,
                'delivery_notes'    => $data['delivery_notes'] ?? null,
                'subtotal'          => $subtotal,
                'tax_amount'        => $taxAmount,
                'shipping_amount'   => $shippingAmount,
                'total'             => $total,
                'status'            => $initialStatus,
                'paid_at'           => $isPaid ? now() : null,
                'approved_by'       => $isPaid ? $creator->id : null,
                'approved_at'       => $isPaid ? now() : null,
            ]);

            $order->items()->createMany($lines);

            // 5. Registrar pago si aplica
            $paymentMethod = $data['payment_method'] ?? 'efectivo';
            if ($isPaid) {
                Payment::query()->create([
                    'order_id'     => $order->id,
                    'method'       => $paymentMethod,
                    'status'       => PaymentStatus::Approved->value,
                    'amount'       => $total,
                    'reference'    => 'POS-'.strtoupper(Str::random(10)),
                    'processed_at' => now(),
                ]);
            }

            // 6. Registro de historial
            OrderStatusHistory::query()->create([
                'order_id'   => $order->id,
                'changed_by' => $creator->id,
                'from_status'=> $isPaid ? OrderStatus::PendingApproval : null,
                'to_status'  => $initialStatus,
                'note'       => "Venta manual registrada vía {$channel->label()} por {$creator->name}.",
            ]);

            // 7. Registro de interacción inicial opcional
            if (! empty($data['interaction_notes'])) {
                OrderInteraction::query()->create([
                    'order_id'         => $order->id,
                    'user_id'          => $creator->id,
                    'channel'          => $channel === OrderChannel::WhatsApp ? InteractionChannel::WhatsApp : InteractionChannel::PuntoFisico,
                    'interaction_type' => 'registro_venta',
                    'description'      => $data['interaction_notes'],
                    'follow_up_notes'  => $data['follow_up_notes'] ?? null,
                ]);
            }

            // 8. Emisión de eventos en tiempo real tras commit
            DB::afterCommit(function () use ($order, $updatedProducts) {
                if (! empty($updatedProducts)) {
                    SafeBroadcaster::broadcast(new InventoryUpdated($updatedProducts));
                }
            });

            return $order;
        });
    }

    /**
     * Permite continuar un pedido existente (ej: WhatsApp a Punto Físico)
     * registrando el cobro o la entrega sin duplicar la orden ni el stock.
     */
    public function continueOrder(Order $order, User $operator, array $data): Order
    {
        return DB::transaction(function () use ($order, $operator, $data): Order {
            $freshOrder = Order::query()
                ->where('id', $order->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($freshOrder->status === OrderStatus::Paid) {
                throw new \RuntimeException('Este pedido ya se encuentra pagado.');
            }

            $method = $data['payment_method'] ?? 'efectivo';
            $reference = $data['reference'] ?? 'CONT-'.strtoupper(Str::random(8));

            Payment::query()->create([
                'order_id'     => $freshOrder->id,
                'method'       => $method,
                'status'       => PaymentStatus::Approved->value,
                'amount'       => $freshOrder->total,
                'reference'    => $reference,
                'processed_at' => now(),
            ]);

            $previousStatus = $freshOrder->status->value;

            $freshOrder->update([
                'status'      => OrderStatus::Paid,
                'paid_at'     => now(),
                'approved_by' => $freshOrder->approved_by ?? $operator->id,
                'approved_at' => $freshOrder->approved_at ?? now(),
            ]);

            OrderStatusHistory::query()->create([
                'order_id'   => $freshOrder->id,
                'changed_by' => $operator->id,
                'from_status'=> $previousStatus,
                'to_status'  => OrderStatus::Paid->value,
                'note'       => "Continuación de compra completada en Punto Físico por {$operator->name}. Ref: {$reference}",
            ]);

            OrderInteraction::query()->create([
                'order_id'         => $freshOrder->id,
                'user_id'          => $operator->id,
                'channel'          => InteractionChannel::PuntoFisico,
                'interaction_type' => 'cierre_presencial',
                'description'      => "Cliente completó el pago/retiro en mostrador. Pago vía {$method}.",
                'follow_up_notes'  => $data['notes'] ?? null,
            ]);

            DB::afterCommit(function () use ($freshOrder, $previousStatus, $reference) {
                SafeBroadcaster::broadcast(new OrderStatusUpdated(
                    $freshOrder,
                    $previousStatus,
                    OrderStatus::Paid->value,
                    "Pedido completado presencialmente. Ref: {$reference}"
                ));
            });

            return $freshOrder;
        });
    }

    private function resolveCustomer(array $data): User
    {
        if (! empty($data['user_id'])) {
            $user = User::query()->find($data['user_id']);
            if ($user) {
                return $user;
            }
        }

        $email = trim((string) ($data['email'] ?? ''));
        if ($email !== '') {
            $existing = User::query()->where('email', $email)->first();
            if ($existing) {
                return $existing;
            }
        } else {
            $email = 'cliente-'.Str::random(8).'@zyro.ec';
        }

        return User::query()->create([
            'name'     => $data['customer_name'] ?? 'Cliente Mostrador',
            'email'    => $email,
            'password' => Hash::make(Str::random(16)),
            'role'     => UserRole::Customer,
        ]);
    }

    private function generateOrderNumber(): string
    {
        do {
            $number = 'ZYR-'.strtoupper(Str::random(8));
        } while (Order::query()->where('order_number', $number)->exists());

        return $number;
    }
}
