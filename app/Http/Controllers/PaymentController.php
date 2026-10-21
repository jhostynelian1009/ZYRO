<?php

namespace App\Http\Controllers;

use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Http\Requests\PaymentRequest;
use App\Models\Order;
use App\Services\AnalyticsTracker;
use App\Services\SimulatedPaymentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class PaymentController extends Controller
{
    public function __construct(
        private readonly SimulatedPaymentService $paymentService,
    ) {}

    /** Muestra el formulario de pago simulado. */
    public function create(Order $order): View|RedirectResponse
    {
        /** @var \App\Models\User $user */
        $user = auth()->user();

        abort_unless($order->isOwnedBy($user), 403);

        if ($order->status === OrderStatus::Paid) {
            return redirect()->route('orders.show', $order)
                ->with('status', 'Este pedido ya se encuentra pagado.');
        }

        if ($order->status === OrderStatus::Rejected || $order->status === OrderStatus::Cancelled) {
            return redirect()->route('orders.show', $order)
                ->withErrors(['payment' => 'Este pedido ha sido cancelado o rechazado y no admite pagos.']);
        }

        $methods = PaymentMethod::cases();

        return view('orders.payment', compact('order', 'methods'));
    }

    /** Procesa el pago simulado. */
    public function store(PaymentRequest $request, Order $order): RedirectResponse
    {
        /** @var \App\Models\User $user */
        $user = auth()->user();

        abort_unless($order->isOwnedBy($user), 403);

        if ($order->status === OrderStatus::Paid) {
            return redirect()->route('orders.show', $order)
                ->with('status', 'Este pedido ya se encuentra pagado.');
        }

        if ($order->status === OrderStatus::Rejected || $order->status === OrderStatus::Cancelled) {
            return redirect()->route('orders.show', $order)
                ->withErrors(['payment' => 'Este pedido ha sido cancelado o rechazado y no admite pagos.']);
        }

        $method = PaymentMethod::from($request->validated('method'));

        try {
            $result = $this->paymentService->process($order, $method);

            if ($result['success']) {
                AnalyticsTracker::track('purchase_completed', null, [
                    'order_id'     => $order->id,
                    'order_number' => $order->order_number,
                    'amount'       => (float) $order->total_amount,
                    'method'       => $method->value,
                ]);

                return redirect()->route('orders.show', $order)
                    ->with('status', $result['message']);
            }

            return redirect()->route('orders.payment', $order)
                ->withErrors(['payment' => $result['message']]);

        } catch (\RuntimeException $e) {
            return redirect()->route('orders.show', $order)
                ->withErrors(['payment' => $e->getMessage()]);
        }
    }
}
