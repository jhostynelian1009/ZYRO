<?php

namespace App\Services;

use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Models\OrderStatusHistory;
use App\Models\Payment;
use App\Models\Product;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Procesa pagos simulados de forma atómica.
 *
 * Reglas de negocio críticas:
 * - Solo pedidos en estado `approved`.
 * - Solo el dueño del pedido.
 * - Bloqueo con lockForUpdate para prevenir doble procesamiento.
 * - El monto lo toma del pedido almacenado, nunca del navegador.
 * - Un pago aprobado previo impide un nuevo pago aprobado.
 * - Si falla cualquier paso, rollback completo.
 * - Pago rechazado: guarda el intento, mantiene la orden `approved`.
 */
class SimulatedPaymentService
{
    /**
     * Ejecuta el pago simulado.
     *
     * @return array{success: bool, payment: Payment, message: string}
     *
     * @throws \RuntimeException si el estado del pedido no permite pago.
     */
    public function process(Order $order, PaymentMethod $method): array
    {
        // Pre-validación fuera de la transacción para fallo rápido.
        if ($order->status !== OrderStatus::Approved) {
            throw new \RuntimeException(
                'Este pedido no está aprobado para pago. Estado actual: '.$order->status->value
            );
        }

        return DB::transaction(function () use ($order, $method): array {
            // Re-bloquear la orden dentro de la transacción.
            $freshOrder = Order::query()
                ->where('id', $order->id)
                ->lockForUpdate()
                ->firstOrFail();

            // Doble verificación de estado tras el lock.
            if ($freshOrder->status !== OrderStatus::Approved) {
                throw new \RuntimeException(
                    'El pedido ya no está en estado aprobado.'
                );
            }

            // Verificar que no existe un pago aprobado previo.
            $existingApproved = Payment::query()
                ->where('order_id', $freshOrder->id)
                ->where('status', PaymentStatus::Approved)
                ->lockForUpdate()
                ->exists();

            if ($existingApproved) {
                throw new \RuntimeException(
                    'Este pedido ya tiene un pago aprobado registrado.'
                );
            }

            // Simular resultado del pago: 90% de éxito.
            // En producción real esto sería la respuesta de la pasarela.
            $approved = $this->simulateGatewayResponse();

            $reference = 'SIM-'.strtoupper(Str::random(12));

            $status = $approved ? PaymentStatus::Approved : PaymentStatus::Rejected;

            if ($approved) {
                // Verificar stock suficiente para todas las líneas.
                foreach ($freshOrder->items as $item) {
                    if ($item->product_id === null) {
                        continue; // Producto eliminado, snapshot preservado.
                    }

                    $product = Product::query()
                        ->where('id', $item->product_id)
                        ->lockForUpdate()
                        ->first();

                    if ($product && $product->stock < $item->quantity) {
                        // Falta stock: registrar pago rechazado y salir.
                        $payment = Payment::query()->create([
                            'order_id'     => $freshOrder->id,
                            'method'       => $method,
                            'status'       => PaymentStatus::Rejected,
                            'amount'       => $freshOrder->total,
                            'reference'    => $reference,
                            'processed_at' => now(),
                        ]);

                        return [
                            'success' => false,
                            'payment' => $payment,
                            'message' => "Stock insuficiente para «{$product->name}». El pedido sigue aprobado; puedes reintentarlo más tarde.",
                        ];
                    }
                }

                // Descontar stock.
                foreach ($freshOrder->items as $item) {
                    if ($item->product_id !== null) {
                        Product::query()
                            ->where('id', $item->product_id)
                            ->decrement('stock', $item->quantity);
                    }
                }

                // Registrar pago aprobado.
                $payment = Payment::query()->create([
                    'order_id'     => $freshOrder->id,
                    'method'       => $method,
                    'status'       => PaymentStatus::Approved,
                    'amount'       => $freshOrder->total,
                    'reference'    => $reference,
                    'processed_at' => now(),
                ]);

                // Marcar pedido como paid.
                $freshOrder->update([
                    'status'  => OrderStatus::Paid,
                    'paid_at' => now(),
                ]);

                // Historial opcional.
                if (class_exists(OrderStatusHistory::class)) {
                    OrderStatusHistory::query()->create([
                        'order_id'   => $freshOrder->id,
                        'changed_by' => $freshOrder->user_id,
                        'from_status'=> OrderStatus::Approved->value,
                        'to_status'  => OrderStatus::Paid->value,
                        'note'       => "Pago simulado aprobado. Referencia: {$reference}",
                    ]);
                }

                return [
                    'success' => true,
                    'payment' => $payment,
                    'message' => "Pago aprobado. Referencia: {$reference}",
                ];
            }

            // Pago rechazado: guardar intento, no cambiar orden ni stock.
            $payment = Payment::query()->create([
                'order_id'     => $freshOrder->id,
                'method'       => $method,
                'status'       => PaymentStatus::Rejected,
                'amount'       => $freshOrder->total,
                'reference'    => $reference,
                'processed_at' => now(),
            ]);

            return [
                'success' => false,
                'payment' => $payment,
                'message' => 'El pago fue rechazado por el simulador. Puedes reintentarlo.',
            ];
        });
    }

    /**
     * Simula la respuesta de una pasarela de pago.
     * En entorno académico: siempre true para facilitar las pruebas.
     */
    private function simulateGatewayResponse(): bool
    {
        return true;
    }
}
