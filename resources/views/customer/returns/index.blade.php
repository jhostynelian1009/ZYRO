@extends('layouts.store')

@section('title', 'Mis Solicitudes de Devolución — ZYRO')

@section('content')
<div class="orders-page-wrapper container">
    <div class="cart-header">
        <p style="font-size:0.6875rem;font-weight:700;letter-spacing:0.2em;text-transform:uppercase;color:var(--zyro-muted);margin-bottom:0.25rem;">
            <a href="{{ route('orders.index') }}" style="color:var(--zyro-muted);">Mi Cuenta</a> &rsaquo; Cambios y Devoluciones
        </p>
        <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:1rem;">
            <h1 style="font-family:'Cormorant Garamond',Georgia,serif;font-size:2.25rem;font-weight:600;line-height:1.15;color:var(--zyro-black);">
                Mis Solicitudes de Devolución
            </h1>
            <a href="{{ route('orders.index') }}" class="btn-action">
                &larr; Volver a Mis Pedidos
            </a>
        </div>
    </div>

    @if (session('status'))
        <div style="background:#ECFDF5;border:1px solid #A7F3D0;border-left:3px solid #10B981;padding:0.875rem 1rem;margin-bottom:1.5rem;font-size:0.8125rem;color:#065F46;border-radius:2px;">
            {{ session('status') }}
        </div>
    @endif

    @if ($requests->isEmpty())
        <div style="background:var(--zyro-light);border:1px solid var(--zyro-border);padding:4rem 2rem;text-align:center;color:var(--zyro-muted);">
            <p style="font-size:1.125rem;font-weight:600;color:var(--zyro-black);margin-bottom:0.5rem;">
                No tienes solicitudes de cambio o devolución
            </p>
            <p style="font-size:0.8125rem;margin-bottom:1.5rem;">
                Si compraste una prenda y necesitas gestionar un cambio de talla o garantía, ingresa al detalle de tu pedido pagado.
            </p>
            <a href="{{ route('orders.index') }}" class="btn-primary" style="display:inline-block;padding:0.75rem 1.5rem;">
                Ver mis pedidos
            </a>
        </div>
    @else
        <div style="background:#FFFFFF;border:1px solid var(--zyro-border);border-radius:2px;overflow:hidden;">
            <div class="table-responsive">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th># Solicitud</th>
                            <th>Pedido</th>
                            <th>Prenda</th>
                            <th>Solución solicitada</th>
                            <th>Estado</th>
                            <th>Fecha</th>
                            <th style="text-align: right;">Detalle</th>
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
                                    <a href="{{ route('orders.show', $item->order_id) }}" style="font-family:monospace; font-weight:600; text-decoration:underline;">
                                        {{ $item->order->order_number ?? '—' }}
                                    </a>
                                </td>
                                <td>
                                    <div style="font-weight:600;">{{ $item->product->name ?? '—' }}</div>
                                    <div style="font-size:0.6875rem; color:var(--zyro-muted);">Talla: {{ $item->size ?: 'Única' }} &bull; Cant: {{ $item->quantity }}</div>
                                </td>
                                <td>
                                    <span style="font-size:0.75rem;">
                                        {{ $item->requested_solution?->label() ?? $item->requested_solution }}
                                    </span>
                                </td>
                                <td>
                                    @include('returns.partials.status-badge', ['status' => $item->status])
                                </td>
                                <td style="color:var(--zyro-muted); font-size:0.8125rem;">
                                    {{ $item->created_at->format('d/m/Y H:i') }}
                                </td>
                                <td style="text-align: right;">
                                    <a href="{{ route('customer.returns.show', $item) }}" class="btn-action">
                                        Ver estado
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if ($requests->hasPages())
                <div style="padding:1rem; border-top:1px solid var(--zyro-border);">
                    {{ $requests->links() }}
                </div>
            @endif
        </div>
    @endif
</div>
@endsection
