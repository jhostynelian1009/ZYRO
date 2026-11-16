<?php

namespace App\Contracts;

use App\Enums\OrderChannel;
use App\Models\Order;
use App\Models\User;

interface OmnichannelOrderServiceInterface
{
    /**
     * Registra una venta procedente de canales asistidos (WhatsApp, Punto Físico).
     *
     * Reglas:
     * - Reutiliza el inventario central `products.stock` con `lockForUpdate()`.
     * - Bloquea ventas si el stock disponible es insuficiente o cantidad <= 0.
     * - Valida canal de origen permitido (`whatsapp`, `punto_fisico`).
     * - Genera identificador único `ZYR-XXXX`.
     * - Asocia al operador responsable (`created_by`).
     * - Emite evento en tiempo real para sincronizar catálogo web.
     *
     * @param  array{
     *     channel_origin: OrderChannel|string,
     *     customer_name: string,
     *     phone: string,
     *     address: string,
     *     city: string,
     *     delivery_method: string,
     *     delivery_notes?: ?string,
     *     payment_method: string,
     *     payment_status?: string,
     *     items: list<array{product_id: int, size: ?string, quantity: int, customization?: ?array}>
     * }  $data
     */
    public function createManualOrder(User $creator, array $data): Order;
}
