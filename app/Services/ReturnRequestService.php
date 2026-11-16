<?php

namespace App\Services;

use App\Contracts\ReturnRequestServiceInterface;
use App\Enums\ReturnInspectedCondition;
use App\Enums\ReturnRequestedSolution;
use App\Enums\ReturnRequestStatus;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ReturnRequest;
use App\Models\ReturnRequestHistory;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class ReturnRequestService implements ReturnRequestServiceInterface
{
    public function createCustomerRequest(User $customer, Order $order, array $data): ReturnRequest
    {
        if ($order->user_id !== $customer->id) {
            throw new \RuntimeException('No tienes permiso para solicitar devoluciones sobre este pedido.');
        }

        $orderItemId = (int) ($data['order_item_id'] ?? 0);
        $orderItem = OrderItem::query()
            ->where('id', $orderItemId)
            ->where('order_id', $order->id)
            ->first();

        if (! $orderItem) {
            throw new \RuntimeException('El artículo indicado no pertenece a este pedido.');
        }

        // Protección contra solicitudes duplicadas en trámite
        $existingPending = ReturnRequest::query()
            ->where('order_item_id', $orderItem->id)
            ->whereIn('status', [ReturnRequestStatus::Recibida->value, ReturnRequestStatus::EnRevision->value])
            ->exists();

        if ($existingPending) {
            throw new \RuntimeException('Ya existe una solicitud activa en trámite para este artículo.');
        }

        $product = Product::query()->findOrFail($orderItem->product_id);
        $quantity = min((int) ($data['quantity'] ?? 1), (int) $orderItem->quantity);
        if ($quantity < 1) {
            $quantity = 1;
        }

        $solution = $data['requested_solution'] instanceof ReturnRequestedSolution
            ? $data['requested_solution']
            : (ReturnRequestedSolution::tryFrom((string) $data['requested_solution']) ?? ReturnRequestedSolution::CambioTalla);

        return DB::transaction(function () use ($customer, $order, $orderItem, $product, $quantity, $data, $solution): ReturnRequest {
            $request = ReturnRequest::query()->create([
                'request_number'      => ReturnRequest::generateRequestNumber(),
                'order_id'            => $order->id,
                'user_id'             => $customer->id,
                'order_item_id'       => $orderItem->id,
                'product_id'          => $product->id,
                'quantity'            => $quantity,
                'size'                => $orderItem->size,
                'reason'              => trim((string) ($data['reason'] ?? 'Cambio solicitado')),
                'problem_description' => trim((string) ($data['problem_description'] ?? '')),
                'requested_solution'  => $solution,
                'status'              => ReturnRequestStatus::Recibida,
                'inspected_condition' => ReturnInspectedCondition::Pendiente,
                'is_restocked'        => false,
            ]);

            ReturnRequestHistory::query()->create([
                'return_request_id' => $request->id,
                'changed_by'        => $customer->id,
                'from_status'       => 'creacion',
                'to_status'         => ReturnRequestStatus::Recibida->value,
                'note'              => 'Solicitud registrada por el cliente.',
            ]);

            return $request;
        });
    }

    public function startInspection(ReturnRequest $request, User $reviewer, ?string $note = null): ReturnRequest
    {
        return DB::transaction(function () use ($request, $reviewer, $note): ReturnRequest {
            $fresh = ReturnRequest::query()->where('id', $request->id)->lockForUpdate()->firstOrFail();

            if ($fresh->status !== ReturnRequestStatus::Recibida) {
                throw new \RuntimeException("Solo solicitudes en estado «Recibida» pueden pasar a inspección. Estado actual: {$fresh->status->value}.");
            }

            $fresh->update([
                'status'      => ReturnRequestStatus::EnRevision,
                'reviewer_id' => $reviewer->id,
            ]);

            ReturnRequestHistory::query()->create([
                'return_request_id' => $fresh->id,
                'changed_by'        => $reviewer->id,
                'from_status'       => ReturnRequestStatus::Recibida->value,
                'to_status'         => ReturnRequestStatus::EnRevision->value,
                'note'              => $note ?: "Inspección física iniciada por {$reviewer->name}.",
            ]);

            return $fresh;
        });
    }

    public function resolveRequest(ReturnRequest $request, User $admin, array $resolutionData): ReturnRequest
    {
        return DB::transaction(function () use ($request, $admin, $resolutionData): ReturnRequest {
            $fresh = ReturnRequest::query()->where('id', $request->id)->lockForUpdate()->firstOrFail();

            if ($fresh->status->isFinal()) {
                throw new \RuntimeException('Esta solicitud ya fue resuelta o rechazada anteriormente.');
            }

            $newStatus = ($resolutionData['status'] ?? 'resuelta') === 'rechazada'
                ? ReturnRequestStatus::Rechazada
                : ReturnRequestStatus::Resuelta;

            $condition = $resolutionData['inspected_condition'] instanceof ReturnInspectedCondition
                ? $resolutionData['inspected_condition']
                : (ReturnInspectedCondition::tryFrom((string) $resolutionData['inspected_condition']) ?? ReturnInspectedCondition::Pendiente);

            $decision = trim((string) ($resolutionData['admin_decision'] ?? ''));
            $shouldRestock = ! empty($resolutionData['should_restock']);
            $replacementProductId = ! empty($resolutionData['replacement_product_id']) ? (int) $resolutionData['replacement_product_id'] : null;
            $replacementSize = $resolutionData['replacement_size'] ?? null;

            // 1. REGLA OBLIGATORIA: Prevención de doble reincorporación de stock devuelto
            if ($shouldRestock) {
                if ($fresh->is_restocked) {
                    throw new \RuntimeException('Esta prenda ya fue reincorporada al inventario vendible.');
                }

                if (! $condition->isRestockable()) {
                    throw new \RuntimeException("Solo prendas dictaminadas como «Óptimo con etiquetas» pueden retornar al stock vendible. Condición actual: {$condition->label()}.");
                }

                // Incremento seguro y atómico en products.stock
                $product = Product::query()->where('id', $fresh->product_id)->lockForUpdate()->firstOrFail();
                $product->increment('stock', $fresh->quantity);

                $fresh->is_restocked = true;
                $fresh->restocked_at = now();
            }

            // 2. Descuento de prenda de reemplazo si aplica
            if ($newStatus === ReturnRequestStatus::Resuelta && $replacementProductId) {
                $replacement = Product::query()->where('id', $replacementProductId)->lockForUpdate()->firstOrFail();
                if ($replacement->stock < $fresh->quantity) {
                    throw new \RuntimeException("Stock insuficiente para la prenda de reemplazo «{$replacement->name}».");
                }

                $replacement->decrement('stock', $fresh->quantity);
                $fresh->replacement_product_id = $replacement->id;
                $fresh->replacement_size = $replacementSize;
            }

            $previousStatus = $fresh->status->value;

            $fresh->update([
                'status'                 => $newStatus,
                'inspected_condition'    => $condition,
                'admin_decision'         => $decision,
                'replacement_product_id' => $fresh->replacement_product_id,
                'replacement_size'       => $fresh->replacement_size,
                'is_restocked'           => $fresh->is_restocked,
                'restocked_at'           => $fresh->restocked_at,
                'resolved_at'            => now(),
                'reviewer_id'            => $fresh->reviewer_id ?? $admin->id,
            ]);

            ReturnRequestHistory::query()->create([
                'return_request_id' => $fresh->id,
                'changed_by'        => $admin->id,
                'from_status'       => $previousStatus,
                'to_status'         => $newStatus->value,
                'note'              => $decision ?: "Solicitud dictaminada como {$newStatus->label()} por {$admin->name}.",
            ]);

            return $fresh;
        });
    }

    public function restockReturnedItem(ReturnRequest $request): ReturnRequest
    {
        return DB::transaction(function () use ($request): ReturnRequest {
            $fresh = ReturnRequest::query()->where('id', $request->id)->lockForUpdate()->firstOrFail();

            if ($fresh->is_restocked) {
                throw new \RuntimeException('Esta prenda ya fue reincorporada al inventario vendible anteriormente.');
            }

            $product = Product::query()->where('id', $fresh->product_id)->lockForUpdate()->firstOrFail();
            $product->increment('stock', $fresh->quantity);

            $fresh->update([
                'is_restocked' => true,
                'restocked_at' => now(),
            ]);

            return $fresh;
        });
    }
}
