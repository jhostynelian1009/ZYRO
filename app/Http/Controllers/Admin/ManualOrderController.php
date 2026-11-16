<?php

namespace App\Http\Controllers\Admin;

use App\Contracts\OmnichannelOrderServiceInterface;
use App\Enums\OrderChannel;
use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ManualOrderController extends Controller
{
    public function create(): View
    {
        $products = Product::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $customers = User::query()
            ->where('role', UserRole::Customer)
            ->latest()
            ->limit(30)
            ->get();

        $channels = [
            OrderChannel::PuntoFisico->value => OrderChannel::PuntoFisico->label(),
            OrderChannel::WhatsApp->value    => OrderChannel::WhatsApp->label(),
        ];

        return view('admin.orders.create', compact('products', 'customers', 'channels'));
    }

    public function store(Request $request, OmnichannelOrderServiceInterface $orderService): RedirectResponse
    {
        $validated = $request->validate([
            'channel_origin'     => 'required|in:punto_fisico,whatsapp',
            'customer_name'      => 'required|string|max:120',
            'phone'              => 'required|string|max:30',
            'email'              => 'nullable|email|max:150',
            'user_id'            => 'nullable|exists:users,id',
            'address'            => 'required|string|max:255',
            'city'               => 'required|string|max:100',
            'delivery_method'    => 'required|in:pickup,standard,express',
            'payment_method'     => 'required|string|max:50',
            'mark_as_paid'       => 'nullable|boolean',
            'delivery_notes'     => 'nullable|string|max:500',
            'interaction_notes'  => 'nullable|string|max:1000',
            'items'              => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity'   => 'required|integer|min:1',
            'items.*.size'       => 'nullable|string|max:20',
        ]);

        /** @var \App\Models\User $admin */
        $admin = auth()->user();

        try {
            $order = $orderService->createManualOrder($admin, $validated);

            return redirect()
                ->route('admin.orders.show', $order)
                ->with('status', "Venta manual registrada exitosamente con número {$order->order_number}.");
        } catch (\Throwable $e) {
            return back()
                ->withInput()
                ->withErrors(['order' => $e->getMessage()]);
        }
    }
}
