@extends('layouts.admin')

@section('title', 'Dashboard — Admin — ZYRO')
@section('section_title', 'Dashboard')

@section('content')
    {{-- Encabezado de Página --}}
    <div class="admin-page-header">
        <div>
            <p class="admin-kicker">Panel administrativo &bull; Control Operativo</p>
            <h1 class="admin-title">Dashboard</h1>
            <p class="admin-subtitle">Resumen operativo de ZYRO.</p>
        </div>
        <div style="display:flex; gap:0.75rem;">
            <a href="{{ route('admin.orders.index') }}" class="btn-action btn-action-primary">
                <span>📦</span>
                <span>Gestionar Pedidos</span>
            </a>
            <button id="refresh-btn" onclick="fetchStats()" class="btn-action" title="Actualizar estadísticas en vivo">
                <span id="refresh-icon">↻</span>
                <span>Actualizar</span>
            </button>
        </div>
    </div>

    {{-- Tarjetas de Métricas (4 Indicadores Reales de MySQL) --}}
    <div class="stats-grid-4" id="stats-grid">
        {{-- 1. Pedidos pendientes de aprobación --}}
        <a href="{{ route('admin.orders.index', ['status' => 'pending_approval']) }}" class="stat-card accent-amber" style="display:flex;">
            <div>
                <div class="stat-card-head">
                    <span class="stat-card-label">Pedidos pendientes de aprobación</span>
                    <span class="stat-card-icon icon-amber">⏳</span>
                </div>
                <div class="stat-card-val" id="stat-pending">{{ $stats['pending_orders'] }}</div>
            </div>
            <p class="stat-card-desc">Requieren aprobación o rechazo del administrador.</p>
        </a>

        {{-- 2. Pedidos pagados --}}
        <a href="{{ route('admin.orders.index', ['status' => 'paid']) }}" class="stat-card accent-green" style="display:flex;">
            <div>
                <div class="stat-card-head">
                    <span class="stat-card-label">Pedidos pagados</span>
                    <span class="stat-card-icon icon-green">✓</span>
                </div>
                <div class="stat-card-val" id="stat-paid">{{ $stats['paid_orders'] }}</div>
            </div>
            <p class="stat-card-desc">Transacciones simuladas completadas con éxito.</p>
        </a>

        {{-- 3. Ingresos simulados --}}
        <div class="stat-card accent-blue">
            <div>
                <div class="stat-card-head">
                    <span class="stat-card-label">Ingresos simulados</span>
                    <span class="stat-card-icon icon-blue">💳</span>
                </div>
                <div class="stat-card-val" id="stat-revenue">${{ $stats['simulated_revenue'] }}</div>
            </div>
            <p class="stat-card-desc">Solo pagos aprobados. Excluye pedidos pendientes o rechazados.</p>
        </div>

        {{-- 4. Productos activos --}}
        <a href="{{ route('admin.inventory.index') }}" class="stat-card accent-black" style="display:flex;">
            <div>
                <div class="stat-card-head">
                    <span class="stat-card-label">Productos activos</span>
                    <span class="stat-card-icon icon-black">🏷️</span>
                </div>
                <div class="stat-card-val" id="stat-products">{{ $stats['total_products'] }}</div>
            </div>
            <p class="stat-card-desc" id="stat-products-desc">
                @if ($stats['low_stock_products'] > 0)
                    <span style="color:var(--zyro-amber-text); font-weight:600;">⚠ {{ $stats['low_stock_products'] }} con stock bajo (&le;3)</span>
                @else
                    Catálogo comercial disponible en tienda.
                @endif
            </p>
        </a>
    </div>

    {{-- Tabla de Actividad Reciente --}}
    <div class="card-table-wrapper">
        <div class="card-table-head">
            <div>
                <h2 class="card-table-title">Actividad Reciente</h2>
                <span style="font-size:0.75rem; color:var(--zyro-muted);">Últimos 10 pedidos registrados en el sistema</span>
            </div>
            <a href="{{ route('admin.orders.index') }}" class="btn-action">
                <span>Ver todos los pedidos</span>
                <span>&rarr;</span>
            </a>
        </div>

        @if ($recentOrders->isEmpty())
            <div style="padding: 4rem 2rem; text-align: center; color: var(--zyro-muted);">
                <p style="font-size: 1.125rem; font-weight: 500; margin-bottom: 0.5rem; color: var(--zyro-black);">No hay pedidos todavía</p>
                <p style="font-size: 0.8125rem;">Cuando los clientes realicen compras desde la tienda, aparecerán listados aquí.</p>
            </div>
        @else
            <div class="table-responsive">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>Número de Pedido</th>
                            <th>Cliente</th>
                            <th>Total</th>
                            <th>Estado</th>
                            <th>Fecha de Creación</th>
                            <th style="text-align: right;">Acción</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($recentOrders as $order)
                            <tr class="{{ $order->status->value === 'pending_approval' ? 'row-pending' : '' }}">
                                <td>
                                    <strong style="font-family:monospace; font-size:0.875rem;">{{ $order->order_number }}</strong>
                                </td>
                                <td>
                                    <div style="font-weight:600;">{{ $order->customer_name ?: ($order->user->name ?? '—') }}</div>
                                    <div style="font-size:0.6875rem; color:var(--zyro-muted);">{{ $order->user->email ?? '—' }}</div>
                                </td>
                                <td>
                                    <span style="font-weight:700;">${{ number_format($order->total, 2) }}</span>
                                </td>
                                <td>
                                    @include('orders.partials.status-badge', ['status' => $order->status])
                                </td>
                                <td style="color:var(--zyro-muted);">
                                    {{ $order->created_at->format('d/m/Y H:i') }}
                                </td>
                                <td style="text-align: right;">
                                    <a href="{{ route('admin.orders.show', $order) }}" class="btn-action btn-action-primary">
                                        Ver detalle
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif

        <div style="padding: 0.85rem 1.5rem; background: #FAFAFA; border-top: 1px solid var(--zyro-border); display:flex; justify-content:space-between; align-items:center; font-size:0.75rem; color:var(--zyro-muted);">
            <span>Actualización periódica automática cada 8 segundos.</span>
            <span id="last-update">Última consulta: {{ now()->format('H:i:s') }}</span>
        </div>
    </div>
@endsection

@push('scripts')
<script>
    const statsUrl = '{{ route('admin.updates') }}';

    function fetchStats() {
        const refreshIcon = document.getElementById('refresh-icon');
        if (refreshIcon) refreshIcon.style.display = 'inline-block';

        fetch(statsUrl, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
            .then(r => r.json())
            .then(data => {
                document.getElementById('stat-pending').textContent  = data.pending_orders;
                document.getElementById('stat-paid').textContent     = data.paid_orders;
                document.getElementById('stat-revenue').textContent  = '$' + data.simulated_revenue;
                document.getElementById('stat-products').textContent = data.total_products;
                document.getElementById('last-update').textContent   = 'Última consulta: ' + new Date().toLocaleTimeString('es-EC');
            })
            .catch(() => {});
    }

    // Polling cada 8 segundos
    setInterval(fetchStats, 8000);
</script>
@endpush
