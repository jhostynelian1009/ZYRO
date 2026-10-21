<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'ZYRO — Moda Urbana & Alta Tecnología')</title>
    <meta name="description" content="@yield('meta_description', 'ZYRO. Tienda de moda urbana con 8 diseños exclusivos de chompas: Línea Tech ($370.00) y Línea Esencial ($80.00).')">
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=cormorant-garamond:500,600,700|inter:400,500,600,700" rel="stylesheet" />

    {{-- Favicon Oficial ZYRO --}}
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('images/favicon.png') }}">
    <link rel="shortcut icon" href="{{ asset('favicon.ico') }}">

    {{-- Assets Vite (Tailwind CSS & JavaScript) --}}
    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @endif

    {{-- Microsoft Clarity (Exclusivo para páginas públicas del catálogo; omitido en admin, checkout, auth y pagos) --}}
    @php
        $clarityProjectId = config('services.clarity.project_id');
        $isPublicTrackingRoute = request()->routeIs('home', 'catalog.index', 'catalog.show', 'catalog.customize');
    @endphp
    @if (filled($clarityProjectId) && $isPublicTrackingRoute)
        <script type="text/javascript">
            (function(c,l,a,r,i,t,y){
                c[a]=c[a]||function(){(c[a].q=c[a].q||[]).push(arguments)};
                t=l.createElement(r);t.async=1;t.src="https://www.clarity.ms/tag/"+i;
                y=l.getElementsByTagName(r)[0];y.parentNode.insertBefore(t,y);
            })(window, document, "clarity", "script", "{{ e($clarityProjectId) }}");
        </script>
    @endif

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
            display: inline-flex;
            align-items: center;
            gap: 0.65rem;
            text-decoration: none;
            flex-shrink: 0;
            transition: opacity 0.2s ease;
        }

        .brand-logo:hover {
            opacity: 0.85;
        }

        .brand-logo-image {
            width: auto;
            height: 38px;
            max-height: 38px;
            object-fit: contain;
            display: block;
        }

        .brand-wordmark {
            font-family: 'Cormorant Garamond', Georgia, serif;
            font-size: 1.625rem;
            font-weight: 700;
            letter-spacing: 0.2em;
            text-transform: uppercase;
            color: var(--zyro-black);
            line-height: 1;
            display: inline-block;
            padding-top: 1px;
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
            gap: 0.35rem;
            font-size: 0.75rem;
            font-weight: 600;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            line-height: 1;
        }

        .cart-pill-icon {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 20px;
            height: 20px;
            flex-shrink: 0;
        }

        .cart-pill-icon svg {
            width: 20px;
            height: 20px;
            display: block;
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
            height: 100%;
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
            aspect-ratio: 16 / 11;
            background: var(--zyro-light);
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 0.875rem;
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
            gap: 1rem;
        }

        .product-card-info {
            display: flex;
            flex-direction: column;
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

        .product-card-footer {
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
            margin-top: auto;
            padding-top: 0.85rem;
            border-top: 1px solid var(--zyro-border);
        }

        .product-price-row {
            display: flex;
            align-items: baseline;
            justify-content: flex-start;
        }

        .product-price {
            font-size: 1.15rem;
            font-weight: 700;
            color: var(--zyro-black);
            line-height: 1.2;
        }

        .product-actions-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
            width: 100%;
        }

        .btn-card-details {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            height: 36px;
            padding: 0 0.5rem;
            font-size: 0.6875rem;
            font-weight: 700;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            background: var(--zyro-black);
            color: var(--zyro-white);
            border: 1px solid var(--zyro-black);
            border-radius: 2px;
            text-align: center;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            cursor: pointer;
            transition: background 0.2s ease, border-color 0.2s ease, color 0.2s ease;
        }

        .btn-card-details:hover {
            background: #27272A;
            border-color: #27272A;
            color: var(--zyro-white);
        }

        .btn-card-customize {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            height: 36px;
            padding: 0 0.5rem;
            font-size: 0.6875rem;
            font-weight: 700;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            background: var(--zyro-white);
            color: var(--zyro-black);
            border: 1px solid var(--zyro-black);
            border-radius: 2px;
            text-align: center;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            cursor: pointer;
            transition: background 0.2s ease, border-color 0.2s ease, color 0.2s ease;
        }

        .btn-card-customize:hover {
            background: var(--zyro-light);
            color: var(--zyro-black);
        }

        .btn-card-action {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            height: 34px;
            font-size: 0.6875rem;
            font-weight: 600;
            letter-spacing: 0.1em;
            text-transform: uppercase;
            padding: 0.45rem 0.85rem;
            background: var(--zyro-black);
            color: var(--zyro-white);
            border: 1px solid var(--zyro-black);
            border-radius: 2px;
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
            .brand-logo-image { height: 32px; }
            .brand-wordmark { font-size: 1.45rem; letter-spacing: 0.16em; }
            .header-actions { gap: 0.75rem; }
            .search-form-compact input { width: 100px; }
        }

        @media (max-width: 640px) {
            .site-header { height: 60px; }
            .header-inner { gap: 0.5rem; padding-inline: 0.75rem; }
            .brand-logo-image { height: 26px; }
            .brand-wordmark { font-size: 1.25rem; letter-spacing: 0.14em; }
            .search-form-compact { display: none; }
            .header-actions { gap: 0.5rem; }
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

        /* ─── CARRITO DE COMPRAS ─── */
        .cart-page-wrapper {
            padding-block: 2.5rem 4rem;
        }

        .cart-header {
            margin-bottom: 2rem;
            padding-bottom: 1.25rem;
            border-bottom: 1px solid var(--zyro-border);
        }

        .cart-header h1 {
            font-family: 'Cormorant Garamond', Georgia, serif;
            font-size: 2.25rem;
            font-weight: 600;
            line-height: 1.15;
            color: var(--zyro-black);
            margin-top: 0.25rem;
        }

        .cart-grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: 2rem;
            align-items: start;
        }

        @media (min-width: 960px) {
            .cart-grid {
                grid-template-columns: 1fr 360px;
            }
        }

        .cart-items-card {
            background: var(--zyro-white);
            border: 1px solid var(--zyro-border);
            border-radius: 2px;
            display: flex;
            flex-direction: column;
            divide-y: 1px solid var(--zyro-border);
        }

        .cart-item-row {
            display: flex;
            align-items: center;
            gap: 1.25rem;
            padding: 1.25rem;
            border-bottom: 1px solid var(--zyro-border);
        }

        .cart-item-row:last-child {
            border-bottom: none;
        }

        .cart-thumb {
            width: 76px;
            height: 92px;
            background: var(--zyro-light);
            border: 1px solid var(--zyro-border);
            border-radius: 2px;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            flex-shrink: 0;
            padding: 0.25rem;
        }

        .cart-thumb img {
            width: 100%;
            height: 100%;
            object-fit: contain;
        }

        .cart-item-info {
            flex: 1;
            min-width: 0;
        }

        .cart-item-name {
            font-family: 'Cormorant Garamond', Georgia, serif;
            font-size: 1.25rem;
            font-weight: 600;
            color: var(--zyro-black);
            line-height: 1.2;
            margin-bottom: 0.25rem;
        }

        .cart-item-size-pill {
            display: inline-block;
            font-size: 0.6875rem;
            font-weight: 600;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            color: var(--zyro-muted);
            background: var(--zyro-light);
            border: 1px solid var(--zyro-border);
            padding: 0.15rem 0.45rem;
            border-radius: 2px;
            margin-bottom: 0.35rem;
        }

        .cart-item-unit-price {
            font-size: 0.8125rem;
            color: var(--zyro-muted);
        }

        .cart-item-actions {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin-top: 0.65rem;
        }

        .cart-qty-form {
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
        }

        .cart-qty-input {
            width: 52px;
            height: 32px;
            padding: 0.25rem;
            text-align: center;
            font-family: inherit;
            font-size: 0.8125rem;
            font-weight: 600;
            color: var(--zyro-black);
            border: 1px solid var(--zyro-border);
            border-radius: 2px;
            background: var(--zyro-white);
            outline: none;
        }

        .cart-qty-input:focus {
            border-color: var(--zyro-black);
        }

        .btn-cart-update {
            background: transparent;
            border: 1px solid var(--zyro-border);
            color: var(--zyro-black);
            font-size: 0.6875rem;
            font-weight: 600;
            letter-spacing: 0.06em;
            text-transform: uppercase;
            padding: 0.4rem 0.65rem;
            border-radius: 2px;
            cursor: pointer;
            transition: all 0.15s ease;
        }

        .btn-cart-update:hover {
            background: var(--zyro-black);
            color: var(--zyro-white);
            border-color: var(--zyro-black);
        }

        .btn-cart-remove {
            background: transparent;
            border: none;
            color: #DC2626;
            font-size: 0.6875rem;
            font-weight: 600;
            letter-spacing: 0.06em;
            text-transform: uppercase;
            cursor: pointer;
            padding: 0.4rem 0.25rem;
            display: inline-flex;
            align-items: center;
            gap: 0.25rem;
            opacity: 0.85;
            transition: opacity 0.15s ease;
        }

        .btn-cart-remove:hover {
            opacity: 1;
            text-decoration: underline;
        }

        .cart-item-total {
            text-align: right;
            min-width: 90px;
        }

        .cart-item-total-amount {
            font-size: 1.05rem;
            font-weight: 700;
            color: var(--zyro-black);
        }

        .cart-summary-card {
            background: var(--zyro-light);
            border: 1px solid var(--zyro-border);
            border-radius: 2px;
            padding: 1.75rem;
            position: sticky;
            top: 88px;
        }

        .summary-card-title {
            font-family: 'Cormorant Garamond', Georgia, serif;
            font-size: 1.5rem;
            font-weight: 600;
            color: var(--zyro-black);
            margin-bottom: 1.25rem;
            padding-bottom: 0.75rem;
            border-bottom: 1px solid var(--zyro-border);
        }

        .summary-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            font-size: 0.875rem;
            color: var(--zyro-muted);
            margin-bottom: 0.65rem;
        }

        .summary-row span:last-child {
            color: var(--zyro-black);
            font-weight: 600;
        }

        .summary-divider {
            height: 1px;
            background: var(--zyro-border);
            margin-block: 1rem;
        }

        .summary-total-row {
            display: flex;
            align-items: baseline;
            justify-content: space-between;
            font-size: 1rem;
            font-weight: 700;
            color: var(--zyro-black);
            margin-bottom: 0.5rem;
        }

        .summary-total-row span:last-child {
            font-size: 1.5rem;
            font-family: 'Cormorant Garamond', Georgia, serif;
            font-weight: 700;
        }

        .summary-note {
            font-size: 0.75rem;
            color: var(--zyro-muted);
            line-height: 1.5;
            margin-bottom: 1.25rem;
        }

        .cart-empty-card {
            background: var(--zyro-white);
            border: 1px solid var(--zyro-border);
            border-radius: 2px;
            padding: 4rem 2rem;
            text-align: center;
            max-width: 600px;
            margin: 0 auto;
        }

        .cart-empty-icon {
            font-size: 2.5rem;
            margin-bottom: 1rem;
            display: block;
        }

        .cart-empty-card h2 {
            font-family: 'Cormorant Garamond', Georgia, serif;
            font-size: 2rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
            color: var(--zyro-black);
        }

        .cart-empty-card p {
            font-size: 0.875rem;
            color: var(--zyro-muted);
            margin-bottom: 1.75rem;
            line-height: 1.6;
        }

        /* ─── CHECKOUT ─── */
        .checkout-page-wrapper {
            padding-block: 2.5rem 4rem;
        }

        .checkout-header {
            margin-bottom: 2rem;
            padding-bottom: 1.25rem;
            border-bottom: 1px solid var(--zyro-border);
        }

        .checkout-header h1 {
            font-family: 'Cormorant Garamond', Georgia, serif;
            font-size: 2.25rem;
            font-weight: 600;
            line-height: 1.15;
            color: var(--zyro-black);
            margin-top: 0.25rem;
        }

        .checkout-grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: 2rem;
            align-items: start;
        }

        @media (min-width: 960px) {
            .checkout-grid {
                grid-template-columns: 1fr 390px;
            }
        }

        .checkout-form-card {
            background: var(--zyro-white);
            border: 1px solid var(--zyro-border);
            border-radius: 2px;
            padding: 2rem;
        }

        .checkout-section-title {
            font-family: 'Cormorant Garamond', Georgia, serif;
            font-size: 1.5rem;
            font-weight: 600;
            color: var(--zyro-black);
            margin-bottom: 1.5rem;
            padding-bottom: 0.75rem;
            border-bottom: 1px solid var(--zyro-border);
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .checkout-fields-grid {
            display: flex;
            flex-direction: column;
            gap: 1.25rem;
        }

        .form-grid-2 {
            display: grid;
            grid-template-columns: 1fr;
            gap: 1.25rem;
        }

        @media (min-width: 640px) {
            .form-grid-2 {
                grid-template-columns: 1fr 1fr;
            }
        }

        .checkout-select {
            width: 100%;
            height: 44px;
            padding: 0.65rem 0.875rem;
            font-family: inherit;
            font-size: 0.875rem;
            color: var(--zyro-black);
            background: var(--zyro-white);
            border: 1px solid var(--zyro-border);
            border-radius: 2px;
            outline: none;
            cursor: pointer;
            box-sizing: border-box;
            transition: border-color 0.2s, box-shadow 0.2s;
        }

        .checkout-select:focus {
            border-color: var(--zyro-black);
            box-shadow: 0 0 0 1px var(--zyro-black);
        }

        .checkout-textarea {
            width: 100%;
            padding: 0.75rem 0.875rem;
            font-family: inherit;
            font-size: 0.875rem;
            color: var(--zyro-black);
            background: var(--zyro-white);
            border: 1px solid var(--zyro-border);
            border-radius: 2px;
            outline: none;
            box-sizing: border-box;
            resize: vertical;
            min-height: 80px;
            transition: border-color 0.2s, box-shadow 0.2s;
        }

        .checkout-textarea:focus {
            border-color: var(--zyro-black);
            box-shadow: 0 0 0 1px var(--zyro-black);
        }

        .checkout-summary-card {
            background: var(--zyro-light);
            border: 1px solid var(--zyro-border);
            border-radius: 2px;
            padding: 1.75rem;
            position: sticky;
            top: 88px;
        }

        .checkout-items-list {
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
            margin-bottom: 1.25rem;
            max-height: 280px;
            overflow-y: auto;
            padding-right: 0.25rem;
        }

        .checkout-item-mini {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 0.75rem;
            padding-bottom: 0.75rem;
            border-bottom: 1px solid var(--zyro-border);
        }

        .checkout-item-mini:last-child {
            border-bottom: none;
            padding-bottom: 0;
        }

        .checkout-item-mini-info {
            display: flex;
            flex-direction: column;
            gap: 0.15rem;
            min-width: 0;
        }

        .checkout-item-mini-name {
            font-size: 0.8125rem;
            font-weight: 600;
            color: var(--zyro-black);
            line-height: 1.3;
        }

        .checkout-item-mini-meta {
            font-size: 0.6875rem;
            color: var(--zyro-muted);
        }

        .checkout-item-mini-price {
            font-size: 0.8125rem;
            font-weight: 600;
            color: var(--zyro-black);
            white-space: nowrap;
        }

        .checkout-disclaimer-box {
            background: var(--zyro-white);
            border: 1px solid var(--zyro-border);
            border-left: 3px solid var(--zyro-black);
            padding: 0.875rem 1rem;
            font-size: 0.75rem;
            color: var(--zyro-muted);
            line-height: 1.5;
            margin-top: 1.25rem;
            border-radius: 2px;
        }

        .btn-confirm-order {
            width: 100%;
            height: 48px;
            background: var(--zyro-black);
            color: var(--zyro-white);
            border: 1px solid var(--zyro-black);
            font-family: inherit;
            font-size: 0.75rem;
            font-weight: 700;
            letter-spacing: 0.15em;
            text-transform: uppercase;
            cursor: pointer;
            border-radius: 2px;
            transition: all 0.2s ease;
            margin-top: 1.5rem;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }

        .btn-confirm-order:hover {
            background: #27272A;
            border-color: #27272A;
        }

        /* ─── MIS PEDIDOS Y DETALLE ─── */
        .orders-page-wrapper {
            padding-block: 2.5rem 4rem;
        }

        .orders-table-wrapper {
            background: var(--zyro-white);
            border: 1px solid var(--zyro-border);
            border-radius: 2px;
            overflow-x: auto;
        }

        .orders-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.8125rem;
            text-align: left;
        }

        .orders-table th {
            background: var(--zyro-light);
            padding: 0.75rem 1rem;
            font-size: 0.6875rem;
            font-weight: 700;
            letter-spacing: 0.1em;
            text-transform: uppercase;
            color: var(--zyro-muted);
            border-bottom: 1px solid var(--zyro-border);
        }

        .orders-table td {
            padding: 1rem;
            border-bottom: 1px solid var(--zyro-border);
            color: var(--zyro-black);
        }

        .orders-table tr:last-child td {
            border-bottom: none;
        }

        .orders-table tr:hover td {
            background: #FAFAFA;
        }

        .order-detail-grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: 2rem;
            align-items: start;
        }

        @media (min-width: 840px) {
            .order-detail-grid {
                grid-template-columns: 1fr 340px;
            }
        }

        .order-card {
            background: var(--zyro-white);
            border: 1px solid var(--zyro-border);
            border-radius: 2px;
            padding: 1.5rem;
        }
    </style>
