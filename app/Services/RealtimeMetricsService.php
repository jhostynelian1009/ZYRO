<?php

namespace App\Services;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Product;

/**
 * Servicio centralizado para métricas en tiempo real.
 * Reutilizado tanto por el DashboardController como por los eventos de Broadcasting.
 */
class RealtimeMetricsService
{
    /**
     * Obtiene las métricas operativas actualizadas desde MySQL.
     *
     * @return array<string, mixed>
     */
    public static function current(): array
    {
        $pendingCount = Order::query()
            ->where('status', OrderStatus::PendingApproval)
            ->count();

        $approvedCount = Order::query()
            ->where('status', OrderStatus::Approved)
            ->count();

        $paidCount = Order::query()
            ->where('status', OrderStatus::Paid)
            ->count();

        $rejectedCount = Order::query()
            ->where('status', OrderStatus::Rejected)
            ->count();

        $totalOrders = Order::query()->count();

        // Ingresos simulados = suma de pagos aprobados de órdenes paid.
        $simulatedRevenue = (float) Payment::query()
            ->where('status', PaymentStatus::Approved)
            ->whereHas('order', fn ($q) => $q->where('status', OrderStatus::Paid))
            ->sum('amount');

        $totalProducts = Product::query()->where('is_active', true)->count();
        $lowStockCount = Product::query()->where('is_active', true)->where('stock', '<=', 3)->count();

        return [
            'pending_orders'     => $pendingCount,
            'approved_orders'    => $approvedCount,
            'paid_orders'        => $paidCount,
            'rejected_orders'    => $rejectedCount,
            'total_orders'       => $totalOrders,
            'simulated_revenue'  => number_format($simulatedRevenue, 2, '.', ','),
            'simulated_revenue_raw' => $simulatedRevenue,
            'total_products'     => $totalProducts,
            'low_stock_products' => $lowStockCount,
        ];
    }
}
