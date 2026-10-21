<?php

namespace App\Events;

use App\Services\RealtimeMetricsService;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Evento emitido ante interacciones y conversiones para actualizar el panel de analítica.
 */
class AnalyticsUpdated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public array $metrics;

    public function __construct(
        public string $eventName,
        public array $data = []
    ) {
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
        return 'AnalyticsUpdated';
    }

    public function broadcastWith(): array
    {
        return [
            'event_name' => $this->eventName,
            'data'       => $this->data,
            'metrics'    => $this->metrics,
        ];
    }
}