</head>
<body>
    {{-- Header Principal --}}
    <header class="site-header">
        <div class="container header-inner">
            {{-- Logo Izquierda --}}
            <a href="{{ route('home') }}" class="brand-logo" aria-label="ZYRO Inicio">
                <img class="brand-logo-image" src="{{ asset('images/zyro-logo-transparent.png') }}" alt="ZYRO Monograma" width="46" height="36">
                <span class="brand-wordmark">ZYRO</span>
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

                <a href="{{ route('cart.index') }}" class="cart-pill" aria-label="Carrito de compras">
                    <span class="cart-pill-icon" aria-hidden="true">
                        {{-- Icono carrito minimalista premium — fondo transparente, silueta negra --}}
                        <svg viewBox="0 0 64 64" fill="currentColor" xmlns="http://www.w3.org/2000/svg" aria-hidden="true" focusable="false">
                            {{-- Asa del carrito (forma de L con esquina redondeada) --}}
                            <path d="M4 10 C4 7.8 5.8 6 8 6 L16 6 C17.1 6 18 6.9 18 8 L18 10 Z" />
                            <rect x="14" y="6" width="4" height="28" rx="2"/>
                            <rect x="14" y="30" width="34" height="4" rx="2"/>
                            {{-- Cuerpo trapezoidal del carrito --}}
                            <path d="M18 10 L54 10 L50 34 L18 34 Z"/>
                            {{-- Rueda izquierda --}}
                            <circle cx="22" cy="46" r="6"/>
                            {{-- Rueda derecha --}}
                            <circle cx="44" cy="46" r="6"/>
                        </svg>
                    </span>
                    <span class="sr-only">Carrito</span>
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
                <a href="{{ route('home') }}" class="brand-logo" style="margin-bottom:0.85rem;" aria-label="ZYRO Inicio">
                    <img class="brand-logo-image" src="{{ asset('images/zyro-logo-white.png') }}" alt="ZYRO Monograma" width="46" height="36" style="height:36px;width:auto;">
                    <span class="brand-wordmark" style="color:#FFFFFF;">ZYRO</span>
                </a>
                <p style="color:#A1A1AA;font-size:0.8125rem;line-height:1.6;margin-bottom:1rem;">
                    Colección editorial de chompas urbanas. Cuatro modelos con termorregulación activa ($370.00) y cuatro esenciales en algodón orgánico puro ($80.00).
                </p>
                <p style="font-size:0.6875rem;color:#71717A;text-transform:uppercase;letter-spacing:0.1em;">
                    Colección Oficial &bull; Cápsula 2026
                </p>
            </div>

            <div class="footer-col">
                <h4>Líneas</h4>
                <ul class="footer-links">
                    <li><a href="{{ route('catalog.index', ['line' => 'tech']) }}">Línea Tech — $370.00</a></li>
                    <li><a href="{{ route('catalog.index', ['line' => 'esencial']) }}">Línea Esencial — $80.00</a></li>
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
            <span>8 Modelos Oficiales: 4 Tech ($370.00) &bull; 4 Esencial ($80.00)</span>
        </div>
    </footer>

    {{-- Listener no-bloqueante para eventos de interacción pública (Microsoft Clarity & ZYRO Heatmap) --}}
    @if ($isPublicTrackingRoute ?? false)
        <script>
            (function () {
                var csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
                document.addEventListener('click', function (e) {
                    var target = e.target.closest('[data-clarity-action]');
                    if (!target) return;

                    var action = target.getAttribute('data-clarity-action');
                    var productId = target.getAttribute('data-product-id');

                    // 1. Notificar a Microsoft Clarity si está activo en la página
                    if (typeof window.clarity === 'function') {
                        window.clarity('event', action);
                    }

                    // 2. Registro asíncrono seguro en la base de datos de ZYRO
                    if (csrf && action) {
                        try {
                            fetch("{{ route('analytics.interaction') }}", {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'X-CSRF-TOKEN': csrf,
                                    'Accept': 'application/json'
                                },
                                keepalive: true,
                                body: JSON.stringify({
                                    event: 'interaction_' + action,
                                    product_id: productId ? parseInt(productId, 10) : null,
                                    metadata: {
                                        action: action,
                                        path: window.location.pathname
                                    }
                                })
                            }).catch(function () {});
                        } catch (err) {}
                    }
                }, true);
            })();
        </script>
    @endif
</body>
</html>
