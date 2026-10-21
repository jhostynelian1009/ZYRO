<?php

namespace App\Http\Controllers\Admin;

use App\Enums\OrderStatus;
use App\Events\OrderStatusUpdated;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderStatusHistory;
use App\Services\SafeBroadcaster;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class OrderController extends Controller
{
    /** Lista pedidos con soporte de filtros por estado y búsqueda. */
    public function index(Request $request): View
    {
        $status = $request->query('status');
        $q = trim((string) $request->query('q'));

        $query = Order::query()->with('user')->latest();

        if ($status && in_array($status, array_column(OrderStatus::cases(), 'value'), true)) {
            $query->where('status', $status);
        }

        if ($q !== '') {
            $query->where(function ($sub) use ($q) {
                $sub->where('order_number', 'like', "%{$q}%")
                    ->orWhere('customer_name', 'like', "%{$q}%")
                    ->orWhereHas('user', function ($u) use ($q) {
                        $u->where('name', 'like', "%{$q}%")
                            ->orWhere('email', 'like', "%{$q}%");
                    });
            });
        }

        $orders = $query->paginate(15)->withQueryString();

        $counts = [
            'all'              => Order::query()->count(),
            'pending_approval' => Order::query()->where('status', OrderStatus::PendingApproval)->count(),
            'approved'         => Order::query()->where('status', OrderStatus::Approved)->count(),
            'paid'             => Order::query()->where('status', OrderStatus::Paid)->count(),
            'rejected'         => Order::query()->where('status', OrderStatus::Rejected)->count(),
        ];

        return view('admin.orders.index', compact('orders', 'status', 'q', 'counts'));
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

        SafeBroadcaster::broadcast(new OrderStatusUpdated(
            $order->fresh(),
            OrderStatus::PendingApproval->value,
            OrderStatus::Approved->value,
            'Aprobado por administrador.'
        ));

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

        SafeBroadcaster::broadcast(new OrderStatusUpdated(
            $order->fresh(),
            OrderStatus::PendingApproval->value,
            OrderStatus::Rejected->value,
            'Rechazado por administrador.'
        ));

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
