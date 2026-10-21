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
        <form method="POST" action="{{ route('orders.store') }}" data-clarity-mask="true" id="checkout-form">
            @csrf

            <div class="checkout-form-card">
                <h2 class="checkout-section-title">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0118 0z"/><circle cx="12" cy="10" r="3"/></svg>
                    Datos del destinatario
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

                    <div class="form-group" id="address-group">
                        <label for="address" class="form-label">
                            Dirección completa
                            <span id="address-pickup-note" style="display:none;font-weight:400;text-transform:none;letter-spacing:0;color:var(--zyro-muted);">
                                (referencia del comprador para comprobante)
                            </span>
                        </label>
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

                </div>{{-- checkout-fields-grid --}}

                {{-- Sección: Método de entrega --}}
                <div style="margin-top:2rem;padding-top:1.5rem;border-top:1px solid var(--zyro-border);">
                    <h2 class="checkout-section-title" style="margin-bottom:0.75rem;">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="1" y="3" width="15" height="13"/><polygon points="16 8 20 8 23 11 23 16 16 16 16 8"/><circle cx="5.5" cy="18.5" r="2.5"/><circle cx="18.5" cy="18.5" r="2.5"/></svg>
                        Método de entrega
                    </h2>
                    <p style="font-size:0.8125rem;color:var(--zyro-muted);margin-bottom:1.25rem;">
                        Selecciona cómo deseas recibir tu pedido. Los plazos informados son estimados en días hábiles.
                    </p>

                    <div class="delivery-methods-stack" role="radiogroup" aria-label="Modalidad de entrega">
                        @foreach ($deliveryMethods as $key => $method)
                            @php
                                $isSelected = ($selectedMethod === $key);
                            @endphp
                            <label
                                for="delivery_{{ $key }}"
                                class="delivery-option-card {{ $isSelected ? 'is-selected' : '' }}"
                                id="card_delivery_{{ $key }}"
                            >
                                <input
                                    type="radio"
                                    name="delivery_method"
                                    id="delivery_{{ $key }}"
                                    value="{{ $key }}"
                                    class="delivery-radio-input"
                                    data-cost="{{ (float) $method['cost'] }}"
                                    data-name="{{ $method['name'] }}"
                                    data-estimate="{{ $method['estimate'] }}"
                                    {{ $isSelected ? 'checked' : '' }}
                                    required
                                >

                                <div class="delivery-card-content">
                                    <div class="delivery-card-top">
                                        <div class="delivery-card-title-wrap">
                                            <span class="delivery-card-icon" aria-hidden="true">
                                                @if ($key === 'standard')
                                                    🚚
                                                @elseif ($key === 'express')
                                                    ⚡
                                                @else
                                                    🏬
                                                @endif
                                            </span>
                                            <div>
                                                <span class="delivery-card-name">{{ $method['name'] }}</span>
                                                <span class="delivery-badge-estimate">
                                                    {{ $method['estimate'] }}
                                                </span>
                                            </div>
                                        </div>

                                        <div class="delivery-card-price">
                                            @if ($method['cost'] > 0)
                                                <span>${{ number_format($method['cost'], 2) }}</span>
                                            @else
                                                <span class="badge-free">GRATIS</span>
                                            @endif
                                        </div>
                                    </div>

                                    <p class="delivery-card-desc">{{ $method['description'] }}</p>

                                    @if ($key === 'pickup')
                                        <div class="delivery-pickup-alert">
                                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                                            <span><strong>Sucursal Centro:</strong> Espera la confirmación de que tu pedido está listo antes de acercarte a retirar.</span>
                                        </div>
                                    @endif
                                </div>
                            </label>
                        @endforeach
                    </div>

                    @error('delivery_method')
                        <p class="form-error" style="margin-top:0.75rem;">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Notas adicionales --}}
                <div class="form-group" style="margin-top:1.5rem;">
                    <label for="delivery_notes" class="form-label">
                        Notas adicionales <span style="font-weight:400;text-transform:none;letter-spacing:0;color:var(--zyro-muted);">(opcional)</span>
                    </label>
                    <textarea
                        id="delivery_notes"
                        name="delivery_notes"
                        class="checkout-textarea @error('delivery_notes') is-invalid @enderror"
                        placeholder="Instrucciones adicionales para la entrega o retiro…"
                    >{{ old('delivery_notes') }}</textarea>
                    @error('delivery_notes')
                        <p class="form-error">{{ $message }}</p>
                    @enderror
                </div>

                <button type="submit" class="btn-confirm-order" style="margin-top:2rem;">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="20 6 9 17 4 12"/></svg>
                    Confirmar y proceder al pago
                </button>

            </div>{{-- checkout-form-card --}}
        </form>

        {{-- Resumen lateral --}}
        <div class="checkout-summary-card">
            <h2 class="summary-card-title">Resumen de compra</h2>

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
                @php
                    $initialTax = round($subtotal * (float) config('shop.iva_rate', 0.15), 2);
                    $initialShipping = (float) ($deliveryMethods[$selectedMethod]['cost'] ?? config('shop.shipping', 6.00));
                    $initialTotal = round($subtotal + $initialTax + $initialShipping, 2);
                @endphp
                <span>${{ number_format($initialTax, 2) }}</span>
            </div>

            <div class="summary-row" style="align-items:flex-start;">
                <div>
                    <span style="display:block;">Método de entrega</span>
                    <span id="summary-delivery-name" style="font-size:0.75rem;color:var(--zyro-muted);display:block;">
                        {{ $deliveryMethods[$selectedMethod]['name'] ?? 'Envío estándar' }}
                    </span>
                    <span id="summary-delivery-estimate" style="font-size:0.6875rem;font-weight:600;color:var(--zyro-black);display:inline-block;margin-top:2px;background:var(--zyro-white);border:1px solid var(--zyro-border);padding:1px 6px;border-radius:2px;">
                        ⏱ {{ $deliveryMethods[$selectedMethod]['estimate'] ?? '2–5 días hábiles' }}
                    </span>
                </div>
                <span id="summary-shipping-cost" style="font-weight:600;">
                    {{ $initialShipping > 0 ? '$'.number_format($initialShipping, 2) : 'GRATIS' }}
                </span>
            </div>

            <div class="summary-divider"></div>

            <div class="summary-total-row">
                <span>Total final</span>
                <span id="summary-total-amount">${{ number_format($initialTotal, 2) }}</span>
            </div>

            <div class="checkout-disclaimer-box">
                <strong style="color:var(--zyro-black);display:block;margin-bottom:0.2rem;">Pago inmediato</strong>
                El total definitivo se calcula en el servidor. Al confirmar, pasarás directamente a la pantalla de pago para finalizar tu compra.
            </div>
        </div>

    </div>{{-- checkout-grid --}}
