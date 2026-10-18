@extends('layouts.store')

@section('title', 'Pedido '.$order->order_number.' — Admin — ZYRO')

@section('content')
<section class="mx-auto max-w-5xl px-4 py-10">

    {{-- Mensajes --}}
    @if (session('status'))
        <div class="mb-6 border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">{{ session('status') }}</div>
    @endif
    @if ($errors->has('order'))
        <div class="mb-6 border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">{{ $errors->first('order') }}</div>
    @endif

    {{-- Cabecera --}}
    <div class="flex flex-wrap items-start justify-between gap-4 mb-8">
        <div>
            <p class="text-xs uppercase tracking-[0.3em] text-zyro-muted mb-1">Admin / Pedido</p>
            <h1 class="font-serif text-3xl">{{ $order->order_number }}</h1>
            <p class="text-sm text-zyro-muted mt-1">{{ $order->created_at->format('d/m/Y H:i') }}</p>
        </div>
        <div class="flex flex-wrap items-center gap-3">
            @include('orders.partials.status-badge', ['status' => $order->status])

            {{-- Acciones solo para pending_approval --}}
            @if ($order->status->value === 'pending_approval')
                <form method="POST" action="{{ route('admin.orders.approve', $order) }}">
                    @csrf
                    <button type="submit"
                            class="bg-green-700 text-white px-4 py-2 text-sm uppercase tracking-wide hover:opacity-80"
                            onclick="return confirm('¿Aprobar el pedido {{ $order->order_number }}?')">
                        Aprobar
                    </button>
                </form>
                <form method="POST" action="{{ route('admin.orders.reject', $order) }}">
                    @csrf
                    <button type="submit"
                            class="bg-red-700 text-white px-4 py-2 text-sm uppercase tracking-wide hover:opacity-80"
                            onclick="return confirm('¿Rechazar el pedido {{ $order->order_number }}? Esta acción no puede deshacerse.')">
                        Rechazar
                    </button>
                </form>
            @endif
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
                            @if ($item->product)
                                <p class="text-xs text-zyro-muted">Stock actual: {{ $item->product->stock }}</p>
                            @endif
                        </div>
                        <p class="font-medium whitespace-nowrap">${{ number_format($item->line_total, 2) }}</p>
                    </div>
                @endforeach
            </div>
            <div class="border border-zyro-border border-t-0 px-4 py-3 bg-zyro-gray text-sm space-y-1">
                <div class="flex justify-between text-zyro-muted"><span>Subtotal</span><span>${{ number_format($order->subtotal, 2) }}</span></div>
                <div class="flex justify-between text-zyro-muted"><span>IVA</span><span>${{ number_format($order->tax_amount, 2) }}</span></div>
                <div class="flex justify-between text-zyro-muted"><span>Envío</span><span>${{ number_format($order->shipping_amount, 2) }}</span></div>
                <div class="flex justify-between font-semibold pt-1 border-t border-zyro-border"><span>Total</span><span>${{ number_format($order->total, 2) }}</span></div>
            </div>
        </div>

        {{-- Cliente y entrega --}}
        <div class="space-y-6">
            <div>
                <h2 class="text-xs uppercase tracking-wide text-zyro-muted mb-3">Cliente</h2>
                <div class="border border-zyro-border px-4 py-3 text-sm space-y-1">
                    <p><span class="text-zyro-muted">Nombre:</span> {{ $order->user->name ?? '—' }}</p>
                    <p><span class="text-zyro-muted">Correo:</span> {{ $order->user->email ?? '—' }}</p>
                </div>
            </div>

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

            {{-- Aprobación / Rechazo --}}
            @if ($order->approved_at)
                <div class="border border-green-200 bg-green-50 px-4 py-3 text-sm">
                    <p class="text-green-700 font-medium">Aprobado</p>
                    <p class="text-green-800">Por: {{ $order->approvedBy->name ?? '—' }}</p>
                    <p class="text-green-800">{{ $order->approved_at->format('d/m/Y H:i') }}</p>
                </div>
            @endif
            @if ($order->rejected_at)
                <div class="border border-red-200 bg-red-50 px-4 py-3 text-sm">
                    <p class="text-red-700 font-medium">Rechazado</p>
                    <p class="text-red-800">Por: {{ $order->rejectedBy->name ?? '—' }}</p>
                    <p class="text-red-800">{{ $order->rejected_at->format('d/m/Y H:i') }}</p>
                </div>
            @endif

            {{-- Pagos --}}
            @if ($order->payments->isNotEmpty())
                <div>
                    <h2 class="text-xs uppercase tracking-wide text-zyro-muted mb-3">Pagos simulados</h2>
                    <div class="border border-zyro-border divide-y divide-zyro-border text-sm">
                        @foreach ($order->payments->sortByDesc('created_at') as $payment)
                            <div class="px-4 py-3 space-y-0.5">
                                <div class="flex justify-between">
                                    <span class="font-medium">{{ $payment->reference }}</span>
                                    <span class="{{ $payment->status->value === 'approved' ? 'text-green-700 font-medium' : 'text-red-700' }}">
                                        {{ $payment->status->value === 'approved' ? 'Aprobado' : 'Rechazado' }}
                                    </span>
                                </div>
                                <p class="text-xs text-zyro-muted">Método: {{ $payment->method->value }}</p>
                                <p class="text-xs text-zyro-muted">Monto: ${{ number_format($payment->amount, 2) }}</p>
                                <p class="text-xs text-zyro-muted">{{ $payment->processed_at?->format('d/m/Y H:i:s') }}</p>
                            </div>
                        @endforeach
                    </div>
                    <p class="text-xs text-zyro-muted mt-1">SIMULACIÓN ACADÉMICA — no se procesó dinero real.</p>
                </div>
            @endif
        </div>
    </div>

    <div class="mt-8 flex gap-4">
        <a href="{{ route('admin.orders.index') }}" class="text-sm underline hover:opacity-60">← Todos los pedidos</a>
        <a href="{{ route('admin.dashboard') }}" class="text-sm underline hover:opacity-60">Dashboard</a>
    </div>
</section>
@endsection
