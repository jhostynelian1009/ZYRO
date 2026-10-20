@extends('layouts.admin')

@section('title', 'Analítica y Mapas de Calor — ZYRO Admin')

@section('content')
<div class="admin-page-header" style="margin-bottom:2rem;">
    <div style="display:flex; flex-wrap:wrap; justify-content:space-between; align-items:flex-start; gap:1.25rem;">
        <div>
            <div class="admin-kicker">Comportamiento &bull; Métricas Reales &bull; Heatmaps</div>
            <h1 class="admin-title">Analítica y Mapas de Calor</h1>
            <p class="admin-subtitle">
                Seguimiento de interacción de usuarios, embudo comercial con datos reales y panel Microsoft Clarity.
            </p>
        </div>

        <div style="display:flex; align-items:center; gap:0.75rem; flex-wrap:wrap;">
            @if (filled($clarityProjectId))
                <a
                    href="{{ $clarityDashboardUrl }}"
                    target="_blank"
                    rel="noopener noreferrer"
                    class="btn-action btn-action-primary"
                    style="background:#0055FF; border-color:#0055FF; padding:0.65rem 1.25rem; font-size:0.75rem; font-weight:700; letter-spacing:0.08em; text-transform:uppercase;"
                >
                    <span>Abrir Microsoft Clarity</span>
                    <span style="font-size:0.875rem;">&nearr;</span>
                </a>
            @else
                <span class="badge-status badge-pending" style="padding:0.5rem 0.85rem; font-size:0.6875rem;">
                    ⏳ Clarity: Variable CLARITY_PROJECT_ID pendiente
                </span>
            @endif
        </div>
    </div>
</div>

{{-- ─── 1. INTEGRACIÓN MICROSOFT CLARITY Y PRIVACIDAD ─── --}}
<div style="background:var(--zyro-white); border:1px solid var(--zyro-border); border-left:4px solid {{ filled($clarityProjectId) ? '#0055FF' : '#F59E0B' }}; padding:1.25rem 1.5rem; margin-bottom:2rem; border-radius:2px;">
    <div style="display:flex; flex-wrap:wrap; justify-content:space-between; align-items:center; gap:1rem;">
        <div>
            <div style="display:flex; align-items:center; gap:0.5rem; margin-bottom:0.35rem;">
                <strong style="font-size:0.875rem; text-transform:uppercase; letter-spacing:0.05em; color:var(--zyro-black);">
                    Integración Microsoft Clarity
                </strong>
                @if (filled($clarityProjectId))
                    <span class="badge-status badge-approved" style="font-size:0.625rem;">ACTIVO (ID: {{ $clarityProjectId }})</span>
                @else
                    <span class="badge-status badge-pending" style="font-size:0.625rem;">NO CONFIGURADO</span>
                @endif
            </div>
            <p style="font-size:0.8125rem; color:var(--zyro-muted); max-width:820px; line-height:1.5;">
                @if (filled($clarityProjectId))
                    El script de Clarity se carga de forma <strong>asíncrona</strong> exclusivamente en las páginas públicas del catálogo (Home, Catálogo, Ficha de producto y Personalizador). Las áreas sensibles (Checkout, Pagos, Iniciar Sesión, Registro y este Panel de Administración) están <strong>completamente aisladas</strong> de cualquier sistema de seguimiento para proteger la privacidad de contraseñas y datos bancarios.
                @else
                    Para activar grabaciones de sesión continuas y mapas de calor dinámicos con Microsoft Clarity, configura la variable de entorno <code>CLARITY_PROJECT_ID</code> en Railway con el ID real proporcionado por Microsoft Clarity.
                @endif
            </p>
        </div>

        @if (filled($clarityProjectId))
            <div style="display:flex; gap:0.5rem;">
                <a
                    href="https://clarity.microsoft.com/projects/view/{{ $clarityProjectId }}/heatmaps"
                    target="_blank"
                    rel="noopener noreferrer"
                    class="filter-tab"
                    style="font-size:0.6875rem;"
                >
                    🔥 Ver Heatmaps en Clarity &nearr;
                </a>
                <a
                    href="https://clarity.microsoft.com/projects/view/{{ $clarityProjectId }}/recordings"
                    target="_blank"
                    rel="noopener noreferrer"
                    class="filter-tab"
                    style="font-size:0.6875rem;"
                >
                    🎥 Ver Grabaciones &nearr;
                </a>
            </div>
        @endif
    </div>