</div>

<style>
    /* Estilos de las tarjetas de métodos de entrega ZYRO */
    .delivery-methods-stack {
        display: flex;
        flex-direction: column;
        gap: 0.875rem;
    }

    .delivery-option-card {
        display: flex;
        align-items: flex-start;
        gap: 1rem;
        padding: 1.125rem 1.25rem;
        background: var(--zyro-white);
        border: 1px solid var(--zyro-border);
        border-radius: 2px;
        cursor: pointer;
        transition: border-color 0.2s ease, background-color 0.2s ease, box-shadow 0.2s ease;
        position: relative;
    }

    .delivery-option-card:hover {
        border-color: #9CA3AF;
    }

    .delivery-option-card.is-selected {
        border-color: var(--zyro-black);
        background: #FAFAFA;
        box-shadow: 0 0 0 1px var(--zyro-black);
    }

    .delivery-radio-input {
        margin-top: 0.25rem;
        accent-color: var(--zyro-black);
        cursor: pointer;
        width: 18px;
        height: 18px;
        flex-shrink: 0;
    }

    .delivery-card-content {
        flex: 1;
        min-width: 0;
    }

    .delivery-card-top {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 0.75rem;
        flex-wrap: wrap;
        margin-bottom: 0.35rem;
    }

    .delivery-card-title-wrap {
        display: flex;
        align-items: center;
        gap: 0.65rem;
        flex-wrap: wrap;
    }

    .delivery-card-icon {
        font-size: 1.25rem;
        line-height: 1;
    }

    .delivery-card-name {
        font-weight: 700;
        font-size: 0.9375rem;
        color: var(--zyro-black);
        letter-spacing: 0.02em;
    }

    .delivery-badge-estimate {
        display: inline-block;
        padding: 2px 7px;
        font-size: 0.6875rem;
        font-weight: 600;
        border-radius: 2px;
        background: var(--zyro-light);
        border: 1px solid var(--zyro-border);
        color: var(--zyro-black);
    }

    .delivery-card-price {
        font-weight: 700;
        font-size: 0.9375rem;
        color: var(--zyro-black);
    }

    .badge-free {
        display: inline-block;
        background: #ECFDF5;
        border: 1px solid #A7F3D0;
        color: #065F46;
        font-size: 0.6875rem;
        font-weight: 700;
        padding: 2px 6px;
        border-radius: 2px;
        letter-spacing: 0.05em;
    }

    .delivery-card-desc {
        font-size: 0.8125rem;
        color: var(--zyro-muted);
        line-height: 1.45;
        margin: 0;
    }

    .delivery-pickup-alert {
        margin-top: 0.5rem;
        padding: 0.5rem 0.75rem;
        background: #F3F4F6;
        border-left: 2px solid var(--zyro-black);
        font-size: 0.75rem;
        color: var(--zyro-black);
        display: flex;
        align-items: flex-start;
        gap: 0.5rem;
        line-height: 1.4;
    }

    .delivery-pickup-alert svg {
        flex-shrink: 0;
        margin-top: 2px;
    }

    @media (max-width: 640px) {
        .delivery-option-card {
            padding: 1rem;
        }
        .delivery-card-top {
            flex-direction: column;
            align-items: flex-start;
            gap: 0.25rem;
        }
    }
