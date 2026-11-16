<?php

namespace App\Contracts;

use App\Enums\ReturnInspectedCondition;
use App\Enums\ReturnRequestedSolution;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\ReturnRequest;
use App\Models\User;

interface ReturnRequestServiceInterface
{
    /**
     * Registra una nueva solicitud de cambio o devolución por parte de un cliente.
     *
     * Reglas:
     * - El pedido debe pertenecer al cliente.
     * - Debe identificar obligatoriamente el `OrderItem` comprado.
     * - Genera un identificador único `DEV-XXXX`.
     * - Estado inicial `recibida` con `inspected_condition = 'pendiente'`.
     * - NO reincorpora stock al inventario.
     *
     * @param  array{
     *     order_item_id: int,
     *     quantity?: int,
     *     reason: string,
     *     problem_description: string,
     *     requested_solution: ReturnRequestedSolution|string
     * }  $data
     */
    public function createCustomerRequest(User $customer, Order $order, array $data): ReturnRequest;

    /**
     * Actualiza la solicitud a estado de revisión e inspección física.
     */
    public function startInspection(ReturnRequest $request, User $reviewer, ?string $note = null): ReturnRequest;

    /**
     * Resuelve la solicitud tras el peritaje técnico.
     *
     * Reglas:
     * - Asienta condición técnica (`inspected_condition`).
     * - Si la condición es `optimo_con_etiquetas` y se autoriza restock, incrementa `products.stock`.
     * - Si se entrega prenda de reemplazo, decrementa `products.stock` del reemplazo.
     * - Cambia estado a `resuelta` o `rechazada`.
     * - Registra historial inmutable de la decisión.
     *
     * @param  array{
     *     status: string,
     *     inspected_condition: ReturnInspectedCondition|string,
     *     admin_decision: string,
     *     should_restock?: bool,
     *     replacement_product_id?: ?int,
     *     replacement_size?: ?string
     * }  $resolutionData
     */
    public function resolveRequest(ReturnRequest $request, User $admin, array $resolutionData): ReturnRequest;

    /**
     * Reincorpora una prenda devuelta al stock vendible con prevención de doble ingreso.
     */
    public function restockReturnedItem(ReturnRequest $request): ReturnRequest;
}
