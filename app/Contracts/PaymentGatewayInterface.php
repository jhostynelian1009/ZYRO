<?php

namespace App\Contracts;

use App\DTOs\PaymentProcessResult;
use App\Models\Order;

interface PaymentGatewayInterface
{
    /**
     * Identificador único del adaptador (mock, payphone, deuna, bank_transfer, cash).
     */
    public function getIdentifier(): string;

    /**
     * Nombre legible del adaptador.
     */
    public function getName(): string;

    /**
     * Modo de operación actual: 'mock', 'sandbox', 'live'.
     */
    public function getMode(): string;

    /**
     * Procesa o inicializa el cobro de un pedido.
     *
     * @param  array<string, mixed>  $payload
     */
    public function charge(Order $order, array $payload = []): PaymentProcessResult;

    /**
     * Consulta el estado de una transacción ante la pasarela para reconciliación.
     */
    public function queryTransaction(string $transactionReference): PaymentProcessResult;

    /**
     * Procesa y valida la autenticidad de una notificación/webhook externo.
     *
     * @param  array<string, mixed>  $payload
     * @param  array<string, string>  $headers
     */
    public function handleWebhook(array $payload, array $headers = []): PaymentProcessResult;
}
