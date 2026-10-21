@extends('layouts.admin')

@section('title', 'Gestión de Pedidos — Admin — ZYRO')
@section('section_title', 'Pedidos')

@section('content')
    <div class="admin-page-header">
        <div>
            <p class="admin-kicker">Operaciones &bull; Ventas</p>
            <h1 class="admin-title">Gestión de Pedidos</h1>
            <p class="admin-subtitle">Revisión, aprobación y seguimiento del flujo transaccional.</p>
        </div>
        <div style="display:flex; gap:0.75rem;">
            <a href="{{ route('admin.dashboard') }}" class="btn-action">
                <span>&larr;</span>
                <span>Volver al Dashboard</span>
            </a>
        </div>
    </div>

    {{-- Filtros y Buscador --}}
    <div class="filters-toolbar">
        <div class="filter-tabs" role="tablist">
            <a
                href="{{ route('admin.orders.index', array_merge(request()->except(['status', 'page']))) }}"
                class="filter-tab {{ empty($status) ? 'active' : '' }}"
            >
                <span>Todos</span>
                <span class="tab-count" id="count-tab-all">{{ $counts['all'] ?? 0 }}</span>
            </a>
            <a
                href="{{ route('admin.orders.index', array_merge(request()->except(['status', 'page']), ['status' => 'pending_approval'])) }}"
                class="filter-tab {{ $status === 'pending_approval' ? 'active' : '' }}"
            >
                <span>Pendientes</span>
                <span class="tab-count" id="count-tab-pending" style="{{ ($counts['pending_approval'] ?? 0) > 0 ? 'background:#FDE68A; color:#92400E;' : '' }}">
                    {{ $counts['pending_approval'] ?? 0 }}
                </span>
            </a>
            <a
                href="{{ route('admin.orders.index', array_merge(request()->except(['status', 'page']), ['status' => 'approved'])) }}"
                class="filter-tab {{ $status === 'approved' ? 'active' : '' }}"
            >
                <span>Aprobados</span>
                <span class="tab-count" id="count-tab-approved">{{ $counts['approved'] ?? 0 }}</span>
            </a>
            <a
                href="{{ route('admin.orders.index', array_merge(request()->except(['status', 'page']), ['status' => 'paid'])) }}"
                class="filter-tab {{ $status === 'paid' ? 'active' : '' }}"
            >
                <span>Pagados</span>
                <span class="tab-count" id="count-tab-paid">{{ $counts['paid'] ?? 0 }}</span>
            </a>
            <a
                href="{{ route('admin.orders.index', array_merge(request()->except(['status', 'page']), ['status' => 'rejected'])) }}"
                class="filter-tab {{ $status === 'rejected' ? 'active' : '' }}"
            >
                <span>Rechazados</span>
                <span class="tab-count" id="count-tab-rejected">{{ $counts['rejected'] ?? 0 }}</span>
            </a>
        </div>

        <form method="GET" action="{{ route('admin.orders.index') }}" class="search-box">
            @if (!empty($status))
                <input type="hidden" name="status" value="{{ $status }}">
            @endif
            <input
                type="search"
                name="q"
                value="{{ $q ?? '' }}"
                placeholder="Buscar por # o cliente..."
                aria-label="Buscar pedidos"
            >
            <button type="submit" aria-label="Ejecutar búsqueda">&rarr;</button>
            @if (!empty($q))
                <a href="{{ route('admin.orders.index', array_merge(request()->except('q'))) }}" style="font-size:0.75rem; color:var(--zyro-muted); margin-left:0.25rem;" title="Limpiar búsqueda">&times;</a>
            @endif
        </form>
    </div>

    {{-- Tabla de Pedidos --}}
    <div class="card-table-wrapper">
        <div class="card-table-head">
            <div>
                <h2 class="card-table-title">
                    Listado de Pedidos
                    @if (!empty($status))
                        <span style="font-size:0.75rem; font-weight:normal; color:var(--zyro-muted);">
                            &bull; Filtrado por: <strong>{{ ucfirst(str_replace('_', ' ', $status)) }}</strong>
                        </span>
                    @endif
                </h2>
                <span style="font-size:0.75rem; color:var(--zyro-muted);">
                    {{ $orders->total() }} pedido(s) encontrado(s)
                </span>
            </div>
        </div>

        <div id="orders-empty-state" style="padding: 4.5rem 2rem; text-align: center; color: var(--zyro-muted); {{ $orders->isEmpty() ? '' : 'display:none;' }}">
            <p style="font-size: 1.125rem; font-weight: 500; margin-bottom: 0.5rem; color: var(--zyro-black);">
                No se encontraron pedidos
            </p>
            <p style="font-size: 0.8125rem; margin-bottom: 1.25rem;">
                @if (!empty($q) || !empty($status))
                    No hay pedidos que coincidan con los criterios de búsqueda o filtro seleccionados.
                @else
                    Aún no se han registrado pedidos en la tienda.
                @endif
            </p>
            @if (!empty($q) || !empty($status))
                <a href="{{ route('admin.orders.index') }}" class="btn-action">
                    Restablecer todos los filtros
                </a>
            @endif
        </div>

        <div id="orders-table-wrap" class="table-responsive" style="{{ $orders->isEmpty() ? 'display:none;' : '' }}">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Número</th>
                        <th>Cliente</th>
                        <th>Modalidad / Entrega</th>
                        <th>Total</th>
                        <th>Estado</th>
                        <th>Fecha de Creación</th>
                        <th style="text-align: right;">Acción</th>
                    </tr>
                </thead>
                <tbody id="admin-orders-tbody">
                    @foreach ($orders as $order)
                        <tr id="admin-order-{{ $order->id }}" class="{{ $order->status->value === 'pending_approval' ? 'row-pending' : '' }}">
                            <td>
                                <strong style="font-family:monospace; font-size:0.875rem;">
                                    {{ $order->order_number }}
                                </strong>
                            </td>
                            <td>
                                <div style="font-weight:600;">{{ $order->customer_name ?: ($order->user->name ?? '—') }}</div>
                                <div style="font-size:0.6875rem; color:var(--zyro-muted);">${{ $order->user->email ?? '—' }}</div>
                            </td>
                            <td>
                                <div style="font-weight:600; font-size:0.8125rem;">{{ $order->delivery_method_label }}</div>
                                <div style="font-size:0.6875rem; color:var(--zyro-muted); margin-top:0.15rem;">
                                    ⏱ {{ $order->delivery_estimate_label }}
                                </div>
                            </td>
                            <td>
                                <span style="font-weight:700;">${{ number_format($order->total, 2) }}</span>
                                <div style="font-size:0.6875rem; color:var(--zyro-muted);">
                                    Envío: {{ $order->shipping_amount > 0 ? '$'.number_format($order->shipping_amount, 2) : 'GRATIS' }}
                                </div>
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

        @if ($orders->hasPages())
            <div class="pagination-wrap">
                {{ $orders->links() }}
            </div>
        @endif
    </div>
