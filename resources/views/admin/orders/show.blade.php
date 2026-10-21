@extends('layouts.admin')

@section('title', 'Pedido ' . $order->order_number . ' — Admin — ZYRO')
@section('section_title', 'Detalle de Pedido')

@section('content')
    {{-- Encabezado con Número de Pedido y Estado --}}
    <div class="admin-page-header">
        <div>
            <div style="display:flex; align-items:center; gap:0.75rem; margin-bottom:0.35rem;">
                <p class="admin-kicker" style="margin-bottom:0;">Operaciones &bull; Pedido</p>
                @include('orders.partials.status-badge', ['status' => $order->status])
            </div>
            <h1 class="admin-title">{{ $order->order_number }}</h1>
            <p class="admin-subtitle">Registrado el {{ $order->created_at->format('d/m/Y \a \l\a\s H:i') }}</p>
        </div>

        {{-- Acciones Administrativas --}}
        <div style="display:flex; flex-wrap:wrap; align-items:center; gap:0.75rem;">
            @if ($order->status->value === 'pending_approval')
                <form method="POST" action="{{ route('admin.orders.approve', $order) }}" style="display:inline;">
                    @csrf
                    <button
                        type="submit"
                        class="btn-action btn-action-approve"
                        onclick="return confirm('¿Aprobar administrativamente el pedido {{ $order->order_number }}?')"
                    >
                        <span>✓</span>
                        <span>Aprobar Pedido</span>
                    </button>
                </form>

                <form method="POST" action="{{ route('admin.orders.reject', $order) }}" style="display:inline;">
                    @csrf
                    <button
                        type="submit"
                        class="btn-action btn-action-reject"
                        onclick="return confirm('¿Rechazar el pedido {{ $order->order_number }}? Esta acción cancelará la orden y no se podrán procesar pagos.')"
                    >
                        <span>✕</span>
                        <span>Rechazar Pedido</span>
                    </button>
                </form>
            @endif

            <a href="{{ route('admin.orders.index') }}" class="btn-action">
                <span>&larr;</span>
                <span>Todos los Pedidos</span>
            </a>
        </div>
    </div>

    {{-- Banner Informativo según Estado --}}
    @if ($order->status->value === 'pending_approval')
        <div style="background:var(--zyro-amber-bg); border:1px solid var(--zyro-amber-border); color:var(--zyro-amber-text); padding:1rem 1.25rem; border-radius:2px; margin-bottom:2rem; font-size:0.8125rem; display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:0.75rem;">
            <div>
                <strong>Revisión de pedido:</strong> Pedido registrado pendiente de pago directo por el cliente o validación administrativa.
            </div>
            <span style="font-size:0.6875rem; text-transform:uppercase; letter-spacing:0.08em; font-weight:700;">
                Pendiente
            </span>
        </div>
    @elseif ($order->status->value === 'approved')
        <div style="background:var(--zyro-blue-bg); border:1px solid var(--zyro-blue-border); color:var(--zyro-blue); padding:1rem 1.25rem; border-radius:2px; margin-bottom:2rem; font-size:0.8125rem;">
            <strong>Pedido aprobado:</strong> El pedido fue validado administrativamente.
            @if ($order->approved_at)
                Aprobado por <strong>{{ $order->approvedBy->name ?? 'Administrador' }}</strong> el {{ $order->approved_at->format('d/m/Y H:i') }}.
            @endif
        </div>
    @elseif ($order->status->value === 'paid')
        <div style="background:var(--zyro-green-bg); border:1px solid var(--zyro-green-border); color:var(--zyro-green-text); padding:1rem 1.25rem; border-radius:2px; margin-bottom:2rem; font-size:0.8125rem;">
            <strong>Pedido pagado y completado:</strong> El pago simulado fue validado y el inventario fue descontado exitosamente. No se requieren más acciones sobre esta orden.
        </div>
    @elseif ($order->status->value === 'rejected')
        <div style="background:var(--zyro-red-bg); border:1px solid var(--zyro-red-border); color:var(--zyro-red-text); padding:1rem 1.25rem; border-radius:2px; margin-bottom:2rem; font-size:0.8125rem;">
            <strong>Pedido rechazado:</strong> Esta orden fue denegada
            @if ($order->rejected_at)
                por <strong>{{ $order->rejectedBy->name ?? 'Administrador' }}</strong> el {{ $order->rejected_at->format('d/m/Y H:i') }}.
            @endif
            El cliente no puede realizar pagos sobre este pedido.
        </div>
    @endif

    {{-- Cuadrícula de Contenido (Artículos a la izquierda, Cliente y Pagos a la derecha) --}}
    <div style="display:grid; grid-template-columns: 1fr; gap: 2rem;">
        <style>
            @media (min-width: 960px) {
                .order-detail-grid {
                    display: grid !important;
                    grid-template-columns: 1.4fr 1fr !important;
                    gap: 2rem !important;
                    align-items: start !important;
                }
            }
        </style>

        <div class="order-detail-grid" style="display:flex; flex-direction:column; gap:2rem;">
            {{-- Columna Izquierda: Artículos y Totales --}}
            <div style="display:flex; flex-direction:column; gap:1.5rem;">
                <div class="card-table-wrapper" style="margin-bottom:0;">
                    <div class="card-table-head">
                        <h2 class="card-table-title">Artículos del Pedido</h2>
                        <span style="font-size:0.75rem; color:var(--zyro-muted);">{{ count($order->items) }} producto(s)</span>
                    </div>

                    <div class="table-responsive">
                        <table class="admin-table">
                            <thead>
                                <tr>
                                    <th>Producto</th>
                                    <th>Talla</th>
                                    <th>Cant.</th>
                                    <th>Precio Unit.</th>
                                    <th style="text-align: right;">Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($order->items as $item)
                                    <tr>
                                        <td>
                                            <div style="font-weight:600;">{{ $item->product_name }}</div>
                                            @if ($item->product)
                                                <div style="font-size:0.6875rem; color:var(--zyro-muted);">
                                                    Stock actual disponible: <strong>{{ $item->product->stock }}</strong>
                                                </div>
                                            @endif
                                            @if ($item->customization)
                                                <div style="font-size:.75rem;color:var(--zyro-muted);line-height:1.6;margin-top:.35rem;"><strong style="color:var(--zyro-black);">Personalización</strong><br>{{ $item->product?->lineName() ?? 'Colección registrada' }} · {{ ucfirst($item->customization['color']) }} · {{ str_replace('_', ' ', $item->customization['location']) }}@if(!empty($item->customization['text']))<br>Texto: “{{ $item->customization['text'] }}”@endif @if(!empty($item->customization['image_path']))<br><a href="{{ route('customizations.image', $item) }}" style="text-decoration:underline;">Ver o descargar imagen</a>@endif</div>
                                            @endif
                                        </td>
                                        <td>
                                            @if ($item->size)
                                                <span style="font-size:0.75rem; font-weight:700; padding:0.15rem 0.45rem; background:#F4F4F5; border:1px solid #E4E4E7; border-radius:2px;">
                                                    {{ $item->size }}
                                                </span>
                                            @else
                                                <span style="color:var(--zyro-muted);">&mdash;</span>
                                            @endif
                                        </td>
                                        <td>
                                            <strong>{{ $item->quantity }}</strong>
                                        </td>
                                        <td>
                                            ${{ number_format($item->unit_price, 2) }}
                                        </td>
                                        <td style="text-align: right; font-weight:700;">
                                            ${{ number_format($item->line_total, 2) }}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    {{-- Desglose Financiero del Servidor --}}
                    <div style="padding: 1.25rem 1.5rem; background: #FAFAFA; border-top: 1px solid var(--zyro-border); display:flex; flex-direction:column; gap:0.5rem; font-size:0.8125rem;">
                        <div style="display:flex; justify-content:space-between; color:var(--zyro-muted);">
                            <span>Subtotal</span>
                            <span>${{ number_format($order->subtotal, 2) }}</span>
                        </div>
                        <div style="display:flex; justify-content:space-between; color:var(--zyro-muted);">
                            <span>IVA (15%)</span>
                            <span>${{ number_format($order->tax_amount, 2) }}</span>
                        </div>
                        <div style="display:flex; justify-content:space-between; color:var(--zyro-muted);">
                            <span>Envío</span>
                            <span>{{ $order->shipping_amount > 0 ? '$'.number_format($order->shipping_amount, 2) : 'GRATIS ($0.00)' }}</span>
                        </div>
                        <div style="display:flex; justify-content:space-between; font-size:1.125rem; font-weight:700; color:var(--zyro-black); padding-top:0.65rem; border-top:1px solid var(--zyro-border); margin-top:0.25rem;">
                            <span>Total del Pedido</span>
                            <span>${{ number_format($order->total, 2) }}</span>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Columna Derecha: Cliente, Entrega y Pagos --}}
            <div style="display:flex; flex-direction:column; gap:1.5rem;">
                {{-- Tarjeta: Datos del Cliente --}}
                <div class="card-table-wrapper" style="margin-bottom:0;">
                    <div class="card-table-head">
                        <h2 class="card-table-title">Datos del Cliente</h2>
                    </div>
                    <div style="padding:1.25rem 1.5rem; font-size:0.8125rem; display:flex; flex-direction:column; gap:0.75rem;">
                        <div>
                            <span style="display:block; font-size:0.6875rem; font-weight:700; text-transform:uppercase; letter-spacing:0.05em; color:var(--zyro-muted); margin-bottom:0.15rem;">
                                Cuenta de Usuario
                            </span>
                            <div style="font-weight:600;">{{ $order->user->name ?? '—' }}</div>
                            <div style="color:var(--zyro-muted);">{{ $order->user->email ?? '—' }}</div>
                        </div>

                        <div style="border-top:1px solid var(--zyro-border); padding-top:0.75rem;">
                            <span style="display:block; font-size:0.6875rem; font-weight:700; text-transform:uppercase; letter-spacing:0.05em; color:var(--zyro-muted); margin-bottom:0.15rem;">
                                Destinatario del Envío
                            </span>
                            <div style="font-weight:600;">{{ $order->customer_name }}</div>
                            <div style="color:var(--zyro-muted);">Teléfono: {{ $order->phone }}</div>
                        </div>

                        <div style="border-top:1px solid var(--zyro-border); padding-top:0.75rem;">
                            <span style="display:block; font-size:0.6875rem; font-weight:700; text-transform:uppercase; letter-spacing:0.05em; color:var(--zyro-muted); margin-bottom:0.15rem;">
                                Logística y Modalidad de Entrega
                            </span>
                            <div style="margin-bottom:0.5rem;">
                                <div style="font-weight:600; font-size:0.875rem; color:var(--zyro-black);">
                                    {{ $order->delivery_method_label }}
                                </div>
                                <div style="display:inline-block; margin-top:0.25rem; background:var(--zyro-light); border:1px solid var(--zyro-border); padding:2px 8px; border-radius:2px; font-size:0.75rem; font-weight:600; color:var(--zyro-black);">
                                    ⏱ Tiempo estimado: {{ $order->delivery_estimate_label }}
                                </div>
                                <div style="font-size:0.75rem; color:var(--zyro-muted); margin-top:0.25rem;">
                                    Costo de envío aplicado: <strong>{{ $order->shipping_amount > 0 ? '$'.number_format($order->shipping_amount, 2) : 'GRATIS ($0.00)' }}</strong>
                                </div>
                            </div>

                            @if ($order->isPickup())
                                <div style="background:#F9FAFB; border:1px solid var(--zyro-border); padding:0.6rem 0.75rem; border-radius:2px; font-size:0.75rem; color:var(--zyro-muted); margin-top:0.5rem;">
                                    <strong style="color:var(--zyro-black); display:block; margin-bottom:0.15rem;">🏬 Sucursal Centro</strong>
                                    Modalidad de retiro en tienda. El cliente retirará personalmente en el local tras recibir la notificación de pedido listo. No aplica entrega a domicilio.
                                </div>
                            @else
                                <div style="margin-top:0.5rem; font-size:0.8125rem;">
                                    <span style="font-size:0.6875rem; font-weight:700; text-transform:uppercase; color:var(--zyro-muted); display:block;">
                                        Dirección de Entrega
                                    </span>
                                    <div>{{ $order->address }}</div>
                                    <div style="color:var(--zyro-muted);">Ciudad: {{ $order->city }}</div>
                                </div>
                            @endif

                            @if ($order->delivery_notes)
                                <div style="margin-top:0.5rem; font-size:0.75rem; font-style:italic; color:var(--zyro-muted); border-top:1px dashed var(--zyro-border); padding-top:0.5rem;">
                                    Notas de entrega: &ldquo;{{ $order->delivery_notes }}&rdquo;
                                </div>
                            @endif
                        </div>
                    </div>
                </div>

                {{-- Tarjeta: Pagos Simulados --}}
                <div class="card-table-wrapper" style="margin-bottom:0;">
                    <div class="card-table-head">
                        <h2 class="card-table-title">Pagos Simulados</h2>
                        <span style="font-size:0.6875rem; color:var(--zyro-muted); text-transform:uppercase; letter-spacing:0.08em; font-weight:700;">
                            Entorno Demo
                        </span>
                    </div>

                    @if ($order->payments->isEmpty())
                        <div style="padding:1.5rem; text-align:center; font-size:0.8125rem; color:var(--zyro-muted);">
                            No se han registrado intentos de pago para este pedido.
                        </div>
                    @else
                        <div style="display:flex; flex-direction:column; divide-y:1px solid var(--zyro-border);">
                            @foreach ($order->payments->sortByDesc('created_at') as $payment)
                                <div style="padding:1rem 1.25rem; border-bottom:1px solid var(--zyro-border); font-size:0.8125rem;">
                                    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:0.25rem;">
                                        <strong style="font-family:monospace;">{{ $payment->reference }}</strong>
                                        @if ($payment->status->value === 'approved')
                                            <span class="badge-status badge-paid">Aprobado</span>
                                        @else
                                            <span class="badge-status badge-rejected">Rechazado</span>
                                        @endif
                                    </div>
                                    <div style="display:flex; justify-content:space-between; color:var(--zyro-muted); font-size:0.75rem;">
                                        <span>Método: {{ ucfirst(str_replace('_', ' ', $payment->method->value)) }}</span>
                                        <span style="font-weight:700; color:var(--zyro-black); font-size:0.8125rem;">
                                            ${{ number_format($payment->amount, 2) }}
                                        </span>
                                    </div>
                                    @if ($payment->processed_at)
                                        <div style="font-size:0.6875rem; color:var(--zyro-muted); margin-top:0.25rem;">
                                            Procesado: {{ $payment->processed_at->format('d/m/Y H:i:s') }}
                                        </div>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection
