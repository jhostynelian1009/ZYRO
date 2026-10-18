<?php

namespace App\Http\Controllers\Admin;

use App\Enums\OrderStatus;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderStatusHistory;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class OrderController extends Controller
{
    /** Lista todos los pedidos. */
    public function index(): View
    {
        $orders = Order::query()
            ->with('user')
            ->latest()
            ->paginate(20);

        return view('admin.orders.index', compact('orders'));
    }

    /** Muestra el detalle de un pedido. */
    public function show(Order $order): View
    {
        $order->load(['user', 'items.product', 'payments', 'approvedBy', 'rejectedBy']);

        return view('admin.orders.show', compact('order'));
    }

    /** Aprueba un pedido pending_approval. */
    public function approve(Order $order): RedirectResponse
    {
        if ($order->status !== OrderStatus::PendingApproval) {
            return back()->withErrors([
                'order' => "Solo se pueden aprobar pedidos en estado «Pendiente de aprobación». Estado actual: {$order->status->value}.",
            ]);
        }

        /** @var \App\Models\User $admin */
        $admin = auth()->user();

        $order->update([
            'status'      => OrderStatus::Approved,
            'approved_by' => $admin->id,
            'approved_at' => now(),
        ]);

        $this->recordHistory($order, OrderStatus::PendingApproval, OrderStatus::Approved, $admin->id, 'Aprobado por administrador.');

        return back()->with('status', "Pedido {$order->order_number} aprobado.");
    }

    /** Rechaza un pedido pending_approval. */
    public function reject(Order $order): RedirectResponse
    {
        if ($order->status !== OrderStatus::PendingApproval) {
            return back()->withErrors([
                'order' => "Solo se pueden rechazar pedidos en estado «Pendiente de aprobación». Estado actual: {$order->status->value}.",
            ]);
        }

        /** @var \App\Models\User $admin */
        $admin = auth()->user();

        $order->update([
            'status'      => OrderStatus::Rejected,
            'rejected_by' => $admin->id,
            'rejected_at' => now(),
        ]);

        $this->recordHistory($order, OrderStatus::PendingApproval, OrderStatus::Rejected, $admin->id, 'Rechazado por administrador.');

        return back()->with('status', "Pedido {$order->order_number} rechazado.");
    }

    private function recordHistory(
        Order       $order,
        OrderStatus $from,
        OrderStatus $to,
        int         $changedBy,
        string      $note
    ): void {
        OrderStatusHistory::query()->create([
            'order_id'    => $order->id,
            'changed_by'  => $changedBy,
            'from_status' => $from->value,
            'to_status'   => $to->value,
            'note'        => $note,
        ]);
    }
}
