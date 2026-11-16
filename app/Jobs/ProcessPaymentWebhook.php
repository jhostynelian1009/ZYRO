<?php

namespace App\Jobs;

use App\Services\PaymentService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * ProcessPaymentWebhook — Job de cola para procesar webhooks de pago.
 *
 * Diseñado para ser despachado desde el controlador cuando se desee
 * procesamiento asíncrono. Actualmente el controlador procesa de forma
 * síncrona, pero el job está preparado para uso futuro con colas Redis/SQS.
 *
 * Reintentos: hasta 3 veces con backoff exponencial.
 */
class ProcessPaymentWebhook implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public array $backoff = [10, 60, 300];

    public function __construct(
        public readonly string $gatewayDriver,
        public readonly array $payload,
        public readonly array $headers = [],
    ) {}

    public function handle(PaymentService $paymentService): void
    {
        try {
            $result = $paymentService->processWebhook(
                $this->gatewayDriver,
                $this->payload,
                $this->headers
            );

            Log::info("ProcessPaymentWebhook [{$this->gatewayDriver}]: {$result->status->value}", [
                'reference' => $result->reference,
            ]);
        } catch (\Throwable $e) {
            Log::error("ProcessPaymentWebhook [{$this->gatewayDriver}] falló: {$e->getMessage()}");
            throw $e;
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::critical("ProcessPaymentWebhook [{$this->gatewayDriver}] agotó reintentos.", [
            'error'   => $exception->getMessage(),
            'payload' => $this->payload,
        ]);
    }
}