</div>

{{-- ─── 2. TARJETAS DE MÉTRICAS REALES ─── --}}
<div class="stats-grid-4" style="margin-bottom:1.5rem;">
    <div class="stat-card accent-blue">
        <div class="stat-card-head">
            <span class="stat-card-label">Vistas de Producto</span>
            <span style="font-size:1.25rem;">👁️</span>
        </div>
        <div class="stat-card-value" style="font-size:2rem; font-weight:700; color:var(--zyro-black);">
            {{ number_format($productViewsCount) }}
        </div>
        <div class="stat-card-desc" style="font-size:0.75rem; color:var(--zyro-muted); margin-top:0.25rem;">
            Registros reales de navegación
        </div>
    </div>

    <div class="stat-card accent-amber">
        <div class="stat-card-head">
            <span class="stat-card-label">Añadidos al Carrito</span>
            <span style="font-size:1.25rem;">🛒</span>
        </div>
        <div class="stat-card-value" style="font-size:2rem; font-weight:700; color:var(--zyro-black);">
            {{ number_format($addToCartCount) }}
        </div>
        <div class="stat-card-desc" style="font-size:0.75rem; color:var(--zyro-muted); margin-top:0.25rem;">
            Prendas seleccionadas por clientes
        </div>
    </div>

    <div class="stat-card accent-green">
        <div class="stat-card-head">
            <span class="stat-card-label">Inicios de Checkout</span>
            <span style="font-size:1.25rem;">💳</span>
        </div>
        <div class="stat-card-value" style="font-size:2rem; font-weight:700; color:var(--zyro-black);">
            {{ number_format($beginCheckoutCount) }}
        </div>
        <div class="stat-card-desc" style="font-size:0.75rem; color:var(--zyro-muted); margin-top:0.25rem;">
            Formularios de entrega abiertos
        </div>
    </div>

    <div class="stat-card accent-black">
        <div class="stat-card-head">
            <span class="stat-card-label">Compras Pagadas</span>
            <span style="font-size:1.25rem;">✓</span>
        </div>
        <div class="stat-card-value" style="font-size:2rem; font-weight:700; color:var(--zyro-black);">
            {{ number_format($paidOrdersCount) }}
        </div>
        <div class="stat-card-desc" style="font-size:0.75rem; color:var(--zyro-muted); margin-top:0.25rem;">
            Recaudación real: <strong>${{ number_format($totalPaidRevenue, 2) }}</strong>
        </div>
    </div>
</div>

