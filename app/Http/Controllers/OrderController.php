<?php

namespace App\Http\Controllers;

use App\Http\Requests\CheckoutRequest;
use App\Models\Order;
use App\Services\CartService;
use App\Services\CheckoutService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class OrderController extends Controller
{
    public function __construct(
        private readonly CartService     $cart,
        private readonly CheckoutService $checkout,
    ) {}

    /** Muestra el formulario de checkout. */
    public function create(): View|RedirectResponse
    {
        if ($this->cart->count() === 0) {
            return redirect()->route('cart.index')
                ->withErrors(['cart' => 'El carrito está vacío.']);
        }

        /** @var \App\Models\User $user */
        $user = auth()->user();

        return view('orders.create', [
            'items'    => $this->cart->items(),
            'subtotal' => $this->cart->subtotal(),
            'user'     => $user,
        ]);
    }

    /** Procesa el checkout y crea el pedido. */
    public function store(CheckoutRequest $request): RedirectResponse
    {
        if ($this->cart->count() === 0) {
            return redirect()->route('cart.index')
                ->withErrors(['cart' => 'El carrito está vacío.']);
        }

        /** @var \App\Models\User $user */
        $user = auth()->user();

        try {
            $order = $this->checkout->createOrder(
                $user,
                $this->cart->items(),
                $request->validated()
            );

            $this->cart->clear();

            return redirect()->route('orders.show', $order)
                ->with('status', "Pedido {$order->order_number} enviado. Pendiente de aprobación.");
        } catch (\RuntimeException $e) {
            return back()->withErrors(['checkout' => $e->getMessage()])->withInput();
        }
    }

    /** Lista los pedidos del cliente autenticado. */
    public function index(): View
    {
        /** @var \App\Models\User $user */
        $user = auth()->user();

        $orders = Order::query()
            ->where('user_id', $user->id)
            ->with('items')
            ->latest()
            ->paginate(10);

        return view('orders.index', compact('orders'));
    }

    /** Muestra el detalle de un pedido propio. */
    public function show(Order $order): View
    {
        /** @var \App\Models\User $user */
        $user = auth()->user();

        abort_unless($order->isOwnedBy($user), 403);

        $order->load(['items.product', 'payments']);

        return view('orders.show', compact('order'));
    }
}
