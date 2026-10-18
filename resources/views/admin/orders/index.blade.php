@extends('layouts.store')

@section('title', 'Pedidos — Admin — ZYRO')

@section('content')
<section class="mx-auto max-w-6xl px-4 py-10">

    <div class="flex flex-wrap items-center justify-between gap-4 mb-8">
        <div>
            <p class="text-xs uppercase tracking-[0.3em] text-zyro-muted mb-1">Admin</p>
            <h1 class="font-serif text-4xl">Pedidos</h1>
        </div>
        <a href="{{ route('admin.dashboard') }}" class="text-sm underline hover:opacity-60">← Dashboard</a>
    </div>

    @if (session('status'))
        <div class="mb-6 border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">{{ session('status') }}</div>
    @endif
    @if ($errors->has('order'))
        <div class="mb-6 border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">{{ $errors->first('order') }}</div>
    @endif

    @if ($orders->isEmpty())
        <p class="border border-zyro-border bg-zyro-gray px-6 py-16 text-center text-sm text-zyro-muted">
            No hay pedidos registrados.
        </p>
    @else
        <div class="overflow-x-auto">
            <table class="w-full text-sm border-collapse">
                <thead>
                    <tr class="border-b border-zyro-border text-left text-xs uppercase tracking-wide text-zyro-muted">
                        <th class="py-3 pr-4">Número</th>
                        <th class="py-3 pr-4">Cliente</th>
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
                            <td class="py-3 pr-4">{{ $order->user->name ?? '—' }}</td>
                            <td class="py-3 pr-4">${{ number_format($order->total, 2) }}</td>
                            <td class="py-3 pr-4">
                                @include('orders.partials.status-badge', ['status' => $order->status])
                            </td>
                            <td class="py-3 pr-4 text-zyro-muted">{{ $order->created_at->format('d/m/Y H:i') }}</td>
                            <td class="py-3">
                                <a href="{{ route('admin.orders.show', $order) }}" class="underline hover:opacity-60">Ver</a>
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
