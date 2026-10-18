@extends('layouts.store')

@section('title', $query ? 'Búsqueda: '.$query.' — ZYRO' : 'Colección de chompas — ZYRO')
@section('meta_description', 'Catálogo ZYRO de chompas. Busca por nombre o descripción.')

@section('content')
    <section class="border-b border-zyro-border bg-zyro-gray">
        <div class="mx-auto max-w-6xl px-4 py-16 md:py-24">
            <p class="uppercase tracking-[0.35em] text-xs mb-4">Colección</p>
            <h1 class="font-serif text-5xl md:text-6xl max-w-2xl leading-tight">Chompas de corte editorial.</h1>
            <p class="mt-4 max-w-xl text-zyro-muted">Diez piezas de demostración. Las fotografías oficiales de ZYRO no están incluidas; se muestran recursos locales provisionales.</p>
        </div>
    </section>

    <section class="mx-auto max-w-6xl px-4 py-12">
        @if ($query !== '')
            <p class="mb-8 text-sm text-zyro-muted">Resultados para “{{ $query }}”</p>
        @endif

        @if ($products->isEmpty())
            <p class="border border-zyro-border bg-zyro-gray px-6 py-16 text-center text-zyro-muted" role="status">
                No se encontraron productos que coincidan con la búsqueda.
            </p>
        @else
            <ul class="grid gap-8 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($products as $product)
                    <li>
                        <article class="group">
                            <a href="{{ route('catalog.show', $product) }}" class="block">
                                <div class="aspect-[3/4] bg-zyro-gray border border-zyro-border overflow-hidden">
                                    <img
                                        src="{{ $product->imageUrl() }}"
                                        alt="Recurso provisional de {{ $product->name }}"
                                        class="h-full w-full object-cover"
                                    >
                                </div>
                                <h2 class="mt-4 font-serif text-2xl">{{ $product->name }}</h2>
                            </a>
                            <p class="mt-1 text-sm">{{ $product->formattedPrice() }}</p>
                            <p class="mt-2 text-xs uppercase tracking-wide text-zyro-muted">
                                Tallas {{ implode(' / ', $product->availableSizes()) }}
                            </p>
                            <p class="mt-1 text-xs text-zyro-muted">
                                @if ($product->stock > 0)
                                    Stock: {{ $product->stock }}
                                @else
                                    Sin stock
                                @endif
                            </p>
                        </article>
                    </li>
                @endforeach
            </ul>
        @endif
    </section>
@endsection
