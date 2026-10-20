@extends('layouts.store')

@section('title', 'Pedido '.$order->order_number.' — ZYRO')

@section('content')
<div class="orders-page-wrapper container">

    {{-- Mensajes de estado --}}
    @if (session('status'))
        <div style="background:#ECFDF5;border:1px solid #A7F3D0;border-left:3px solid #10B981;padding:0.875rem 1rem;margin-bottom:1.5rem;font-size:0.8125rem;color:#065F46;border-radius:2px;">
            {{ session('status') }}
        </div>
    @endif
    @if ($errors->has('payment'))
        <div style="background:#FEF2F2;border:1px solid #FECACA;border-left:3px solid #DC2626;padding:0.875rem 1rem;margin-bottom:1.5rem;font-size:0.8125rem;color:#991B1B;border-radius:2px;">
            {{ $errors->first('payment') }}
        </div>
    @endif

    {{-- Cabecera del pedido --}}
    <div class="cart-header">
        <p style="font-size:0.6875rem;font-weight:700;letter-spacing:0.2em;text-transform:uppercase;color:var(--zyro-muted);margin-bottom:0.25rem;">
            <a href="{{ route('orders.index') }}" style="color:var(--zyro-muted);">Mis pedidos</a> &rsaquo; Detalle
        </p>
        <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:1rem;">
            <h1 style="font-family:'Cormorant Garamond',Georgia,serif;font-size:2.25rem;font-weight:600;line-height:1.15;color:var(--zyro-black);">
                {{ $order->order_number }}
            </h1>
            <div style="display:flex;align-items:center;gap:1rem;">
                @include('orders.partials.status-badge', ['status' => $order->status])
                <span style="font-size:0.75rem;color:var(--zyro-muted);">{{ $order->created_at->format('d/m/Y H:i') }}</span>
            </div>
        </div>
    </div>

    <div class="order-detail-grid">
        {{-- Artículos y totales --}}
        <div class="order-card">
            <h2 style="font-size:0.6875rem;font-weight:700;letter-spacing:0.15em;text-transform:uppercase;color:var(--zyro-muted);margin-bottom:1rem;">
                Artículos del pedido
            </h2>

            <div style="border:1px solid var(--zyro-border);border-radius:2px;overflow:hidden;margin-bottom:0;">
                @foreach ($order->items as $item)
                    <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:1rem;padding:1rem;border-bottom:1px solid var(--zyro-border);">
                        <div>
                            <p style="font-weight:600;font-size:0.9375rem;color:var(--zyro-black);margin-bottom:0.2rem;">{{ $item->product_name }}</p>
                            @if ($item->size)
                                <span style="display:inline-block;font-size:0.6875rem;font-weight:600;letter-spacing:0.08em;text-transform:uppercase;color:var(--zyro-muted);background:var(--zyro-light);border:1px solid var(--zyro-border);padding:0.15rem 0.45rem;border-radius:2px;margin-bottom:0.25rem;">
                                    Talla: {{ $item->size }}
                                </span>
                            @endif
                            @if ($item->customization)
                                <div style="font-size:.75rem;color:var(--zyro-muted);line-height:1.6;margin:.25rem 0;"><strong style="color:var(--zyro-black);">Personalización:</strong> {{ ucfirst($item->customization['color']) }}, {{ str_replace('_', ' ', $item->customization['location']) }}@if(!empty($item->customization['text'])) · “{{ $item->customization['text'] }}”@endif @if(!empty($item->customization['image_path'])) · <a href="{{ route('customizations.image', $item) }}" style="text-decoration:underline;">Ver imagen</a>@endif</div>
                            @endif
                            <p style="font-size:0.75rem;color:var(--zyro-muted);">×{{ $item->quantity }} × ${{ number_format($item->unit_price, 2) }}</p>
                        </div>
                        <p style="font-weight:700;font-size:1rem;color:var(--zyro-black);white-space:nowrap;">${{ number_format($item->line_total, 2) }}</p>
                    </div>
                @endforeach

                {{-- Totales --}}
                <div style="background:var(--zyro-light);padding:1rem;">
                    <div class="summary-row" style="margin-bottom:0.5rem;">
                        <span>Subtotal</span>
                        <span>${{ number_format($order->subtotal, 2) }}</span>
                    </div>
                    <div class="summary-row" style="margin-bottom:0.5rem;">
                        <span>IVA 12%</span>
                        <span>${{ number_format($order->tax_amount, 2) }}</span>
                    </div>
                    <div class="summary-row" style="margin-bottom:0.75rem;">
                        <span>Envío</span>
                        <span>${{ number_format($order->shipping_amount, 2) }}</span>
                    </div>
                    <div style="display:flex;justify-content:space-between;align-items:baseline;padding-top:0.75rem;border-top:1px solid var(--zyro-border);font-weight:700;font-size:1.05rem;color:var(--zyro-black);">
                        <span>Total</span>
                        <span style="font-family:'Cormorant Garamond',Georgia,serif;font-size:1.5rem;">${{ number_format($order->total, 2) }}</span>
                    </div>
                </div>
            </div>
        </div>

        {{-- Panel derecho: entrega + pago --}}
        <div style="display:flex;flex-direction:column;gap:1.5rem;">

            {{-- Datos de entrega --}}
            <div class="order-card">
                <h2 style="font-size:0.6875rem;font-weight:700;letter-spacing:0.15em;text-transform:uppercase;color:var(--zyro-muted);margin-bottom:1rem;">
                    Datos de entrega
                </h2>
                <div style="display:flex;flex-direction:column;gap:0.5rem;font-size:0.875rem;color:var(--zyro-black);">
                    <p><span style="color:var(--zyro-muted);">Destinatario:</span> {{ $order->customer_name }}</p>
                    <p><span style="color:var(--zyro-muted);">Teléfono:</span> {{ $order->phone }}</p>
                    <p><span style="color:var(--zyro-muted);">Dirección:</span> {{ $order->address }}, {{ $order->city }}</p>
                    <p><span style="color:var(--zyro-muted);">Modalidad:</span> {{ ucfirst($order->delivery_method) }}</p>
                    @if ($order->delivery_notes)
                        <p><span style="color:var(--zyro-muted);">Notas:</span> {{ $order->delivery_notes }}</p>
                    @endif
                </div>
            </div>

            {{-- Sección de pago según estado --}}
            @if ($order->status->value === 'approved')
                <div style="background:#ECFDF5;border:1px solid #A7F3D0;border-left:3px solid #10B981;border-radius:2px;padding:1.25rem;">
                    <p style="font-size:0.6875rem;font-weight:700;letter-spacing:0.15em;text-transform:uppercase;color:#065F46;margin-bottom:0.5rem;">
                        ✓ Pedido aprobado
                    </p>
                    <p style="font-size:0.8125rem;color:#065F46;margin-bottom:1rem;line-height:1.5;">
                        Tu pedido fue aprobado. Puedes continuar con el proceso de pago simulado.
                    </p>
                    <a href="{{ route('orders.payment', $order) }}" class="btn-primary" style="display:inline-block;padding:0.75rem 1.5rem;">
                        Realizar pago
                    </a>
                </div>

            @elseif ($order->status->value === 'paid')
                <div style="background:#EDE9FE;border:1px solid #C4B5FD;border-left:3px solid #7C3AED;border-radius:2px;padding:1.25rem;">
                    <p style="font-size:0.6875rem;font-weight:700;letter-spacing:0.15em;text-transform:uppercase;color:#4C1D95;margin-bottom:0.5rem;">
                        ✓ Pago completado
                    </p>
                    @php
                        $pago = $order->payments->where('status.value', 'approved')->first()
                            ?? $order->payments->sortByDesc('created_at')->first();
                    @endphp
                    @if ($pago)
                        <p style="font-size:0.8125rem;color:#4C1D95;margin-bottom:0.25rem;">Referencia: <strong>{{ $pago->reference }}</strong></p>
                        <p style="font-size:0.8125rem;color:#4C1D95;">Monto: <strong>${{ number_format($pago->amount, 2) }}</strong></p>
                    @endif
                    <p style="font-size:0.75rem;color:#6D28D9;margin-top:0.5rem;">Transacción de prueba completada exitosamente.</p>
                </div>

            @elseif ($order->status->value === 'rejected')
                <div style="background:#FEF2F2;border:1px solid #FECACA;border-left:3px solid #DC2626;border-radius:2px;padding:1.25rem;">
                    <p style="font-size:0.6875rem;font-weight:700;letter-spacing:0.15em;text-transform:uppercase;color:#991B1B;margin-bottom:0.5rem;">
                        Pedido rechazado
                    </p>
                    <p style="font-size:0.8125rem;color:#991B1B;line-height:1.5;">
                        Este pedido fue rechazado por el administrador. No es posible procesarlo.
                    </p>
                </div>

            @else
                <div style="background:var(--zyro-light);border:1px solid var(--zyro-border);border-left:3px solid var(--zyro-muted);border-radius:2px;padding:1.25rem;">
                    <p style="font-size:0.6875rem;font-weight:700;letter-spacing:0.15em;text-transform:uppercase;color:var(--zyro-muted);margin-bottom:0.5rem;">
                        Pendiente de aprobación
                    </p>
                    <p style="font-size:0.8125rem;color:var(--zyro-muted);line-height:1.5;">
                        Tu pedido está en revisión. El pago estará disponible una vez que el administrador lo apruebe.
                    </p>
                </div>
            @endif

            {{-- Historial de intentos de pago --}}
            @if ($order->payments->isNotEmpty())
                <div class="order-card">
                    <h2 style="font-size:0.6875rem;font-weight:700;letter-spacing:0.15em;text-transform:uppercase;color:var(--zyro-muted);margin-bottom:1rem;">
                        Intentos de pago
                    </h2>
                    <div style="display:flex;flex-direction:column;gap:0.5rem;">
                        @foreach ($order->payments->sortByDesc('created_at') as $payment)
                            <div style="display:flex;justify-content:space-between;align-items:center;font-size:0.8125rem;padding:0.5rem 0;border-bottom:1px solid var(--zyro-border);">
                                <span style="color:var(--zyro-muted);font-family:monospace;font-size:0.75rem;">{{ $payment->reference }}</span>
                                <span style="font-weight:600;color:{{ $payment->status->value === 'approved' ? '#065F46' : '#991B1B' }};">
                                    {{ $payment->status->value === 'approved' ? '✓ Aprobado' : '✗ Rechazado' }}
                                </span>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

        </div>{{-- panel derecho --}}
    </div>{{-- order-detail-grid --}}

    <div style="margin-top:2rem;padding-top:1.25rem;border-top:1px solid var(--zyro-border);">
        <a href="{{ route('orders.index') }}" class="btn-cart-update">← Volver a mis pedidos</a>
    </div>

</div>
@endsection
