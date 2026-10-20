@extends('layouts.store')

@section('title', 'Colección de Chompas — ZYRO')
@section('meta_description', 'Descubre las 8 chompas exclusivas de ZYRO: 4 modelos con tecnología de termorregulación activa ($370.00) y 4 modelos esenciales en algodón orgánico ($80.00).')

@section('content')
    {{-- ─── HERO EDITORIAL COMPACTO Y ELEGANTE ─── --}}
    <section style="background: var(--zyro-light); border-bottom: 1px solid var(--zyro-border); padding-block: 3.5rem;">
        <div class="container" style="display:grid; grid-template-columns: 1fr; gap: 2.5rem; align-items:center;">
            <style>
                @media (min-width: 860px) {
                    .hero-grid-inner {
                        display: grid !important;
                        grid-template-columns: 1.15fr 0.85fr !important;
                        gap: 3.5rem !important;
                        align-items: center !important;
                    }
                }
            </style>
            <div class="hero-grid-inner" style="display:flex; flex-direction:column; gap:2rem;">
                <div>
                    <span style="font-size:0.6875rem; font-weight:700; letter-spacing:0.2em; text-transform:uppercase; color:var(--zyro-tech-blue); display:inline-block; margin-bottom:0.75rem;">
                        Colección Cápsula 2026
                    </span>
                    <h1 class="font-serif" style="font-size: clamp(2.25rem, 5vw, 3.5rem); font-weight:600; line-height:1.1; margin-bottom:1rem; color:var(--zyro-black);">
                        Moda Urbana.<br>Rendimiento Térmico.
                    </h1>
                    <p style="font-size:0.9375rem; color:var(--zyro-muted); line-height:1.6; max-width:520px; margin-bottom:1.75rem;">
                        Ocho siluetas editoriales con arquitectura textil de vanguardia: cuatro piezas con termorregulación activa ($370.00) y cuatro modelos esenciales en fibras puras de algodón orgánico ($80.00).
                    </p>
                    <div style="display:flex; flex-wrap:wrap; gap:0.75rem;">
                        <a href="#catalogo" class="btn-primary">Ver Colección</a>
                        <a href="{{ route('catalog.index', ['line' => 'tech']) }}#catalogo" class="btn-secondary">Línea Tech ($370.00)</a>
                    </div>
                </div>

                {{-- Fotografía real de la prenda insignia en marco editorial --}}
                <div style="background:var(--zyro-white); border:1px solid var(--zyro-border); padding:1.25rem; position:relative; box-shadow:0 4px 20px rgba(0,0,0,0.04);">
                    <div style="aspect-ratio: 1 / 1.1; background:#F2F2F4; display:flex; align-items:center; justify-content:center; padding:1.5rem; overflow:hidden;">
                        <img
                            src="{{ asset('images/products/lunar-tech-source.png') }}"
                            alt="Edición Insignia — Colección Cápsula"
                            style="width:100%; height:100%; object-fit:contain;"
                        >
                    </div>
                    <div style="display:flex; align-items:center; justify-content:space-between; margin-top:0.875rem; font-size:0.75rem;">
                        <div>
                            <strong style="font-family:'Cormorant Garamond', Georgia, serif; font-size:1.15rem; color:var(--zyro-black);">Edición Insignia</strong>
                            <span style="display:block; font-size:0.6875rem; color:var(--zyro-muted); text-transform:uppercase; letter-spacing:0.08em;">Línea Tech &bull; Con Tecnología</span>
                        </div>
                        <span style="font-weight:700; font-size:1rem; color:var(--zyro-black);">$370.00</span>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- ─── SECCIÓN DE CATÁLOGO UNIFICADA (TÍTULO + DESCRIPCIÓN + FILTROS + GRID) ─── --}}
    <section id="catalogo" style="padding-block: 4rem;">
        <div class="container">
            {{-- Encabezado y Filtros integrados en la misma sección --}}
            <div class="catalog-section-header">
                <div class="catalog-title-wrap">
                    <h2>Colección de Chompas</h2>
                    <p>Ocho modelos exclusivos. Precios oficiales consultados directamente desde MySQL.</p>
                </div>

                {{-- Filtros funcionales: Todos, Con tecnología, Sin tecnología --}}
                <div class="filter-buttons" role="group" aria-label="Filtro de productos">
                    <a
                        href="{{ route('catalog.index', array_merge(request()->except('line'), ['line' => 'todos'])) }}#catalogo"
                        class="filter-btn {{ ($filter === 'todos' || empty($filter)) ? 'active' : '' }}"
                    >
                        Todos ({{ $counts['todos'] ?? 8 }})
                    </a>
                    <a
                        href="{{ route('catalog.index', array_merge(request()->except('line'), ['line' => 'tech'])) }}#catalogo"
                        class="filter-btn filter-tech {{ in_array($filter, ['tech', 'con-tecnologia']) ? 'active' : '' }}"
                    >
                        Con Tecnología ({{ $counts['tech'] ?? 4 }}) &bull; $370.00
                    </a>
                    <a
                        href="{{ route('catalog.index', array_merge(request()->except('line'), ['line' => 'esencial'])) }}#catalogo"
                        class="filter-btn {{ in_array($filter, ['esencial', 'sin-tecnologia']) ? 'active' : '' }}"
                    >
                        Sin Tecnología ({{ $counts['esencial'] ?? 4 }}) &bull; $80.00
                    </a>
                </div>
            </div>

            {{-- Aviso de búsqueda si aplica --}}
            @if ($query !== '')
                <div style="background:var(--zyro-light); border:1px solid var(--zyro-border); padding:0.75rem 1rem; margin-bottom:2rem; font-size:0.8125rem; display:flex; align-items:center; justify-content:space-between;">
                    <span>Resultados para: <strong>&ldquo;{{ $query }}&rdquo;</strong> ({{ count($products) }} encontrados)</span>
                    <a href="{{ route('catalog.index') }}" style="font-size:0.75rem; text-transform:uppercase; letter-spacing:0.08em; text-decoration:underline;">Restablecer</a>
                </div>
            @endif

            {{-- Grid de 4 Columnas Consistente --}}
            @if ($products->isEmpty())
                <div style="border:1px solid var(--zyro-border); background:var(--zyro-light); padding:4rem 2rem; text-align:center; color:var(--zyro-muted);">
                    <h3 class="font-serif" style="font-size:1.75rem; color:var(--zyro-black); margin-bottom:0.5rem;">No se encontraron productos que coincidan con la búsqueda</h3>
                    <p style="font-size:0.875rem; margin-bottom:1.5rem;">Prueba con otros términos o explora las categorías de nuestra colección.</p>
                    <a href="{{ route('catalog.index') }}" class="btn-primary">Ver Todos los Modelos</a>
                </div>
            @else
                <ul class="products-grid">
                    @foreach ($products as $product)
                        <li>
                            <article class="product-card">
                                {{-- Contenedor de Imagen Contenida (Sin recortes forzados) --}}
                                <div class="product-image-box">
                                    <a href="{{ route('catalog.show', $product) }}" style="display:flex; width:100%; height:100%; align-items:center; justify-content:center;" tabindex="-1">
                                        <img
                                            src="{{ $product->imageUrl() }}"
                                            alt="{{ $product->name }}"
                                            loading="lazy"
                                        >
                                    </a>

                                    {{-- Distintivo de Línea --}}
                                    <div class="card-badges">
                                        @if ($product->hasTechnology())
                                            <span class="badge-tech">CON TECNOLOGÍA</span>
                                        @else
                                            <span class="badge-esencial">ESENCIAL</span>
                                        @endif
                                    </div>
                                </div>

                                {{-- Cuerpo de la Tarjeta --}}
                                <div class="product-card-body">
                                    <div class="product-card-info">
                                        <div class="product-meta-line">{{ $product->lineName() }}</div>
                                        <h3 class="product-name">
                                            <a href="{{ route('catalog.show', $product) }}">
                                                {{ $product->name }}
                                            </a>
                                        </h3>
                                    </div>

                                    {{-- Footer de Tarjeta: Precio en fila independiente y botones abajo --}}
                                    <div class="product-card-footer">
                                        <div class="product-price-row">
                                            <span class="product-price">{{ $product->formattedPrice() }}</span>
                                        </div>

                                        <div class="product-actions-row">
                                            <a href="{{ route('catalog.show', $product) }}" class="btn-card-details">
                                                Detalles
                                            </a>
                                            <a href="{{ route('catalog.customize', $product) }}" class="btn-card-customize">
                                                Personalizar
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </article>
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>
    </section>

    {{-- ─── SECCIÓN TECNOLÓGICA CON zyro-technology-explainer.png ─── --}}
    <section id="tecnologia" style="background:var(--zyro-black); color:var(--zyro-white); padding-block: 5rem; border-top:1px solid #27272A;">
        <div class="container">
            <style>
                @media (min-width: 860px) {
                    .tech-section-grid {
                        display: grid !important;
                        grid-template-columns: 1fr 1fr !important;
                        gap: 4rem !important;
                        align-items: center !important;
                    }
                }
            </style>
            <div class="tech-section-grid" style="display:flex; flex-direction:column; gap:2.5rem;">
                <div>
                    <span style="font-size:0.6875rem; font-weight:700; letter-spacing:0.2em; text-transform:uppercase; color:var(--zyro-tech-blue); display:block; margin-bottom:0.75rem;">
                        Ingeniería Textil
                    </span>
                    <h2 class="font-serif" style="font-size: clamp(2rem, 4vw, 3rem); font-weight:600; line-height:1.15; margin-bottom:1.25rem;">
                        Termorregulación Activa &bull; Línea Tech
                    </h2>
                    <p style="font-size:0.875rem; color:#A1A1AA; line-height:1.7; margin-bottom:1.5rem;">
                        Los cuatro modelos de la Línea Tech ($370.00) integran filamentos conductores micro-aislados que modulan pasivamente la retención calórica frente a fluctuaciones de temperatura corporal y ambiente.
                    </p>

                    <div style="display:flex; flex-direction:column; gap:1rem; border-top:1px solid #27272A; padding-top:1.5rem; margin-bottom:2rem; font-size:0.8125rem;">
                        <div style="display:flex; gap:0.75rem;">
                            <span style="color:var(--zyro-tech-blue); font-weight:700;">&bull;</span>
                            <div>
                                <strong style="color:#FFFFFF; display:block;">Micro-canales térmicos</strong>
                                <span style="color:#A1A1AA;">Distribución de flujo de calor en pecho y torso.</span>
                            </div>
                        </div>
                        <div style="display:flex; gap:0.75rem;">
                            <span style="color:var(--zyro-tech-blue); font-weight:700;">&bull;</span>
                            <div>
                                <strong style="color:#FFFFFF; display:block;">Tejido elástico bio-reactivo</strong>
                                <span style="color:#A1A1AA;">Mayor permeabilidad al aire durante el movimiento activo.</span>
                            </div>
                        </div>
                    </div>

                    <a href="{{ route('catalog.index', ['line' => 'tech']) }}#catalogo" class="btn-primary" style="background:var(--zyro-white); color:var(--zyro-black); border-color:var(--zyro-white);">
                        Ver Modelos Tech ($370.00)
                    </a>
                </div>

                {{-- Esquema Tecnológico --}}
                <div style="background:#18181B; border:1px solid #27272A; padding:1rem;">
                    <div style="aspect-ratio: 4 / 3; display:flex; align-items:center; justify-content:center; background:#000000; overflow:hidden;">
                        <img
                            src="{{ asset('images/technology/zyro-technology-explainer.png') }}"
                            alt="Esquema técnico de termorregulación ZYRO"
                            style="width:100%; height:100%; object-fit:contain;"
                            loading="lazy"
                        >
                    </div>
                    <span style="display:block; text-align:center; font-size:0.6875rem; color:#71717A; margin-top:0.75rem; text-transform:uppercase; letter-spacing:0.08em;">
                        Esquema técnico de micro-malla térmica
                    </span>
                </div>
            </div>
        </div>
    </section>
@endsection
