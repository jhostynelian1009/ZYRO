<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Admin — ZYRO')</title>

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=cormorant-garamond:600,700|inter:400,500,600,700" rel="stylesheet" />

    <style>
        :root {
            --zyro-black: #090909;
            --zyro-white: #FFFFFF;
            --zyro-bg: #F5F5F5;
            --zyro-border: #E5E5E5;
            --zyro-border-dark: #1F1F1F;
            --zyro-muted: #6B7280;
            --zyro-blue: #315DDB;
            --zyro-blue-bg: #EFF3FC;
            --zyro-blue-border: #C7D7F8;
            --zyro-green: #10B981;
            --zyro-green-bg: #ECFDF5;
            --zyro-green-text: #065F46;
            --zyro-green-border: #A7F3D0;
            --zyro-amber: #F59E0B;
            --zyro-amber-bg: #FFFBEB;
            --zyro-amber-text: #92400E;
            --zyro-amber-border: #FDE68A;
            --zyro-red: #EF4444;
            --zyro-red-bg: #FEF2F2;
            --zyro-red-text: #991B1B;
            --zyro-red-border: #FECACA;
            --sidebar-width: 260px;
        }

        * { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: 'Inter', system-ui, -apple-system, sans-serif;
            color: var(--zyro-black);
            background: var(--zyro-bg);
            min-height: 100vh;
            display: flex;
            line-height: 1.5;
            -webkit-font-smoothing: antialiased;
        }

        a { color: inherit; text-decoration: none; }
        .font-serif { font-family: 'Cormorant Garamond', Georgia, serif; }

        /* ─── CONTENEDOR PRINCIPAL Y SIDEBAR ─── */
        .admin-layout {
            display: flex;
            width: 100%;
            min-height: 100vh;
        }

        .admin-sidebar {
            width: var(--sidebar-width);
            min-width: var(--sidebar-width);
            background: var(--zyro-black);
            color: #E5E5E5;
            display: flex;
            flex-direction: column;
            position: sticky;
            top: 0;
            height: 100vh;
            overflow-y: auto;
            border-right: 1px solid var(--zyro-border-dark);
            z-index: 50;
            transition: transform 0.25s ease;
        }

        .sidebar-brand {
            padding: 1.25rem 1.5rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            border-bottom: 1px solid var(--zyro-border-dark);
        }

        .brand-link {
            display: flex;
            align-items: center;
            min-width: 0;
        }

        .brand-logo-image {
            display: block;
            width: 112px;
            height: 64px;
            object-fit: contain;
        }

        .brand-badge {
            font-size: 0.5625rem;
            font-weight: 700;
            letter-spacing: 0.12em;
            text-transform: uppercase;
            padding: 0.2rem 0.45rem;
            background: #1F1F1F;
            color: #A1A1AA;
            border: 1px solid #2D2D2D;
            border-radius: 2px;
        }

        .sidebar-nav {
            padding: 1rem 0;
            display: flex;
            flex-direction: column;
            gap: 0.25rem;
            flex: 1;
        }

        .nav-section-title {
            font-size: 0.625rem;
            font-weight: 700;
            letter-spacing: 0.15em;
            text-transform: uppercase;
            color: #71717A;
            padding: 0.75rem 1.5rem 0.35rem;
        }

        .sidebar-link {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0.625rem 1.5rem;
            font-size: 0.8125rem;
            font-weight: 500;
            color: #A1A1AA;
            transition: all 0.15s ease;
            position: relative;
        }

        .sidebar-link:hover {
            color: #FFFFFF;
            background: rgba(255, 255, 255, 0.05);
        }

        .sidebar-link.active {
            color: #FFFFFF;
            background: rgba(255, 255, 255, 0.1);
            font-weight: 600;
        }

        .sidebar-link.active::before {
            content: '';
            position: absolute;
            left: 0;
            top: 0;
            bottom: 0;
            width: 3px;
            background: var(--zyro-blue);
        }

        .sidebar-link-inner {
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .sidebar-link-icon {
            width: 16px;
            height: 16px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            color: #71717A;
        }

        .sidebar-link.active .sidebar-link-icon {
            color: var(--zyro-blue);
        }

        .sidebar-count {
            font-size: 0.6875rem;
            padding: 0.15rem 0.45rem;
            border-radius: 2px;
            background: #1F1F1F;
            color: #A1A1AA;
        }

        .sidebar-count.amber {
            background: rgba(245, 158, 11, 0.2);
            color: #FCD34D;
        }

        .sidebar-footer {
            padding: 1rem 1.5rem;
            border-top: 1px solid var(--zyro-border-dark);
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }

        .btn-sidebar-logout {
            width: 100%;
            background: transparent;
            border: 1px solid #27272A;
            color: #A1A1AA;
            padding: 0.5rem 0.75rem;
            font-size: 0.75rem;
            font-weight: 600;
            letter-spacing: 0.1em;
            text-transform: uppercase;
            cursor: pointer;
            text-align: center;
            transition: all 0.15s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }

        .btn-sidebar-logout:hover {
            background: #1F1F1F;
            color: #FFFFFF;
            border-color: #3F3F46;
        }

        /* ─── CONTENIDO CENTRAL Y TOPBAR ─── */
        .admin-main {
            flex: 1;
            display: flex;
            flex-direction: column;
            min-width: 0;
            background: var(--zyro-bg);
        }

        .admin-topbar {
            height: 64px;
            background: var(--zyro-white);
            border-bottom: 1px solid var(--zyro-border);
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding-inline: 2rem;
            position: sticky;
            top: 0;
            z-index: 40;
        }

        .topbar-left {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .btn-mobile-toggle {
            display: none;
            background: transparent;
            border: 1px solid var(--zyro-border);
            padding: 0.4rem 0.6rem;
            border-radius: 2px;
            cursor: pointer;
            color: var(--zyro-black);
        }

        .topbar-breadcrumb {
            font-size: 0.8125rem;
            color: var(--zyro-muted);
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .topbar-breadcrumb strong {
            color: var(--zyro-black);
            font-weight: 600;
        }

        .topbar-right {
            display: flex;
            align-items: center;
            gap: 1.25rem;
        }

        .sim-tag {
            font-size: 0.6875rem;
            font-weight: 600;
            letter-spacing: 0.1em;
            text-transform: uppercase;
            padding: 0.25rem 0.6rem;
            background: var(--zyro-amber-bg);
            color: var(--zyro-amber-text);
            border: 1px solid var(--zyro-amber-border);
            border-radius: 2px;
            display: flex;
            align-items: center;
            gap: 0.35rem;
        }

        .user-pill {
            display: flex;
            align-items: center;
            gap: 0.65rem;
            font-size: 0.8125rem;
        }

        .user-avatar {
            width: 32px;
            height: 32px;
            background: var(--zyro-black);
            color: var(--zyro-white);
            font-weight: 700;
            font-size: 0.8125rem;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .user-info {
            display: flex;
            flex-direction: column;
            line-height: 1.2;
        }

        .user-name {
            font-weight: 600;
            color: var(--zyro-black);
        }

        .user-role {
            font-size: 0.6875rem;
            color: var(--zyro-muted);
        }

        .admin-content-area {
            flex: 1;
            padding: 2.25rem 2rem;
            max-width: 1400px;
            width: 100%;
            margin-inline: auto;
        }

        /* ─── COMPONENTES UI: TARJETAS, TABLAS, BOTONES ─── */
        .admin-page-header {
            display: flex;
            flex-wrap: wrap;
            align-items: flex-end;
            justify-content: space-between;
            gap: 1rem;
            margin-bottom: 2rem;
        }

        .admin-kicker {
            font-size: 0.6875rem;
            font-weight: 700;
            letter-spacing: 0.2em;
            text-transform: uppercase;
            color: var(--zyro-muted);
            margin-bottom: 0.35rem;
        }

        .admin-title {
            font-family: 'Cormorant Garamond', Georgia, serif;
            font-size: 2.25rem;
            font-weight: 600;
            line-height: 1.15;
            color: var(--zyro-black);
        }

        .admin-subtitle {
            font-size: 0.875rem;
            color: var(--zyro-muted);
            margin-top: 0.25rem;
        }

        /* Alertas */
        .alert {
            padding: 0.875rem 1.25rem;
            font-size: 0.8125rem;
            border-radius: 2px;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .alert-success {
            background: var(--zyro-green-bg);
            border: 1px solid var(--zyro-green-border);
            color: var(--zyro-green-text);
        }

        .alert-error {
            background: var(--zyro-red-bg);
            border: 1px solid var(--zyro-red-border);
            color: var(--zyro-red-text);
        }

        /* Tarjetas de Métricas */
        .stats-grid-4 {
            display: grid;
            grid-template-columns: repeat(1, 1fr);
            gap: 1.25rem;
            margin-bottom: 2rem;
        }

        @media (min-width: 640px) {
            .stats-grid-4 { grid-template-columns: repeat(2, 1fr); }
        }

        @media (min-width: 1100px) {
            .stats-grid-4 { grid-template-columns: repeat(4, 1fr); }
        }

        .stat-card {
            background: var(--zyro-white);
            border: 1px solid var(--zyro-border);
            padding: 1.5rem;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            transition: box-shadow 0.2s ease, border-color 0.2s ease;
            position: relative;
        }

        .stat-card:hover {
            box-shadow: 0 4px 16px rgba(0, 0, 0, 0.04);
            border-color: #D4D4D8;
        }

        .stat-card.accent-amber { border-top: 3px solid var(--zyro-amber); }
        .stat-card.accent-green { border-top: 3px solid var(--zyro-green); }
        .stat-card.accent-blue { border-top: 3px solid var(--zyro-blue); }
        .stat-card.accent-black { border-top: 3px solid var(--zyro-black); }

        .stat-card-head {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            margin-bottom: 0.75rem;
        }

        .stat-card-label {
            font-size: 0.6875rem;
            font-weight: 700;
            letter-spacing: 0.1em;
            text-transform: uppercase;
            color: var(--zyro-muted);
        }

        .stat-card-icon {
            width: 32px;
            height: 32px;
            border-radius: 4px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.95rem;
        }

        .icon-amber { background: var(--zyro-amber-bg); color: var(--zyro-amber-text); }
        .icon-green { background: var(--zyro-green-bg); color: var(--zyro-green-text); }
        .icon-blue { background: var(--zyro-blue-bg); color: var(--zyro-blue); }
        .icon-black { background: #F4F4F5; color: var(--zyro-black); }

        .stat-card-val {
            font-family: 'Cormorant Garamond', Georgia, serif;
            font-size: 2.5rem;
            font-weight: 700;
            color: var(--zyro-black);
            line-height: 1;
            margin-bottom: 0.5rem;
        }

        .stat-card-desc {
            font-size: 0.75rem;
            color: var(--zyro-muted);
            line-height: 1.4;
        }

        /* Tablas */
        .card-table-wrapper {
            background: var(--zyro-white);
            border: 1px solid var(--zyro-border);
            overflow: hidden;
            margin-bottom: 2rem;
        }

        .card-table-head {
            padding: 1.25rem 1.5rem;
            border-bottom: 1px solid var(--zyro-border);
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
            background: #FAFAFA;
        }

        .card-table-title {
            font-size: 0.9375rem;
            font-weight: 700;
            color: var(--zyro-black);
            letter-spacing: 0.02em;
        }

        .table-responsive {
            width: 100%;
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
        }

        .admin-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.8125rem;
            text-align: left;
        }

        .admin-table th {
            padding: 0.75rem 1.25rem;
            font-size: 0.6875rem;
            font-weight: 700;
            letter-spacing: 0.1em;
            text-transform: uppercase;
            color: var(--zyro-muted);
            background: #FAFAFA;
            border-bottom: 1px solid var(--zyro-border);
            white-space: nowrap;
        }

        .admin-table td {
            padding: 1rem 1.25rem;
            border-bottom: 1px solid var(--zyro-border);
            vertical-align: middle;
            color: var(--zyro-black);
        }

        .admin-table tbody tr:hover {
            background: #FAFAFA;
        }

        .admin-table tbody tr.row-pending {
            background: rgba(254, 243, 199, 0.25);
        }

        .admin-table tbody tr.row-pending:hover {
            background: rgba(254, 243, 199, 0.45);
        }

        /* Badges de Estado */
        .badge-status {
            display: inline-flex;
            align-items: center;
            gap: 0.35rem;
            padding: 0.25rem 0.65rem;
            font-size: 0.6875rem;
            font-weight: 600;
            letter-spacing: 0.05em;
            text-transform: uppercase;
            border-radius: 2px;
            white-space: nowrap;
        }

        .badge-pending {
            background: var(--zyro-amber-bg);
            color: var(--zyro-amber-text);
            border: 1px solid var(--zyro-amber-border);
        }

        .badge-approved {
            background: var(--zyro-blue-bg);
            color: var(--zyro-blue);
            border: 1px solid var(--zyro-blue-border);
        }

        .badge-paid {
            background: var(--zyro-green-bg);
            color: var(--zyro-green-text);
            border: 1px solid var(--zyro-green-border);
        }

        .badge-rejected {
            background: var(--zyro-red-bg);
            color: var(--zyro-red-text);
            border: 1px solid var(--zyro-red-border);
        }

        .badge-cancelled {
            background: #F4F4F5;
            color: #71717A;
            border: 1px solid #E4E4E7;
        }

        /* Botones de acción */
        .btn-action {
            display: inline-flex;
            align-items: center;
            gap: 0.35rem;
            padding: 0.45rem 0.85rem;
            font-size: 0.6875rem;
            font-weight: 600;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            border-radius: 2px;
            border: 1px solid var(--zyro-border);
            background: var(--zyro-white);
            color: var(--zyro-black);
            cursor: pointer;
            transition: all 0.15s ease;
            white-space: nowrap;
        }

        .btn-action:hover {
            border-color: var(--zyro-black);
            background: var(--zyro-black);
            color: var(--zyro-white);
        }

        .btn-action-primary {
            background: var(--zyro-black);
            color: var(--zyro-white);
            border-color: var(--zyro-black);
        }

        .btn-action-primary:hover {
            background: #27272A;
        }

        .btn-action-approve {
            background: var(--zyro-green);
            color: #FFFFFF;
            border-color: var(--zyro-green);
        }

        .btn-action-approve:hover {
            background: #059669;
        }

        .btn-action-reject {
            background: var(--zyro-red);
            color: #FFFFFF;
            border-color: var(--zyro-red);
        }

        .btn-action-reject:hover {
            background: #DC2626;
        }

        /* Filtros y Buscador */
        .filters-toolbar {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
            margin-bottom: 1.5rem;
        }

        .filter-tabs {
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
        }

        .filter-tab {
            padding: 0.5rem 0.85rem;
            font-size: 0.75rem;
            font-weight: 600;
            letter-spacing: 0.05em;
            text-transform: uppercase;
            border: 1px solid var(--zyro-border);
            background: var(--zyro-white);
            color: var(--zyro-muted);
            border-radius: 2px;
            transition: all 0.15s ease;
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
        }

        .filter-tab:hover {
            border-color: var(--zyro-black);
            color: var(--zyro-black);
        }

        .filter-tab.active {
            background: var(--zyro-black);
            color: var(--zyro-white);
            border-color: var(--zyro-black);
        }

        .filter-tab.active .tab-count {
            background: rgba(255, 255, 255, 0.2);
            color: #FFFFFF;
        }

        .tab-count {
            font-size: 0.625rem;
            padding: 0.1rem 0.35rem;
            background: #F4F4F5;
            color: #71717A;
            border-radius: 2px;
        }

        .search-box {
            display: flex;
            align-items: center;
            background: var(--zyro-white);
            border: 1px solid var(--zyro-border);
            border-radius: 2px;
            padding: 0.25rem 0.5rem;
        }

        .search-box input {
            border: none;
            outline: none;
            padding: 0.35rem 0.5rem;
            font-size: 0.8125rem;
            width: 200px;
            font-family: inherit;
        }

        .search-box button {
            background: transparent;
            border: none;
            cursor: pointer;
            padding: 0.25rem 0.5rem;
            font-size: 0.8125rem;
            color: var(--zyro-muted);
        }

        /* Paginación simple */
        .pagination-wrap {
            padding: 1rem 1.5rem;
            border-top: 1px solid var(--zyro-border);
            background: #FAFAFA;
        }

        /* Responsive Mobile */
        @media (max-width: 900px) {
            .admin-sidebar {
                position: fixed;
                left: 0;
                top: 0;
                bottom: 0;
                transform: translateX(-100%);
            }

            .admin-sidebar.open {
                transform: translateX(0);
            }

            .sidebar-overlay {
                display: none;
                position: fixed;
                inset: 0;
                background: rgba(0, 0, 0, 0.5);
                z-index: 45;
            }

            .sidebar-overlay.active {
                display: block;
            }

            .btn-mobile-toggle {
                display: inline-flex;
            }

            .admin-topbar {
                padding-inline: 1rem;
            }

            .admin-content-area {
                padding: 1.5rem 1rem;
            }
        }
    </style>
</head>
<body>
    <div class="admin-layout">
        {{-- Overlay para móvil --}}
        <div id="sidebar-overlay" class="sidebar-overlay" onclick="toggleSidebar()"></div>

        {{-- ─── SIDEBAR FIJO ─── --}}
        <aside id="admin-sidebar" class="admin-sidebar">
            <div class="sidebar-brand">
                <a href="{{ route('admin.dashboard') }}" class="brand-link" aria-label="ZYRO Admin">
                    <img class="brand-logo-image" src="{{ asset('images/zyro-logo.png') }}" alt="ZYRO">
                </a>
                <span class="brand-badge">ADMIN</span>
            </div>

            <nav class="sidebar-nav" aria-label="Navegación administrativa">
                <div class="nav-section-title">Principal</div>

                {{-- 2. Dashboard --}}
                <a href="{{ route('admin.dashboard') }}" class="sidebar-link {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">
                    <span class="sidebar-link-inner">
                        <span class="sidebar-link-icon">📊</span>
                        <span>Dashboard</span>
                    </span>
                </a>

                <div class="nav-section-title">Operaciones</div>

                {{-- 3. Pedidos --}}
                <a href="{{ route('admin.orders.index') }}" class="sidebar-link {{ request()->routeIs('admin.orders.*') && !request('status') ? 'active' : '' }}">
                    <span class="sidebar-link-inner">
                        <span class="sidebar-link-icon">📦</span>
                        <span>Todos los Pedidos</span>
                    </span>
                </a>

                {{-- 4. Pendientes de aprobación --}}
                <a href="{{ route('admin.orders.index', ['status' => 'pending_approval']) }}" class="sidebar-link {{ request('status') === 'pending_approval' ? 'active' : '' }}">
                    <span class="sidebar-link-inner">
                        <span class="sidebar-link-icon">⏳</span>
                        <span>Pendientes</span>
                    </span>
                    @php
                        $pendingQuickCount = \App\Models\Order::query()->where('status', \App\Enums\OrderStatus::PendingApproval)->count();
                    @endphp
                    @if ($pendingQuickCount > 0)
                        <span class="sidebar-count amber">{{ $pendingQuickCount }}</span>
                    @endif
                </a>

                {{-- 5. Pedidos aprobados --}}
                <a href="{{ route('admin.orders.index', ['status' => 'approved']) }}" class="sidebar-link {{ request('status') === 'approved' ? 'active' : '' }}">
                    <span class="sidebar-link-inner">
                        <span class="sidebar-link-icon">✓</span>
                        <span>Aprobados</span>
                    </span>
                </a>

                {{-- 6. Pedidos pagados --}}
                <a href="{{ route('admin.orders.index', ['status' => 'paid']) }}" class="sidebar-link {{ request('status') === 'paid' ? 'active' : '' }}">
                    <span class="sidebar-link-inner">
                        <span class="sidebar-link-icon">💳</span>
                        <span>Pagados</span>
                    </span>
                </a>

                {{-- 7. Pedidos rechazados --}}
                <a href="{{ route('admin.orders.index', ['status' => 'rejected']) }}" class="sidebar-link {{ request('status') === 'rejected' ? 'active' : '' }}">
                    <span class="sidebar-link-inner">
                        <span class="sidebar-link-icon">✕</span>
                        <span>Rechazados</span>
                    </span>
                </a>

                <div class="nav-section-title">Catálogo</div>

                {{-- 8. Inventario --}}
                <a href="{{ route('admin.inventory.index') }}" class="sidebar-link {{ request()->routeIs('admin.inventory.*') ? 'active' : '' }}">
                    <span class="sidebar-link-inner">
                        <span class="sidebar-link-icon">🏷️</span>
                        <span>Inventario</span>
                    </span>
                </a>

                <div class="nav-section-title">Navegación</div>

                {{-- 9. Volver a la tienda --}}
                <a href="{{ route('home') }}" class="sidebar-link" target="_blank" rel="noopener">
                    <span class="sidebar-link-inner">
                        <span class="sidebar-link-icon">🏪</span>
                        <span>Ver Tienda</span>
                    </span>
                    <span style="font-size:0.75rem; color:#71717A;">&nearr;</span>
                </a>
            </nav>

            {{-- 10. Cerrar sesión --}}
            <div class="sidebar-footer">
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="btn-sidebar-logout">
                        <span>Cerrar sesión</span>
                        <span>&rarr;</span>
                    </button>
                </form>
            </div>
        </aside>

        {{-- ─── MAIN WRAPPER ─── --}}
        <div class="admin-main">
            {{-- Topbar --}}
            <header class="admin-topbar">
                <div class="topbar-left">
                    <button class="btn-mobile-toggle" onclick="toggleSidebar()" aria-label="Abrir menú">
                        ☰
                    </button>
                    <nav class="topbar-breadcrumb" aria-label="Miga de pan">
                        <span>ZYRO Admin</span>
                        <span>/</span>
                        <strong>@yield('section_title', 'Dashboard')</strong>
                    </nav>
                </div>

                <div class="topbar-right">
                    <div class="sim-tag" style="background:#F4F4F5; border:1px solid #E4E4E7; color:#71717A;">
                        <span>Entorno de Pruebas</span>
                    </div>

                    <div class="user-pill">
                        <div class="user-avatar">
                            {{ strtoupper(substr(auth()->user()->name ?? 'A', 0, 1)) }}
                        </div>
                        <div class="user-info">
                            <span class="user-name">{{ auth()->user()->name ?? 'Administrador' }}</span>
                            <span class="user-role">Administrador</span>
                        </div>
                    </div>
                </div>
            </header>

            {{-- Área de Contenido --}}
            <main class="admin-content-area">
                @if (session('status'))
                    <div class="alert alert-success" role="alert">
                        <span>✓</span>
                        <span>{{ session('status') }}</span>
                    </div>
                @endif

                @if ($errors->any())
                    <div class="alert alert-error" role="alert">
                        <span>⚠</span>
                        <div>
                            @foreach ($errors->all() as $err)
                                <p>{{ $err }}</p>
                            @endforeach
                        </div>
                    </div>
                @endif

                @yield('content')
            </main>
        </div>
    </div>

    <script>
        function toggleSidebar() {
            const sidebar = document.getElementById('admin-sidebar');
            const overlay = document.getElementById('sidebar-overlay');
            if (sidebar && overlay) {
                sidebar.classList.toggle('open');
                overlay.classList.toggle('active');
            }
        }
    </script>
    @stack('scripts')
</body>
</html>
