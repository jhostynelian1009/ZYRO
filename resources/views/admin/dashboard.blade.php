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

        <div id="recent-empty" style="padding: 4rem 2rem; text-align: center; color: var(--zyro-muted); {{ $recentOrders->isEmpty() ? '' : 'display:none;' }}">
            <p style="font-size: 1.125rem; font-weight: 500; margin-bottom: 0.5rem; color: var(--zyro-black);">No hay pedidos todavía</p>
            <p style="font-size: 0.8125rem;">Cuando los clientes realicen compras desde la tienda, aparecerán listados aquí.</p>
        </div>

        <div id="recent-table-wrap" class="table-responsive" style="{{ $recentOrders->isEmpty() ? 'display:none;' : '' }}">
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
                <tbody id="recent-orders-tbody">
                    @foreach ($recentOrders as $order)
                        <tr id="recent-order-{{ $order->id }}" class="{{ $order->status->value === 'pending_approval' ? 'row-pending' : '' }}">
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
                            <td class="order-status-cell">
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

        <div style="padding: 0.85rem 1.5rem; background: #FAFAFA; border-top: 1px solid var(--zyro-border); display:flex; justify-content:space-between; align-items:center; font-size:0.75rem; color:var(--zyro-muted);">
            <span>Actualización periódica automática cada 8 segundos.</span>
            <span id="last-update">Última consulta: {{ now()->format('H:i:s') }}</span>
        </div>
    </div>
@endsection

@push('scripts')
<script>
    const statsUrl = '{{ route('admin.updates') }}';

    function updateStatsUI(data) {
        if (!data) return;
        const pendingEl = document.getElementById('stat-pending');
        const paidEl    = document.getElementById('stat-paid');
        const revEl     = document.getElementById('stat-revenue');
        const prodEl    = document.getElementById('stat-products');
        const prodDesc  = document.getElementById('stat-products-desc');
        const lastUp    = document.getElementById('last-update');

        if (pendingEl && data.pending_orders !== undefined) pendingEl.textContent = data.pending_orders;
        if (paidEl && data.paid_orders !== undefined) paidEl.textContent = data.paid_orders;
        if (revEl && data.simulated_revenue !== undefined) revEl.textContent = '$' + data.simulated_revenue;
        if (prodEl && data.total_products !== undefined) prodEl.textContent = data.total_products;

        if (prodDesc && data.low_stock_products !== undefined) {
            if (data.low_stock_products > 0) {
                prodDesc.innerHTML = `<span style="color:var(--zyro-amber-text); font-weight:600;">⚠ ${data.low_stock_products} con stock bajo (&le;3)</span>`;
            } else {
                prodDesc.textContent = 'Catálogo comercial disponible en tienda.';
            }
        }

        if (lastUp) {
            lastUp.textContent = 'Última sincronización: ' + new Date().toLocaleTimeString('es-EC');
        }
    }

    function fetchStats() {
        fetch(statsUrl, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
            .then(r => r.json())
            .then(data => updateStatsUI(data))
            .catch(() => {});
    }

    function createBadgeHtml(status) {
        if (status === 'approved') {
            return '<span class="badge-status badge-approved">✓ Aprobado</span>';
        } else if (status === 'paid') {
            return '<span class="badge-status badge-paid">✓ Pagado</span>';
        } else if (status === 'rejected') {
            return '<span class="badge-status badge-rejected">✗ Rechazado</span>';
        } else if (status === 'cancelled') {
            return '<span class="badge-status badge-cancelled">Cancelado</span>';
        }
        return '<span class="badge-status badge-pending">⏳ Pendiente de aprobación</span>';
    }

    document.addEventListener('DOMContentLoaded', function () {
        if (typeof window.zyroSubscribe === 'function') {
            const channel = window.zyroSubscribe('private', 'admin');

            // 1. Pedido nuevo en tiempo real
            channel.listen('.OrderCreated', function (e) {
                if (e.metrics) updateStatsUI(e.metrics);

                const emptyDiv  = document.getElementById('recent-empty');
                const tableWrap = document.getElementById('recent-table-wrap');
                const tbody     = document.getElementById('recent-orders-tbody');

                if (emptyDiv) emptyDiv.style.display = 'none';
                if (tableWrap) tableWrap.style.display = 'block';

                if (tbody) {
                    // Evitar duplicar si ya existe
                    const existingRow = document.getElementById(`recent-order-${e.order.id}`);
                    if (!existingRow) {
                        const tr = document.createElement('tr');
                        tr.id = `recent-order-${e.order.id}`;
                        tr.className = 'row-pending';
                        tr.style.animation = 'zyroSlideIn 0.3s ease';

                        tr.innerHTML = `
                            <td><strong style="font-family:monospace; font-size:0.875rem;">${e.order.order_number}</strong></td>
                            <td>
                                <div style="font-weight:600;">${e.order.customer_name}</div>
                                <div style="font-size:0.6875rem; color:var(--zyro-muted);">${e.order.customer_email || '—'}</div>
                            </td>
                            <td><span style="font-weight:700;">$${e.order.total_formatted}</span></td>
                            <td class="order-status-cell">${createBadgeHtml(e.order.status)}</td>
                            <td style="color:var(--zyro-muted);">${e.order.created_at}</td>
                            <td style="text-align: right;">
                                <a href="${e.order.detail_url}" class="btn-action btn-action-primary">Ver detalle</a>
                            </td>
                        `;
                        tbody.prepend(tr);

                        // Mantener máximo 10 filas
                        while (tbody.children.length > 10) {
                            tbody.removeChild(tbody.lastElementChild);
                        }
                    }
                }
            });

            // 2. Cambio de estado en tiempo real
            channel.listen('.OrderStatusUpdated', function (e) {
                if (e.metrics) updateStatsUI(e.metrics);

                const row = document.getElementById(`recent-order-${e.order_id}`);
                if (row) {
                    const statusCell = row.querySelector('.order-status-cell');
                    if (statusCell) {
                        statusCell.innerHTML = createBadgeHtml(e.to_status);
                    }
                    if (e.to_status === 'pending_approval') {
                        row.classList.add('row-pending');
                    } else {
                        row.classList.remove('row-pending');
                    }
                }
            });

            // 3. Pago e Inventario
            channel.listen('.PaymentStatusUpdated', function (e) {
                if (e.metrics) updateStatsUI(e.metrics);
            });

            channel.listen('.InventoryUpdated', function (e) {
                if (e.metrics) updateStatsUI(e.metrics);
            });
        }

        // Respaldo seguro: sincronización ligera cada 25 segundos
        setInterval(fetchStats, 25000);
    });
</script>
@endpush
