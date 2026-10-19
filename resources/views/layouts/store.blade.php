<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'ZYRO — Moda Urbana & Alta Tecnología')</title>
    <meta name="description" content="@yield('meta_description', 'ZYRO. Tienda de moda urbana con 8 diseños exclusivos de chompas: Línea Tech ($299.99) y Línea Esencial ($79.99).')">
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=cormorant-garamond:500,600,700|inter:400,500,600,700" rel="stylesheet" />

    <style>
        :root {
            --zyro-black: #0F0F11;
            --zyro-white: #FFFFFF;
            --zyro-light: #F7F7F8;
            --zyro-border: #E8E8EC;
            --zyro-muted: #71717A;
            --zyro-tech-blue: #0055FF;
            --zyro-tech-blue-hover: #0044CC;
        }

        * { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: 'Inter', system-ui, -apple-system, sans-serif;
            color: var(--zyro-black);
            background: var(--zyro-white);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            line-height: 1.5;
            -webkit-font-smoothing: antialiased;
        }

        a { color: inherit; text-decoration: none; transition: opacity 0.2s, color 0.2s; }
        img { display: block; max-width: 100%; }

        .font-serif { font-family: 'Cormorant Garamond', Georgia, serif; }

        .container {
            width: 100%;
            max-width: 1240px;
            margin-inline: auto;
            padding-inline: 1.25rem;
        }

        /* ─── CABECERA COMPACTA Y MODERNA ─── */
        .site-header {
            position: sticky;
            top: 0;
            z-index: 50;
            background: rgba(255, 255, 255, 0.98);
            backdrop-filter: blur(8px);
            border-bottom: 1px solid var(--zyro-border);
            height: 68px;
            display: flex;
            align-items: center;
        }

        .header-inner {
            display: grid;
            grid-template-columns: auto 1fr auto;
            align-items: center;
            gap: 1.5rem;
            width: 100%;
        }

        .brand-logo {
            display: flex;
            align-items: center;
            gap: 0.65rem;
        }

        .brand-emblem {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 32px;
            height: 32px;
            background: var(--zyro-black);
            color: var(--zyro-white);
            font-family: 'Inter', sans-serif;
            font-weight: 700;
            font-size: 0.875rem;
            letter-spacing: -0.05em;
        }

        .brand-name {
            font-family: 'Cormorant Garamond', Georgia, serif;
            font-size: 1.75rem;
            font-weight: 700;
            letter-spacing: 0.28em;
            line-height: 1;
            color: var(--zyro-black);
        }

        .nav-center {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 2rem;
            font-size: 0.75rem;
            font-weight: 600;
            letter-spacing: 0.12em;
            text-transform: uppercase;
        }

        .nav-center a {
            position: relative;
            padding-block: 0.25rem;
        }

        .nav-center a:hover {
            color: var(--zyro-tech-blue);
        }

        .header-actions {
            display: flex;
            align-items: center;
            justify-content: flex-end;
            gap: 1.25rem;
            font-size: 0.8125rem;
        }

        .search-form-compact {
            display: flex;
            align-items: center;
            border: 1px solid var(--zyro-border);
            border-radius: 2px;
            background: var(--zyro-light);
            padding: 0.25rem 0.5rem;
        }

        .search-form-compact input {
            border: none;
            background: transparent;
            font-size: 0.75rem;
            outline: none;
            width: 140px;
            color: var(--zyro-black);
        }

        .search-form-compact button {
            background: transparent;
            border: none;
            cursor: pointer;
            font-size: 0.75rem;
            color: var(--zyro-muted);
            padding-inline: 0.25rem;
        }

        .cart-pill {
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            font-size: 0.75rem;
            font-weight: 600;
            letter-spacing: 0.08em;
            text-transform: uppercase;
        }

        .cart-count {
            background: var(--zyro-black);
            color: var(--zyro-white);
            font-size: 0.6875rem;
            width: 18px;
            height: 18px;
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
        }

        /* ─── BOTONES Y BADGES ─── */
        .btn-primary {
            display: inline-block;
            background: var(--zyro-black);
            color: var(--zyro-white);
            font-size: 0.75rem;
            font-weight: 600;
            letter-spacing: 0.15em;
            text-transform: uppercase;
            padding: 0.875rem 1.75rem;
            border: 1px solid var(--zyro-black);
            cursor: pointer;
            text-align: center;
            transition: background 0.2s, color 0.2s;
        }

        .btn-primary:hover {
            background: #27272A;
            border-color: #27272A;
        }

        .btn-secondary {
            display: inline-block;
            background: transparent;
            color: var(--zyro-black);
            font-size: 0.75rem;
            font-weight: 600;
            letter-spacing: 0.15em;
            text-transform: uppercase;
            padding: 0.875rem 1.75rem;
            border: 1px solid var(--zyro-black);
            cursor: pointer;
            text-align: center;
            transition: background 0.2s, color 0.2s;
        }

        .btn-secondary:hover {
            background: var(--zyro-black);
            color: var(--zyro-white);
        }

        .badge-tech {
            background: var(--zyro-tech-blue);
            color: var(--zyro-white);
            font-size: 0.625rem;
            font-weight: 700;
            letter-spacing: 0.12em;
            text-transform: uppercase;
            padding: 0.25rem 0.5rem;
            display: inline-block;
        }

        .badge-esencial {
            background: var(--zyro-black);
            color: var(--zyro-white);
            font-size: 0.625rem;
            font-weight: 600;
            letter-spacing: 0.12em;
            text-transform: uppercase;
            padding: 0.25rem 0.5rem;
            display: inline-block;
        }

        .badge-ref {
            background: #E4E4E7;
            color: #52525B;
            font-size: 0.5625rem;
            font-weight: 600;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            padding: 0.2rem 0.45rem;
            display: inline-block;
        }

        /* ─── FILTROS Y HEADER CATÁLOGO ─── */
        .catalog-section-header {
            display: flex;
            flex-direction: column;
            gap: 1.25rem;
            margin-bottom: 2rem;
            padding-bottom: 1.5rem;
            border-bottom: 1px solid var(--zyro-border);
        }

        @media (min-width: 768px) {
            .catalog-section-header {
                flex-direction: row;
                align-items: flex-end;
                justify-content: space-between;
            }
        }

        .catalog-title-wrap h2 {
            font-family: 'Cormorant Garamond', Georgia, serif;
            font-size: 2.25rem;
            font-weight: 600;
            line-height: 1.15;
            color: var(--zyro-black);
        }

        .catalog-title-wrap p {
            font-size: 0.8125rem;
            color: var(--zyro-muted);
            margin-top: 0.25rem;
        }

        .filter-buttons {
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
        }

        .filter-btn {
            font-size: 0.6875rem;
            font-weight: 600;
            letter-spacing: 0.1em;
            text-transform: uppercase;
            padding: 0.5rem 1rem;
            border: 1px solid var(--zyro-border);
            background: var(--zyro-white);
            color: var(--zyro-muted);
            transition: all 0.2s;
        }

        .filter-btn:hover {
            border-color: var(--zyro-black);
            color: var(--zyro-black);
        }

        .filter-btn.active {
            background: var(--zyro-black);
            color: var(--zyro-white);
            border-color: var(--zyro-black);
        }

        .filter-btn.active.filter-tech {
            background: var(--zyro-tech-blue);
            border-color: var(--zyro-tech-blue);
        }

        /* ─── GRID DE PRODUCTOS — 4 COLUMNAS ─── */
        .products-grid {
            display: grid;
            grid-template-columns: repeat(1, 1fr);
            gap: 1.5rem;
            list-style: none;
        }

        @media (min-width: 560px) {
            .products-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (min-width: 992px) {
            .products-grid {
                grid-template-columns: repeat(4, 1fr);
            }
        }

        .product-card {
            background: var(--zyro-white);
            border: 1px solid var(--zyro-border);
            display: flex;
            flex-direction: column;
            transition: transform 0.2s ease, box-shadow 0.2s ease, border-color 0.2s ease;
        }

        .product-card:hover {
            border-color: var(--zyro-black);
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.06);
            transform: translateY(-2px);
        }

        .product-image-box {
            position: relative;
            width: 100%;
            aspect-ratio: 1 / 1.15;
            background: var(--zyro-light);
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1.25rem;
            overflow: hidden;
        }

        .product-image-box img {
            width: 100%;
            height: 100%;
            object-fit: contain;
            transition: transform 0.3s ease;
        }

        .product-card:hover .product-image-box img {
            transform: scale(1.04);
        }

        .card-badges {
            position: absolute;
            top: 0.75rem;
            left: 0.75rem;
            display: flex;
            flex-direction: column;
            gap: 0.35rem;
            z-index: 2;
        }

        .product-card-body {
            padding: 1.25rem;
            display: flex;
            flex-direction: column;
            flex: 1;
            justify-content: space-between;
        }

        .product-meta-line {
            font-size: 0.6875rem;
            font-weight: 600;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            color: var(--zyro-muted);
            margin-bottom: 0.35rem;
        }

        .product-name {
            font-family: 'Cormorant Garamond', Georgia, serif;
            font-size: 1.35rem;
            font-weight: 600;
            color: var(--zyro-black);
            line-height: 1.2;
            margin-bottom: 0.5rem;
        }

        .product-price-row {
            display: flex;
            align-items: baseline;
            justify-content: space-between;
            margin-top: 0.75rem;
            padding-top: 0.75rem;
            border-top: 1px solid var(--zyro-light);
        }

        .product-price {
            font-size: 1.125rem;
            font-weight: 700;
            color: var(--zyro-black);
        }

        .btn-card-action {
            display: inline-block;
            font-size: 0.6875rem;
            font-weight: 600;
            letter-spacing: 0.1em;
            text-transform: uppercase;
            padding: 0.45rem 0.85rem;
            background: var(--zyro-black);
            color: var(--zyro-white);
            border: 1px solid var(--zyro-black);
            transition: background 0.2s, color 0.2s;
        }

        .btn-card-action:hover {
            background: var(--zyro-tech-blue);
            border-color: var(--zyro-tech-blue);
        }

        /* ─── FOOTER ─── */
        .site-footer {
            margin-top: auto;
            background: #111113;
            color: #D4D4D8;
            padding-block: 3.5rem 1.5rem;
            font-size: 0.8125rem;
        }

        .footer-grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: 2.5rem;
        }

        @media (min-width: 768px) {
            .footer-grid {
                grid-template-columns: 1.5fr 1fr 1fr 1fr;
            }
        }

        .footer-col h4 {
            color: var(--zyro-white);
            font-size: 0.6875rem;
            font-weight: 700;
            letter-spacing: 0.15em;
            text-transform: uppercase;
            margin-bottom: 1rem;
        }

        .footer-links {
            list-style: none;
            display: flex;
            flex-direction: column;
            gap: 0.6rem;
        }

        .footer-links a:hover {
            color: var(--zyro-white);
        }

        .footer-bottom {
            margin-top: 3rem;
            padding-top: 1.5rem;
            border-top: 1px solid #27272A;
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
            font-size: 0.75rem;
            color: #71717A;
            text-align: center;
        }

        @media (min-width: 768px) {
            .footer-bottom {
                flex-direction: row;
                justify-content: space-between;
                text-align: left;
            }
        }

        /* Responsive Nav */
        @media (max-width: 880px) {
            .nav-center { display: none; }
            .header-inner { grid-template-columns: auto 1fr; }
        }

        /* ─── ESTRUCTURA PRINCIPAL Y STICKY FOOTER ─── */
        .site-main {
            flex: 1;
            display: flex;
            flex-direction: column;
            width: 100%;
        }

        /* ─── PÁGINAS DE AUTENTICACIÓN (LOGIN & REGISTRO) ─── */
        .auth-page-wrapper {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 3.5rem 1.25rem;
            background: var(--zyro-light);
            width: 100%;
        }

        .auth-card {
            width: 100%;
            max-width: 420px;
            background: var(--zyro-white);
            border: 1px solid var(--zyro-border);
            box-shadow: 0 4px 24px rgba(0, 0, 0, 0.04);
            padding: 2.5rem 2.25rem;
            border-radius: 2px;
        }

        .auth-header {
            margin-bottom: 2rem;
            text-align: center;
        }

        .auth-kicker {
            display: inline-block;
            font-size: 0.6875rem;
            font-weight: 700;
            letter-spacing: 0.2em;
            text-transform: uppercase;
            color: var(--zyro-muted);
            margin-bottom: 0.5rem;
        }

        .auth-title {
            font-family: 'Cormorant Garamond', Georgia, serif;
            font-size: 2.25rem;
            font-weight: 600;
            line-height: 1.15;
            color: var(--zyro-black);
            margin-bottom: 0.5rem;
        }

        .auth-subtitle {
            font-size: 0.8125rem;
            color: var(--zyro-muted);
            line-height: 1.5;
        }

        .auth-form {
            display: flex;
            flex-direction: column;
            gap: 1.25rem;
        }

        .form-group {
            display: flex;
            flex-direction: column;
            gap: 0.375rem;
        }

        .form-label {
            font-size: 0.6875rem;
            font-weight: 700;
            letter-spacing: 0.1em;
            text-transform: uppercase;
            color: var(--zyro-black);
        }

        .form-input {
            width: 100%;
            padding: 0.75rem 0.875rem;
            font-family: inherit;
            font-size: 0.875rem;
            color: var(--zyro-black);
            background: var(--zyro-white);
            border: 1px solid var(--zyro-border);
            border-radius: 2px;
            transition: border-color 0.2s, box-shadow 0.2s;
            outline: none;
            box-sizing: border-box;
        }

        .form-input:focus {
            border-color: var(--zyro-black);
            box-shadow: 0 0 0 1px var(--zyro-black);
        }

        .form-input.is-invalid {
            border-color: #DC2626;
            background: #FEF2F2;
        }

        .form-input.is-invalid:focus {
            box-shadow: 0 0 0 1px #DC2626;
        }

        .form-error {
            font-size: 0.75rem;
            color: #DC2626;
            margin-top: 0.25rem;
            line-height: 1.4;
            display: flex;
            align-items: center;
            gap: 0.25rem;
        }

        .form-checkbox-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            font-size: 0.8125rem;
            margin-top: -0.25rem;
        }

        .form-checkbox-label {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            color: var(--zyro-muted);
            cursor: pointer;
            font-size: 0.8125rem;
            user-select: none;
        }

        .form-checkbox-label input[type="checkbox"] {
            width: 16px;
            height: 16px;
            accent-color: var(--zyro-black);
            cursor: pointer;
        }

        .btn-auth-submit {
            width: 100%;
            background: var(--zyro-black);
            color: var(--zyro-white);
            border: 1px solid var(--zyro-black);
            padding: 0.875rem 1.5rem;
            font-size: 0.75rem;
            font-weight: 700;
            letter-spacing: 0.15em;
            text-transform: uppercase;
            cursor: pointer;
            text-align: center;
            transition: background 0.2s, border-color 0.2s, transform 0.1s;
            margin-top: 0.5rem;
            border-radius: 2px;
        }

        .btn-auth-submit:hover {
            background: #27272A;
            border-color: #27272A;
        }

        .btn-auth-submit:active {
            transform: scale(0.99);
        }

        .btn-auth-submit:focus-visible {
            outline: 2px solid var(--zyro-black);
            outline-offset: 2px;
        }

        .auth-card-footer {
            margin-top: 1.75rem;
            padding-top: 1.25rem;
            border-top: 1px solid var(--zyro-border);
            text-align: center;
            font-size: 0.8125rem;
            color: var(--zyro-muted);
        }

        .auth-card-footer a {
            color: var(--zyro-black);
            font-weight: 600;
            text-decoration: underline;
            transition: color 0.2s;
        }

        .auth-card-footer a:hover {
            color: var(--zyro-tech-blue);
        }
    </style>
