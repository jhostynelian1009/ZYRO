@extends('layouts.store')

@section('title', 'Carrito — ZYRO')

@section('content')
<div class="cart-page-wrapper container">

    <div class="cart-header">
        <p style="font-size:0.6875rem;font-weight:700;letter-spacing:0.2em;text-transform:uppercase;color:var(--zyro-muted);margin-bottom:0.25rem;">
            Mi cuenta
        </p>
        <h1>Carrito de compras</h1>
    </div>

    @error('cart')
        <div style="background:#FEF2F2;border:1px solid #FECACA;border-left:3px solid #DC2626;padding:0.875rem 1rem;margin-bottom:1.5rem;font-size:0.8125rem;color:#991B1B;border-radius:2px;">
            {{ $message }}
        </div>
    @enderror

    @if (empty($items))
        <div class="cart-empty-card">
            <span class="cart-empty-icon">🛒</span>
            <h2>El carrito está vacío</h2>
            <p>Aún no has agregado ninguna prenda. Explora la colección y encuentra tu modelo.</p>
            <a href="{{ route('catalog.index') }}" class="btn-primary">Ver colección</a>
        </div>
    @else
        <div class="cart-grid">
            {{-- Lista de ítems --}}
            <div class="cart-items-card">
                @foreach ($items as $key => $item)
                    <div class="cart-item-row">
                        {{-- Miniatura --}}
                        <div class="cart-thumb">
                            <img
                                src="{{ asset($item['image_path'] ?? 'images/products/placeholder.svg') }}"
                                alt="{{ $item['name'] }}"
                            >
                        </div>

                        {{-- Info --}}
                        <div class="cart-item-info">
                            <p class="cart-item-name">{{ $item['name'] }}</p>
                            @if ($item['size'])
                                <span class="cart-item-size-pill">Talla: {{ $item['size'] }}</span>
                            @endif
                            @if (!empty($item['customization']))
                                <div style="font-size:.75rem;color:var(--zyro-muted);margin-top:.45rem;line-height:1.55;"><strong style="color:var(--zyro-black);">Personalizada</strong> · {{ ucfirst($item['customization']['color']) }} · {{ str_replace('_', ' ', $item['customization']['location']) }}@if(!empty($item['customization']['text']))<br>Texto: “{{ $item['customization']['text'] }}”@endif</div>
                            @endif
                            <p class="cart-item-unit-price">${{ number_format($item['price'], 2) }} por unidad</p>

                            <div class="cart-item-actions">
                                {{-- Actualizar cantidad --}}
                                <form method="POST" action="{{ route('cart.update', $item['product_id']) }}" class="cart-qty-form">
                                    @csrf
                                    @method('PATCH')
                                    <input type="hidden" name="size" value="{{ $item['size'] }}">
                                    <input type="hidden" name="item_key" value="{{ $key }}">
                                    <label for="qty-{{ $key }}" class="sr-only">Cantidad</label>
                                    <input
                                        id="qty-{{ $key }}"
                                        type="number"
                                        name="quantity"
                                        value="{{ $item['quantity'] }}"
                                        min="1"
                                        max="{{ $item['stock'] }}"
                                        class="cart-qty-input"
                                    >
                                    <button type="submit" class="btn-cart-update">Actualizar</button>
                                </form>

                                {{-- Eliminar --}}
                                <form method="POST" action="{{ route('cart.destroy', $item['product_id']) }}?size={{ $item['size'] }}&item_key={{ urlencode($key) }}">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn-cart-remove">
                                        <svg width="12" height="12" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z" clip-rule="evenodd" /></svg>
                                        Eliminar
                                    </button>
                                </form>
                            </div>
                        </div>

                        {{-- Subtotal --}}
                        <div class="cart-item-total">
                            <p class="cart-item-total-amount">${{ number_format($item['price'] * $item['quantity'], 2) }}</p>
                            <p style="font-size:0.6875rem;color:var(--zyro-muted);margin-top:0.25rem;">×{{ $item['quantity'] }}</p>
                        </div>
                    </div>
                @endforeach
            </div>

            {{-- Resumen --}}
            <div class="cart-summary-card">
                <h2 class="summary-card-title">Resumen</h2>

                <div class="summary-row">
                    <span>Productos ({{ array_sum(array_column($items, 'quantity')) }})</span>
                    <span>${{ number_format($subtotal, 2) }}</span>
                </div>
                <div class="summary-row" style="font-size:0.8125rem; color:var(--zyro-muted);">
                    <span>Envío</span>
                    <span>${{ number_format(config('shop.shipping', 6.00), 2) }}</span>
                </div>
                <div class="summary-row" style="font-size:0.8125rem; color:var(--zyro-muted);">
                    <span>IVA (15%)</span>
                    <span>${{ number_format($subtotal * config('shop.iva_rate', 0.15), 2) }}</span>
                </div>

                <div class="summary-divider"></div>

                <div class="summary-total-row">
                    <span>Total estimado</span>
                    <span>${{ number_format($subtotal + ($subtotal * config('shop.iva_rate', 0.15)) + config('shop.shipping', 6.00), 2) }}</span>
                </div>

                <p class="summary-note">El total definitivo (IVA 15% + envío $6.00) se calcula y valida en el servidor al confirmar.</p>

                <a href="{{ route('checkout.create') }}" class="btn-primary" style="width:100%;text-align:center;display:block;padding:0.875rem;">
                    Continuar al checkout
                </a>
                <a href="{{ route('catalog.index') }}" class="btn-secondary" style="width:100%;text-align:center;display:block;padding:0.875rem;margin-top:0.75rem;">
                    Seguir comprando
                </a>
            </div>
        </div>
    @endif
</div>
@endsection
