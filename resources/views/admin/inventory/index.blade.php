@extends('layouts.admin')

@section('title', 'Inventario — Admin — ZYRO')
@section('section_title', 'Inventario')

@section('content')
    <div class="admin-page-header">
        <div>
            <p class="admin-kicker">Catálogo &bull; Existencias</p>
            <h1 class="admin-title">Inventario de Productos</h1>
            <p class="admin-subtitle">Control de stock, disponibilidad y líneas comerciales en tienda.</p>
        </div>
        <div style="display:flex; gap:0.75rem;">
            <a href="{{ route('admin.dashboard') }}" class="btn-action">
                <span>&larr;</span>
                <span>Volver al Dashboard</span>
            </a>
        </div>
    </div>

    {{-- Métricas de Inventario --}}
    <div class="stats-grid-4">
        <div class="stat-card accent-black">
            <div class="stat-card-head">
                <span class="stat-card-label">Total en Catálogo</span>
                <span class="stat-card-icon icon-black">📦</span>
            </div>
            <div class="stat-card-val" id="inv-stat-total">{{ $stats['total'] }}</div>
            <p class="stat-card-desc">Registros totales en base de datos.</p>
        </div>

        <div class="stat-card accent-green">
            <div class="stat-card-head">
                <span class="stat-card-label">Modelos Activos</span>
                <span class="stat-card-icon icon-green">✓</span>
            </div>
            <div class="stat-card-val" id="inv-stat-active">{{ $stats['active'] }}</div>
            <p class="stat-card-desc">Visibles y comprables en la tienda.</p>
        </div>

        <div class="stat-card accent-amber">
            <div class="stat-card-head">
                <span class="stat-card-label">Stock Bajo (&le; 3)</span>
                <span class="stat-card-icon icon-amber">⚠</span>
            </div>
            <div class="stat-card-val" id="inv-stat-low">{{ $stats['low_stock'] }}</div>
            <p class="stat-card-desc">Modelos activos próximos a agotarse.</p>
        </div>

        <div class="stat-card accent-blue">
            <div class="stat-card-head">
                <span class="stat-card-label">Inactivos / Archivo</span>
                <span class="stat-card-icon icon-blue">📁</span>
            </div>
            <div class="stat-card-val" id="inv-stat-inactive">{{ $stats['inactive'] }}</div>
            <p class="stat-card-desc">Históricos preservados por integridad.</p>
        </div>
    </div>

    {{-- Filtros y Buscador --}}
    <div class="filters-toolbar">
        <div class="filter-tabs" role="tablist">
            <a
                href="{{ route('admin.inventory.index', array_merge(request()->except(['line', 'page']))) }}"
                class="filter-tab {{ empty($line) ? 'active' : '' }}"
            >
                <span>Todos</span>
            </a>
            <a
                href="{{ route('admin.inventory.index', array_merge(request()->except(['line', 'page']), ['line' => 'tech'])) }}"
                class="filter-tab {{ $line === 'tech' ? 'active' : '' }}"
            >
                <span>Línea Tech ($370.00)</span>
            </a>
            <a
                href="{{ route('admin.inventory.index', array_merge(request()->except(['line', 'page']), ['line' => 'esencial'])) }}"
                class="filter-tab {{ $line === 'esencial' ? 'active' : '' }}"
            >
                <span>Línea Esencial ($80.00)</span>
            </a>
        </div>

        <form method="GET" action="{{ route('admin.inventory.index') }}" class="search-box">
            @if (!empty($line))
                <input type="hidden" name="line" value="{{ $line }}">
            @endif
            <input
                type="search"
                name="q"
                value="{{ $q ?? '' }}"
                placeholder="Buscar prenda..."
                aria-label="Buscar prenda en inventario"
            >
            <button type="submit" aria-label="Ejecutar búsqueda">&rarr;</button>
            @if (!empty($q))
                <a href="{{ route('admin.inventory.index', array_merge(request()->except('q'))) }}" style="font-size:0.75rem; color:var(--zyro-muted); margin-left:0.25rem;" title="Limpiar búsqueda">&times;</a>
            @endif
        </form>
    </div>

    {{-- Tabla de Inventario --}}
    <div class="card-table-wrapper">
        <div class="card-table-head">
            <div>
                <h2 class="card-table-title">Detalle de Existencias</h2>
                <span style="font-size:0.75rem; color:var(--zyro-muted);">
                    {{ $products->total() }} modelo(s) encontrado(s)
                </span>
            </div>
        </div>

        @if ($products->isEmpty())
            <div style="padding: 4rem 2rem; text-align: center; color: var(--zyro-muted);">
                <p style="font-size: 1.125rem; font-weight: 500; margin-bottom: 0.5rem; color: var(--zyro-black);">
                    No se encontraron productos
                </p>
                <p style="font-size: 0.8125rem; margin-bottom: 1.25rem;">
                    No hay modelos que coincidan con los filtros de búsqueda seleccionados.
                </p>
                <a href="{{ route('admin.inventory.index') }}" class="btn-action">
                    Restablecer filtros
                </a>
            </div>
        @else
            <div class="table-responsive">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>Prenda</th>
                            <th>Línea Comercial</th>
                            <th>Precio</th>
                            <th>Existencias</th>
                            <th>Tallas</th>
                            <th>Estado</th>
                            <th style="text-align: right;">Acción</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($products as $product)
                            <tr id="inv-row-{{ $product->id }}">
                                <td>
                                    <div style="display:flex; align-items:center; gap:0.75rem;">
                                        <div style="width:54px; height:38px; background:#F4F4F5; border:1px solid #E4E4E7; border-radius:2px; display:flex; align-items:center; justify-content:center; overflow:hidden; flex-shrink:0;">
                                            <img
                                                src="{{ $product->imageUrl() }}"
                                                alt="{{ $product->name }}"
                                                style="width:100%; height:100%; object-fit:contain;"
                                            >
                                        </div>
                                        <div>
                                            <div style="font-weight:600;">{{ $product->name }}</div>
                                            <div style="font-size:0.6875rem; font-family:monospace; color:var(--zyro-muted);">
                                                {{ $product->slug }}
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    @if ($product->hasTechnology())
                                        <span class="badge-status badge-approved">
                                            Línea Tech &bull; Con Tecnología
                                        </span>
                                    @else
                                        <span class="badge-status badge-cancelled" style="color:var(--zyro-black); font-weight:600;">
                                            Línea Esencial
                                        </span>
                                    @endif
                                </td>
                                <td>
                                    <span style="font-weight:700; font-size:0.875rem;">
                                        {{ $product->formattedPrice() }}
                                    </span>
                                </td>
                                <td class="inv-stock-cell">
                                    @if ($product->stock <= 0)
                                        <span class="badge-status badge-rejected">
                                            Agotado (0)
                                        </span>
                                    @elseif ($product->stock <= 3)
                                        <span class="badge-status badge-pending">
                                            Bajo ({{ $product->stock }} uds)
                                        </span>
                                    @else
                                        <span style="font-weight:600; color:var(--zyro-green-text);">
                                            {{ $product->stock }} unidades
                                        </span>
                                    @endif
                                </td>
                                <td>
                                    @if (!empty($product->availableSizes()))
                                        <div style="display:flex; gap:0.25rem; flex-wrap:wrap;">
                                            @foreach ($product->availableSizes() as $sz)
                                                <span style="font-size:0.625rem; font-weight:700; padding:0.1rem 0.35rem; background:#F4F4F5; border:1px solid #E4E4E7; border-radius:2px;">
                                                    {{ $sz }}
                                                </span>
                                            @endforeach
                                        </div>
                                    @else
                                        <span style="color:var(--zyro-muted);">&mdash;</span>
                                    @endif
                                </td>
                                <td>
                                    @if ($product->is_active)
                                        <span class="badge-status badge-paid">
                                            Activo
                                        </span>
                                    @else
                                        <span class="badge-status badge-cancelled">
                                            Inactivo
                                        </span>
                                    @endif
                                </td>
                                <td style="text-align: right;">
                                    @if ($product->is_active)
                                        <a href="{{ route('catalog.show', $product) }}" target="_blank" class="btn-action" title="Ver en la tienda">
                                            <span>Ver tienda</span>
                                            <span>&nearr;</span>
                                        </a>
                                    @else
                                        <span style="font-size:0.6875rem; color:var(--zyro-muted);">Archivado</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if ($products->hasPages())
                <div class="pagination-wrap">
                    {{ $products->links() }}
                </div>
            @endif
        @endif
    </div>
