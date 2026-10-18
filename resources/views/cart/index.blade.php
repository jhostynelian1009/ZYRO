@extends('layouts.store')

@section('title', 'Carrito — ZYRO')

@section('content')
<section class="mx-auto max-w-6xl px-4 py-12">
    <h1 class="font-serif text-4xl mb-8">Carrito</h1>

    @error('cart')
        <p class="mb-6 border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">{{ $message }}</p>
    @enderror

    @if (empty($items))
        <div class="border border-zyro-border bg-zyro-gray px-6 py-16 text-center text-zyro-muted">
            <p class="mb-4">El carrito está vacío.</p>
            <a href="{{ route('catalog.index') }}" class="inline-block bg-zyro text-white px-6 py-2 text-sm uppercase tracking-wide">Ver colección</a>
        </div>
    @else
        <div class="grid gap-8 lg:grid-cols-3">
            {{-- Lista de ítems --}}
            <div class="lg:col-span-2 space-y-4">
                @foreach ($items as $key => $item)
                    <div class="flex gap-4 border border-zyro-border bg-white p-4">
                        <div class="w-20 flex-shrink-0 bg-zyro-gray border border-zyro-border">
                            <img
                                src="{{ asset($item['image_path'] ?? 'images/products/placeholder.svg') }}"
                                alt="{{ $item['name'] }}"
                                class="h-full w-full object-cover aspect-[3/4]"
                            >
                        </div>
                        <div class="flex-1">
                            <p class="font-serif text-lg">{{ $item['name'] }}</p>
                            @if ($item['size'])
                                <p class="text-xs uppercase tracking-wide text-zyro-muted mt-1">Talla: {{ $item['size'] }}</p>
                            @endif
                            <p class="text-sm mt-1">${{ number_format($item['price'], 2) }}</p>

                            <div class="flex items-center gap-4 mt-3">
                                {{-- Actualizar cantidad --}}
                                <form method="POST" action="{{ route('cart.update', $item['product_id']) }}" class="flex items-center gap-2">
                                    @csrf
                                    @method('PATCH')
                                    <input type="hidden" name="size" value="{{ $item['size'] }}">
                                    <label for="qty-{{ $key }}" class="sr-only">Cantidad</label>
                                    <input
                                        id="qty-{{ $key }}"
                                        type="number"
                                        name="quantity"
                                        value="{{ $item['quantity'] }}"
                                        min="1"
                                        max="{{ $item['stock'] }}"
                                        class="w-16 border border-zyro-border px-2 py-1 text-sm text-center"
                                    >
                                    <button type="submit" class="text-xs uppercase tracking-wide underline hover:opacity-60">Actualizar</button>
                                </form>

                                {{-- Eliminar --}}
                                <form method="POST" action="{{ route('cart.destroy', $item['product_id']) }}?size={{ $item['size'] }}">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-xs uppercase tracking-wide text-red-600 hover:opacity-60">Eliminar</button>
                                </form>
                            </div>
                        </div>
                        <div class="text-right">
                            <p class="text-sm font-medium">${{ number_format($item['price'] * $item['quantity'], 2) }}</p>
                            <p class="text-xs text-zyro-muted mt-1">×{{ $item['quantity'] }}</p>
                        </div>
                    </div>
                @endforeach
            </div>

            {{-- Resumen --}}
            <div class="border border-zyro-border bg-zyro-gray p-6 h-fit">
                <h2 class="font-serif text-xl mb-4">Resumen</h2>
                <div class="flex justify-between text-sm mb-2">
                    <span>Subtotal (estimado)</span>
                    <span>${{ number_format($subtotal, 2) }}</span>
                </div>
                <p class="text-xs text-zyro-muted mb-6">El total final (IVA + envío) se calculará al confirmar el pedido.</p>
                <a href="{{ route('checkout.create') }}" class="block w-full bg-zyro text-white text-center py-3 text-sm uppercase tracking-wide">
                    Ir al checkout
                </a>
                <a href="{{ route('catalog.index') }}" class="block w-full text-center border border-zyro-border py-3 text-sm uppercase tracking-wide mt-2 hover:bg-zyro hover:text-white transition-colors">
                    Seguir comprando
                </a>
            </div>
        </div>
    @endif
</section>
@endsection
