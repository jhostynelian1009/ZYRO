@extends('layouts.store')

@section('title', 'Pago simulado — '.$order->order_number.' — ZYRO')

@section('content')
<div class="orders-page-wrapper container">

    {{-- Indicación discreta de entorno de pago simulado --}}
    <div style="background:var(--zyro-light);border:1px solid var(--zyro-border);border-left:3px solid var(--zyro-tech-blue);padding:0.875rem 1.25rem;margin-bottom:2rem;font-size:0.8125rem;color:var(--zyro-muted);line-height:1.5;border-radius:2px;">
        <strong style="color:var(--zyro-black);display:block;margin-bottom:0.15rem;">🔒 Entorno de pago protegido (Modo demostración)</strong>
        Esta pasarela procesa transacciones simuladas. No se requiere tarjeta real ni se efectúa cargo alguno.
    </div>

    <div class="cart-header">
        <p style="font-size:0.6875rem;font-weight:700;letter-spacing:0.2em;text-transform:uppercase;color:var(--zyro-muted);margin-bottom:0.25rem;">
            <a href="{{ route('orders.index') }}" style="color:var(--zyro-muted);">Mis pedidos</a> &rsaquo; <a href="{{ route('orders.show', $order) }}" style="color:var(--zyro-muted);">{{ $order->order_number }}</a> &rsaquo; Pago
        </p>
        <h1 style="font-family:'Cormorant Garamond',Georgia,serif;font-size:2.25rem;font-weight:600;line-height:1.15;color:var(--zyro-black);">
            Pago simulado
        </h1>
        <p style="font-size:0.875rem;color:var(--zyro-muted);margin-top:0.35rem;">
            Pedido <strong style="color:var(--zyro-black);">{{ $order->order_number }}</strong> · Total: <strong style="color:var(--zyro-black);">${{ number_format($order->total, 2) }}</strong>
        </p>
    </div>

    @if ($errors->has('payment'))
        <div style="background:#FEF2F2;border:1px solid #FECACA;border-left:3px solid #DC2626;padding:0.875rem 1rem;margin-bottom:1.5rem;font-size:0.8125rem;color:#991B1B;border-radius:2px;">
            {{ $errors->first('payment') }}
        </div>
    @endif

    <div class="checkout-grid">
        {{-- Formulario de pago --}}
        <form method="POST" action="{{ route('orders.payment.store', $order) }}" id="payment-form" data-clarity-mask="true">
            @csrf

            <div class="checkout-form-card">
                <h2 class="checkout-section-title">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="1" y="4" width="22" height="16" rx="2" ry="2"/><line x1="1" y1="10" x2="23" y2="10"/></svg>
                    Método de pago (demo)
                </h2>

                <div style="display:flex;flex-direction:column;gap:0.75rem;">
                    @foreach ($methods as $method)
                        @php
                            $labels = [
                                'test_card'          => ['icon' => '💳', 'label' => 'Tarjeta de prueba',         'desc' => 'Simula un cargo con tarjeta. Sin datos reales.'],
                                'bank_transfer_demo' => ['icon' => '🏦', 'label' => 'Transferencia bancaria demo','desc' => 'Simula una transferencia. Sin datos bancarios.'],
                                'wallet_demo'        => ['icon' => '📱', 'label' => 'Billetera virtual demo',    'desc' => 'Simula pago con billetera. Sin credenciales reales.'],
                            ];
                            $meta = $labels[$method->value] ?? ['icon' => '💰', 'label' => $method->value, 'desc' => ''];
                        @endphp
                        <label style="display:flex;align-items:flex-start;gap:0.875rem;border:1px solid var(--zyro-border);padding:1rem 1.125rem;cursor:pointer;border-radius:2px;transition:border-color 0.15s ease;background:var(--zyro-white);"
                               onclick="this.style.borderColor='var(--zyro-black)'">
                            <input type="radio" name="method" value="{{ $method->value }}" style="margin-top:0.2rem;accent-color:var(--zyro-black);"
                                   {{ old('method') === $method->value || ($loop->first && !old('method')) ? 'checked' : '' }}>
                            <div>
                                <p style="font-weight:600;font-size:0.875rem;color:var(--zyro-black);margin-bottom:0.2rem;">
                                    {{ $meta['icon'] }} {{ $meta['label'] }}
                                </p>
                                <p style="font-size:0.75rem;color:var(--zyro-muted);">{{ $meta['desc'] }}</p>
                            </div>
                        </label>
                    @endforeach
                </div>

                @error('method')
                    <p class="form-error" style="margin-top:0.5rem;">{{ $message }}</p>
                @enderror

                <button
                    type="submit"
                    id="pay-btn"
                    class="btn-confirm-order"
                    onclick="this.disabled=true;this.textContent='Procesando…';this.form.submit();"
                >
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
                    Confirmar pago simulado
                </button>
            </div>
        </form>

        {{-- Resumen del pedido --}}
        <div class="checkout-summary-card">
            <h2 class="summary-card-title">Resumen del pedido</h2>

            <div class="summary-row">
                <span>Subtotal</span>
                <span>${{ number_format($order->subtotal, 2) }}</span>
            </div>
            <div class="summary-row">
                <span>IVA (15%)</span>
                <span>${{ number_format($order->tax_amount, 2) }}</span>
            </div>
            <div class="summary-row" style="align-items:flex-start;">
                <div>
                    <span>Modalidad de entrega</span>
                    <span style="font-size:0.75rem; color:var(--zyro-muted); display:block;">
                        {{ $order->delivery_method_label }}
                    </span>
                    <span style="font-size:0.6875rem; font-weight:600; color:var(--zyro-black); display:inline-block; margin-top:2px; background:var(--zyro-white); border:1px solid var(--zyro-border); padding:1px 6px; border-radius:2px;">
                        ⏱ {{ $order->delivery_estimate_label }}
                    </span>
                </div>
                <span style="font-weight:600;">
                    {{ $order->shipping_amount > 0 ? '$'.number_format($order->shipping_amount, 2) : 'GRATIS' }}
                </span>
            </div>

            <div class="summary-divider"></div>

            <div class="summary-total-row">
                <span>Total a simular</span>
                <span>${{ number_format($order->total, 2) }}</span>
            </div>

            <p style="font-size:0.75rem;color:var(--zyro-muted);margin-top:1rem;text-align:center;line-height:1.5;">
                Transacción de prueba — No se efectúan cargos reales
            </p>

            <div style="margin-top:1.5rem;padding-top:1.25rem;border-top:1px solid var(--zyro-border);">
                <a href="{{ route('orders.show', $order) }}" style="font-size:0.8125rem;color:var(--zyro-muted);text-decoration:underline;">
                    ← Volver al pedido
                </a>
            </div>
        </div>
    </div>

</div>
@endsection
