<?php

namespace App\Http\Controllers\Admin;

use App\Contracts\ReturnRequestServiceInterface;
use App\Enums\ReturnInspectedCondition;
use App\Enums\ReturnRequestStatus;
use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ReturnRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ReturnRequestController extends Controller
{
    public function index(Request $request): View
    {
        $status = $request->query('status');
        $q = trim((string) $request->query('q'));

        $query = ReturnRequest::query()->with(['order', 'user', 'product', 'reviewer'])->latest();

        if ($status && in_array($status, array_column(ReturnRequestStatus::cases(), 'value'), true)) {
            $query->where('status', $status);
        }

        if ($q !== '') {
            $query->where(function ($sub) use ($q) {
                $sub->where('request_number', 'like', "%{$q}%")
                    ->orWhereHas('order', function ($o) use ($q) {
                        $o->where('order_number', 'like', "%{$q}%");
                    })
                    ->orWhereHas('user', function ($u) use ($q) {
                        $u->where('name', 'like', "%{$q}%")
                            ->orWhere('email', 'like', "%{$q}%");
                    });
            });
        }

        $requests = $query->paginate(15)->withQueryString();

        $counts = [
            'all'         => ReturnRequest::query()->count(),
            'recibida'    => ReturnRequest::query()->where('status', ReturnRequestStatus::Recibida)->count(),
            'en_revision' => ReturnRequest::query()->where('status', ReturnRequestStatus::EnRevision)->count(),
            'resuelta'    => ReturnRequest::query()->where('status', ReturnRequestStatus::Resuelta)->count(),
            'rechazada'   => ReturnRequest::query()->where('status', ReturnRequestStatus::Rechazada)->count(),
        ];

        return view('admin.returns.index', compact('requests', 'status', 'q', 'counts'));
    }

    public function show(ReturnRequest $returnRequest): View
    {
        $returnRequest->load(['order.items', 'user', 'orderItem', 'product', 'reviewer', 'replacementProduct', 'histories.changedBy']);

        $availableProducts = Product::query()->where('is_active', true)->where('stock', '>', 0)->orderBy('name')->get();
        $conditions = ReturnInspectedCondition::cases();

        return view('admin.returns.show', compact('returnRequest', 'availableProducts', 'conditions'));
    }

    public function startInspection(Request $request, ReturnRequest $returnRequest, ReturnRequestServiceInterface $returnService): RedirectResponse
    {
        $note = $request->input('note');

        /** @var \App\Models\User $admin */
        $admin = auth()->user();

        try {
            $returnService->startInspection($returnRequest, $admin, $note);

            return back()->with('status', 'Solicitud colocada en estado «En revisión». Inspección física iniciada.');
        } catch (\Throwable $e) {
            return back()->withErrors(['request' => $e->getMessage()]);
        }
    }

    public function resolve(Request $request, ReturnRequest $returnRequest, ReturnRequestServiceInterface $returnService): RedirectResponse
    {
        $validated = $request->validate([
            'status'                 => 'required|in:resuelta,rechazada',
            'inspected_condition'    => 'required|in:pendiente,optimo_con_etiquetas,usado_limpio,defecto_fabrica,no_apto',
            'admin_decision'         => 'required|string|max:1000',
            'should_restock'         => 'nullable|boolean',
            'replacement_product_id' => 'nullable|exists:products,id',
            'replacement_size'       => 'nullable|string|max:20',
        ]);

        /** @var \App\Models\User $admin */
        $admin = auth()->user();

        try {
            $returnService->resolveRequest($returnRequest, $admin, $validated);

            $action = $validated['status'] === 'resuelta' ? 'resuelta con éxito' : 'rechazada';

            return back()->with('status', "Solicitud {$returnRequest->request_number} dictaminada como {$action}.");
        } catch (\Throwable $e) {
            return back()->withErrors(['request' => $e->getMessage()]);
        }
    }
}