@endsection

@push('scripts')
<script>
    const currentStatusFilter = @json($status ?? '');
    const currentSearchQuery  = @json($q ?? '');

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

    function updateTabCounts(metrics) {
        if (!metrics) return;
        const allEl      = document.getElementById('count-tab-all');
        const pendingEl  = document.getElementById('count-tab-pending');
        const approvedEl = document.getElementById('count-tab-approved');
        const paidEl     = document.getElementById('count-tab-paid');
        const rejectedEl = document.getElementById('count-tab-rejected');

        if (allEl && metrics.total_orders !== undefined) allEl.textContent = metrics.total_orders;
        if (pendingEl && metrics.pending_orders !== undefined) {
            pendingEl.textContent = metrics.pending_orders;
            pendingEl.style.background = metrics.pending_orders > 0 ? '#FDE68A' : '';
            pendingEl.style.color = metrics.pending_orders > 0 ? '#92400E' : '';
        }
        if (approvedEl && metrics.approved_orders !== undefined) approvedEl.textContent = metrics.approved_orders;
        if (paidEl && metrics.paid_orders !== undefined) paidEl.textContent = metrics.paid_orders;
        if (rejectedEl && metrics.rejected_orders !== undefined) rejectedEl.textContent = metrics.rejected_orders;
    }

    document.addEventListener('DOMContentLoaded', function () {
        if (typeof window.zyroSubscribe === 'function') {
            const channel = window.zyroSubscribe('private', 'admin');

            // 1. Nuevo pedido creado
            channel.listen('.OrderCreated', function (e) {
                if (e.metrics) updateTabCounts(e.metrics);

                // Si el filtro actual es compatible (todos o pendientes, y sin búsqueda activa)
                const isCompatible = (currentStatusFilter === '' || currentStatusFilter === 'pending_approval') && currentSearchQuery === '';

                if (isCompatible) {
                    const emptyState = document.getElementById('orders-empty-state');
                    const tableWrap  = document.getElementById('orders-table-wrap');
                    const tbody      = document.getElementById('admin-orders-tbody');

                    if (emptyState) emptyState.style.display = 'none';
                    if (tableWrap) tableWrap.style.display = 'block';

                    if (tbody && !document.getElementById(`admin-order-${e.order.id}`)) {
                        const tr = document.createElement('tr');
                        tr.id = `admin-order-${e.order.id}`;
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
                    }
                }
            });

            // 2. Cambio de estado en pedidos
            channel.listen('.OrderStatusUpdated', function (e) {
                if (e.metrics) updateTabCounts(e.metrics);

                const row = document.getElementById(`admin-order-${e.order_id}`);
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

                    // Si estamos filtrando estrictamente por otro estado y el pedido ya no coincide,
                    // le damos un estilo visual atenuado
                    if (currentStatusFilter && currentStatusFilter !== e.to_status) {
                        row.style.opacity = '0.5';
                    }
                }
            });

            // 3. Pagos
            channel.listen('.PaymentStatusUpdated', function (e) {
                if (e.metrics) updateTabCounts(e.metrics);
            });
        }
    });
</script>
@endpush
