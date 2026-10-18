@extends('layouts.store')

@section('title', $product->name.' — ZYRO')
@section('meta_description', $product->description)

@section('content')
    <article class="mx-auto max-w-6xl px-4 py-12 grid gap-10 md:grid-cols-2">
        <div class="aspect-[3/4] bg-zyro-gray border border-zyro-border">
            <img
                src="{{ $product->imageUrl() }}"
                alt="Recurso provisional de {{ $product->name }}"
                class="h-full w-full object-cover"
            >
        </div>
        <div>
            <p class="uppercase tracking-[0.3em] text-xs mb-3">Chompas</p>
            <h1 class="font-serif text-5xl">{{ $product->name }}</h1>
            <p class="mt-4 text-lg">{{ $product->formattedPrice() }}</p>
            <p class="mt-6 text-zyro-muted leading-relaxed">{{ $product->description }}</p>
            <p class="mt-6 text-sm uppercase tracking-wide">Tallas {{ implode(' / ', $product->availableSizes()) }}</p>
            <p class="mt-2 text-sm text-zyro-muted">
                @if ($product->isPurchasable())
                    Stock disponible: {{ $product->stock }}
                @else
                    Producto no disponible para compra.
                @endif
            </p>
            @if ($product->isPurchasable())
                @auth
                    <form method="POST" action="{{ route('cart.store', $product->id) }}" class="mt-8 space-y-4">
                        @csrf
                        @if (count($product->availableSizes()) > 0)
                            <div>
                                <label for="size" class="block text-sm mb-1 uppercase tracking-wide">Talla</label>
                                <select id="size" name="size" class="border border-zyro-border px-3 py-2 bg-white text-sm">
                                    <option value="">— Selecciona una talla —</option>
                                    @foreach ($product->availableSizes() as $sz)
                                        <option value="{{ $sz }}">{{ $sz }}</option>
                                    @endforeach
                                </select>
                            </div>
                        @endif
                        <div>
                            <label for="quantity" class="block text-sm mb-1 uppercase tracking-wide">Cantidad</label>
                            <input id="quantity" name="quantity" type="number" value="1" min="1" max="{{ $product->stock }}"
                                   class="w-24 border border-zyro-border px-3 py-2 text-sm text-center">
                        </div>
                        @error('cart') <p class="text-sm text-red-700">{{ $message }}</p> @enderror
                        <button type="submit" class="w-full bg-zyro text-white py-3 uppercase tracking-wide text-sm hover:opacity-80 transition-opacity">
                            Agregar al carrito
                        </button>
                    </form>
                @else
                    <div class="mt-8">
                        <a href="{{ route('login') }}" class="block w-full bg-zyro text-white text-center py-3 uppercase tracking-wide text-sm">
                            Inicia sesión para comprar
                        </a>
                    </div>
                @endauth
            @else
                <p class="mt-8 text-sm text-zyro-muted border border-zyro-border px-4 py-3">Producto no disponible para compra.</p>
            @endif
        </div>
    </article>
@endsection
