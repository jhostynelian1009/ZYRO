<?php

namespace App\Events;

use App\Services\RealtimeMetricsService;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Evento emitido cuando cambia el stock de productos tras un pago aprobado.
 */
class InventoryUpdated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public array $metrics;

    /**
     * @param array<int, array{product_id:int, name:string, stock:int, is_low:bool, is_out:bool}> $items
     */
    public function __construct(public array $items)
    {
        $this->metrics = RealtimeMetricsService::current();
    }

    /**
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('admin'),
        ];
    }

    public function broadcastAs(): string
    {
        return 'InventoryUpdated';
    }

    public function broadcastWith(): array
    {
        return [
            'items'   => $this->items,
            'metrics' => $this->metrics,
        ];
    }
}
