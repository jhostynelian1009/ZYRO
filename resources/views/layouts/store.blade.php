<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'ZYRO')</title>
    <meta name="description" content="@yield('meta_description', 'ZYRO. Chompas de corte editorial. Tienda académica con pedido, aprobación y pago simulado.')">
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=cormorant-garamond:500,600|inter:400,500,600" rel="stylesheet" />
    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @else
        <style>
            :root { color-scheme: light; }
            * { box-sizing: border-box; }
            body { margin: 0; font-family: Inter, system-ui, sans-serif; color: #111111; background: #FFFFFF; min-height: 100vh; display: flex; flex-direction: column; }
            a { color: inherit; text-decoration: none; }
            header, footer { border-color: #E5E5E5; }
            header { border-bottom: 1px solid #E5E5E5; }
            footer { margin-top: 4rem; border-top: 1px solid #E5E5E5; background: #F5F5F5; }
            .mx-auto { margin-inline: auto; } .max-w-6xl { max-width: 72rem; } .max-w-md { max-width: 28rem; } .max-w-2xl { max-width: 42rem; } .max-w-xl { max-width: 36rem; }
            .px-4 { padding-inline: 1rem; } .py-4 { padding-block: 1rem; } .py-12 { padding-block: 3rem; } .py-16 { padding-block: 4rem; } .pb-5 { padding-bottom: 1.25rem; }
            .flex { display: flex; } .flex-col { flex-direction: column; } .flex-1 { flex: 1; } .flex-wrap { flex-wrap: wrap; }
            .items-center { align-items: center; } .justify-between { justify-content: space-between; } .gap-2 { gap: .5rem; } .gap-3 { gap: .75rem; } .gap-4 { gap: 1rem; } .gap-5 { gap: 1.25rem; } .gap-8 { gap: 2rem; } .gap-10 { gap: 2.5rem; }
            .grid { display: grid; } .w-full { width: 100%; } .h-full { height: 100%; }
            .border { border: 1px solid #E5E5E5; } .border-b { border-bottom: 1px solid #E5E5E5; }
            .bg-zyro-gray, .bg-zyro-gray { background: #F5F5F5; } .bg-white { background: #fff; } .bg-zyro { background: #111; } .text-white { color: #fff; } .text-zyro-muted { color: #666; }
            .font-serif { font-family: "Cormorant Garamond", Georgia, serif; } .uppercase { text-transform: uppercase; }
            .text-sm { font-size: .9rem; } .text-xs { font-size: .75rem; } .text-2xl { font-size: 1.5rem; } .text-4xl { font-size: 2.25rem; } .text-5xl { font-size: 3rem; }
            .tracking-wide { letter-spacing: .12em; } input, button { font: inherit; } input, textarea { border: 1px solid #E5E5E5; padding: .5rem .75rem; width: 100%; }
            button, .inline-block { cursor: pointer; } .sr-only { position: absolute; width: 1px; height: 1px; overflow: hidden; clip: rect(0,0,0,0); }
            img { max-width: 100%; display: block; } .aspect-\[3\/4\] { aspect-ratio: 3/4; } .object-cover { object-fit: cover; }
            @media (min-width: 768px) { .md\:flex-row { flex-direction: row; } .md\:grid-cols-2 { grid-template-columns: 1fr 1fr; } .md\:items-center { align-items: center; } .md\:justify-between { justify-content: space-between; } .md\:text-6xl { font-size: 3.75rem; } .md\:py-24 { padding-block: 6rem; } }
            @media (min-width: 640px) { .sm\:grid-cols-2 { grid-template-columns: 1fr 1fr; } }
            @media (min-width: 1024px) { .lg\:grid-cols-3 { grid-template-columns: 1fr 1fr 1fr; } .lg\:grid-cols-4 { grid-template-columns: repeat(4, 1fr); } }
        </style>
    @endif
</head>
<body class="bg-white text-zyro min-h-screen flex flex-col">
    <header class="border-b border-zyro-border">
        <div class="mx-auto max-w-6xl px-4 py-4 flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
            <a href="{{ route('catalog.index') }}" class="flex items-center gap-3" aria-label="ZYRO inicio">
                <span class="inline-flex h-10 w-10 items-center justify-center border border-zyro text-[11px] font-semibold zyro-logo-mark">Z</span>
                <span class="font-serif text-2xl tracking-[0.35em]">ZYRO</span>
            </a>
            <p class="sr-only">Espacio reservado para el logotipo oficial. Copiar el archivo original a public/images/brand/logo.svg</p>

            <nav class="flex flex-wrap items-center gap-5 text-sm tracking-wide uppercase" aria-label="Principal">
                <a href="{{ route('catalog.index') }}" class="hover:opacity-60">Colección</a>
                @auth
                    <a href="{{ route('cart.index') }}" class="hover:opacity-60 relative">
                        Carrito
                        @php $cartCount = count(session('cart.items', [])); @endphp
                        @if ($cartCount > 0)
                            <span style="font-size:.65rem;background:#111;color:#fff;border-radius:50%;padding:1px 5px;margin-left:3px;vertical-align:middle;">{{ $cartCount }}</span>
                        @endif
                    </a>
                    <a href="{{ route('orders.index') }}" class="hover:opacity-60">Mis pedidos</a>
                    @if (auth()->user()->isAdmin())
                        <a href="{{ route('admin.dashboard') }}" class="hover:opacity-60 text-zyro-muted">Admin</a>
                    @endif
                    <span class="normal-case tracking-normal text-zyro-muted">{{ auth()->user()->name }}</span>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="uppercase tracking-wide hover:opacity-60">Salir</button>
                    </form>
                @else
                    <a href="{{ route('login') }}" class="hover:opacity-60">Cuenta</a>
                    <a href="{{ route('register') }}" class="hover:opacity-60">Registro</a>
                @endauth
            </nav>
        </div>
        <div class="mx-auto max-w-6xl px-4 pb-5">
            <form method="GET" action="{{ route('catalog.index') }}" class="flex gap-2" role="search">
                <label for="catalog-search" class="sr-only">Buscar productos</label>
                <input
                    id="catalog-search"
                    type="search"
                    name="q"
                    value="{{ $query ?? request('q') }}"
                    placeholder="Buscar por nombre o descripción"
                    class="w-full border border-zyro-border bg-zyro-gray px-3 py-2 text-sm"
                >
                <button type="submit" class="bg-zyro px-4 py-2 text-sm uppercase tracking-wide text-white">
                    Buscar
                </button>
            </form>
        </div>
    </header>

    <main class="flex-1">
        @if (session('status'))
            <div class="mx-auto max-w-6xl px-4 pt-6 text-sm" role="status">{{ session('status') }}</div>
        @endif
        @yield('content')
    </main>

    <footer class="mt-16 border-t border-zyro-border bg-zyro-gray">
        <div class="mx-auto max-w-6xl px-4 py-12 grid gap-8 sm:grid-cols-2 lg:grid-cols-4 text-sm">
            <div>
                <p class="font-serif text-xl tracking-[0.3em] mb-3">ZYRO</p>
                <p class="text-zyro-muted">Moda minimalista. Entorno académico con pagos simulados.</p>
            </div>
            <div>
                <h2 class="uppercase tracking-wide text-xs mb-3">Colecciones</h2>
                <a href="{{ route('catalog.index') }}" class="block text-zyro-muted hover:text-zyro">Chompas</a>
            </div>
            <div>
                <h2 class="uppercase tracking-wide text-xs mb-3">Información</h2>
                <p class="text-zyro-muted">Envíos y plazos se documentarán en checkout. Impuestos y métodos demo se confirman en el servidor.</p>
            </div>
            <div>
                <h2 class="uppercase tracking-wide text-xs mb-3">Cuenta</h2>
                @auth
                    <p class="text-zyro-muted">Sesión iniciada.</p>
                @else
                    <a href="{{ route('login') }}" class="block text-zyro-muted hover:text-zyro">Iniciar sesión</a>
                    <a href="{{ route('register') }}" class="block text-zyro-muted hover:text-zyro">Crear cuenta</a>
                @endauth
            </div>
        </div>
    </footer>
</body>
</html>