</style>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const subtotal = {{ (float) $subtotal }};
        const taxRate = {{ (float) config('shop.iva_rate', 0.15) }};
        const taxAmount = Math.round(subtotal * taxRate * 100) / 100;

        const radios = document.querySelectorAll('input[name="delivery_method"]');
        const summaryName = document.getElementById('summary-delivery-name');
        const summaryEstimate = document.getElementById('summary-delivery-estimate');
        const summaryCost = document.getElementById('summary-shipping-cost');
        const summaryTotal = document.getElementById('summary-total-amount');
        const pickupNote = document.getElementById('address-pickup-note');

        function updateDeliveryUI(radio) {
            if (!radio) return;

            const cost = parseFloat(radio.dataset.cost || 0);
            const name = radio.dataset.name || 'Envío estándar';
            const estimate = radio.dataset.estimate || '2–5 días hábiles';
            const methodKey = radio.value;

            // Actualizar clases de tarjetas
            document.querySelectorAll('.delivery-option-card').forEach(card => {
                card.classList.remove('is-selected');
            });
            const parentCard = radio.closest('.delivery-option-card');
            if (parentCard) {
                parentCard.classList.add('is-selected');
            }

            // Actualizar resumen lateral
            if (summaryName) summaryName.textContent = name;
            if (summaryEstimate) summaryEstimate.textContent = '⏱ ' + estimate;
            if (summaryCost) summaryCost.textContent = cost > 0 ? '$' + cost.toFixed(2) : 'GRATIS';

            const total = subtotal + taxAmount + cost;
            if (summaryTotal) summaryTotal.textContent = '$' + total.toFixed(2);

            // Nota de dirección si es retiro en tienda
            if (pickupNote) {
                pickupNote.style.display = (methodKey === 'pickup') ? 'inline' : 'none';
            }
        }

        radios.forEach(radio => {
            radio.addEventListener('change', function () {
                updateDeliveryUI(this);
            });
            if (radio.checked) {
                updateDeliveryUI(radio);
            }
        });
    });
</script>
@endpush
