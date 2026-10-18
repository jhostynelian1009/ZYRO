@extends('layouts.store')

@section('title', 'Mis pedidos — ZYRO')

@section('content')
<section class="mx-auto max-w-6xl px-4 py-12">
    <h1 class="font-serif text-4xl mb-8">Mis pedidos</h1>

    @if ($orders->isEmpty())
        <div class="border border-zyro-border bg-zyro-gray px-6 py-16 text-center text-zyro-muted">
            <p class="mb-4">No tienes pedidos todavía.</p>
            <a href="{{ route('catalog.index') }}" class="inline-block bg-zyro text-white px-6 py-2 text-sm uppercase tracking-wide">Ver colección</a>
        </div>
    @else
        <div class="overflow-x-auto">
            <table class="w-full text-sm border-collapse">
                <thead>
                    <tr class="border-b border-zyro-border text-left text-xs uppercase tracking-wide text-zyro-muted">
                        <th class="py-3 pr-4">Número</th>
                        <th class="py-3 pr-4">Total</th>
                        <th class="py-3 pr-4">Estado</th>
                        <th class="py-3 pr-4">Fecha</th>
                        <th class="py-3"></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($orders as $order)
                        <tr class="border-b border-zyro-border hover:bg-zyro-gray">
                            <td class="py-3 pr-4 font-medium">{{ $order->order_number }}</td>
                            <td class="py-3 pr-4">${{ number_format($order->total, 2) }}</td>
                            <td class="py-3 pr-4">
                                @include('orders.partials.status-badge', ['status' => $order->status])
                            </td>
                            <td class="py-3 pr-4 text-zyro-muted">{{ $order->created_at->format('d/m/Y H:i') }}</td>
                            <td class="py-3">
                                <a href="{{ route('orders.show', $order) }}" class="underline hover:opacity-60">Ver</a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="mt-6">{{ $orders->links() }}</div>
    @endif
</section>
@endsection