</head>
<body>
    {{-- Header Principal --}}
    <header class="site-header">
        <div class="container header-inner">
            {{-- Logo Izquierda --}}
            <a href="{{ route('home') }}" class="brand-logo" aria-label="ZYRO Inicio">
                <span class="brand-emblem">Z</span>
                <span class="brand-name">ZYRO</span>
            </a>

            {{-- Navegación Centrada --}}
            <nav class="nav-center" aria-label="Navegación principal">
                <a href="{{ route('catalog.index') }}">Colección</a>
                <a href="{{ route('catalog.index', ['line' => 'tech']) }}">Con Tecnología</a>
                <a href="{{ route('catalog.index', ['line' => 'esencial']) }}">Línea Esencial</a>
                <a href="{{ route('home') }}#tecnologia">Tecnología</a>
            </nav>

            {{-- Acciones Derecha --}}
            <div class="header-actions">
                <form method="GET" action="{{ route('catalog.index') }}" class="search-form-compact" role="search">
                    <input
                        type="search"
                        name="q"
                        value="{{ request('q') }}"
                        placeholder="Buscar modelo..."
                        aria-label="Buscar producto"
                    >
                    <button type="submit" aria-label="Ejecutar búsqueda">&rarr;</button>
                </form>

                <a href="{{ route('cart.index') }}" class="cart-pill" aria-label="Ver carrito">
                    <span>Carrito</span>
                    @php $cartCount = count(session('cart.items', [])); @endphp
                    @if ($cartCount > 0)
                        <span class="cart-count">{{ $cartCount }}</span>
                    @endif
                </a>

                @auth
                    <a href="{{ route('orders.index') }}" style="font-weight:600;font-size:0.75rem;text-transform:uppercase;letter-spacing:0.08em;">Mis Pedidos</a>
                    @if (auth()->user()->isAdmin())
                        <a href="{{ route('admin.dashboard') }}" style="background:#0F0F11;color:#fff;padding:0.25rem 0.5rem;font-size:0.6875rem;font-weight:700;letter-spacing:0.1em;text-transform:uppercase;">Admin</a>
                    @endif
                    <form method="POST" action="{{ route('logout') }}" style="display:inline;">
                        @csrf
                        <button type="submit" style="background:none;border:none;cursor:pointer;color:var(--zyro-muted);font-size:0.75rem;font-weight:600;text-transform:uppercase;">Salir</button>
                    </form>
                @else
                    <a href="{{ route('login') }}" style="font-weight:600;font-size:0.75rem;text-transform:uppercase;letter-spacing:0.08em;">Entrar</a>
                @endauth
            </div>
        </div>
    </header>

    {{-- Contenido de Página --}}
    <main class="site-main">
        @if (session('status'))
            <div class="container" style="padding-top:1rem;">
                <div style="background:#ECFDF5;border:1px solid #A7F3D0;color:#065F46;padding:0.75rem 1rem;font-size:0.8125rem;border-radius:2px;">
                    {{ session('status') }}
                </div>
            </div>
        @endif

        @yield('content')
    </main>

    {{-- Footer --}}
    <footer class="site-footer">
        <div class="container footer-grid">
            <div class="footer-col">
                <div class="brand-logo" style="margin-bottom:0.75rem;">
                    <span class="brand-emblem" style="background:#fff;color:#0F0F11;">Z</span>
                    <span class="brand-name" style="color:#fff;">ZYRO</span>
                </div>
                <p style="color:#A1A1AA;font-size:0.8125rem;line-height:1.6;margin-bottom:1rem;">
                    Colección editorial de chompas urbanas. Cuatro modelos con termorregulación activa ($299.99) y cuatro esenciales en algodón orgánico puro ($79.99).
                </p>
                <p style="font-size:0.6875rem;color:#71717A;text-transform:uppercase;letter-spacing:0.1em;">
                    Colección Oficial &bull; Cápsula 2026
                </p>
            </div>

            <div class="footer-col">
                <h4>Líneas</h4>
                <ul class="footer-links">
                    <li><a href="{{ route('catalog.index', ['line' => 'tech']) }}">Línea Tech — $299.99</a></li>
                    <li><a href="{{ route('catalog.index', ['line' => 'esencial']) }}">Línea Esencial — $79.99</a></li>
                    <li><a href="{{ route('catalog.index') }}">Catálogo Completo (8)</a></li>
                    <li><a href="{{ route('home') }}#tecnologia">Innovación Textil</a></li>
                </ul>
            </div>

            <div class="footer-col">
                <h4>Información</h4>
                <ul class="footer-links">
                    <li><span>Despacho seguro nacional</span></li>
                    <li><span>Aprobación administrativa</span></li>
                    <li><span>Proceso de compra verificado</span></li>
                    <li><span>Inventario sincronizado</span></li>
                </ul>
            </div>

            <div class="footer-col">
                <h4>Mi Cuenta</h4>
                <ul class="footer-links">
                    @auth
                        <li><a href="{{ route('orders.index') }}">Historial de pedidos</a></li>
                        @if(auth()->user()->isAdmin())
                            <li><a href="{{ route('admin.dashboard') }}">Panel de Administración</a></li>
                        @endif
                    @else
                        <li><a href="{{ route('login') }}">Iniciar Sesión</a></li>
                        <li><a href="{{ route('register') }}">Crear Cuenta</a></li>
                    @endauth
                </ul>
            </div>
        </div>

        <div class="container footer-bottom">
            <span>&copy; {{ date('Y') }} ZYRO. Tienda de moda urbana y alta tecnología textil.</span>
            <span>8 Modelos Oficiales: 4 Tech ($299.99) &bull; 4 Esencial ($79.99)</span>
        </div>
    </footer>
</body>
</html>
