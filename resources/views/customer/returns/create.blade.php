@extends('layouts.store')

@section('title', 'Solicitar Cambio o Devolución — ZYRO')

@section('content')
<div class="orders-page-wrapper container">
    <div class="cart-header">
        <p style="font-size:0.6875rem;font-weight:700;letter-spacing:0.2em;text-transform:uppercase;color:var(--zyro-muted);margin-bottom:0.25rem;">
            <a href="{{ route('orders.show', $order) }}" style="color:var(--zyro-muted);">Pedido {{ $order->order_number }}</a> &rsaquo; Cambios y Devoluciones
        </p>
        <h1 style="font-family:'Cormorant Garamond',Georgia,serif;font-size:2.25rem;font-weight:600;line-height:1.15;color:var(--zyro-black);">
            Solicitar Cambio o Devolución
        </h1>
        <p style="font-size:0.875rem;color:var(--zyro-muted);margin-top:0.25rem;">
            Completa la información sobre la prenda adquirida para coordinar la revisión física.
        </p>
    </div>

    @if ($errors->any())
        <div style="background:#FEF2F2;border:1px solid #FECACA;border-left:3px solid #DC2626;padding:1rem;margin-bottom:1.5rem;font-size:0.8125rem;color:#991B1B;border-radius:2px;">
            <strong style="display:block;margin-bottom:0.25rem;">Corrige los siguientes campos:</strong>
            <ul style="margin-left:1.25rem;list-style:disc;">
                @foreach ($errors->all() as $err)
                    <li>{{ $err }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div style="max-width:760px;background:#FFFFFF;border:1px solid var(--zyro-border);padding:2rem;border-radius:2px;">
        <form method="POST" action="{{ route('customer.returns.store', $order) }}">
            @csrf

            {{-- Selección del artículo del pedido --}}
            <div style="margin-bottom:1.5rem;">
                <label for="order_item_id" style="display:block;font-size:0.75rem;font-weight:700;letter-spacing:0.08em;text-transform:uppercase;margin-bottom:0.5rem;color:var(--zyro-black);">
                    Prenda del pedido a devolver *
                </label>
                <select name="order_item_id" id="order_item_id" required style="width:100%;padding:0.75rem;border:1px solid var(--zyro-border);font-size:0.875rem;">
                    @foreach ($order->items as $item)
                        <option value="{{ $item->id }}" {{ old('order_item_id') == $item->id ? 'selected' : '' }}>
                            {{ $item->product_name }} {{ $item->size ? '(Talla: '.$item->size.')' : '' }} &bull; Cantidad: {{ $item->quantity }} &bull; ${{ number_format($item->line_total, 2) }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div style="display:grid;grid-template-columns:1fr 1fr;gap:1.5rem;margin-bottom:1.5rem;">
                {{-- Cantidad --}}
                <div>
                    <label for="quantity" style="display:block;font-size:0.75rem;font-weight:700;letter-spacing:0.08em;text-transform:uppercase;margin-bottom:0.5rem;color:var(--zyro-black);">
                        Cantidad a devolver *
                    </label>
                    <input type="number" id="quantity" name="quantity" min="1" max="10" value="{{ old('quantity', 1) }}" required style="width:100%;padding:0.75rem;border:1px solid var(--zyro-border);font-size:0.875rem;">
                </div>

                {{-- Solución solicitada --}}
                <div>
                    <label for="requested_solution" style="display:block;font-size:0.75rem;font-weight:700;letter-spacing:0.08em;text-transform:uppercase;margin-bottom:0.5rem;color:var(--zyro-black);">
                        Solución pretendida *
                    </label>
                    <select name="requested_solution" id="requested_solution" required style="width:100%;padding:0.75rem;border:1px solid var(--zyro-border);font-size:0.875rem;">
                        @foreach ($solutions as $sol)
                            <option value="{{ $sol->value }}" {{ old('requested_solution') === $sol->value ? 'selected' : '' }}>
                                {{ $sol->label() }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>

            {{-- Motivo principal --}}
            <div style="margin-bottom:1.5rem;">
                <label for="reason" style="display:block;font-size:0.75rem;font-weight:700;letter-spacing:0.08em;text-transform:uppercase;margin-bottom:0.5rem;color:var(--zyro-black);">
                    Motivo principal *
                </label>
                <input type="text" id="reason" name="reason" value="{{ old('reason') }}" required placeholder="Ej: Cambio de talla, disconformidad de ajuste, defecto detectado..." style="width:100%;padding:0.75rem;border:1px solid var(--zyro-border);font-size:0.875rem;">
            </div>

            {{-- Detalle del problema --}}
            <div style="margin-bottom:2rem;">
                <label for="problem_description" style="display:block;font-size:0.75rem;font-weight:700;letter-spacing:0.08em;text-transform:uppercase;margin-bottom:0.5rem;color:var(--zyro-black);">
                    Descripción detallada del caso *
                </label>
                <textarea id="problem_description" name="problem_description" rows="4" required placeholder="Describe las condiciones de la prenda y detalles adicionales de tu solicitud..." style="width:100%;padding:0.75rem;border:1px solid var(--zyro-border);font-size:0.875rem;">{{ old('problem_description') }}</textarea>
                <p style="font-size:0.6875rem;color:var(--zyro-muted);margin-top:0.35rem;">
                    Por políticas de calidad ZYRO, las prendas devueltas serán sometidas a inspección física en sucursal antes de autorizar la entrega de reemplazo o reintegro.
                </p>
            </div>

            <div style="display:flex;align-items:center;justify-content:space-between;padding-top:1rem;border-top:1px solid var(--zyro-border);">
                <a href="{{ route('orders.show', $order) }}" class="btn-action">
                    &larr; Cancelar y volver
                </a>
                <button type="submit" class="btn-primary" style="padding:0.85rem 2rem;">
                    Enviar Solicitud
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
