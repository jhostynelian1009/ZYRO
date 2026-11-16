<?php

namespace App\Http\Controllers;

use App\Services\PaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * PaymentWebhookController — recibe notificaciones POST de pasarelas externas.
 *
 * - Excluido de CSRF (registrado en rutas sin middleware VerifyCsrfToken).
 * - Idempotente: webhooks duplicados no generan efectos secundarios.
 * - Registra el webhook en payment_webhooks y delega validación al adaptador.
 */
class PaymentWebhookController extends Controller
{
    public function __construct(
        private readonly PaymentService $paymentService,
    ) {}

    /**
     * Recibe y procesa un webhook de una pasarela de pago.
     *
     * @param  string  $gateway  Identificador del adaptador (payphone, deuna, mock, etc.)
     */
    public function receive(Request $request, string $gateway): JsonResponse
    {
        $allowedGateways = ['mock', 'payphone', 'deuna', 'bank_transfer', 'cash'];

        if (!in_array($gateway, $allowedGateways, true)) {
            Log::warning("Webhook recibido para pasarela desconocida: {$gateway}");
            return response()->json(['error' => 'Pasarela no reconocida.'], 404);
        }

        $payload = $request->all();
        $headers = collect($request->headers->all())
            ->mapWithKeys(fn ($v, $k) => [strtolower($k) => is_array($v) ? ($v[0] ?? '') : $v])
            ->toArray();
        $headers['x-raw-payload'] = $request->getContent();

        try {
            $result = $this->paymentService->processWebhook($gateway, $payload, $headers);

            Log::info("Webhook [{$gateway}] procesado correctamente.", [
                'reference' => $result->reference,
                'status'    => $result->status->value,
            ]);

            return response()->json([
                'success'   => $result->success,
                'status'    => $result->status->value,
                'reference' => $result->reference,
                'message'   => $result->message,
            ]);
        } catch (\Throwable $e) {
            Log::error("Error procesando webhook [{$gateway}]: {$e->getMessage()}", [
                'payload' => $payload,
                'trace'   => $e->getTraceAsString(),
            ]);

            return response()->json([
                'error'   => 'Error procesando el webhook.',
                'message' => $e->getMessage(),
            ], 500);
        }
    }
}
