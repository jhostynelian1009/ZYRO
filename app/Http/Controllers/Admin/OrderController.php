<?php

namespace App\Http\Controllers\Admin;

use App\Contracts\OmnichannelOrderServiceInterface;
use App\Contracts\WhatsAppLinkGeneratorInterface;
use App\Enums\InteractionChannel;
use App\Enums\OrderStatus;
use App\Events\OrderStatusUpdated;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderInteraction;
use App\Models\OrderStatusHistory;
use App\Services\SafeBroadcaster;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class OrderController extends Controller
{
    /** Lista pedidos con soporte de filtros por estado, canal, fecha y búsqueda. */
    public function index(Request $request): View
    {
        $status = $request->query('status');
        $channel = $request->query('channel');
        $dateFrom = $request->query('date_from');
        $dateTo = $request->query('date_to');
        $q = trim((string) $request->query('q'));

        $query = Order::query()->with(['user', 'creator'])->latest();

        if ($status && in_array($status, array_column(OrderStatus::cases(), 'value'), true)) {
            $query->where('status', $status);
        }

        if ($channel && in_array($channel, ['web', 'whatsapp', 'punto_fisico'], true)) {
            $query->where('channel_origin', $channel);
        }

        if ($dateFrom) {
            $query->whereDate('created_at', '>=', $dateFrom);
        }

        if ($dateTo) {
            $query->whereDate('created_at', '<=', $dateTo);
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

        $channelCounts = [
            'all'          => Order::query()->count(),
            'web'          => Order::query()->where('channel_origin', 'web')->count(),
            'whatsapp'     => Order::query()->where('channel_origin', 'whatsapp')->count(),
            'punto_fisico' => Order::query()->where('channel_origin', 'punto_fisico')->count(),
        ];

        return view('admin.orders.index', compact(
            'orders',
            'status',
            'channel',
            'dateFrom',
            'dateTo',
            'q',
            'counts',
            'channelCounts'
        ));
    }

    /** Muestra el detalle de un pedido. */
    public function show(Order $order, WhatsAppLinkGeneratorInterface $waHelper): View
    {
        $order->load(['user', 'creator', 'items.product', 'payments', 'approvedBy', 'rejectedBy', 'interactions.user', 'returnRequests']);

        $whatsappAgentUrl = $waHelper->generateAgentContactLink($order);

        return view('admin.orders.show', compact('order', 'whatsappAgentUrl'));
    }

    /** Registra una interacción en la bitácora de atención del pedido. */
    public function storeInteraction(Request $request, Order $order): RedirectResponse
    {
        $validated = $request->validate([
            'channel'          => 'required|in:whatsapp,punto_fisico,llamada,email,web',
            'interaction_type' => 'required|string|max:60',
            'description'      => 'required|string|max:1000',
            'follow_up_notes'  => 'nullable|string|max:500',
        ]);

        /** @var \App\Models\User $admin */
        $admin = auth()->user();

        OrderInteraction::query()->create([
            'order_id'         => $order->id,
            'user_id'          => $admin->id,
            'channel'          => $validated['channel'],
            'interaction_type' => $validated['interaction_type'],
            'description'      => $validated['description'],
            'follow_up_notes'  => $validated['follow_up_notes'] ?? null,
        ]);

        return back()->with('status', 'Interacción registrada en la bitácora del pedido.');
    }

    /** Permite continuar un pedido existente (ej. cobrar/entregar en punto físico). */
    public function continueOrder(Request $request, Order $order, OmnichannelOrderServiceInterface $orderService): RedirectResponse
    {
        $validated = $request->validate([
            'payment_method' => 'required|string|max:50',
            'reference'      => 'nullable|string|max:100',
            'notes'          => 'nullable|string|max:500',
        ]);

        /** @var \App\Models\User $admin */
        $admin = auth()->user();

        try {
            $orderService->continueOrder($order, $admin, $validated);

            return back()->with('status', "Pedido {$order->order_number} completado y cobrado presencialmente.");
        } catch (\Throwable $e) {
            return back()->withErrors(['order' => $e->getMessage()]);
        }
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
