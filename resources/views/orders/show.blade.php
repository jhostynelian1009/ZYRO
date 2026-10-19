@extends('layouts.store')

@section('title', 'Pedido '.$order->order_number.' — ZYRO')

@section('content')
<section class="mx-auto max-w-4xl px-4 py-12">

    {{-- Mensajes de estado --}}
    @if (session('status'))
        <div class="mb-6 border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">{{ session('status') }}</div>
    @endif
    @if ($errors->has('payment'))
        <div class="mb-6 border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">{{ $errors->first('payment') }}</div>
    @endif

    {{-- Cabecera --}}
    <div class="flex flex-wrap items-start justify-between gap-4 mb-8">
        <div>
            <p class="text-xs uppercase tracking-[0.3em] text-zyro-muted mb-1">Pedido</p>
            <h1 class="font-serif text-3xl">{{ $order->order_number }}</h1>
            <p class="text-sm text-zyro-muted mt-1">{{ $order->created_at->format('d/m/Y H:i') }}</p>
        </div>
        <div class="text-right">
            @include('orders.partials.status-badge', ['status' => $order->status])
        </div>
    </div>

    <div class="grid gap-8 md:grid-cols-2">
        {{-- Artículos --}}
        <div>
            <h2 class="text-xs uppercase tracking-wide text-zyro-muted mb-3">Artículos</h2>
            <div class="border border-zyro-border divide-y divide-zyro-border">
                @foreach ($order->items as $item)
                    <div class="flex justify-between gap-4 px-4 py-3 text-sm">
                        <div>
                            <p class="font-medium">{{ $item->product_name }}</p>
                            @if ($item->size)
                                <p class="text-xs text-zyro-muted">Talla: {{ $item->size }}</p>
                            @endif
                            <p class="text-xs text-zyro-muted">×{{ $item->quantity }} × ${{ number_format($item->unit_price, 2) }}</p>
                        </div>
                        <p class="font-medium whitespace-nowrap">${{ number_format($item->line_total, 2) }}</p>
                    </div>
                @endforeach
            </div>

            {{-- Totales --}}
            <div class="border border-zyro-border border-t-0 px-4 py-3 bg-zyro-gray text-sm space-y-1">
                <div class="flex justify-between text-zyro-muted">
                    <span>Subtotal</span>
                    <span>${{ number_format($order->subtotal, 2) }}</span>
                </div>
                <div class="flex justify-between text-zyro-muted">
                    <span>IVA</span>
                    <span>${{ number_format($order->tax_amount, 2) }}</span>
                </div>
                <div class="flex justify-between text-zyro-muted">
                    <span>Envío</span>
                    <span>${{ number_format($order->shipping_amount, 2) }}</span>
                </div>
                <div class="flex justify-between font-semibold pt-1 border-t border-zyro-border">
                    <span>Total</span>
                    <span>${{ number_format($order->total, 2) }}</span>
                </div>
            </div>
        </div>

        {{-- Entrega y estado --}}
        <div class="space-y-6">
            <div>
                <h2 class="text-xs uppercase tracking-wide text-zyro-muted mb-3">Datos de entrega</h2>
                <div class="border border-zyro-border px-4 py-3 text-sm space-y-1">
                    <p><span class="text-zyro-muted">Destinatario:</span> {{ $order->customer_name }}</p>
                    <p><span class="text-zyro-muted">Teléfono:</span> {{ $order->phone }}</p>
                    <p><span class="text-zyro-muted">Dirección:</span> {{ $order->address }}, {{ $order->city }}</p>
                    <p><span class="text-zyro-muted">Modalidad:</span> {{ ucfirst($order->delivery_method) }}</p>
                    @if ($order->delivery_notes)
                        <p><span class="text-zyro-muted">Notas:</span> {{ $order->delivery_notes }}</p>
                    @endif
                </div>
            </div>

            {{-- Pago simulado --}}
            @if ($order->status->value === 'approved')
                <div class="border border-green-200 bg-green-50 px-4 py-4">
                    <p class="text-xs uppercase tracking-wide text-green-700 mb-2">Pedido aprobado</p>
                    <p class="text-sm text-green-800 mb-3">Tu pedido fue aprobado. Puedes realizar el pago simulado.</p>
                    <a href="{{ route('orders.payment', $order) }}"
                       class="inline-block bg-zyro text-white px-5 py-2 text-sm uppercase tracking-wide">
                        Pagar ahora
                    </a>
                </div>
            @elseif ($order->status->value === 'paid')
                <div class="border border-purple-200 bg-purple-50 px-4 py-4">
                    <p class="text-xs uppercase tracking-wide text-purple-700 mb-1">Pagado</p>
                    @php $pago = $order->payments->where('status.value', 'approved')->first() ?? $order->payments->sortByDesc('created_at')->first(); @endphp
                    @if ($pago)
                        <p class="text-sm text-purple-800">Referencia: {{ $pago->reference }}</p>
                        <p class="text-sm text-purple-800">Monto: ${{ number_format($pago->amount, 2) }}</p>
                    @endif
                    <p class="text-xs text-purple-600 mt-2">Transacción de prueba completada exitosamente.</p>
                </div>
            @elseif ($order->status->value === 'rejected')
                <div class="border border-red-200 bg-red-50 px-4 py-4">
                    <p class="text-xs uppercase tracking-wide text-red-700 mb-1">Pedido rechazado</p>
                    <p class="text-sm text-red-800">Este pedido fue rechazado por el administrador. No es posible pagarlo.</p>
                </div>
            @else
                <div class="border border-zyro-border bg-zyro-gray px-4 py-4">
                    <p class="text-xs uppercase tracking-wide text-zyro-muted mb-1">Pendiente de aprobación</p>
                    <p class="text-sm text-zyro-muted">Tu pedido está en revisión. El pago estará disponible una vez aprobado.</p>
                </div>
            @endif

            {{-- Historial de pagos --}}
            @if ($order->payments->isNotEmpty())
                <div>
                    <h2 class="text-xs uppercase tracking-wide text-zyro-muted mb-3">Intentos de pago</h2>
                    <div class="border border-zyro-border divide-y divide-zyro-border text-sm">
                        @foreach ($order->payments->sortByDesc('created_at') as $payment)
                            <div class="flex justify-between px-4 py-2">
                                <span class="text-zyro-muted">{{ $payment->reference }}</span>
                                <span class="{{ $payment->status->value === 'approved' ? 'text-green-700' : 'text-red-700' }}">
                                    {{ $payment->status->value === 'approved' ? 'Aprobado' : 'Rechazado' }}
                                </span>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>
    </div>

    <div class="mt-8">
        <a href="{{ route('orders.index') }}" class="text-sm underline hover:opacity-60">← Mis pedidos</a>
    </div>
</section>
@endsection
