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
                        <th>Total</th>
                        <th>Estado</th>
                        <th>Fecha</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($orders as $order)
                        <tr>
                            <td style="font-weight:600;font-family:'Cormorant Garamond',Georgia,serif;font-size:1rem;">
                                {{ $order->order_number }}
                            </td>
                            <td style="font-weight:600;">${{ number_format($order->total, 2) }}</td>
                            <td>
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
