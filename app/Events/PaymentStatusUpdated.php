<?php

namespace App\Events;

use App\Models\Order;
use App\Models\Payment;
use App\Services\RealtimeMetricsService;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Evento emitido al procesar un pago (aprobado o rechazado).
 */
class PaymentStatusUpdated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public array $metrics;

    public function __construct(
        public Order $order,
        public Payment $payment,
        public string $message
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
            new PrivateChannel('user.'.$this->order->user_id),
            new PrivateChannel('orders.'.$this->order->id),
        ];
    }

    public function broadcastAs(): string
    {
        return 'PaymentStatusUpdated';
    }

    public function broadcastWith(): array
    {
        return [
            'order_id'         => $this->order->id,
            'order_number'     => $this->order->order_number,
            'payment_id'       => $this->payment->id,
            'reference'        => $this->payment->reference,
            'amount'           => (float) $this->payment->amount,
            'amount_formatted' => number_format((float) $this->payment->amount, 2),
            'method'           => $this->payment->method?->value ?? 'simulator',
            'status'           => $this->payment->status->value,
            'is_approved'      => $this->payment->status->value === 'approved',
            'message'          => $this->message,
            'order_url'        => route('orders.show', $this->order),
            'admin_order_url'  => route('admin.orders.show', $this->order),
            'metrics'          => $this->metrics,
        ];
    }
}
