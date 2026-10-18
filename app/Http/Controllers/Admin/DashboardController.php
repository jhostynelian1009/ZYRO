<?php

namespace App\Http\Controllers\Admin;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

class DashboardController extends Controller
{
    /** Dashboard principal con estadísticas. */
    public function index(): View
    {
        $stats = $this->buildStats();

        $recentOrders = Order::query()
            ->with('user')
            ->latest()
            ->limit(10)
            ->get();

        return view('admin.dashboard', compact('stats', 'recentOrders'));
    }

    /**
     * Endpoint JSON para polling del dashboard.
     * Solo accesible para admins (middleware en ruta).
     */
    public function updates(): JsonResponse
    {
        return response()->json($this->buildStats());
    }

    private function buildStats(): array
    {
        $pendingCount = Order::query()
            ->where('status', OrderStatus::PendingApproval)
            ->count();

        $paidCount = Order::query()
            ->where('status', OrderStatus::Paid)
            ->count();

        // Ingresos simulados = suma de pagos aprobados de órdenes paid.
        $simulatedRevenue = Payment::query()
            ->where('status', PaymentStatus::Approved)
            ->whereHas('order', fn ($q) => $q->where('status', OrderStatus::Paid))
            ->sum('amount');

        $totalProducts = Product::query()->where('is_active', true)->count();
        $lowStockCount = Product::query()->where('is_active', true)->where('stock', '<=', 3)->count();

        return [
            'pending_orders'     => $pendingCount,
            'paid_orders'        => $paidCount,
            'simulated_revenue'  => number_format((float) $simulatedRevenue, 2, '.', ','),
            'total_products'     => $totalProducts,
            'low_stock_products' => $lowStockCount,
        ];
    }
}
