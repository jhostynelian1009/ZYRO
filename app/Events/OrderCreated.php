<?php

namespace App\Events;

use App\Models\Order;
use App\Services\RealtimeMetricsService;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Evento emitido cuando un cliente genera un pedido válido en la tienda.
 * Notifica al panel administrativo en tiempo real.
 */
class OrderCreated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public array $metrics;

    public function __construct(public Order $order)
    {
        $this->order->loadMissing('user');
        $this->metrics = RealtimeMetricsService::current();
    }

    /**
     * Canal privado exclusivo para administradores.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('admin'),
        ];
    }

    /**
     * Nombre del evento para clientes WebSocket.
     */
    public function broadcastAs(): string
    {
        return 'OrderCreated';
    }

    /**
     * Datos públicos del evento (sin exponer datos bancarios ni tokens).
     *
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'order' => [
                'id'              => $this->order->id,
                'order_number'    => $this->order->order_number,
                'customer_name'   => $this->order->customer_name ?: ($this->order->user->name ?? 'Cliente ZYRO'),
                'customer_email'  => $this->order->user->email ?? '—',
                'total'           => (float) $this->order->total,
                'total_formatted' => number_format((float) $this->order->total, 2),
                'shipping_amount' => (float) $this->order->shipping_amount,
                'delivery_method' => $this->order->delivery_method,
                'delivery_estimate' => $this->order->delivery_estimate,
                'status'          => $this->order->status->value,
                'status_label'    => 'Pendiente de aprobación',
                'created_at'      => $this->order->created_at?->format('d/m/Y H:i') ?? now()->format('d/m/Y H:i'),
                'detail_url'      => route('admin.orders.show', $this->order),
            ],
            'metrics' => $this->metrics,
        ];
    }
}
