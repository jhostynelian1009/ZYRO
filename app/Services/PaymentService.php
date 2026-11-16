<?php

namespace App\Services;

use App\Contracts\PaymentGatewayInterface;
use App\DTOs\PaymentProcessResult;
use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Events\AnalyticsUpdated;
use App\Events\InventoryUpdated;
use App\Events\OrderStatusUpdated;
use App\Events\PaymentStatusUpdated;
use App\Models\Order;
use App\Models\OrderStatusHistory;
use App\Models\Payment;
use App\Models\PaymentWebhook;
use App\Models\Product;
use App\Payments\PaymentGatewayManager;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * PaymentService — Orquestador F4.
 *
 * Coordina idempotencia, validación de monto, transacciones DB,
 * descuento de inventario, eventos y persistencia del pago.
 *
 * Compatible con SimulatedPaymentService (sigue funcionando en paralelo).
 */
class PaymentService
{
    public function __construct(
        private readonly PaymentGatewayManager $manager,
    ) {}

    /**
     * Procesa un pago usando la pasarela configurada.
     *
     * @param  array<string, mixed>  $payload
     * @return array{success: bool, payment: Payment, result: PaymentProcessResult, message: string}
     *
     * @throws RuntimeException
     */
    public function process(Order $order, PaymentMethod $method, array $payload = [], ?string $gatewayDriver = null): array
    {
        // Idempotencia: generar o reutilizar clave.
        $idempotencyKey = $payload['idempotency_key'] ?? Str::uuid()->toString();

        // Verificar si ya existe un pago con esta clave de idempotencia.
        $existingPayment = Payment::query()
            ->where('idempotency_key', $idempotencyKey)
            ->first();

        if ($existingPayment) {
            $result = $this->buildResultFromPayment($existingPayment, $order);
            return [
                'success' => $existingPayment->isApproved(),
                'payment' => $existingPayment,
                'result'  => $result,
                'message' => 'Pago ya procesado (idempotencia). Referencia: ' . $existingPayment->reference,
            ];
        }

        // Validación rápida antes de abrir transacción.
        $this->guardOrderPayable($order);

        $payload['idempotency_key'] = $idempotencyKey;

        // Resolver pasarela.
        $gateway = $this->manager->gateway($gatewayDriver);

        return DB::transaction(function () use ($order, $method, $payload, $gateway, $idempotencyKey): array {
            // Bloquear la orden.
            $freshOrder = Order::query()
                ->where('id', $order->id)
                ->lockForUpdate()
                ->firstOrFail();

            $this->guardOrderPayable($freshOrder);

            // Verificar pago aprobado existente (segunda comprobación dentro del lock).
            $existingApproved = Payment::query()
                ->where('order_id', $freshOrder->id)
                ->where('status', PaymentStatus::Approved)
                ->lockForUpdate()
                ->exists();

            if ($existingApproved) {
                throw new RuntimeException('Este pedido ya tiene un pago aprobado registrado.');
            }

            // Validar monto en servidor (nunca confiar en el frontend).
            $serverAmount = (float) $freshOrder->total;
            if (isset($payload['amount']) && abs((float) $payload['amount'] - $serverAmount) > 0.01) {
                throw new RuntimeException(
                    "Monto inválido. El monto del servidor es {$serverAmount} USD. No se puede procesar."
                );
            }

            // Validar moneda en servidor (operación exclusiva en USD).
            if (isset($payload['currency']) && strtoupper((string) $payload['currency']) !== 'USD') {
                throw new RuntimeException(
                    "Moneda no soportada: [{$payload['currency']}]. ZYRO opera exclusivamente en USD."
                );
            }

            // Ejecutar cobro en la pasarela.
            $result = $gateway->charge($freshOrder, $payload);

            // Si requiere redirección (PayPhone, DEUNA), guardar pago pendiente y retornar.
            if ($result->requiresRedirect) {
                $payment = Payment::query()->create([
                    'order_id'               => $freshOrder->id,
                    'method'                 => $method,
                    'gateway'                => $gateway->getIdentifier(),
                    'gateway_mode'           => $gateway->getMode(),
                    'status'                 => PaymentStatus::Pending,
                    'amount'                 => $serverAmount,
                    'currency'               => 'USD',
                    'reference'              => $result->reference,
                    'external_transaction_id'=> $result->externalTransactionId,
                    'idempotency_key'        => $idempotencyKey,
                    'raw_response'           => $result->rawResponse,
                    'processed_at'           => now(),
                ]);

                return [
                    'success' => false,
                    'payment' => $payment,
                    'result'  => $result,
                    'message' => 'Redirigiendo a la pasarela de pago.',
                ];
            }

            if ($result->success && $result->status === PaymentStatus::Approved) {
                return $this->handleApprovedPayment($freshOrder, $method, $gateway, $result, $idempotencyKey, $serverAmount);
            }

            // Pago rechazado / fallido.
            return $this->handleFailedPayment($freshOrder, $method, $gateway, $result, $idempotencyKey, $serverAmount);
        });
    }

