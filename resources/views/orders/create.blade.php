@extends('layouts.store')

@section('title', 'Checkout — ZYRO')

@section('content')
<div class="checkout-page-wrapper container">

    <div class="checkout-header">
        <p style="font-size:0.6875rem;font-weight:700;letter-spacing:0.2em;text-transform:uppercase;color:var(--zyro-muted);margin-bottom:0.25rem;">
            Mi cuenta &rsaquo; Checkout
        </p>
        <h1>Confirmar pedido</h1>
    </div>

    @if ($errors->has('checkout'))
        <div style="background:#FEF2F2;border:1px solid #FECACA;border-left:3px solid #DC2626;padding:0.875rem 1rem;margin-bottom:1.5rem;font-size:0.8125rem;color:#991B1B;border-radius:2px;">
            {{ $errors->first('checkout') }}
        </div>
    @endif

    <div class="checkout-grid">
        {{-- Formulario de entrega --}}
        <form method="POST" action="{{ route('orders.store') }}" data-clarity-mask="true">
            @csrf

            <div class="checkout-form-card">
                <h2 class="checkout-section-title">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0118 0z"/><circle cx="12" cy="10" r="3"/></svg>
                    Datos de entrega
                </h2>

                <div class="checkout-fields-grid">

                    <div class="form-group">
                        <label for="customer_name" class="form-label">Nombre del destinatario</label>
                        <input
                            id="customer_name"
                            name="customer_name"
                            type="text"
                            value="{{ old('customer_name', $user->name) }}"
                            required
                            autocomplete="name"
                            placeholder="Nombre completo"
                            class="form-input @error('customer_name') is-invalid @enderror"
                        >
                        @error('customer_name')
                            <p class="form-error">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="form-grid-2">
                        <div class="form-group">
                            <label for="phone" class="form-label">Teléfono de contacto</label>
                            <input
                                id="phone"
                                name="phone"
                                type="tel"
                                value="{{ old('phone') }}"
                                required
                                autocomplete="tel"
                                placeholder="+593 99 000 0000"
                                class="form-input @error('phone') is-invalid @enderror"
                            >
                            @error('phone')
                                <p class="form-error">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="form-group">
                            <label for="city" class="form-label">Ciudad / Cantón</label>
                            <input
                                id="city"
                                name="city"
                                type="text"
                                value="{{ old('city') }}"
                                required
                                autocomplete="address-level2"
                                placeholder="Ej. Quito, Guayaquil"
                                class="form-input @error('city') is-invalid @enderror"
                            >
                            @error('city')
                                <p class="form-error">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="address" class="form-label">Dirección completa</label>
                        <input
                            id="address"
                            name="address"
                            type="text"
                            value="{{ old('address') }}"
                            required
                            autocomplete="street-address"
                            placeholder="Calle, número, sector o referencia"
                            class="form-input @error('address') is-invalid @enderror"
                        >
                        @error('address')
                            <p class="form-error">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="form-group">
                        <label for="delivery_method" class="form-label">Modalidad de entrega</label>
                        <select
                            id="delivery_method"
                            name="delivery_method"
                            required
                            class="checkout-select @error('delivery_method') is-invalid @enderror"
                        >
                            <option value="">— Seleccionar modalidad —</option>
                            <option value="pickup" {{ old('delivery_method') === 'pickup' ? 'selected' : '' }}>
                                Retiro en tienda (sin cargo adicional)
                            </option>
                            <option value="standard" {{ old('delivery_method') === 'standard' ? 'selected' : '' }}>
                                Envío estándar (demo — ${{ number_format(config('shop.shipping', 6.00), 2) }})
                            </option>
                            <option value="express" {{ old('delivery_method') === 'express' ? 'selected' : '' }}>
                                Envío express (demo — ${{ number_format(config('shop.shipping', 6.00), 2) }})
                            </option>
                        </select>
                        @error('delivery_method')
                            <p class="form-error">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="form-group">
                        <label for="delivery_notes" class="form-label">
                            Notas de entrega <span style="font-weight:400;text-transform:none;letter-spacing:0;color:var(--zyro-muted);">(opcional)</span>
                        </label>
                        <textarea
                            id="delivery_notes"
                            name="delivery_notes"
                            class="checkout-textarea @error('delivery_notes') is-invalid @enderror"
                            placeholder="Instrucciones adicionales para el repartidor…"
                        >{{ old('delivery_notes') }}</textarea>
                        @error('delivery_notes')
                            <p class="form-error">{{ $message }}</p>
                        @enderror
                    </div>

                </div>{{-- checkout-fields-grid --}}

                <button type="submit" class="btn-confirm-order">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="20 6 9 17 4 12"/></svg>
                    Confirmar y proceder al pago
                </button>

            </div>{{-- checkout-form-card --}}
        </form>

        {{-- Resumen lateral --}}
        <div class="checkout-summary-card">
            <h2 class="summary-card-title">Tu pedido</h2>

            <div class="checkout-items-list">
                @foreach ($items as $item)
                    <div class="checkout-item-mini">
                        <div class="checkout-item-mini-info">
                            <span class="checkout-item-mini-name">{{ $item['name'] }}</span>
                            <span class="checkout-item-mini-meta">
                                @if($item['size'])Talla {{ $item['size'] }} · @endif×{{ $item['quantity'] }}
                            </span>
                        </div>
                        <span class="checkout-item-mini-price">${{ number_format($item['price'] * $item['quantity'], 2) }}</span>
                    </div>
                @endforeach
            </div>

            <div class="summary-divider"></div>

            <div class="summary-row">
                <span>Subtotal</span>
                <span>${{ number_format($subtotal, 2) }}</span>
            </div>
            <div class="summary-row">
                <span>IVA (15%)</span>
                <span>${{ number_format($subtotal * config('shop.iva_rate', 0.15), 2) }}</span>
            </div>
            <div class="summary-row">
                <span>Envío (demo)</span>
                <span>${{ number_format(config('shop.shipping', 6.00), 2) }}</span>
            </div>

            <div class="summary-divider"></div>

            <div class="summary-total-row">
                <span>Total estimado</span>
                <span>${{ number_format($subtotal + $subtotal * config('shop.iva_rate', 0.15) + config('shop.shipping', 6.00), 2) }}</span>
            </div>

            <div class="checkout-disclaimer-box">
                <strong style="color:var(--zyro-black);display:block;margin-bottom:0.2rem;">Pago inmediato</strong>
                El total definitivo se calcula en el servidor al confirmar. Al confirmar tu pedido, pasarás directamente a la pantalla de pago para finalizar tu compra.
            </div>
        </div>

    </div>{{-- checkout-grid --}}
</div>
@endsection
