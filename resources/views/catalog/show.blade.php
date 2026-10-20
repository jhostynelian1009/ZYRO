@extends('layouts.store')

@section('title', $product->name.' — '.$product->lineName().' — ZYRO')
@section('meta_description', $product->description)

@section('content')
    {{-- Migas de Pan --}}
    <div style="background:var(--zyro-light); border-bottom:1px solid var(--zyro-border); padding-block:0.75rem;">
        <nav class="container" style="font-size:0.6875rem; font-weight:600; text-transform:uppercase; letter-spacing:0.1em; color:var(--zyro-muted); display:flex; align-items:center; gap:0.5rem;" aria-label="Migas de pan">
            <a href="{{ route('home') }}">Inicio</a>
            <span>/</span>
            <a href="{{ route('catalog.index') }}">Colección</a>
            <span>/</span>
            <a href="{{ route('catalog.index', ['line' => $product->hasTechnology() ? 'tech' : 'esencial']) }}">
                {{ $product->lineName() }}
            </a>
            <span>/</span>
            <span style="color:var(--zyro-black);">{{ $product->name }}</span>
        </nav>
    </div>

    {{-- Ficha Principal de Producto --}}
    <article class="container" style="padding-block: 3.5rem;">
        <style>
            .product-show-grid {
                display: grid;
                grid-template-columns: 1fr;
                gap: 3rem;
            }

            @media (min-width: 860px) {
                .product-show-grid {
                    grid-template-columns: 1.05fr 0.95fr;
                    gap: 4rem;
                }
            }

            .size-selector-pills {
                display: flex;
                gap: 0.5rem;
                margin-top: 0.5rem;
            }

            .size-pill-label {
                flex: 1;
                max-width: 72px;
                height: 44px;
                border: 1px solid var(--zyro-border);
                background: #FFFFFF;
                display: flex;
                align-items: center;
                justify-content: center;
                font-size: 0.8125rem;
                font-weight: 600;
                cursor: pointer;
                transition: all 0.2s;
            }

            .size-pill-label:hover {
                border-color: var(--zyro-black);
            }

            .size-pill-label:has(input:checked) {
                background: var(--zyro-black);
                color: #FFFFFF;
                border-color: var(--zyro-black);
            }
        </style>

        <div class="product-show-grid">
            {{-- Columna de Imagen Contenida --}}
            <div>
                <div style="background:var(--zyro-light); border:1px solid var(--zyro-border); aspect-ratio: 1 / 1.15; display:flex; align-items:center; justify-content:center; padding:2rem; position:relative; overflow:hidden;">
                    <img
                        src="{{ $product->imageUrl() }}"
                        alt="{{ $product->name }}"
                        style="width:100%; height:100%; object-fit:contain;"
                    >

                    <div style="position:absolute; top:1rem; left:1rem; display:flex; flex-direction:column; gap:0.4rem;">
                        @if ($product->hasTechnology())
                            <span class="badge-tech">CON TECNOLOGÍA</span>
                        @else
                            <span class="badge-esencial">ESENCIAL</span>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Columna de Información y Compra --}}
            <div style="display:flex; flex-direction:column;">
                <div style="border-bottom:1px solid var(--zyro-border); padding-bottom:1.5rem;">
                    <span style="font-size:0.6875rem; font-weight:700; letter-spacing:0.15em; text-transform:uppercase; color:{{ $product->hasTechnology() ? 'var(--zyro-tech-blue)' : 'var(--zyro-muted)' }}; display:block; margin-bottom:0.5rem;">
                        {{ $product->lineName() }}
                    </span>
                    <h1 class="font-serif" style="font-size: clamp(2.25rem, 4vw, 3rem); font-weight:600; line-height:1.15; color:var(--zyro-black); margin-bottom:0.75rem;">
                        {{ $product->name }}
                    </h1>
                    <div style="font-size:1.75rem; font-weight:700; color:var(--zyro-black);">
                        {{ $product->formattedPrice() }}
                    </div>
                </div>

                <div style="padding-block:1.5rem; border-bottom:1px solid var(--zyro-border); font-size:0.875rem; color:#3F3F46; line-height:1.7;">
                    <p style="margin-bottom:1.25rem;">{{ $product->description }}</p>

                    {{-- Especificaciones claras --}}
                    <div style="background:var(--zyro-light); border:1px solid var(--zyro-border); padding:1rem; font-size:0.75rem; display:flex; flex-direction:column; gap:0.5rem;">
                        <strong style="text-transform:uppercase; letter-spacing:0.1em; color:var(--zyro-black); margin-bottom:0.25rem;">Especificaciones</strong>
                        @if ($product->hasTechnology())
                            <div style="display:flex; justify-content:space-between;">
                                <span style="color:var(--zyro-muted);">Línea</span>
                                <strong style="color:var(--zyro-tech-blue);">Con Tecnología Activa ($370.00)</strong>
                            </div>
                            <div style="display:flex; justify-content:space-between;">
                                <span style="color:var(--zyro-muted);">Termorregulación</span>
                                <span>Micro-canales térmicos inductivos</span>
                            </div>
                            <div style="display:flex; justify-content:space-between;">
                                <span style="color:var(--zyro-muted);">Composición</span>
                                <span>75% Lana Merino, 20% Fibras Tech, 5% Elastano</span>
                            </div>
                        @else
                            <div style="display:flex; justify-content:space-between;">
                                <span style="color:var(--zyro-muted);">Línea</span>
                                <strong style="color:var(--zyro-black);">Línea Esencial ($80.00)</strong>
                            </div>
                            <div style="display:flex; justify-content:space-between;">
                                <span style="color:var(--zyro-muted);">Material</span>
                                <span>100% Algodón orgánico peinado de fibra larga</span>
                            </div>
                            <div style="display:flex; justify-content:space-between;">
                                <span style="color:var(--zyro-muted);">Acabado</span>
                                <span>Punto fino suave con remates reforzados</span>
                            </div>
                        @endif
                        <div style="display:flex; justify-content:space-between;">
                            <span style="color:var(--zyro-muted);">Stock en MySQL</span>
                            <span style="font-weight:600;">{{ $product->stock }} unidades disponibles</span>
                        </div>
                    </div>
                </div>

                {{-- Formulario de Selección de Talla y Carrito --}}
                <div style="padding-top:1.5rem;">
                    @if ($product->isPurchasable())
                        @auth
                            <form method="POST" action="{{ route('cart.store', $product->id) }}">
                                @csrf

                                {{-- Selector de Tallas Claro y Sin Entidades Mal Renderizadas --}}
                                <div style="margin-bottom:1.5rem;">
                                    <div style="display:flex; justify-content:space-between; font-size:0.75rem; font-weight:600; text-transform:uppercase; letter-spacing:0.08em; margin-bottom:0.5rem;">
                                        <span>Seleccionar Talla</span>
                                        <span style="color:var(--zyro-muted); font-weight:400;">Guía estándar</span>
                                    </div>

                                    <div class="size-selector-pills">
                                        @foreach ($product->availableSizes() as $index => $size)
                                            <label class="size-pill-label">
                                                <input type="radio" name="size" value="{{ $size }}" style="display:none;" {{ $index === 0 ? 'checked' : '' }}>
                                                <span>{{ $size }}</span>
                                            </label>
                                        @endforeach
                                    </div>
                                    @error('size') <p style="color:#DC2626; font-size:0.75rem; margin-top:0.35rem;">{{ $message }}</p> @enderror
                                </div>

                                {{-- Cantidad --}}
                                <div style="margin-bottom:1.5rem; display:flex; align-items:center; gap:1rem;">
                                    <div>
                                        <label for="quantity" style="display:block; font-size:0.6875rem; font-weight:600; text-transform:uppercase; letter-spacing:0.08em; margin-bottom:0.35rem;">Cantidad</label>
                                        <input
                                            id="quantity"
                                            name="quantity"
                                            type="number"
                                            value="1"
                                            min="1"
                                            max="{{ $product->stock }}"
                                            style="width:80px; height:42px; border:1px solid var(--zyro-border); text-align:center; font-size:0.875rem; font-weight:600;"
                                        >
                                    </div>
                                    <div style="font-size:0.75rem; color:var(--zyro-muted); padding-top:1rem;">
                                        Máximo {{ $product->stock }} unidades por pedido
                                    </div>
                                </div>

                                @error('cart')
                                    <p style="color:#DC2626; font-size:0.8125rem; margin-bottom:1rem; padding:0.5rem; background:#FEF2F2; border:1px solid #FCA5A5;">{{ $message }}</p>
                                @enderror

                                <button type="submit" class="btn-primary" style="width:100%; padding-block:1rem; font-size:0.8125rem;">
                                    Añadir al Carrito &bull; {{ $product->formattedPrice() }}
                                </button>
                            </form>
                            <a href="{{ route('catalog.customize', $product) }}" class="btn-secondary" style="width:100%;margin-top:.75rem;padding-block:1rem;text-align:center;display:block;font-size:.8125rem;">Personaliza tu chompa</a>
                        @else
                            <div style="background:var(--zyro-light); border:1px solid var(--zyro-border); padding:1.5rem; text-align:center;">
                                <p style="font-size:0.875rem; font-weight:600; margin-bottom:0.5rem; color:var(--zyro-black);">Inicia sesión para realizar tu pedido</p>
                                <p style="font-size:0.75rem; color:var(--zyro-muted); margin-bottom:1.25rem;">Los clientes registrados pueden confirmar pedidos y solicitar la aprobación administrativa.</p>
                                <div style="display:flex; justify-content:center; gap:0.75rem;">
                                    <a href="{{ route('login') }}" class="btn-primary" style="padding:0.65rem 1.5rem;">Iniciar Sesión</a>
                                    <a href="{{ route('register') }}" class="btn-secondary" style="padding:0.65rem 1.5rem;">Crear Cuenta</a>
                                </div>
                            </div>
                        @endauth
                    @else
                        <div style="background:#FEF2F2; border:1px solid #FCA5A5; color:#991B1B; padding:1rem; font-size:0.8125rem;">
                            Producto actualmente sin stock disponible.
                        </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- Modelos Relacionados --}}
        @if (isset($relatedProducts) && $relatedProducts->isNotEmpty())
            <div style="margin-top:5rem; padding-top:3rem; border-top:1px solid var(--zyro-border);">
                <div style="display:flex; justify-content:space-between; align-items:flex-end; margin-bottom:2rem;">
                    <div>
                        <span style="font-size:0.6875rem; font-weight:700; letter-spacing:0.15em; text-transform:uppercase; color:var(--zyro-muted);">Descubre Más</span>
                        <h3 class="font-serif" style="font-size:1.75rem; font-weight:600; color:var(--zyro-black);">Otros Modelos de {{ $product->lineName() }}</h3>
                    </div>
                    <a href="{{ route('catalog.index', ['line' => $product->hasTechnology() ? 'tech' : 'esencial']) }}" style="font-size:0.75rem; font-weight:600; text-transform:uppercase; letter-spacing:0.1em; text-decoration:underline;">Ver Todos</a>
                </div>

                <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap:1.5rem;">
                    @foreach ($relatedProducts as $rel)
                        <article class="product-card">
                            <div class="product-image-box">
                                <a href="{{ route('catalog.show', $rel) }}" style="display:flex; width:100%; height:100%; align-items:center; justify-content:center;">
                                    <img src="{{ $rel->imageUrl() }}" alt="{{ $rel->name }}">
                                </a>
                                <div class="card-badges">
                                    @if ($rel->hasTechnology())
                                        <span class="badge-tech">CON TECNOLOGÍA</span>
                                    @else
                                        <span class="badge-esencial">ESENCIAL</span>
                                    @endif
                                </div>
                            </div>
                            <div class="product-card-body">
                                <div class="product-card-info">
                                    <h4 class="product-name" style="font-size:1.15rem;">
                                        <a href="{{ route('catalog.show', $rel) }}">{{ $rel->name }}</a>
                                    </h4>
                                </div>
                                <div class="product-card-footer">
                                    <div class="product-price-row">
                                        <span class="product-price">{{ $rel->formattedPrice() }}</span>
                                    </div>
                                    <div class="product-actions-row">
                                        <a href="{{ route('catalog.show', $rel) }}" class="btn-card-details">
                                            Detalles
                                        </a>
                                        <a href="{{ route('catalog.customize', $rel) }}" class="btn-card-customize">
                                            Personalizar
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </article>
                    @endforeach
                </div>
            </div>
        @endif
    </article>
@endsection
