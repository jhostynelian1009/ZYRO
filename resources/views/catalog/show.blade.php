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
            <p class="mt-8 text-sm text-zyro-muted">El carrito y el checkout se habilitarán en la siguiente etapa.</p>
            <a href="{{ route('catalog.index') }}" class="mt-8 inline-block border border-zyro px-5 py-2 text-sm uppercase tracking-wide">
                Volver al catálogo
            </a>
        </div>
    </article>
@endsection
