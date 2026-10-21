<?php

namespace App\Events;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Services\RealtimeMetricsService;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Evento emitido cuando un pedido cambia de estado:
 * - Aprobado por admin (pending_approval -> approved)
 * - Rechazado por admin (pending_approval -> rejected)
 * - Pagado por cliente (approved -> paid)
 */
class OrderStatusUpdated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public array $metrics;

    public function __construct(
        public Order $order,
        public string $fromStatus,
        public string $toStatus,
        public ?string $note = null
    ) {
        $this->order->loadMissing('user');
        $this->metrics = RealtimeMetricsService::current();
    }

    /**
     * Canales privados:
     * - 'admin': para el panel de administración
     * - 'user.{id}': para la cuenta del cliente propietario
     * - 'orders.{id}': para la pantalla de detalle del pedido abierto
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('admin'),
            new PrivateChannel('user.'.$this->order->user_id),
            new PrivateChannel('orders.'.$this->order->id),
        ];
    }

    public function broadcastAs(): string
    {
        return 'OrderStatusUpdated';
    }

    public function broadcastWith(): array
    {
        $statusLabels = [
            'pending_approval' => 'Pendiente de aprobación',
            'approved'         => 'Aprobado',
            'paid'             => 'Pagado',
            'rejected'         => 'Rechazado',
            'cancelled'        => 'Cancelado',
        ];

        return [
            'order_id'          => $this->order->id,
            'order_number'      => $this->order->order_number,
            'from_status'       => $this->fromStatus,
            'to_status'         => $this->toStatus,
            'to_status_label'   => $statusLabels[$this->toStatus] ?? ucfirst($this->toStatus),
            'note'              => $this->note,
            'updated_at'        => now()->format('d/m/Y H:i'),
            'payment_url'       => route('orders.payment', $this->order),
            'order_url'         => route('orders.show', $this->order),
            'admin_order_url'   => route('admin.orders.show', $this->order),
            'metrics'           => $this->metrics,
        ];
    }
}