{{-- ─── 3. EMBUDO DE CONVERSIÓN REAL ─── --}}
<div style="background:var(--zyro-white); border:1px solid var(--zyro-border); padding:1.75rem; margin-bottom:2rem; border-radius:2px;">
    <div style="display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:1.5rem; flex-wrap:wrap; gap:1rem;">
        <div>
            <h3 style="font-family:'Cormorant Garamond', Georgia, serif; font-size:1.5rem; color:var(--zyro-black); margin-bottom:0.25rem;">
                Embudo de Conversión Comercial (Datos Reales)
            </h3>
            <p style="font-size:0.8125rem; color:var(--zyro-muted);">
                Flujo paso a paso desde el descubrimiento de la prenda hasta la liquidación de la orden en ZYRO.
            </p>
        </div>

        <div style="display:flex; gap:1.5rem; font-size:0.75rem;">
            <div>
                <span style="color:var(--zyro-muted); display:block;">Conversión Global (Vistas &rarr; Pago):</span>
                <strong style="font-size:1.15rem; color:var(--zyro-black);">{{ $globalConversionRate }}%</strong>
            </div>
            <div>
                <span style="color:var(--zyro-muted); display:block;">Carrito &rarr; Pago:</span>
                <strong style="font-size:1.15rem; color:#0055FF;">{{ $cartConversionRate }}%</strong>
            </div>
        </div>
    </div>

    @if ($productViewsCount === 0 && $ordersCount === 0)
        <div style="background:#F9F9FB; border:1px dashed #D4D4D8; padding:2.5rem; text-align:center; color:var(--zyro-muted);">
            <div style="font-size:2rem; margin-bottom:0.5rem;">📊</div>
            <strong style="display:block; font-size:0.9375rem; color:var(--zyro-black); margin-bottom:0.25rem;">
                Embudo comercial en espera de interacciones
            </strong>
            <p style="font-size:0.8125rem; max-width:480px; margin-inline:auto;">
                Conforme los visitantes interactúen con el catálogo público y completen compras, cada barra representará de forma exacta el porcentaje de retención en cada etapa.
            </p>
        </div>
    @else
        <div style="display:flex; flex-direction:column; gap:1rem;">
            @php
                $maxFunnel = max(1, $productViewsCount, $ordersCount);
            @endphp
            @foreach ($funnel as $step)
                @php
                    $barWidth = $maxFunnel > 0 ? max(4, round(($step['count'] / $maxFunnel) * 100)) : 0;
                @endphp
                <div>
                    <div style="display:flex; justify-content:space-between; font-size:0.8125rem; margin-bottom:0.35rem;">
                        <div>
                            <span style="font-weight:700; color:var(--zyro-black);">Paso {{ $step['step'] }}: {{ $step['name'] }}</span>
                            <span style="color:var(--zyro-muted); font-size:0.75rem; margin-left:0.5rem;">&bull; {{ $step['description'] }}</span>
                        </div>
                        <div style="font-weight:700; color:var(--zyro-black);">
                            {{ number_format($step['count']) }}
                            @if ($productViewsCount > 0)
                                <span style="font-size:0.75rem; color:var(--zyro-muted); font-weight:400; margin-left:0.35rem;">({{ $step['pct_base'] }}%)</span>
                            @endif
                        </div>
                    </div>
                    <div style="width:100%; height:12px; background:#F4F4F5; border-radius:2px; overflow:hidden;">
                        <div style="width:{{ $barWidth }}%; height:100%; background:{{ $step['step'] === 5 ? '#10B981' : ($step['step'] === 1 ? '#0055FF' : '#18181B') }}; transition:width 0.5s ease;"></div>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>

{{-- ─── 4. RANKING DE PRODUCTOS MÁS VISITADOS (DATOS REALES) ─── --}}
<div style="background:var(--zyro-white); border:1px solid var(--zyro-border); margin-bottom:2rem; border-radius:2px; overflow:hidden;">
    <div style="padding:1.25rem 1.5rem; border-bottom:1px solid var(--zyro-border); display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:0.75rem;">
        <div>
            <h3 style="font-family:'Cormorant Garamond', Georgia, serif; font-size:1.375rem; color:var(--zyro-black);">
                Rendimiento de los 8 Modelos ZYRO
            </h3>
            <p style="font-size:0.75rem; color:var(--zyro-muted);">
                Datos consolidados de vistas, añadidos al carrito y compras pagadas por producto.
            </p>
        </div>

        <span class="badge-status badge-approved" style="font-size:0.625rem;">DATOS 100% REALES EN BASE DE DATOS</span>
    </div>

    <div style="overflow-x:auto;">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Modelo</th>
                    <th>Línea</th>
                    <th>Precio</th>
                    <th style="text-align:right;">Vistas</th>
                    <th style="text-align:right;">Al Carrito</th>
                    <th style="text-align:right;">Tasa Carrito</th>
                    <th style="text-align:right;">Compras Pagadas</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($productRankings as $row)
                    @php $p = $row['product']; @endphp
                    <tr>
                        <td>
                            <div style="display:flex; align-items:center; gap:0.75rem;">
                                <div style="width:52px; height:36px; background:#F4F4F5; border:1px solid #E4E4E7; border-radius:2px; display:flex; align-items:center; justify-content:center; overflow:hidden; flex-shrink:0;">
                                    <img src="{{ $p->imageUrl() }}" alt="{{ $p->name }}" style="width:100%; height:100%; object-fit:contain;">
                                </div>
                                <div>
                                    <strong style="color:var(--zyro-black);">{{ $p->name }}</strong>
                                    <div style="font-size:0.6875rem; font-family:monospace; color:var(--zyro-muted);">{{ $p->slug }}</div>
                                </div>
                            </div>
                        </td>
                        <td>
                            @if ($p->hasTechnology())
                                <span class="badge-status badge-approved">TECH ($370)</span>
                            @else
                                <span class="badge-status" style="background:#F4F4F5; color:#52525B; border:1px solid #E4E4E7;">ESENCIAL ($80)</span>
                            @endif
                        </td>
                        <td style="font-weight:700;">{{ $p->formattedPrice() }}</td>
                        <td style="text-align:right; font-weight:700;">{{ number_format($row['views']) }}</td>
                        <td style="text-align:right; font-weight:600;">{{ number_format($row['cart_adds']) }}</td>
                        <td style="text-align:right; color:var(--zyro-muted);">{{ $row['cart_rate'] }}%</td>
                        <td style="text-align:right; font-weight:700; color:{{ $row['purchases'] > 0 ? '#10B981' : 'var(--zyro-muted)' }};">
                            {{ number_format($row['purchases']) }}
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>