@endsection

@push('scripts')
<script>
    function renderStockBadge(stock) {
        if (stock <= 0) {
            return '<span class="badge-status badge-rejected">Agotado (0)</span>';
        } else if (stock <= 3) {
            return `<span class="badge-status badge-pending">Bajo (${stock} uds)</span>`;
        }
        return `<span style="font-weight:600; color:var(--zyro-green-text);">${stock} unidades</span>`;
    }

    document.addEventListener('DOMContentLoaded', function () {
        if (typeof window.zyroSubscribe === 'function') {
            const channel = window.zyroSubscribe('private', 'admin');

            channel.listen('.InventoryUpdated', function (e) {
                // 1. Actualizar métricas de cabecera
                if (e.metrics) {
                    const lowStockEl = document.getElementById('inv-stat-low');
                    if (lowStockEl && e.metrics.low_stock_products !== undefined) {
                        lowStockEl.textContent = e.metrics.low_stock_products;
                    }
                }

                // 2. Actualizar celdas de existencias en tiempo real
                if (Array.isArray(e.items)) {
                    e.items.forEach(item => {
                        const row = document.getElementById(`inv-row-${item.product_id}`);
                        if (row) {
                            const cell = row.querySelector('.inv-stock-cell');
                            if (cell) {
                                cell.innerHTML = renderStockBadge(item.stock);
                                cell.style.animation = 'zyroSlideIn 0.3s ease';
                            }
                        }
                    });
                }
            });
        }
    });
</script>
@endpush
