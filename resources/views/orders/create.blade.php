@extends('layouts.store')

@section('title', 'Checkout — ZYRO')

@section('content')
<section class="mx-auto max-w-6xl px-4 py-12">
    <h1 class="font-serif text-4xl mb-8">Checkout</h1>

    @if ($errors->has('checkout'))
        <div class="mb-6 border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">{{ $errors->first('checkout') }}</div>
    @endif

    <div class="grid gap-8 lg:grid-cols-3">
        {{-- Formulario de entrega --}}
        <form method="POST" action="{{ route('orders.store') }}" class="lg:col-span-2 space-y-5">
            @csrf

            <h2 class="font-serif text-2xl mb-2">Datos de entrega</h2>

            <div>
                <label for="customer_name" class="block text-sm mb-1">Nombre del destinatario</label>
                <input id="customer_name" name="customer_name" type="text" value="{{ old('customer_name', $user->name) }}" required class="w-full border border-zyro-border px-3 py-2">
                @error('customer_name') <p class="mt-1 text-sm text-red-700">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="phone" class="block text-sm mb-1">Teléfono</label>
                <input id="phone" name="phone" type="tel" value="{{ old('phone') }}" required class="w-full border border-zyro-border px-3 py-2">
                @error('phone') <p class="mt-1 text-sm text-red-700">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="address" class="block text-sm mb-1">Dirección</label>
                <input id="address" name="address" type="text" value="{{ old('address') }}" required class="w-full border border-zyro-border px-3 py-2">
                @error('address') <p class="mt-1 text-sm text-red-700">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="city" class="block text-sm mb-1">Ciudad / Cantón</label>
                <input id="city" name="city" type="text" value="{{ old('city') }}" required class="w-full border border-zyro-border px-3 py-2">
                @error('city') <p class="mt-1 text-sm text-red-700">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="delivery_method" class="block text-sm mb-1">Modalidad de entrega</label>
                <select id="delivery_method" name="delivery_method" required class="w-full border border-zyro-border px-3 py-2 bg-white">
                    <option value="">— Seleccionar —</option>
                    <option value="pickup" {{ old('delivery_method') === 'pickup' ? 'selected' : '' }}>Retiro en tienda (demo)</option>
                    <option value="standard" {{ old('delivery_method') === 'standard' ? 'selected' : '' }}>Envío estándar (demo, $3.00)</option>
                    <option value="express" {{ old('delivery_method') === 'express' ? 'selected' : '' }}>Envío express (demo, $3.00)</option>
                </select>
                @error('delivery_method') <p class="mt-1 text-sm text-red-700">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="delivery_notes" class="block text-sm mb-1">Notas de entrega <span class="text-zyro-muted">(opcional)</span></label>
                <textarea id="delivery_notes" name="delivery_notes" rows="3" class="w-full border border-zyro-border px-3 py-2">{{ old('delivery_notes') }}</textarea>
                @error('delivery_notes') <p class="mt-1 text-sm text-red-700">{{ $message }}</p> @enderror
            </div>

            <button type="submit" class="w-full bg-zyro text-white py-3 uppercase tracking-wide text-sm">
                Confirmar pedido
            </button>
        </form>

        {{-- Resumen del pedido --}}
        <div>
            <div class="border border-zyro-border bg-zyro-gray p-6 mb-4">
                <h2 class="font-serif text-xl mb-4">Tu pedido</h2>
                @foreach ($items as $item)
                    <div class="flex justify-between text-sm mb-2">
                        <span>{{ $item['name'] }}@if($item['size']) · {{ $item['size'] }}@endif ×{{ $item['quantity'] }}</span>
                        <span>${{ number_format($item['price'] * $item['quantity'], 2) }}</span>
                    </div>
                @endforeach
                <hr class="my-3 border-zyro-border">
                <div class="flex justify-between text-sm mb-1">
                    <span>Subtotal</span>
                    <span>${{ number_format($subtotal, 2) }}</span>
                </div>
                <div class="flex justify-between text-sm mb-1 text-zyro-muted">
                    <span>IVA 12 %</span>
                    <span>${{ number_format($subtotal * 0.12, 2) }}</span>
                </div>
                <div class="flex justify-between text-sm mb-1 text-zyro-muted">
                    <span>Envío (demo)</span>
                    <span>$3.00</span>
                </div>
                <hr class="my-3 border-zyro-border">
                <div class="flex justify-between font-medium">
                    <span>Total estimado</span>
                    <span>${{ number_format($subtotal + $subtotal * 0.12 + 3.00, 2) }}</span>
                </div>
                <p class="text-xs text-zyro-muted mt-2">El total definitivo se calcula en el servidor al confirmar.</p>
            </div>

            <p class="text-xs text-zyro-muted text-center">
                Confirmar el pedido no genera un pago. El administrador debe aprobarlo primero.
            </p>
        </div>
    </div>
</section>
@endsection