{{-- ─── 5. VISUALIZACIÓN DE MAPA DE CALOR: MODO REAL VS MODO DEMOSTRACIÓN ─── --}}
<div style="background:var(--zyro-white); border:1px solid var(--zyro-border); padding:1.75rem; margin-bottom:2rem; border-radius:2px;">
    <div style="display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:1.5rem; flex-wrap:wrap; gap:1rem;">
        <div>
            <div style="display:flex; align-items:center; gap:0.5rem; margin-bottom:0.25rem;">
                <h3 style="font-family:'Cormorant Garamond', Georgia, serif; font-size:1.5rem; color:var(--zyro-black);">
                    Visualizador de Mapa de Calor de ZYRO
                </h3>
            </div>
            <p style="font-size:0.8125rem; color:var(--zyro-muted);">
                Representación visual de fricción y áreas de atención sobre las tarjetas oficiales del catálogo.
            </p>
        </div>

        {{-- Selector de Modo --}}
        <div style="display:flex; border:1px solid var(--zyro-border); border-radius:2px; overflow:hidden; background:var(--zyro-light);">
            <button
                type="button"
                id="btn-mode-real"
                onclick="setHeatmapMode('real')"
                style="padding:0.5rem 1rem; font-size:0.75rem; font-weight:700; border:none; cursor:pointer; background:var(--zyro-black); color:#FFFFFF; text-transform:uppercase; letter-spacing:0.05em; transition:all 0.15s;"
            >
                ✓ Datos Reales Registrados
            </button>
            <button
                type="button"
                id="btn-mode-demo"
                onclick="setHeatmapMode('demo')"
                style="padding:0.5rem 1rem; font-size:0.75rem; font-weight:700; border:none; cursor:pointer; background:transparent; color:var(--zyro-muted); text-transform:uppercase; letter-spacing:0.05em; transition:all 0.15s;"
            >
                🧪 Modo Demostración (Ilustrativo)
            </button>
        </div>
    </div>

    {{-- Banner de Modo Demostración (Oculto por defecto) --}}
    <div id="demo-disclaimer-banner" style="display:none; background:#FFFBEB; border:1px solid #FDE68A; border-left:4px solid #F59E0B; padding:0.875rem 1.25rem; margin-bottom:1.5rem; border-radius:2px;">
        <div style="display:flex; align-items:center; gap:0.6rem;">
            <span style="font-size:1.25rem;">⚠️</span>
            <div>
                <strong style="font-size:0.8125rem; text-transform:uppercase; letter-spacing:0.05em; color:#92400E;">
                    MODO DEMOSTRACIÓN — DATOS ILUSTRATIVOS PARA PRESENTACIÓN
                </strong>
                <p style="font-size:0.75rem; color:#B45309; margin-top:0.15rem;">
                    Este mapa ilustra de manera pedagógica cómo se concentran las zonas de calor (clics en "Detalles", "Personalizar" y filtros). <strong>No representa actividad real de clientes</strong>.
                </p>
            </div>
        </div>
    </div>

    {{-- Vista Modo Real: Estado Vacío o Densidad Real --}}
    <div id="heatmap-view-real">
        @if (!$hasRealInteractions)
            <div style="background:#F9F9FB; border:1px dashed #D4D4D8; padding:3rem 2rem; text-align:center; color:var(--zyro-muted); border-radius:2px;">
                <div style="font-size:2.5rem; margin-bottom:0.75rem;">🔥</div>
                <h4 style="font-family:'Cormorant Garamond', Georgia, serif; font-size:1.35rem; color:var(--zyro-black); margin-bottom:0.35rem;">
                    Aún no hay suficientes interacciones registradas
                </h4>
                <p style="font-size:0.8125rem; max-width:540px; margin-inline:auto; line-height:1.6; margin-bottom:1.25rem;">
                    Conforme los clientes naveguen por la tienda pública, abran fichas de producto y añadan chompas al carrito, las zonas de calor se calcularán y mapearán automáticamente aquí sobre cada modelo.
                </p>
                <div style="display:flex; justify-content:center; gap:0.75rem;">
                    <button type="button" onclick="setHeatmapMode('demo')" class="filter-tab active" style="font-size:0.75rem;">
                        Ver simulación en Modo Demostración &rarr;
                    </button>
                    @if (filled($clarityProjectId))
                        <a href="{{ $clarityDashboardUrl }}" target="_blank" rel="noopener noreferrer" class="filter-tab" style="font-size:0.75rem;">
                            Abrir Heatmaps en vivo en Clarity &nearr;
                        </a>
                    @endif
                </div>
            </div>
        @else
            {{-- Grid con Densidad Real Registrada --}}
            <div style="display:grid; grid-template-columns:repeat(auto-fill, minmax(240px, 1fr)); gap:1.25rem;">
                @foreach ($productRankings as $row)
                    @php
                        $p = $row['product'];
                        $intensity = min(100, $row['interaction_score'] * 15);
                    @endphp
                    <div style="border:1px solid var(--zyro-border); background:var(--zyro-white); padding:1rem; position:relative; overflow:hidden;">
                        <div style="aspect-ratio:16/11; background:#F4F4F5; display:flex; align-items:center; justify-content:center; margin-bottom:0.75rem; position:relative;">
                            <img src="{{ $p->imageUrl() }}" alt="{{ $p->name }}" style="width:100%; height:100%; object-fit:contain;">
                            {{-- Indicador de calor real --}}
                            <div style="position:absolute; bottom:0.5rem; right:0.5rem; background:rgba(0,0,0,0.85); color:#fff; font-size:0.625rem; font-weight:700; padding:0.2rem 0.45rem; border-radius:2px;">
                                {{ $row['views'] }} vistas &bull; {{ $row['clicks'] }} clics
                            </div>
                        </div>
                        <div style="font-size:0.875rem; font-weight:700; margin-bottom:0.25rem;">{{ $p->name }}</div>
                        <div style="display:flex; justify-content:space-between; font-size:0.75rem; color:var(--zyro-muted);">
                            <span>{{ $p->formattedPrice() }}</span>
                            <span style="font-weight:600; color:var(--zyro-black);">Score: {{ $row['interaction_score'] }}</span>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>

    {{-- Vista Modo Demostración (Ilustrativo) --}}
    <div id="heatmap-view-demo" style="display:none;">
        <div style="display:grid; grid-template-columns:repeat(auto-fill, minmax(260px, 1fr)); gap:1.25rem;">
            @php
                $demoHotspots = [
                    ['name' => 'ZYRO Lunar Tech', 'img' => 'images/products/lunar-tech-source.png', 'views' => 248, 'hot' => 'Extrema', 'hot_color' => '#EF4444', 'details_pct' => '84%', 'cust_pct' => '62%'],
                    ['name' => 'ZYRO Eclipse Tech', 'img' => 'images/products/eclipse-tech-source.png', 'views' => 192, 'hot' => 'Alta', 'hot_color' => '#F59E0B', 'details_pct' => '71%', 'cust_pct' => '54%'],
                    ['name' => 'ZYRO Nova Tech', 'img' => 'images/products/tech/nova-tech.jpg', 'views' => 143, 'hot' => 'Media', 'hot_color' => '#0055FF', 'details_pct' => '58%', 'cust_pct' => '41%'],
                    ['name' => 'ZYRO Orbit Tech', 'img' => 'images/products/tech/orbit-tech.jpg', 'views' => 118, 'hot' => 'Media', 'hot_color' => '#0055FF', 'details_pct' => '52%', 'cust_pct' => '38%'],
                    ['name' => 'ZYRO Essential', 'img' => 'images/products/essential-beige-source.png', 'views' => 165, 'hot' => 'Alta', 'hot_color' => '#F59E0B', 'details_pct' => '68%', 'cust_pct' => '49%'],
                    ['name' => 'ZYRO Pure', 'img' => 'images/products/soft-pink-source.png', 'views' => 134, 'hot' => 'Media', 'hot_color' => '#0055FF', 'details_pct' => '55%', 'cust_pct' => '36%'],
                    ['name' => 'ZYRO Classic', 'img' => 'images/products/classic-gray-source.png', 'views' => 98, 'hot' => 'Baja', 'hot_color' => '#10B981', 'details_pct' => '42%', 'cust_pct' => '28%'],
                    ['name' => 'ZYRO Soft', 'img' => 'images/products/essential/soft.jpg', 'views' => 87, 'hot' => 'Baja', 'hot_color' => '#10B981', 'details_pct' => '39%', 'cust_pct' => '25%'],
                ];
            @endphp
            @foreach ($demoHotspots as $demo)
                <div style="border:2px solid {{ $demo['hot_color'] }}; background:var(--zyro-white); padding:1rem; position:relative; box-shadow:0 4px 16px rgba(0,0,0,0.06); border-radius:2px;">
                    <div style="position:absolute; top:0.5rem; right:0.5rem; background:{{ $demo['hot_color'] }}; color:#FFFFFF; font-size:0.5625rem; font-weight:700; padding:0.2rem 0.5rem; border-radius:2px; letter-spacing:0.08em; text-transform:uppercase; z-index:3;">
                        ZONA {{ $demo['hot'] }} (DEMO)
                    </div>

                    <div style="aspect-ratio:16/11; background:#F4F4F5; display:flex; align-items:center; justify-content:center; margin-bottom:0.75rem; position:relative; overflow:hidden;">
                        <img src="{{ asset($demo['img']) }}" alt="{{ $demo['name'] }}" style="width:100%; height:100%; object-fit:contain;">
                        {{-- Mancha de calor ilustrativa (Radial Gradient) --}}
                        <div style="position:absolute; inset:0; background:radial-gradient(circle at 60% 70%, rgba(239, 68, 68, 0.45) 0%, rgba(245, 158, 11, 0.25) 40%, transparent 70%); pointer-events:none;"></div>
                    </div>

                    <div style="font-size:0.875rem; font-weight:700; margin-bottom:0.5rem;">{{ $demo['name'] }}</div>

                    {{-- Concentración de Clics Ilustrativa --}}
                    <div style="display:grid; grid-template-columns:1fr 1fr; gap:0.5rem; font-size:0.6875rem; text-align:center;">
                        <div style="background:#FEF2F2; border:1px solid #FECACA; padding:0.35rem; border-radius:2px;">
                            <span style="display:block; color:#991B1B; font-weight:700;">Detalles</span>
                            <span style="color:#DC2626;">{{ $demo['details_pct'] }} clics</span>
                        </div>
                        <div style="background:#EFF3FC; border:1px solid #C7D7F8; padding:0.35rem; border-radius:2px;">
                            <span style="display:block; color:#0055FF; font-weight:700;">Personalizar</span>
                            <span style="color:#315DDB;">{{ $demo['cust_pct'] }} clics</span>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</div>

{{-- Script de Alternancia Real / Demostración --}}
<script>
    function setHeatmapMode(mode) {
        var realBtn = document.getElementById('btn-mode-real');
        var demoBtn = document.getElementById('btn-mode-demo');
        var realView = document.getElementById('heatmap-view-real');
        var demoView = document.getElementById('heatmap-view-demo');
        var banner = document.getElementById('demo-disclaimer-banner');

        if (mode === 'demo') {
            realBtn.style.background = 'transparent';
            realBtn.style.color = 'var(--zyro-muted)';
            demoBtn.style.background = '#0F0F11';
            demoBtn.style.color = '#FFFFFF';
            realView.style.display = 'none';
            demoView.style.display = 'block';
            banner.style.display = 'block';
        } else {
            realBtn.style.background = '#0F0F11';
            realBtn.style.color = '#FFFFFF';
            demoBtn.style.background = 'transparent';
            demoBtn.style.color = 'var(--zyro-muted)';
            realView.style.display = 'block';
            demoView.style.display = 'none';
            banner.style.display = 'none';
        }
    }
</script>
@endsection
