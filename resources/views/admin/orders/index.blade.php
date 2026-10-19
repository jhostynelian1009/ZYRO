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
                <span class="tab-count">{{ $counts['all'] ?? 0 }}</span>
            </a>
            <a
                href="{{ route('admin.orders.index', array_merge(request()->except(['status', 'page']), ['status' => 'pending_approval'])) }}"
                class="filter-tab {{ $status === 'pending_approval' ? 'active' : '' }}"
            >
                <span>Pendientes</span>
                <span class="tab-count" style="{{ ($counts['pending_approval'] ?? 0) > 0 ? 'background:#FDE68A; color:#92400E;' : '' }}">
                    {{ $counts['pending_approval'] ?? 0 }}
                </span>
            </a>
            <a
                href="{{ route('admin.orders.index', array_merge(request()->except(['status', 'page']), ['status' => 'approved'])) }}"
                class="filter-tab {{ $status === 'approved' ? 'active' : '' }}"
            >
                <span>Aprobados</span>
                <span class="tab-count">{{ $counts['approved'] ?? 0 }}</span>
            </a>
            <a
                href="{{ route('admin.orders.index', array_merge(request()->except(['status', 'page']), ['status' => 'paid'])) }}"
                class="filter-tab {{ $status === 'paid' ? 'active' : '' }}"
            >
                <span>Pagados</span>
                <span class="tab-count">{{ $counts['paid'] ?? 0 }}</span>
            </a>
            <a
                href="{{ route('admin.orders.index', array_merge(request()->except(['status', 'page']), ['status' => 'rejected'])) }}"
                class="filter-tab {{ $status === 'rejected' ? 'active' : '' }}"
            >
                <span>Rechazados</span>
                <span class="tab-count">{{ $counts['rejected'] ?? 0 }}</span>
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

        @if ($orders->isEmpty())
            <div style="padding: 4.5rem 2rem; text-align: center; color: var(--zyro-muted);">
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
        @else
            <div class="table-responsive">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>Número</th>
                            <th>Cliente</th>
                            <th>Total</th>
                            <th>Estado</th>
                            <th>Fecha de Creación</th>
                            <th style="text-align: right;">Acción</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($orders as $order)
                            <tr class="{{ $order->status->value === 'pending_approval' ? 'row-pending' : '' }}">
                                <td>
                                    <strong style="font-family:monospace; font-size:0.875rem;">
                                        {{ $order->order_number }}
                                    </strong>
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

            @if ($orders->hasPages())
                <div class="pagination-wrap">
                    {{ $orders->links() }}
                </div>
            @endif
        @endif
    </div>
@endsection
