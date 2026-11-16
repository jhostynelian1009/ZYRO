@extends('layouts.admin')

@section('title', 'Registrar venta manual — Admin — ZYRO')
@section('section_title', 'Crear venta omnicanal')

@section('content')
    <div class="admin-page-header">
        <div>
            <p class="admin-kicker">Operaciones &bull; Venta manual</p>
            <h1 class="admin-title">Registrar venta</h1>
            <p class="admin-subtitle">Crear una venta procedente de punto físico o WhatsApp.</p>
        </div>
        <div>
            <a href="{{ route('admin.orders.index') }}" class="btn-action">
                <span>&larr;</span>
                <span>Volver a Pedidos</span>
            </a>
        </div>
    </div>

    @if ($errors->any())
        <div class="alert alert-error" style="margin-bottom:1.5rem;">
            <ul style="list-style:none; margin:0; padding:0;">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('admin.manual-orders.store') }}" class="card-table-wrapper" style="padding:1.5rem; background:var(--zyro-white); border:1px solid var(--zyro-border);">
        @csrf
        <div class="grid" style="display:grid; grid-template-columns:1fr 1fr; gap:1.5rem;">
            {{-- Canal de origen --}}
            <div>
                <label class="font-bold" for="channel_origin">Canal de origen</label>
                <select name="channel_origin" id="channel_origin" class="form-select" required>
                    @foreach ($channels as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            {{-- Responsable (admin) se registra automáticamente – campo oculto con ID del admin --}}
            <input type="hidden" name="user_id" value="{{ auth()->id() }}">

            {{-- Cliente – buscar existente o crear nuevo --}}
            <div>
                <label class="font-bold" for="customer_name">Cliente</label>
                <input type="text" name="customer_name" id="customer_name" class="form-input" required placeholder="Nombre del cliente" value="{{ old('customer_name') }}">
            </div>
            <div>
                <label class="font-bold" for="email">Email (opcional)</label>
                <input type="email" name="email" id="email" class="form-input" placeholder="correo@ejemplo.com" value="{{ old('email') }}">
            </div>
            <div>
                <label class="font-bold" for="phone">Teléfono</label>
                <input type="text" name="phone" id="phone" class="form-input" required placeholder="+593 9xx xxx xxx" value="{{ old('phone') }}">
            </div>
            <div>
                <label class="font-bold" for="address">Dirección</label>
                <input type="text" name="address" id="address" class="form-input" required placeholder="Calle, número, barrio" value="{{ old('address') }}">
            </div>
            <div>
                <label class="font-bold" for="city">Ciudad</label>
                <input type="text" name="city" id="city" class="form-input" required placeholder="Guayaquil" value="{{ old('city') }}">
            </div>
            <div>
                <label class="font-bold" for="delivery_method">Modalidad de entrega</label>
                <select name="delivery_method" id="delivery_method" class="form-select" required>
                    <option value="standard">Estándar (domicilio)</option>
                    <option value="express">Exprés</option>
                    <option value="pickup">Retiro en tienda</option>
                </select>
            </div>
            <div>
                <label class="font-bold" for="payment_method">Método de pago</label>
                <select name="payment_method" id="payment_method" class="form-select" required>
                    <option value="cash">Efectivo</option>
                    <option value="card">Tarjeta (simulada)</option>
                </select>
            </div>
            <div>
                <label class="font-bold" for="mark_as_paid">
                    <input type="checkbox" name="mark_as_paid" id="mark_as_paid" value="1">
                    Marcar como pagado (demo)
                </label>
            </div>
        </div>
        <hr class="my-4" />
        <h2 class="font-bold mb-2">Productos</h2>
        <div id="items-container">
            {{-- Por defecto un ítem – se puede añadir más vía JS (no incluido aquí) --}}
            <div class="grid" style="display:grid; grid-template-columns:2fr 1fr 1fr 1fr; gap:0.75rem; align-items:end; margin-bottom:1rem;">
                <div>
                    <label class="font-bold" for="items[0][product_id]">Producto</label>
                    <select name="items[0][product_id]" class="form-select" required>
                        @foreach ($products as $product)
                            <option value="{{ $product->id }}">{{ $product->name }} ({{ $product->stock }} disponibles)</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="font-bold" for="items[0][size]">Talla (opcional)</label>
                    <input type="text" name="items[0][size]" class="form-input" placeholder="ej: M" />
                </div>
                <div>
                    <label class="font-bold" for="items[0][quantity]">Cantidad</label>
                    <input type="number" name="items[0][quantity]" class="form-input" min="1" value="1" required />
                </div>
                <div>
                    <label class="font-bold" for="items[0][notes]">Notas</label>
                    <input type="text" name="items[0][notes]" class="form-input" placeholder="Observaciones" />
                </div>
            </div>
        </div>
        <button type="submit" class="btn-action btn-action-primary" style="margin-top:1rem;">
            <span>✓ Registrar venta</span>
        </button>
    </form>
@endsection