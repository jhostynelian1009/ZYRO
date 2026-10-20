<?php

namespace App\Http\Controllers\Admin;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Http\Controllers\Controller;
use App\Models\AnalyticsEvent;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AnalyticsController extends Controller
{
    /**
     * Muestra el panel de analítica de comportamiento, embudo y mapas de calor.
     */
    public function index(Request $request): View
    {
        $clarityProjectId = config('services.clarity.project_id');
        $clarityDashboardUrl = filled($clarityProjectId)
            ? "https://clarity.microsoft.com/projects/view/{$clarityProjectId}/dashboard"
            : null;

        // ─── 1. MÉTRICAS REALES DESDE BASE DE DATOS ──────────────────────────
        $productViewsCount = AnalyticsEvent::views()->count();
        $addToCartCount    = AnalyticsEvent::cartAdds()->count();
        $beginCheckoutCount = AnalyticsEvent::checkouts()->count();
        $ordersCount       = Order::query()->count();
        $paidOrdersCount   = Order::query()->where('status', OrderStatus::Paid)->count();
        $totalPaidRevenue  = (float) Payment::query()->where('status', PaymentStatus::Approved)->sum('amount');
        $interactionClicks = AnalyticsEvent::query()->where('event_type', 'like', 'interaction_%')->count();

        // Tasas de conversión reales
        $globalConversionRate = $productViewsCount > 0
            ? round(($paidOrdersCount / $productViewsCount) * 100, 2)
            : 0.0;

        $cartConversionRate = $addToCartCount > 0
            ? round(($paidOrdersCount / $addToCartCount) * 100, 2)
            : 0.0;

        // ─── 2. EMBUDO DE CONVERSIÓN REAL (5 ETAPAS) ─────────────────────────
        $funnel = [
            [
                'step'        => 1,
                'name'        => 'Vistas de Producto',
                'description' => 'Clientes navegando por fichas de chompas',
                'count'       => $productViewsCount,
                'pct_base'    => 100.0,
            ],
            [
                'step'        => 2,
                'name'        => 'Añadidos al Carrito',
                'description' => 'Prendas añadidas para compra o personalización',
                'count'       => $addToCartCount,
                'pct_base'    => $productViewsCount > 0 ? round(($addToCartCount / $productViewsCount) * 100, 1) : 0.0,
            ],
            [
                'step'        => 3,
                'name'        => 'Inicios de Checkout',
                'description' => 'Ingreso al formulario de entrega',
                'count'       => $beginCheckoutCount,
                'pct_base'    => $productViewsCount > 0 ? round(($beginCheckoutCount / $productViewsCount) * 100, 1) : 0.0,
            ],
            [
                'step'        => 4,
                'name'        => 'Pedidos Registrados',
                'description' => 'Solicitud confirmada en espera de revisión',
                'count'       => $ordersCount,
                'pct_base'    => $productViewsCount > 0 ? round(($ordersCount / $productViewsCount) * 100, 1) : 0.0,
            ],
            [
                'step'        => 5,
                'name'        => 'Compras Completadas',
                'description' => 'Pedidos aprobados y pagados con éxito',
                'count'       => $paidOrdersCount,
                'pct_base'    => $productViewsCount > 0 ? round(($paidOrdersCount / $productViewsCount) * 100, 1) : 0.0,
            ],
        ];

        // ─── 3. PRODUCTOS MÁS VISITADOS E INTERACTUADOS ──────────────────────
        $activeProducts = Product::query()->active()->get();

        $productRankings = $activeProducts->map(function (Product $product) {
            $views = AnalyticsEvent::views()->where('product_id', $product->id)->count();
            $cartAdds = AnalyticsEvent::cartAdds()->where('product_id', $product->id)->count();
            $clicks = AnalyticsEvent::query()
                ->where('event_type', 'like', 'interaction_%')
                ->where('product_id', $product->id)
                ->count();

            $purchasesCount = OrderItem::query()
                ->where('product_id', $product->id)
                ->whereHas('order', fn ($q) => $q->where('status', OrderStatus::Paid))
                ->sum('quantity');

            $cartRate = $views > 0 ? round(($cartAdds / $views) * 100, 1) : 0.0;
            $interactionScore = ($views * 1) + ($cartAdds * 4) + ($clicks * 2) + ($purchasesCount * 8);

            return [
                'product'          => $product,
                'views'            => $views,
                'cart_adds'        => $cartAdds,
                'clicks'           => $clicks,
                'purchases'        => (int) $purchasesCount,
                'cart_rate'        => $cartRate,
                'interaction_score' => $interactionScore,
            ];
        })->sortByDesc('views')->values();

        // ─── 4. DESGLOSE POR DISPOSITIVO (REAL) ──────────────────────────────
        $deviceBreakdown = AnalyticsEvent::query()
            ->selectRaw('device_type, count(*) as count')
            ->groupBy('device_type')
            ->pluck('count', 'device_type')
            ->toArray();

        $totalDeviceEvents = array_sum($deviceBreakdown);
        $devices = [
            'desktop' => [
                'name'  => 'Escritorio',
                'count' => $deviceBreakdown['desktop'] ?? 0,
                'pct'   => $totalDeviceEvents > 0 ? round((($deviceBreakdown['desktop'] ?? 0) / $totalDeviceEvents) * 100, 1) : 0,
                'icon'  => '💻',
            ],
            'mobile'  => [
                'name'  => 'Móvil',
                'count' => $deviceBreakdown['mobile'] ?? 0,
                'pct'   => $totalDeviceEvents > 0 ? round((($deviceBreakdown['mobile'] ?? 0) / $totalDeviceEvents) * 100, 1) : 0,
                'icon'  => '📱',
            ],
            'tablet'  => [
                'name'  => 'Tableta',
                'count' => $deviceBreakdown['tablet'] ?? 0,
                'pct'   => $totalDeviceEvents > 0 ? round((($deviceBreakdown['tablet'] ?? 0) / $totalDeviceEvents) * 100, 1) : 0,
                'icon'  => '📟',
            ],
        ];

        // ─── 5. ESTADO DEL MAPA DE CALOR (REAL VS DEMO) ───────────────────────
        $hasRealInteractions = ($productViewsCount + $interactionClicks + $addToCartCount) > 0;

        return view('admin.analytics.index', [
            'clarityProjectId'     => $clarityProjectId,
            'clarityDashboardUrl' => $clarityDashboardUrl,
            'productViewsCount'    => $productViewsCount,
            'addToCartCount'       => $addToCartCount,
            'beginCheckoutCount'   => $beginCheckoutCount,
            'ordersCount'          => $ordersCount,
            'paidOrdersCount'      => $paidOrdersCount,
            'totalPaidRevenue'     => $totalPaidRevenue,
            'interactionClicks'    => $interactionClicks,
            'globalConversionRate' => $globalConversionRate,
            'cartConversionRate'   => $cartConversionRate,
            'funnel'               => $funnel,
            'productRankings'      => $productRankings,
            'devices'              => $devices,
            'hasRealInteractions'  => $hasRealInteractions,
        ]);
    }
}