    /**
     * Procesa un webhook entrante de forma idempotente.
     */
    public function processWebhook(string $gatewayDriver, array $payload, array $headers = []): PaymentProcessResult
    {
        $gateway = $this->manager->gateway($gatewayDriver);

        $externalEventId = $payload['event_id'] ?? $payload['transactionId'] ?? $payload['id'] ?? $payload['order_token'] ?? Str::uuid()->toString();

        // Idempotencia de webhooks.
        $existing = PaymentWebhook::query()
            ->where('gateway', $gatewayDriver)
            ->where('external_event_id', $externalEventId)
            ->first();

        if ($existing && $existing->status === 'processed') {
            return $this->buildResultFromWebhook($existing);
        }

        // Registrar webhook.
        $webhook = PaymentWebhook::query()->updateOrCreate(
            ['gateway' => $gatewayDriver, 'external_event_id' => $externalEventId],
            [
                'event_type' => $payload['event_type'] ?? $payload['type'] ?? 'payment',
                'payload'    => $payload,
                'headers'    => $headers,
                'signature'  => $headers['x-signature'] ?? $headers['x-deuna-signature'] ?? null,
                'status'     => 'received',
            ]
        );

        // Validación de moneda en webhook (operación exclusiva en USD)
        if (isset($payload['currency']) && strtoupper((string) $payload['currency']) !== 'USD') {
            $webhook->update(['status' => 'failed', 'error' => "Moneda no soportada: {$payload['currency']}"]);
            return PaymentProcessResult::failed(
                reference: (string) ($payload['reference'] ?? $payload['order_token'] ?? $payload['clientTxId'] ?? 'UNKNOWN'),
                amount: 0.0,
                message: "Moneda inválida en webhook: [{$payload['currency']}]. ZYRO opera exclusivamente en USD.",
                status: PaymentStatus::Failed,
                rawResponse: $payload,
            );
        }

        try {
            $result = $gateway->handleWebhook($payload, $headers);

            $webhook->update([
                'status'       => 'processed',
                'processed_at' => now(),
            ]);

            // Reconciliar pago si se aprobó.
            if ($result->success && $result->status === PaymentStatus::Approved) {
                $this->reconcilePaymentFromWebhook($result, $gatewayDriver);
            }

            return $result;
        } catch (\Throwable $e) {
            $webhook->update([
                'status' => 'failed',
                'error'  => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Privados
    // ─────────────────────────────────────────────────────────────────────────

    private function guardOrderPayable(Order $order): void
    {
        if ($order->status === OrderStatus::Paid) {
            throw new RuntimeException('Este pedido ya tiene un pago aprobado registrado.');
        }

        if (in_array($order->status, [OrderStatus::Rejected, OrderStatus::Cancelled], true)) {
            throw new RuntimeException(
                'Este pedido no puede procesarse. Estado actual: ' . $order->status->value
            );
        }
    }

    private function handleApprovedPayment(
        Order $freshOrder,
        PaymentMethod $method,
        PaymentGatewayInterface $gateway,
        PaymentProcessResult $result,
        string $idempotencyKey,
        float $serverAmount
    ): array {
        // Verificar stock.
        foreach ($freshOrder->items as $item) {
            if ($item->product_id === null) {
                continue;
            }

            $product = Product::query()
                ->where('id', $item->product_id)
                ->lockForUpdate()
                ->first();

            if ($product && $product->stock < $item->quantity) {
                $payment = $this->savePayment($freshOrder, $method, $gateway, PaymentStatus::Failed, $result, $idempotencyKey, $serverAmount);
                $payment->update(['error_message' => "Stock insuficiente para «{$product->name}»."]);

                DB::afterCommit(fn () => SafeBroadcaster::broadcast(new PaymentStatusUpdated(
                    $freshOrder, $payment, "Stock insuficiente para «{$product->name}»."
                )));

                return [
                    'success' => false,
                    'payment' => $payment,
                    'result'  => $result,
                    'message' => "Stock insuficiente para «{$product->name}». El pedido permanece pendiente.",
                ];
            }
        }

        // Descontar inventario.
        $updatedProducts = [];
        foreach ($freshOrder->items as $item) {
            if ($item->product_id !== null) {
                Product::query()->where('id', $item->product_id)->decrement('stock', $item->quantity);

                $prod = Product::query()->find($item->product_id);
                if ($prod) {
                    $updatedProducts[] = [
                        'product_id' => $prod->id,
                        'name'       => $prod->name,
                        'stock'      => $prod->stock,
                        'is_low'     => $prod->stock <= 3 && $prod->stock > 0,
                        'is_out'     => $prod->stock <= 0,
                    ];
                }
            }
        }

        $payment = $this->savePayment($freshOrder, $method, $gateway, PaymentStatus::Approved, $result, $idempotencyKey, $serverAmount);

        $previousStatus = $freshOrder->status->value;

        $freshOrder->update([
            'status'  => OrderStatus::Paid,
            'paid_at' => now(),
        ]);

        if (class_exists(OrderStatusHistory::class)) {
            OrderStatusHistory::query()->create([
                'order_id'    => $freshOrder->id,
                'changed_by'  => $freshOrder->user_id,
                'from_status' => $previousStatus,
                'to_status'   => OrderStatus::Paid->value,
                'note'        => "Pago aprobado vía {$gateway->getName()}. Ref: {$result->reference}",
            ]);
        }

        DB::afterCommit(function () use ($freshOrder, $payment, $result, $updatedProducts, $previousStatus, $gateway) {
            SafeBroadcaster::broadcast(new PaymentStatusUpdated($freshOrder, $payment, "Pago aprobado. Ref: {$result->reference}"));
            SafeBroadcaster::broadcast(new OrderStatusUpdated($freshOrder, $previousStatus, OrderStatus::Paid->value, "Pago aprobado vía {$gateway->getName()}."));

            if (!empty($updatedProducts)) {
                SafeBroadcaster::broadcast(new InventoryUpdated($updatedProducts));
            }

            SafeBroadcaster::broadcast(new AnalyticsUpdated('purchase_completed', [
                'order_id'     => $freshOrder->id,
                'order_number' => $freshOrder->order_number,
                'amount'       => (float) $freshOrder->total,
            ]));
        });

        return [
            'success' => true,
            'payment' => $payment,
            'result'  => $result,
            'message' => "Pago aprobado vía {$gateway->getName()}. Referencia: {$result->reference}",
        ];
    }

    private function handleFailedPayment(
        Order $freshOrder,
        PaymentMethod $method,
        PaymentGatewayInterface $gateway,
        PaymentProcessResult $result,
        string $idempotencyKey,
        float $serverAmount
    ): array {
        $payment = $this->savePayment($freshOrder, $method, $gateway, $result->status, $result, $idempotencyKey, $serverAmount);

        DB::afterCommit(fn () => SafeBroadcaster::broadcast(new PaymentStatusUpdated(
            $freshOrder, $payment, $result->message
        )));

        return [
            'success' => false,
            'payment' => $payment,
            'result'  => $result,
            'message' => $result->message,
        ];
    }

    private function savePayment(
        Order $order,
        PaymentMethod $method,
        PaymentGatewayInterface $gateway,
        PaymentStatus $status,
        PaymentProcessResult $result,
        string $idempotencyKey,
        float $amount
    ): Payment {
        return Payment::query()->create([
            'order_id'               => $order->id,
            'method'                 => $method,
            'gateway'                => $gateway->getIdentifier(),
            'gateway_mode'           => $gateway->getMode(),
            'status'                 => $status,
            'amount'                 => $amount,
            'currency'               => 'USD',
            'reference'              => $result->reference,
            'external_transaction_id'=> $result->externalTransactionId,
            'idempotency_key'        => $idempotencyKey,
            'raw_response'           => $result->rawResponse,
            'error_message'          => $result->success ? null : $result->message,
            'processed_at'           => now(),
        ]);
    }

    private function reconcilePaymentFromWebhook(PaymentProcessResult $result, string $gatewayDriver): void
    {
        $payment = Payment::query()
            ->where('external_transaction_id', $result->externalTransactionId)
            ->orWhere('reference', $result->reference)
            ->where('gateway', $gatewayDriver)
            ->first();

        if (!$payment) {
            return;
        }

        if ($payment->status !== PaymentStatus::Approved) {
            DB::transaction(function () use ($payment, $result, $gatewayDriver) {
                $order = Order::query()->with('items')->where('id', $payment->order_id)->lockForUpdate()->firstOrFail();

                // Protección contra eventos fuera de orden: orden ya cancelada o rechazada
                if (in_array($order->status, [OrderStatus::Cancelled, OrderStatus::Rejected], true)) {
                    Log::warning("Reconciliación abortada: la orden {$order->id} ya se encuentra en estado {$order->status->value}.");
                    $payment->update([
                        'status'        => PaymentStatus::Failed,
                        'error_message' => "Pago descartado: la orden ya se encuentra {$order->status->value}.",
                        'processed_at'  => now(),
                    ]);
                    return;
                }

                // Idempotencia: si la orden ya fue pagada, no descontar inventario nuevamente.
                if ($order->status !== OrderStatus::Paid) {
                    // Descontar inventario de cada producto de la orden
                    $updatedProducts = [];
                    foreach ($order->items as $item) {
                        if ($item->product_id !== null) {
                            $product = Product::query()->where('id', $item->product_id)->lockForUpdate()->first();
                            if ($product) {
                                $product->decrement('stock', $item->quantity);
                                $freshProd = Product::query()->find($item->product_id);
                                if ($freshProd) {
                                    $updatedProducts[] = [
                                        'product_id' => $freshProd->id,
                                        'name'       => $freshProd->name,
                                        'stock'      => $freshProd->stock,
                                        'is_low'     => $freshProd->stock <= 3 && $freshProd->stock > 0,
                                        'is_out'     => $freshProd->stock <= 0,
                                    ];
                                }
                            }
                        }
                    }

                    $previousStatus = $order->status->value;
                    $order->update([
                        'status'  => OrderStatus::Paid,
                        'paid_at' => now(),
                    ]);

                    if (class_exists(OrderStatusHistory::class)) {
                        OrderStatusHistory::query()->create([
                            'order_id'    => $order->id,
                            'changed_by'  => $order->user_id,
                            'from_status' => $previousStatus,
                            'to_status'   => OrderStatus::Paid->value,
                            'note'        => "Pago confirmado vía webhook ({$gatewayDriver}). Ref: {$result->reference}",
                        ]);
                    }

                    DB::afterCommit(function () use ($order, $previousStatus, $gatewayDriver, $updatedProducts) {
                        SafeBroadcaster::broadcast(new OrderStatusUpdated(
                            $order, $previousStatus, OrderStatus::Paid->value, "Pago confirmado vía webhook ({$gatewayDriver})."
                        ));

                        if (!empty($updatedProducts)) {
                            SafeBroadcaster::broadcast(new InventoryUpdated($updatedProducts));
                        }

                        SafeBroadcaster::broadcast(new AnalyticsUpdated('purchase_completed', [
                            'order_id'     => $order->id,
                            'order_number' => $order->order_number,
                            'amount'       => (float) $order->total,
                        ]));
                    });
                }

                $payment->update([
                    'status'      => PaymentStatus::Approved,
                    'raw_response'=> array_merge($payment->raw_response ?? [], $result->rawResponse),
                    'processed_at'=> now(),
                ]);
            });
        }
    }

    private function buildResultFromPayment(Payment $payment, Order $order): PaymentProcessResult
    {
        $status = $payment->status ?? PaymentStatus::Pending;
        return new PaymentProcessResult(
            success:               $payment->isApproved(),
            status:                $status,
            reference:             $payment->reference ?? '',
            amount:                (float) $payment->amount,
            externalTransactionId: $payment->external_transaction_id,
            currency:              $payment->currency ?? 'USD',
            message:               $payment->isApproved() ? 'Pago aprobado (idempotencia).' : ($payment->error_message ?? 'Pago no aprobado.'),
            rawResponse:           $payment->raw_response ?? [],
            idempotencyKey:        $payment->idempotency_key,
        );
    }

    private function buildResultFromWebhook(PaymentWebhook $webhook): PaymentProcessResult
    {
        $payload = $webhook->payload ?? [];
        return PaymentProcessResult::approved(
            reference:             $payload['reference'] ?? $webhook->external_event_id,
            amount:                (float) ($payload['amount'] ?? 0),
            externalTransactionId: $webhook->external_event_id,
            message:               'Webhook ya procesado (idempotencia).',
            rawResponse:           $payload,
        );
    }
}
