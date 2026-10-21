@extends('layouts.store')

@section('title', 'Mis pedidos — ZYRO')

@section('content')
<div class="orders-page-wrapper container">

    <div class="cart-header">
        <p style="font-size:0.6875rem;font-weight:700;letter-spacing:0.2em;text-transform:uppercase;color:var(--zyro-muted);margin-bottom:0.25rem;">
            Mi cuenta
        </p>
        <h1>Mis pedidos</h1>
    </div>

    @if ($orders->isEmpty())
        <div class="cart-empty-card">
            <span class="cart-empty-icon">📦</span>
            <h2>Aún no tienes pedidos</h2>
            <p>Explora la colección ZYRO y realiza tu primera compra.</p>
            <a href="{{ route('catalog.index') }}" class="btn-primary">Ver colección</a>
        </div>
    @else
        <div class="orders-table-wrapper">
            <table class="orders-table">
                <thead>
                    <tr>
                        <th>Número de pedido</th>
                        <th>Entrega</th>
                        <th>Total</th>
                        <th>Estado</th>
                        <th>Fecha</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($orders as $order)
                        <tr id="customer-order-{{ $order->id }}">
                            <td style="font-weight:600;font-family:'Cormorant Garamond',Georgia,serif;font-size:1rem;">
                                {{ $order->order_number }}
                            </td>
                            <td>
                                <div style="font-size:0.8125rem;font-weight:600;color:var(--zyro-black);">
                                    {{ $order->delivery_method_label }}
                                </div>
                                <div style="font-size:0.6875rem;color:var(--zyro-muted);margin-top:0.15rem;">
                                    ⏱ {{ $order->delivery_estimate_label }}
                                </div>
                            </td>
                            <td style="font-weight:600;">${{ number_format($order->total, 2) }}</td>
                            <td class="customer-order-status">
                                @include('orders.partials.status-badge', ['status' => $order->status])
                            </td>
                            <td style="color:var(--zyro-muted);">{{ $order->created_at->format('d/m/Y H:i') }}</td>
                            <td style="text-align:right;">
                                <a href="{{ route('orders.show', $order) }}" class="btn-cart-update">Ver detalle</a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        @if ($orders->hasPages())
            <div style="margin-top:1.5rem;">{{ $orders->links() }}</div>
        @endif
    @endif
</div>
@endsection

@push('scripts')
<script>
    function renderCustomerBadge(status) {
        if (status === 'approved') {
            return '<span class="badge-status badge-approved">✓ Aprobado</span>';
        } else if (status === 'paid') {
            return '<span class="badge-status badge-paid">✓ Pagado</span>';
        } else if (status === 'rejected') {
            return '<span class="badge-status badge-rejected">✗ Rechazado</span>';
        }
        return '<span class="badge-status badge-pending">⏳ Pendiente de aprobación</span>';
    }

    document.addEventListener('DOMContentLoaded', function () {
        @auth
        if (typeof window.zyroSubscribe === 'function') {
            const userChannel = window.zyroSubscribe('private', 'user.{{ auth()->id() }}');

            userChannel.listen('.OrderStatusUpdated', function (e) {
                const row = document.getElementById(`customer-order-${e.order_id}`);
                if (row) {
                    const statusCell = row.querySelector('.customer-order-status');
                    if (statusCell) {
                        statusCell.innerHTML = renderCustomerBadge(e.to_status);
                        statusCell.style.animation = 'zyroSlideIn 0.3s ease';
                    }
                }

                if (typeof window.zyroToast === 'function') {
                    const type = e.to_status === 'approved' ? 'green' : (e.to_status === 'rejected' ? 'red' : 'blue');
                    window.zyroToast(
                        `Tu pedido ${e.order_number} ahora está: ${e.to_status_label}`,
                        type,
                        e.order_url,
                        'Ver pedido'
                    );
                }
            });
        }
        @endauth
    });
</script>
@endpush
