<?php

namespace App\Http\Controllers;

use App\Contracts\ReturnRequestServiceInterface;
use App\Enums\ReturnRequestedSolution;
use App\Models\Order;
use App\Models\ReturnRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CustomerReturnRequestController extends Controller
{
    public function index(): View
    {
        /** @var \App\Models\User $user */
        $user = auth()->user();

        $requests = ReturnRequest::query()
            ->where('user_id', $user->id)
            ->with(['order', 'product', 'orderItem'])
            ->latest()
            ->paginate(10);

        return view('customer.returns.index', compact('requests'));
    }

    public function create(Order $order): View|RedirectResponse
    {
        /** @var \App\Models\User $user */
        $user = auth()->user();

        if (! $order->isOwnedBy($user)) {
            abort(403, 'No tienes permiso para solicitar cambios sobre este pedido.');
        }

        $order->load('items.product');
        $solutions = ReturnRequestedSolution::cases();

        return view('customer.returns.create', compact('order', 'solutions'));
    }

    public function store(Request $request, Order $order, ReturnRequestServiceInterface $returnService): RedirectResponse
    {
        /** @var \App\Models\User $user */
        $user = auth()->user();

        if (! $order->isOwnedBy($user)) {
            abort(403, 'No tienes permiso para solicitar cambios sobre este pedido.');
        }

        $validated = $request->validate([
            'order_item_id'       => 'required|exists:order_items,id',
            'quantity'            => 'required|integer|min:1',
            'reason'              => 'required|string|max:150',
            'problem_description' => 'required|string|max:1000',
            'requested_solution'  => 'required|in:cambio_talla,cambio_color,devolucion_dinero,garantia_defecto',
        ]);

        try {
            $returnRequest = $returnService->createCustomerRequest($user, $order, $validated);

            return redirect()
                ->route('customer.returns.index')
                ->with('status', "Tu solicitud #{$returnRequest->request_number} ha sido registrada. Nos comunicaremos contigo para coordinar la revisión de la prenda.");
        } catch (\Throwable $e) {
            return back()
                ->withInput()
                ->withErrors(['return_request' => $e->getMessage()]);
        }
    }

    public function show(ReturnRequest $returnRequest): View
    {
        /** @var \App\Models\User $user */
        $user = auth()->user();

        if ($returnRequest->user_id !== $user->id) {
            abort(403);
        }

        $returnRequest->load(['order', 'product', 'orderItem', 'replacementProduct', 'histories']);

        return view('customer.returns.show', compact('returnRequest'));
    }
}
