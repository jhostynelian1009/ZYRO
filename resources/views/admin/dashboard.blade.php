@extends('layouts.store')

@section('title', 'Panel Admin — ZYRO')

@section('content')
<section class="mx-auto max-w-6xl px-4 py-10">

    <div class="flex flex-wrap items-center justify-between gap-4 mb-8">
        <div>
            <p class="text-xs uppercase tracking-[0.3em] text-zyro-muted mb-1">Entorno académico / pagos simulados</p>
            <h1 class="font-serif text-4xl">Panel administrativo</h1>
        </div>
        <div class="flex gap-3 text-sm">
            <a href="{{ route('admin.orders.index') }}" class="border border-zyro px-4 py-2 uppercase tracking-wide hover:bg-zyro hover:text-white transition-colors">Pedidos</a>
            <button id="refresh-btn" onclick="fetchStats()" class="border border-zyro-border px-4 py-2 uppercase tracking-wide text-zyro-muted hover:border-zyro hover:text-zyro transition-colors">↻ Actualizar</button>
        </div>
    </div>

    {{-- Tarjetas de estadísticas --}}
    <div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-4 mb-10" id="stats-grid">
        <div class="border border-zyro-border p-5 bg-zyro-gray">
            <p class="text-xs uppercase tracking-wide text-zyro-muted mb-2">Pedidos pendientes</p>
            <p class="font-serif text-4xl" id="stat-pending">{{ $stats['pending_orders'] }}</p>
        </div>
        <div class="border border-zyro-border p-5 bg-zyro-gray">
            <p class="text-xs uppercase tracking-wide text-zyro-muted mb-2">Pedidos pagados</p>
            <p class="font-serif text-4xl" id="stat-paid">{{ $stats['paid_orders'] }}</p>
        </div>
        <div class="border border-zyro-border p-5 bg-zyro-gray">
            <p class="text-xs uppercase tracking-wide text-zyro-muted mb-2">Ingresos simulados</p>
            <p class="font-serif text-4xl" id="stat-revenue">${{ $stats['simulated_revenue'] }}</p>
            <p class="text-xs text-zyro-muted mt-1">Solo pagos aprobados</p>
        </div>
        <div class="border border-zyro-border p-5 bg-zyro-gray">
            <p class="text-xs uppercase tracking-wide text-zyro-muted mb-2">Productos activos</p>
            <p class="font-serif text-4xl" id="stat-products">{{ $stats['total_products'] }}</p>
            @if ($stats['low_stock_products'] > 0)
                <p class="text-xs text-yellow-700 mt-1">⚠ {{ $stats['low_stock_products'] }} con stock bajo (≤3)</p>
            @endif
        </div>
    </div>

    {{-- Pedidos recientes --}}
    <div>
        <h2 class="text-xs uppercase tracking-wide text-zyro-muted mb-4">Actividad reciente</h2>
        @if ($recentOrders->isEmpty())
            <p class="border border-zyro-border bg-zyro-gray px-6 py-10 text-center text-sm text-zyro-muted">
                No hay pedidos todavía.
            </p>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-sm border-collapse">
                    <thead>
                        <tr class="border-b border-zyro-border text-left text-xs uppercase tracking-wide text-zyro-muted">
                            <th class="py-3 pr-4">Número</th>
                            <th class="py-3 pr-4">Cliente</th>
                            <th class="py-3 pr-4">Total</th>
                            <th class="py-3 pr-4">Estado</th>
                            <th class="py-3 pr-4">Fecha</th>
                            <th class="py-3"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($recentOrders as $order)
                            <tr class="border-b border-zyro-border hover:bg-zyro-gray">
                                <td class="py-3 pr-4 font-medium">{{ $order->order_number }}</td>
                                <td class="py-3 pr-4">{{ $order->user->name ?? '—' }}</td>
                                <td class="py-3 pr-4">${{ number_format($order->total, 2) }}</td>
                                <td class="py-3 pr-4">
                                    @include('orders.partials.status-badge', ['status' => $order->status])
                                </td>
                                <td class="py-3 pr-4 text-zyro-muted">{{ $order->created_at->format('d/m/Y H:i') }}</td>
                                <td class="py-3">
                                    <a href="{{ route('admin.orders.show', $order) }}" class="underline hover:opacity-60">Ver</a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

    <p class="mt-6 text-xs text-zyro-muted text-center" id="last-update">
        Actualizado: {{ now()->format('H:i:s') }}
    </p>
</section>

{{-- Polling cada 8 segundos --}}
<script>
    const statsUrl = '{{ route('admin.updates') }}';

    function fetchStats() {
        fetch(statsUrl, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
            .then(r => r.json())
            .then(data => {
                document.getElementById('stat-pending').textContent  = data.pending_orders;
                document.getElementById('stat-paid').textContent     = data.paid_orders;
                document.getElementById('stat-revenue').textContent  = '$' + data.simulated_revenue;
                document.getElementById('stat-products').textContent = data.total_products;
                document.getElementById('last-update').textContent   = 'Actualizado: ' + new Date().toLocaleTimeString('es-EC');
            })
            .catch(() => {}); // Silenciar errores de red; el admin puede usar el botón
    }

    // Polling cada 8 segundos
    setInterval(fetchStats, 8000);
</script>
@endsection
