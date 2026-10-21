<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\RealtimeMetricsService;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

class DashboardController extends Controller
{
    /** Dashboard principal con estadísticas. */
    public function index(): View
    {
        $stats = RealtimeMetricsService::current();

        $recentOrders = Order::query()
            ->with('user')
            ->latest()
            ->limit(10)
            ->get();

        return view('admin.dashboard', compact('stats', 'recentOrders'));
    }

    /**
     * Endpoint JSON para polling de respaldo del dashboard.
     * Solo accesible para admins (middleware en ruta).
     */
    public function updates(): JsonResponse
    {
        return response()->json(RealtimeMetricsService::current());
    }
}
