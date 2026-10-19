@extends('layouts.store')

@section('title', 'Pago simulado — '.$order->order_number.' — ZYRO')

@section('content')
<section class="mx-auto max-w-2xl px-4 py-12">

    {{-- Indicación discreta de entorno de pago simulado --}}
    <div style="background:#F7F7F8; border:1px solid #E8E8EC; border-left:3px solid #0055FF; padding:0.875rem 1.25rem; margin-bottom:2rem; font-size:0.8125rem; color:#52525B; line-height:1.5;">
        <strong style="color:#0F0F11; display:block; margin-bottom:0.2rem;">Entorno de pago protegido (Modo demostración):</strong>
        Esta pasarela procesa transacciones simuladas de prueba. No se requiere tarjeta real ni se debitará saldo alguno.
    </div>

    <h1 class="font-serif text-3xl mb-2">Pago simulado</h1>
    <p class="text-sm text-zyro-muted mb-8">Pedido <span class="font-medium">{{ $order->order_number }}</span> · Total: <span class="font-medium">${{ number_format($order->total, 2) }}</span></p>

    @if ($errors->has('payment'))
        <div class="mb-6 border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">{{ $errors->first('payment') }}</div>
    @endif

    <form method="POST" action="{{ route('orders.payment.store', $order) }}" id="payment-form">
        @csrf

        <div class="mb-6">
            <p class="text-sm font-medium mb-3">Selecciona el método de pago (demo)</p>
            <div class="space-y-3">
                @foreach ($methods as $method)
                    @php
                        $labels = [
                            'test_card'           => ['icon' => '💳', 'label' => 'Tarjeta de prueba',        'desc' => 'Simula un pago con tarjeta. Sin datos reales.'],
                            'bank_transfer_demo'  => ['icon' => '🏦', 'label' => 'Transferencia bancaria demo', 'desc' => 'Simula una transferencia. Sin datos bancarios.'],
                            'wallet_demo'         => ['icon' => '📱', 'label' => 'Billetera virtual demo',   'desc' => 'Simula pago con billetera. Sin credenciales reales.'],
                        ];
                        $meta = $labels[$method->value] ?? ['icon' => '💰', 'label' => $method->value, 'desc' => ''];
                    @endphp
                    <label class="flex items-start gap-3 border border-zyro-border p-4 cursor-pointer hover:bg-zyro-gray has-[:checked]:border-zyro has-[:checked]:bg-zyro-gray">
                        <input type="radio" name="method" value="{{ $method->value }}" class="mt-1"
                               {{ old('method') === $method->value || ($loop->first && !old('method')) ? 'checked' : '' }}>
                        <div>
                            <p class="font-medium text-sm">{{ $meta['icon'] }} {{ $meta['label'] }}</p>
                            <p class="text-xs text-zyro-muted mt-0.5">{{ $meta['desc'] }}</p>
                        </div>
                    </label>
                @endforeach
            </div>
            @error('method') <p class="mt-2 text-sm text-red-700">{{ $message }}</p> @enderror
        </div>

        <div class="border border-zyro-border bg-zyro-gray px-4 py-3 mb-6 text-sm">
            <div class="flex justify-between mb-1">
                <span class="text-zyro-muted">Subtotal</span>
                <span>${{ number_format($order->subtotal, 2) }}</span>
            </div>
            <div class="flex justify-between mb-1">
                <span class="text-zyro-muted">IVA</span>
                <span>${{ number_format($order->tax_amount, 2) }}</span>
            </div>
            <div class="flex justify-between mb-1">
                <span class="text-zyro-muted">Envío</span>
                <span>${{ number_format($order->shipping_amount, 2) }}</span>
            </div>
            <div class="flex justify-between font-semibold pt-2 border-t border-zyro-border">
                <span>Total a simular</span>
                <span>${{ number_format($order->total, 2) }}</span>
            </div>
        </div>

        <button type="submit" id="pay-btn"
                class="w-full bg-zyro text-white py-3 uppercase tracking-wide text-sm"
                onclick="this.disabled=true;this.textContent='Procesando...';this.form.submit();">
            Confirmar pago simulado
        </button>
    </form>

    <p class="mt-6 text-xs text-center text-zyro-muted">
        Transacción de prueba — No se efectúan cargos monetarios reales
    </p>

    <div class="mt-6">
        <a href="{{ route('orders.show', $order) }}" class="text-sm underline hover:opacity-60">← Volver al pedido</a>
    </div>
</section>
@endsection
