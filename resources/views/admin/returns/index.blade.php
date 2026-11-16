@extends('layouts.admin')

@section('title', 'Gestión de Devoluciones — Admin — ZYRO')
@section('section_title', 'Devoluciones')

@section('content')
    <div class="admin-page-header">
        <div>
            <p class="admin-kicker">Operaciones &bull; Devoluciones</p>
            <h1 class="admin-title">Gestión de Devoluciones</h1>
            <p class="admin-subtitle">Revisión, inspección y resolución de solicitudes de cambio y garantía.</p>
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
            <a href="{{ route('admin.returns.index', array_merge(request()->except(['status', 'page']))) }}"
               class="filter-tab {{ empty($status) ? 'active' : '' }}">
                <span>Todos</span>
                <span class="tab-count">{{ $counts['all'] ?? 0 }}</span>
            </a>
            <a href="{{ route('admin.returns.index', array_merge(request()->except(['status', 'page']), ['status' => 'recibida'])) }}"
               class="filter-tab {{ $status === 'recibida' ? 'active' : '' }}">
                <span>Recibidas</span>
                <span class="tab-count" style="{{ ($counts['recibida'] ?? 0) > 0 ? 'background:#FDE68A; color:#92400E;' : '' }}">
                    {{ $counts['recibida'] ?? 0 }}
                </span>
            </a>
            <a href="{{ route('admin.returns.index', array_merge(request()->except(['status', 'page']), ['status' => 'en_revision'])) }}"
               class="filter-tab {{ $status === 'en_revision' ? 'active' : '' }}">
                <span>En revisión</span>
                <span class="tab-count">{{ $counts['en_revision'] ?? 0 }}</span>
            </a>
            <a href="{{ route('admin.returns.index', array_merge(request()->except(['status', 'page']), ['status' => 'resuelta'])) }}"
               class="filter-tab {{ $status === 'resuelta' ? 'active' : '' }}">
                <span>Resueltas</span>
                <span class="tab-count">{{ $counts['resuelta'] ?? 0 }}</span>
            </a>
            <a href="{{ route('admin.returns.index', array_merge(request()->except(['status', 'page']), ['status' => 'rechazada'])) }}"
               class="filter-tab {{ $status === 'rechazada' ? 'active' : '' }}">
                <span>Rechazadas</span>
                <span class="tab-count">{{ $counts['rechazada'] ?? 0 }}</span>
            </a>
        </div>

        <form method="GET" action="{{ route('admin.returns.index') }}" class="search-box">
            @if (!empty($status))
                <input type="hidden" name="status" value="{{ $status }}">
            @endif
            <input type="search" name="q" value="{{ $q ?? '' }}" placeholder="Buscar por # o cliente..." aria-label="Buscar solicitudes">
            <button type="submit" aria-label="Ejecutar búsqueda">&rarr;</button>
            @if (!empty($q))
                <a href="{{ route('admin.returns.index', array_merge(request()->except('q'))) }}" style="font-size:0.75rem; color:var(--zyro-muted); margin-left:0.25rem;" title="Limpiar búsqueda">&times;</a>
            @endif
        </form>
    </div>

    {{-- Tabla de Devoluciones --}}
    <div class="card-table-wrapper">
        <div class="card-table-head">
            <div>
                <h2 class="card-table-title">
                    Listado de Solicitudes
                    @if (!empty($status))
                        <span style="font-size:0.75rem; font-weight:normal; color:var(--zyro-muted);">
                            &bull; Filtrado por: <strong>{{ ucfirst(str_replace('_', ' ', $status)) }}</strong>
                        </span>
                    @endif
                </h2>
                <span style="font-size:0.75rem; color:var(--zyro-muted);">
                    {{ $requests->total() }} solicitud(es) encontrada(s)
                </span>
            </div>
        </div>

        @if ($requests->isEmpty())
            <div style="padding: 4.5rem 2rem; text-align: center; color: var(--zyro-muted);">
                <p style="font-size: 1.125rem; font-weight: 500; margin-bottom: 0.5rem; color: var(--zyro-black);">
                    No se encontraron solicitudes
                </p>
                <p style="font-size: 0.8125rem; margin-bottom: 1.25rem;">
                    No hay solicitudes registradas con los filtros seleccionados.
                </p>
                @if (!empty($q) || !empty($status))
                    <a href="{{ route('admin.returns.index') }}" class="btn-action">
                        Restablecer todos los filtros
                    </a>
                @endif
            </div>
        @else
            <div class="table-responsive">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th># Solicitud</th>
                            <th>Pedido</th>
                            <th>Cliente</th>
                            <th>Producto / Talla</th>
                            <th>Cantidad</th>
                            <th>Estado</th>
                            <th>Revisor</th>
                            <th>Fecha</th>
                            <th style="text-align: right;">Acción</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($requests as $item)
                            <tr>
                                <td>
                                    <strong style="font-family:monospace; font-size:0.875rem;">
                                        {{ $item->request_number }}
                                    </strong>
                                </td>
                                <td>
                                    <a href="{{ route('admin.orders.show', $item->order_id) }}" style="font-family:monospace; font-weight:600; text-decoration:underline;">
                                        {{ $item->order->order_number ?? '—' }}
                                    </a>
                                </td>
                                <td>
                                    <div style="font-weight:600;">{{ $item->order->customer_name ?: ($item->user->name ?? '—') }}</div>
                                    <div style="font-size:0.6875rem; color:var(--zyro-muted);">{{ $item->user->email ?? '—' }}</div>
                                </td>
                                <td>
                                    <div style="font-weight:600;">{{ $item->product->name ?? '—' }}</div>
                                    <div style="font-size:0.6875rem; color:var(--zyro-muted);">Talla: {{ $item->size ?: 'Única' }}</div>
                                </td>
                                <td><strong>{{ $item->quantity }}</strong></td>
                                <td>@include('returns.partials.status-badge', ['status' => $item->status])</td>
                                <td>{{ $item->reviewer->name ?? '—' }}</td>
                                <td style="color:var(--zyro-muted);">{{ $item->created_at->format('d/m/Y H:i') }}</td>
                                <td style="text-align: right;">
                                    <a href="{{ route('admin.returns.show', $item) }}" class="btn-action btn-action-primary">
                                        Ver detalle
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if ($requests->hasPages())
                <div class="pagination-wrap">
                    {{ $requests->links() }}
                </div>
            @endif
        @endif
    </div>
@endsection
