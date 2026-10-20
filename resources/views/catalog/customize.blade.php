@extends('layouts.store')

@section('title', 'Personaliza tu chompa — '.$product->name.' — ZYRO')

@section('content')
@php
    $slug = $product->slug;
    $isTech = $product->hasTechnology();

    // Silueta de prenda según el modelo oficial
    if (str_contains($slug, 'classic')) {
        $garmentType = 'crewneck';
        $garmentTitle = 'Chompa Crewneck Cuello Redondo';
        $garmentSpec = 'Algodón pesado 400 GSM · Hombro caído · Canalé reforzado';
    } elseif (str_contains($slug, 'lunar') || str_contains($slug, 'nova')) {
        $garmentType = 'tech_collar';
        $garmentTitle = 'Chompa Técnica con Cuello Ergonómico';
        $garmentSpec = 'Tejido bio-reactivo · Cremallera técnica 1/4 · Paneles térmicos';
    } else {
        $garmentType = 'hoodie';
        $garmentTitle = 'Chompa Hoodie con Capucha & Bolsillo';
        $garmentSpec = 'Capucha doble estructurada · Cordones con puntera · Bolsillo canguro';
    }

    $colorMap = [
        'black' => ['name' => 'Negro Carbón', 'hex' => '#141416', 'accent' => '#26262b', 'shadow' => '#08080a'],
        'white' => ['name' => 'Blanco Crudo', 'hex' => '#F4F4F0', 'accent' => '#FFFFFF', 'shadow' => '#D5D5CE'],
        'gray'  => ['name' => 'Gris Melange', 'hex' => '#656872', 'accent' => '#7A7E8A', 'shadow' => '#484B54'],
        'navy'  => ['name' => 'Azul Navy',    'hex' => '#18243C', 'accent' => '#26375A', 'shadow' => '#0D1424'],
        'red'   => ['name' => 'Rojo Borgoña', 'hex' => '#871B26', 'accent' => '#A32431', 'shadow' => '#560F17'],
        'beige' => ['name' => 'Beige Arena',  'hex' => '#D4C4B0', 'accent' => '#E5D8C7', 'shadow' => '#B09E88'],
    ];
@endphp

<div class="container" style="padding-block: 2rem 4rem;">

    {{-- Breadcrumb y Cabecera Editorial --}}
    <div style="margin-bottom: 2rem;">
        <div style="display:flex; align-items:center; gap:0.5rem; font-size:0.6875rem; font-weight:700; letter-spacing:0.12em; text-transform:uppercase; color:var(--zyro-muted); margin-bottom:0.5rem;">
            <a href="{{ route('catalog.index') }}" style="color:var(--zyro-muted);">Colección</a>
            <span>&rsaquo;</span>
            <a href="{{ route('catalog.show', $product) }}" style="color:var(--zyro-muted);">{{ $product->name }}</a>
            <span>&rsaquo;</span>
            <span style="color:var(--zyro-black);">Personalizador Oficial</span>
        </div>
        <div style="display:flex; justify-content:space-between; align-items:flex-end; flex-wrap:wrap; gap:1rem;">
            <div>
                <h1 class="font-serif" style="font-size: clamp(2rem, 4vw, 3rem); line-height: 1.1; color:var(--zyro-black);">
                    Personaliza tu chompa
                </h1>
                <p style="color:var(--zyro-muted); font-size:0.875rem; margin-top:0.35rem;">
                    Modelo base: <strong style="color:var(--zyro-black);">{{ $product->name }}</strong> ·
                    Precio final: <strong style="color:var(--zyro-black);">{{ $product->formattedPrice() }}</strong> ·
                    <span style="color:#059669; font-weight:600;">Personalización incluida sin recargo</span>
                </p>
            </div>
            <div style="display:flex; gap:0.75rem;">
                <button type="button" id="btn-toggle-ref-photo" class="btn-secondary" style="font-size:0.6875rem; padding:0.5rem 0.85rem; display:inline-flex; align-items:center; gap:0.4rem;">
                    <span>📷</span> Ver fotografía de catálogo
                </button>
            </div>
        </div>
    </div>

    {{-- Modal flotante de foto de referencia --}}
    <div id="modal-ref-photo" style="display:none; position:fixed; inset:0; z-index:9999; background:rgba(0,0,0,0.65); backdrop-filter:blur(4px); align-items:center; justify-content:center; padding:1.5rem;">
        <div style="background:#fff; max-width:480px; width:100%; border:1px solid var(--zyro-border); border-radius:2px; overflow:hidden; box-shadow:0 20px 40px rgba(0,0,0,0.25);">
            <div style="display:flex; justify-content:space-between; align-items:center; padding:1rem 1.25rem; border-bottom:1px solid var(--zyro-border);">
                <div>
                    <span style="font-size:0.6875rem; font-weight:700; letter-spacing:0.1em; text-transform:uppercase; color:var(--zyro-muted);">Referencia de Catálogo</span>
                    <h3 style="font-family:'Cormorant Garamond',Georgia,serif; font-size:1.25rem; font-weight:600; color:var(--zyro-black);">{{ $product->name }}</h3>
                </div>
                <button type="button" id="btn-close-ref-photo" style="background:none; border:none; font-size:1.5rem; cursor:pointer; line-height:1; color:var(--zyro-muted); padding:0.25rem;">&times;</button>
            </div>
            <div style="background:var(--zyro-light); padding:1.5rem; text-align:center;">
                <img src="{{ $product->imageUrl() }}" alt="{{ $product->name }}" style="max-height:360px; margin:0 auto; object-fit:contain; filter:drop-shadow(0 8px 16px rgba(0,0,0,0.08));">
            </div>
            <div style="padding:1rem 1.25rem; font-size:0.75rem; color:var(--zyro-muted); background:#FAFAFA; border-top:1px solid var(--zyro-border); line-height:1.5;">
                {{ $product->description }}
            </div>
        </div>
    </div>

    <style>
        .customizer-layout {
            display: grid;
            grid-template-columns: 1fr;
            gap: 2.5rem;
            align-items: start;
        }
        @media (min-width: 980px) {
            .customizer-layout {
                grid-template-columns: 1.15fr 0.85fr;
            }
        }

        /* ─── VISOR DE LA PRENDA ─── */
        .preview-stage-card {
            background: #F7F7F8;
            border: 1px solid var(--zyro-border);
            border-radius: 2px;
            overflow: hidden;
            position: sticky;
            top: 88px;
            display: flex;
            flex-direction: column;
        }

        .preview-toolbar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0.875rem 1.25rem;
            background: #FFFFFF;
            border-bottom: 1px solid var(--zyro-border);
            gap: 0.75rem;
            flex-wrap: wrap;
        }

        .view-pill-group {
            display: inline-flex;
            background: #F1F1F3;
            padding: 3px;
            border-radius: 2px;
            border: 1px solid var(--zyro-border);
        }

        .view-pill-btn {
            background: transparent;
            border: none;
            padding: 0.35rem 0.85rem;
            font-size: 0.6875rem;
            font-weight: 700;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            color: var(--zyro-muted);
            cursor: pointer;
            border-radius: 2px;
            transition: all 0.15s ease;
        }

        .view-pill-btn.active {
            background: var(--zyro-black);
            color: #FFFFFF;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }

        .zoom-controls {
            display: inline-flex;
            align-items: center;
            gap: 0.25rem;
            background: #FFFFFF;
            border: 1px solid var(--zyro-border);
            border-radius: 2px;
            padding: 2px;
        }

        .zoom-btn {
            background: transparent;
            border: none;
            width: 28px;
            height: 28px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 0.875rem;
            font-weight: 700;
            cursor: pointer;
            color: var(--zyro-black);
            border-radius: 2px;
            transition: background 0.15s;
        }

        .zoom-btn:hover {
            background: #F4F4F5;
        }

        .zoom-indicator {
            font-size: 0.6875rem;
            font-weight: 600;
            padding-inline: 0.4rem;
            color: var(--zyro-muted);
            min-width: 44px;
            text-align: center;
            user-select: none;
        }

        .preview-canvas-wrap {
            position: relative;
            min-height: 480px;
            height: 580px;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            background: radial-gradient(circle at 50% 40%, #FFFFFF 0%, #F4F4F6 85%);
            user-select: none;
        }

        .preview-svg-container {
            width: 100%;
            height: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: transform 0.2s cubic-bezier(0.2, 0, 0, 1);
            transform-origin: center center;
        }

        .preview-svg-container svg {
            width: min(92%, 460px);
            height: auto;
            max-height: 94%;
            filter: drop-shadow(0 14px 28px rgba(0, 0, 0, 0.08));
        }

        .preview-footer-info {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0.75rem 1.25rem;
            background: #FFFFFF;
            border-top: 1px solid var(--zyro-border);
            font-size: 0.75rem;
            color: var(--zyro-muted);
        }

        /* ─── PANEL DE CONFIGURACIÓN ─── */
        .config-card {
            background: #FFFFFF;
            border: 1px solid var(--zyro-border);
            border-radius: 2px;
            padding: 2rem;
            box-shadow: 0 4px 16px rgba(0,0,0,0.02);
        }

        .step-section {
            padding-bottom: 1.75rem;
            margin-bottom: 1.75rem;
            border-bottom: 1px solid var(--zyro-border);
        }
        .step-section:last-of-type {
            border-bottom: none;
            padding-bottom: 0;
            margin-bottom: 0;
        }

        .step-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 1rem;
        }

        .step-title {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.75rem;
            font-weight: 700;
            letter-spacing: 0.12em;
            text-transform: uppercase;
            color: var(--zyro-black);
        }

        .step-badge {
            background: var(--zyro-black);
            color: #FFFFFF;
            width: 20px;
            height: 20px;
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 0.6875rem;
            font-weight: 700;
        }

        /* Muestras de color */
        .color-swatch-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 0.75rem;
        }
        @media (min-width: 480px) {
            .color-swatch-grid {
                grid-template-columns: repeat(6, 1fr);
            }
        }

        .color-swatch-card {
            border: 1px solid var(--zyro-border);
            border-radius: 2px;
            padding: 0.65rem 0.4rem;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 0.4rem;
            cursor: pointer;
            transition: all 0.15s ease;
            background: #FFFFFF;
            text-align: center;
        }
        .color-swatch-card:hover {
            border-color: var(--zyro-black);
        }
        .color-swatch-card:has(input:checked) {
            border-color: var(--zyro-black);
            background: #FAFAFA;
            box-shadow: 0 0 0 1px var(--zyro-black);
        }
        .color-swatch-card input {
            position: absolute;
            opacity: 0;
            pointer-events: none;
        }
        .swatch-circle {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            border: 1px solid rgba(0,0,0,0.12);
            box-shadow: inset 0 2px 4px rgba(0,0,0,0.08);
        }
        .swatch-name {
            font-size: 0.6875rem;
            font-weight: 600;
            color: var(--zyro-black);
            line-height: 1.2;
        }

        /* Tallas */
        .sizes-row {
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
        }
        .size-box {
            flex: 1;
            min-width: 54px;
            border: 1px solid var(--zyro-border);
            border-radius: 2px;
            padding: 0.65rem 0.5rem;
            text-align: center;
            font-size: 0.8125rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.15s ease;
            background: #FFFFFF;
        }
        .size-box:hover {
            border-color: var(--zyro-black);
        }
        .size-box:has(input:checked) {
            background: var(--zyro-black);
            color: #FFFFFF;
            border-color: var(--zyro-black);
        }
        .size-box input {
            position: absolute;
            opacity: 0;
            pointer-events: none;
        }

        /* Tabs de diseño */
        .design-type-tabs {
            display: flex;
            gap: 0.5rem;
            margin-bottom: 1.25rem;
            border-bottom: 1px solid var(--zyro-border);
            padding-bottom: 0.75rem;
        }
        .design-type-btn {
            background: transparent;
            border: none;
            padding: 0.4rem 0.75rem;
            font-size: 0.75rem;
            font-weight: 700;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            color: var(--zyro-muted);
            cursor: pointer;
            border-bottom: 2px solid transparent;
            margin-bottom: -0.75rem;
            transition: all 0.15s;
        }
        .design-type-btn.active {
            color: var(--zyro-black);
            border-bottom-color: var(--zyro-black);
        }

        /* Dropzone para imagen */
        .dropzone-box {
            border: 1px dashed var(--zyro-border);
            border-radius: 2px;
            padding: 1.5rem 1rem;
            text-align: center;
            background: #FAFAFA;
            cursor: pointer;
            transition: border-color 0.2s, background 0.2s;
            position: relative;
        }
        .dropzone-box:hover, .dropzone-box.dragover {
            border-color: var(--zyro-black);
            background: #F4F4F6;
        }
        .dropzone-box input[type="file"] {
            position: absolute;
            inset: 0;
            opacity: 0;
            cursor: pointer;
            width: 100%;
            height: 100%;
        }

        /* Ubicaciones */
        .location-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 0.65rem;
        }
        .location-card {
            border: 1px solid var(--zyro-border);
            border-radius: 2px;
            padding: 0.75rem;
            display: flex;
            align-items: center;
            gap: 0.6rem;
            cursor: pointer;
            transition: all 0.15s ease;
            background: #FFFFFF;
        }
        .location-card:hover {
            border-color: var(--zyro-black);
        }
        .location-card:has(input:checked) {
            border-color: var(--zyro-black);
            background: #FAFAFA;
            box-shadow: 0 0 0 1px var(--zyro-black);
        }
        .location-card input {
            accent-color: var(--zyro-black);
        }
        .location-name {
            font-size: 0.75rem;
            font-weight: 600;
            color: var(--zyro-black);
        }
        .location-hint {
            font-size: 0.6875rem;
            color: var(--zyro-muted);
        }

        /* Sliders de ajuste */
        .slider-row {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin-top: 0.85rem;
        }
        .custom-slider {
            flex: 1;
            height: 4px;
            background: #E4E4E7;
            border-radius: 2px;
            outline: none;
            accent-color: var(--zyro-black);
        }

        /* Bounding box interactivo en SVG */
        .custom-target-rect {
            stroke: #0055FF;
            stroke-width: 1.5;
            stroke-dasharray: 4,4;
            fill: rgba(0, 85, 255, 0.04);
            transition: opacity 0.2s ease;
        }
        .draggable-mark {
            cursor: grab;
            transition: filter 0.15s ease;
        }
        .draggable-mark:active {
            cursor: grabbing;
        }
    </style>

    <div class="customizer-layout">

        {{-- ═══════════════════════════════════════════════════════════════
             COLUMNA IZQUIERDA: VISOR DE ALTA PRECISIÓN 2D DE LA PRENDA
             ═══════════════════════════════════════════════════════════════ --}}
        <div>
            <div class="preview-stage-card">

                {{-- Barra superior de controles --}}
                <div class="preview-toolbar">
                    <div style="display:flex; align-items:center; gap:0.5rem;">
                        <span style="font-size:0.6875rem; font-weight:700; letter-spacing:0.1em; text-transform:uppercase; color:var(--zyro-muted);">Vista:</span>
                        <div class="view-pill-group" role="tablist">
                            <button type="button" class="view-pill-btn active" data-view="front" id="btn-view-front">Frontal</button>
                            <button type="button" class="view-pill-btn" data-view="back" id="btn-view-back">Trasera</button>
                        </div>
                    </div>

                    <div style="display:flex; align-items:center; gap:0.5rem;">
                        <span style="font-size:0.6875rem; font-weight:700; letter-spacing:0.1em; text-transform:uppercase; color:var(--zyro-muted);">Zoom:</span>
                        <div class="zoom-controls">
                            <button type="button" class="zoom-btn" id="btn-zoom-out" title="Alejar vista">&minus;</button>
                            <span class="zoom-indicator" id="zoom-label">100%</span>
                            <button type="button" class="zoom-btn" id="btn-zoom-in" title="Acercar vista">&plus;</button>
                            <button type="button" class="zoom-btn" id="btn-zoom-reset" title="Restablecer zoom" style="font-size:0.6875rem; width:auto; padding-inline:0.4rem;">Reset</button>
                        </div>
                    </div>
                </div>

                {{-- Lienzo SVG interactivo --}}
                <div class="preview-canvas-wrap" id="canvas-stage">
                    <div class="preview-svg-container" id="svg-transform-wrapper">
                        <svg id="customizer-svg" viewBox="0 0 500 580" xmlns="http://www.w3.org/2000/svg" role="img" aria-label="Visualizador interactivo de chompa ZYRO">
                            <defs>
                                {{-- Sombra suave volumétrica bajo la chompa --}}
                                <filter id="zyro-drop-shadow" x="-10%" y="-10%" width="120%" height="130%">
                                    <feDropShadow dx="0" dy="16" stdDeviation="18" flood-color="#000000" flood-opacity="0.14" />
                                </filter>

                                {{-- Gradiente de sombreado de torso para volumen tridimensional --}}
                                <linearGradient id="body-depth-grad" x1="0%" y1="0%" x2="100%" y2="0%">
                                    <stop offset="0%" stop-color="#000000" stop-opacity="0.22" />
                                    <stop offset="20%" stop-color="#000000" stop-opacity="0.04" />
                                    <stop offset="50%" stop-color="#FFFFFF" stop-opacity="0.10" />
                                    <stop offset="80%" stop-color="#000000" stop-opacity="0.04" />
                                    <stop offset="100%" stop-color="#000000" stop-opacity="0.22" />
                                </linearGradient>

                                {{-- Gradiente para mangas largas --}}
                                <linearGradient id="sleeve-left-grad" x1="0%" y1="0%" x2="100%" y2="100%">
                                    <stop offset="0%" stop-color="#FFFFFF" stop-opacity="0.08" />
                                    <stop offset="60%" stop-color="#000000" stop-opacity="0.10" />
                                    <stop offset="100%" stop-color="#000000" stop-opacity="0.25" />
                                </linearGradient>

                                <linearGradient id="sleeve-right-grad" x1="100%" y1="0%" x2="0%" y2="100%">
                                    <stop offset="0%" stop-color="#FFFFFF" stop-opacity="0.08" />
                                    <stop offset="60%" stop-color="#000000" stop-opacity="0.10" />
                                    <stop offset="100%" stop-color="#000000" stop-opacity="0.25" />
                                </linearGradient>

                                {{-- Sombra profunda interior de capucha --}}
                                <radialGradient id="hood-inner-shadow" cx="50%" cy="40%" r="55%">
                                    <stop offset="30%" stop-color="#000000" stop-opacity="0.65" />
                                    <stop offset="100%" stop-color="#000000" stop-opacity="0.15" />
                                </radialGradient>

                                {{-- Textura acanalada para puños y pretina (Rib Pattern) --}}
                                <pattern id="rib-lines" width="4" height="4" patternUnits="userSpaceOnUse">
                                    <line x1="0" y1="0" x2="0" y2="4" stroke="#000000" stroke-opacity="0.12" stroke-width="1.2" />
                                </pattern>
                            </defs>

                            {{-- ==============================================================
                                 GRUPO VISTA FRONTAL (VISIBLE POR DEFECTO)
                                 ============================================================== --}}
                            <g id="view-group-front">
                                {{-- 1. BASE DE TELA: MANGAS LARGAS Y CUERPO --}}
                                <g filter="url(#zyro-drop-shadow)">
                                    {{-- Manga Izquierda Larga (Llega hasta la muñeca) --}}
                                    <path class="fabric-fill" d="M 160 148 C 145 180, 105 320, 84 435 L 122 445 C 138 340, 162 250, 156 270 Z" fill="#141416" stroke="#000000" stroke-opacity="0.25" stroke-width="1.5" />
                                    <path d="M 160 148 C 145 180, 105 320, 84 435 L 122 445 C 138 340, 162 250, 156 270 Z" fill="url(#sleeve-left-grad)" />

                                    {{-- Manga Derecha Larga (Llega hasta la muñeca) --}}
                                    <path class="fabric-fill" d="M 340 148 C 355 180, 395 320, 416 435 L 378 445 C 362 340, 338 250, 344 270 Z" fill="#141416" stroke="#000000" stroke-opacity="0.25" stroke-width="1.5" />
                                    <path d="M 340 148 C 355 180, 395 320, 416 435 L 378 445 C 362 340, 338 250, 344 270 Z" fill="url(#sleeve-right-grad)" />

                                    {{-- Torso / Cuerpo de la chompa (Heavyweight drape) --}}
                                    <path class="fabric-fill" d="M 160 148 C 190 142, 310 142, 340 148 C 348 210, 344 320, 336 450 L 164 450 C 156 320, 152 210, 160 148 Z" fill="#141416" stroke="#000000" stroke-opacity="0.25" stroke-width="1.5" />
                                    {{-- Volumen del torso --}}
                                    <path d="M 160 148 C 190 142, 310 142, 340 148 C 348 210, 344 320, 336 450 L 164 450 C 156 320, 152 210, 160 148 Z" fill="url(#body-depth-grad)" />
                                </g>

                                {{-- 2. PUÑOS ACANALADOS DE MANGA (RIBBED CUFFS) --}}
                                {{-- Puño izquierdo --}}
                                <path class="fabric-fill" d="M 84 435 L 122 445 L 118 468 L 78 456 Z" fill="#141416" stroke="#000000" stroke-opacity="0.3" stroke-width="1.2" />
                                <path d="M 84 435 L 122 445 L 118 468 L 78 456 Z" fill="url(#rib-lines)" />
                                <line x1="84" y1="435" x2="122" y2="445" stroke="#000000" stroke-opacity="0.25" stroke-dasharray="2,2" stroke-width="1" />

                                {{-- Puño derecho --}}
                                <path class="fabric-fill" d="M 416 435 L 378 445 L 382 468 L 422 456 Z" fill="#141416" stroke="#000000" stroke-opacity="0.3" stroke-width="1.2" />
                                <path d="M 416 435 L 378 445 L 382 468 L 422 456 Z" fill="url(#rib-lines)" />
                                <line x1="416" y1="435" x2="378" y2="445" stroke="#000000" stroke-opacity="0.25" stroke-dasharray="2,2" stroke-width="1" />

                                {{-- 3. PRETINA INFERIOR ACANALADA (RIBBED HEM) --}}
                                <path class="fabric-fill" d="M 164 450 L 336 450 L 334 492 C 280 496, 220 496, 166 492 Z" fill="#141416" stroke="#000000" stroke-opacity="0.3" stroke-width="1.5" />
                                <path d="M 164 450 L 336 450 L 334 492 C 280 496, 220 496, 166 492 Z" fill="url(#rib-lines)" />
                                {{-- Costura elástica en unión de pretina --}}
                                <line x1="164" y1="450" x2="336" y2="450" stroke="#000000" stroke-opacity="0.35" stroke-width="1.5" />
                                <line x1="164" y1="452" x2="336" y2="452" stroke="#FFFFFF" stroke-opacity="0.15" stroke-dasharray="3,2" stroke-width="1" />

                                {{-- 4. COSTURAS DE CONFECCIÓN EN HOMBROS Y SISA --}}
                                <path d="M 160 148 C 175 200, 165 250, 156 270" fill="none" stroke="#000000" stroke-opacity="0.3" stroke-width="1.5" />
                                <path d="M 340 148 C 325 200, 335 250, 344 270" fill="none" stroke="#000000" stroke-opacity="0.3" stroke-width="1.5" />
                                <path d="M 160 148 L 205 136" fill="none" stroke="#000000" stroke-opacity="0.25" stroke-dasharray="3,2" stroke-width="1" />
                                <path d="M 340 148 L 295 136" fill="none" stroke="#000000" stroke-opacity="0.25" stroke-dasharray="3,2" stroke-width="1" />

                                @if ($garmentType === 'hoodie')
                                    {{-- 5. BOLSILLO CANGURO FRONTAL (HOODIE KANGAROO POCKET) --}}
                                    <g id="front-kangaroo-pocket">
                                        {{-- Sombra proyectada por el bolsillo --}}
                                        <path d="M 182 450 L 318 450 L 296 348 L 204 348 Z" fill="#000000" fill-opacity="0.15" transform="translate(0, 3)" />
                                        {{-- Tela del bolsillo --}}
                                        <path class="fabric-fill" d="M 180 450 L 320 450 L 295 348 C 280 346, 220 346, 205 348 Z" fill="#141416" stroke="#000000" stroke-opacity="0.3" stroke-width="1.5" />
                                        {{-- Aberturas diagonales con ribetes para manos --}}
                                        <path d="M 205 348 C 190 380, 185 415, 180 450" fill="none" stroke="#000000" stroke-opacity="0.4" stroke-width="2.5" />
                                        <path d="M 205 348 C 190 380, 185 415, 180 450" fill="none" stroke="#FFFFFF" stroke-opacity="0.18" stroke-dasharray="3,2" stroke-width="1.2" />
                                        <path d="M 295 348 C 310 380, 315 415, 320 450" fill="none" stroke="#000000" stroke-opacity="0.4" stroke-width="2.5" />
                                        <path d="M 295 348 C 310 380, 315 415, 320 450" fill="none" stroke="#FFFFFF" stroke-opacity="0.18" stroke-dasharray="3,2" stroke-width="1.2" />
                                        {{-- Pespunte superior doble del bolsillo --}}
                                        <path d="M 205 348 C 220 346, 280 346, 295 348" fill="none" stroke="#000000" stroke-opacity="0.3" stroke-width="1.5" />
                                        <path d="M 206 351 C 221 349, 279 349, 294 351" fill="none" stroke="#FFFFFF" stroke-opacity="0.15" stroke-dasharray="3,2" stroke-width="1" />
                                    </g>

                                    {{-- 6. CAPUCHA FRONTAL CON PROFUNDIDAD Y CORDONES (HOODIE HOOD) --}}
                                    <g id="front-hoodie-hood">
                                        {{-- Capucha exterior reposando alrededor del cuello --}}
                                        <path class="fabric-fill" d="M 180 144 C 170 100, 200 68, 250 68 C 300 68, 330 100, 320 144 C 305 180, 280 195, 250 195 C 220 195, 195 180, 180 144 Z" fill="#141416" stroke="#000000" stroke-opacity="0.35" stroke-width="1.8" />
                                        {{-- Hueco/Abertura interior de la capucha con forro profundo --}}
                                        <ellipse cx="250" cy="148" rx="46" ry="32" fill="url(#hood-inner-shadow)" />
                                        <ellipse cx="250" cy="148" rx="46" ry="32" fill="none" stroke="#000000" stroke-opacity="0.4" stroke-width="2" />
                                        {{-- Ojales metálicos para cordones --}}
                                        <circle cx="232" cy="184" r="3" fill="#D1D5DB" stroke="#374151" stroke-width="1" />
                                        <circle cx="268" cy="184" r="3" fill="#D1D5DB" stroke="#374151" stroke-width="1" />
                                        {{-- Cordón izquierdo con curvatura natural y puntera metálica --}}
                                        <path d="M 232 186 C 230 215, 222 240, 226 270" fill="none" stroke="#E5E7EB" stroke-width="3" stroke-linecap="round" />
                                        <path d="M 232 186 C 230 215, 222 240, 226 270" fill="none" stroke="#9CA3AF" stroke-dasharray="2,2" stroke-width="2" />
                                        <rect x="223.5" y="270" width="5" height="14" rx="1.5" fill="#9CA3AF" stroke="#4B5563" stroke-width="0.8" />
                                        <line x1="225" y1="272" x2="225" y2="282" stroke="#FFFFFF" stroke-opacity="0.6" stroke-width="1" />
                                        {{-- Cordón derecho con curvatura natural y puntera metálica --}}
                                        <path d="M 268 186 C 270 215, 276 245, 272 278" fill="none" stroke="#E5E7EB" stroke-width="3" stroke-linecap="round" />
                                        <path d="M 268 186 C 270 215, 276 245, 272 278" fill="none" stroke="#9CA3AF" stroke-dasharray="2,2" stroke-width="2" />
                                        <rect x="269.5" y="278" width="5" height="14" rx="1.5" fill="#9CA3AF" stroke="#4B5563" stroke-width="0.8" />
                                        <line x1="271" y1="280" x2="271" y2="290" stroke="#FFFFFF" stroke-opacity="0.6" stroke-width="1" />
                                    </g>

                                @elseif ($garmentType === 'tech_collar')
                                    {{-- CUELLO ALTO ERGONÓMICO CON CREMALLERA 1/4 (TECH COLLAR) --}}
                                    <g id="front-tech-collar">
                                        {{-- Cuello embudo alto estructurado --}}
                                        <path class="fabric-fill" d="M 205 136 C 210 98, 290 98, 295 136 C 285 168, 215 168, 205 136 Z" fill="#141416" stroke="#000000" stroke-opacity="0.4" stroke-width="2" />
                                        <ellipse cx="250" cy="108" rx="40" ry="12" fill="#0A0A0C" stroke="#000000" stroke-width="1.5" />
                                        {{-- Cremallera frontal termosellada 1/4 --}}
                                        <line x1="250" y1="116" x2="250" y2="230" stroke="#0055FF" stroke-width="3" stroke-linecap="round" />
                                        <line x1="250" y1="116" x2="250" y2="230" stroke="#FFFFFF" stroke-opacity="0.7" stroke-dasharray="2,3" stroke-width="2" />
                                        {{-- Tirador de cremallera metálico ZYRO --}}
                                        <rect x="247" y="228" width="6" height="12" rx="1.5" fill="#D1D5DB" stroke="#1F2937" stroke-width="1" />
                                        {{-- Paneles técnicos de compresión en hombros --}}
                                        <path d="M 205 136 Q 220 185 160 215" fill="none" stroke="#0055FF" stroke-opacity="0.4" stroke-width="1.5" />
                                        <path d="M 295 136 Q 280 185 340 215" fill="none" stroke="#0055FF" stroke-opacity="0.4" stroke-width="1.5" />
                                    </g>

                                @else
                                    {{-- CUELLO REDONDO CLÁSICO CON ACANALADO (CREWNECK) --}}
                                    <g id="front-crewneck-collar">
                                        {{-- Banda acanalada de cuello --}}
                                        <path class="fabric-fill" d="M 205 136 C 220 174, 280 174, 295 136 C 285 125, 215 125, 205 136 Z" fill="#141416" stroke="#000000" stroke-opacity="0.35" stroke-width="2" />
                                        <path d="M 205 136 C 220 174, 280 174, 295 136 C 285 125, 215 125, 205 136 Z" fill="url(#rib-lines)" />
                                        {{-- Pespunte triangular clásico en V bajo el cuello --}}
                                        <path d="M 238 165 L 250 182 L 262 165" fill="none" stroke="#000000" stroke-opacity="0.25" stroke-width="1.5" />
                                        <path d="M 238 165 L 250 182 L 262 165" fill="none" stroke="#FFFFFF" stroke-opacity="0.12" stroke-dasharray="2,2" stroke-width="1" />
                                    </g>
                                @endif
                            </g>

                            {{-- ==============================================================
                                 GRUPO VISTA TRASERA (OCULTO POR DEFECTO)
                                 ============================================================== --}}
                            <g id="view-group-back" style="display:none;">
                                {{-- 1. BASE DE TELA: MANGAS Y ESPALDA AMPLIA --}}
                                <g filter="url(#zyro-drop-shadow)">
                                    {{-- Manga Derecha Posterior (Lado izquierdo del espectador) --}}
                                    <path class="fabric-fill" d="M 160 148 C 145 180, 105 320, 84 435 L 122 445 C 138 340, 162 250, 156 270 Z" fill="#141416" stroke="#000000" stroke-opacity="0.25" stroke-width="1.5" />
                                    <path d="M 160 148 C 145 180, 105 320, 84 435 L 122 445 C 138 340, 162 250, 156 270 Z" fill="url(#sleeve-left-grad)" />

                                    {{-- Manga Izquierda Posterior (Lado derecho del espectador) --}}
                                    <path class="fabric-fill" d="M 340 148 C 355 180, 395 320, 416 435 L 378 445 C 362 340, 338 250, 344 270 Z" fill="#141416" stroke="#000000" stroke-opacity="0.25" stroke-width="1.5" />
                                    <path d="M 340 148 C 355 180, 395 320, 416 435 L 378 445 C 362 340, 338 250, 344 270 Z" fill="url(#sleeve-right-grad)" />

                                    {{-- Torso / Espalda amplia --}}
                                    <path class="fabric-fill" d="M 160 148 C 190 142, 310 142, 340 148 C 348 210, 344 320, 336 450 L 164 450 C 156 320, 152 210, 160 148 Z" fill="#141416" stroke="#000000" stroke-opacity="0.25" stroke-width="1.5" />
                                    <path d="M 160 148 C 190 142, 310 142, 340 148 C 348 210, 344 320, 336 450 L 164 450 C 156 320, 152 210, 160 148 Z" fill="url(#body-depth-grad)" />
                                </g>

                                {{-- Puños posteriores --}}
                                <path class="fabric-fill" d="M 84 435 L 122 445 L 118 468 L 78 456 Z" fill="#141416" stroke="#000000" stroke-opacity="0.3" stroke-width="1.2" />
                                <path d="M 84 435 L 122 445 L 118 468 L 78 456 Z" fill="url(#rib-lines)" />
                                <path class="fabric-fill" d="M 416 435 L 378 445 L 382 468 L 422 456 Z" fill="#141416" stroke="#000000" stroke-opacity="0.3" stroke-width="1.2" />
                                <path d="M 416 435 L 378 445 L 382 468 L 422 456 Z" fill="url(#rib-lines)" />

                                {{-- Pretina posterior --}}
                                <path class="fabric-fill" d="M 164 450 L 336 450 L 334 492 C 280 496, 220 496, 166 492 Z" fill="#141416" stroke="#000000" stroke-opacity="0.3" stroke-width="1.5" />
                                <path d="M 164 450 L 336 450 L 334 492 C 280 496, 220 496, 166 492 Z" fill="url(#rib-lines)" />

                                {{-- Costuras dorsales y canesú --}}
                                <line x1="164" y1="190" x2="336" y2="190" stroke="#000000" stroke-opacity="0.25" stroke-dasharray="3,2" stroke-width="1.2" />

                                @if ($garmentType === 'hoodie')
                                    {{-- CAPUCHA DESCANSANDO SOBRE LA ESPALDA --}}
                                    <g id="back-hoodie-hood">
                                        {{-- Sombra de la capucha sobre la espalda alta --}}
                                        <path d="M 195 130 C 180 185, 200 230, 250 230 C 300 230, 320 185, 305 130 Z" fill="#000000" fill-opacity="0.2" transform="translate(0, 4)" />
                                        {{-- Caída de tela de la capucha --}}
                                        <path class="fabric-fill" d="M 195 125 C 180 180, 205 225, 250 225 C 295 225, 320 180, 305 125 C 280 115, 220 115, 195 125 Z" fill="#141416" stroke="#000000" stroke-opacity="0.35" stroke-width="1.8" />
                                        {{-- Costura central vertical de la capucha --}}
                                        <path d="M 250 85 C 250 140, 250 180, 250 225" fill="none" stroke="#000000" stroke-opacity="0.35" stroke-width="1.8" />
                                        <path d="M 250 85 C 250 140, 250 180, 250 225" fill="none" stroke="#FFFFFF" stroke-opacity="0.15" stroke-dasharray="3,2" stroke-width="1" />
                                    </g>
                                @elseif ($garmentType === 'tech_collar')
                                    {{-- CUELLO TRASERO TÉCNICO CON PANEL DORSAL --}}
                                    <path class="fabric-fill" d="M 205 136 C 210 98, 290 98, 295 136 C 275 145, 225 145, 205 136 Z" fill="#141416" stroke="#000000" stroke-opacity="0.4" stroke-width="2" />
                                    <path d="M 225 190 L 250 260 L 275 190" fill="none" stroke="#0055FF" stroke-opacity="0.5" stroke-width="1.5" />
                                @else
                                    {{-- CUELLO TRASERO CREWNECK CON MEDIALUNA --}}
                                    <path class="fabric-fill" d="M 205 136 C 220 148, 280 148, 295 136 C 280 128, 220 128, 205 136 Z" fill="#141416" stroke="#000000" stroke-opacity="0.3" stroke-width="1.8" />
                                    <path d="M 205 136 C 220 148, 280 148, 295 136 C 280 128, 220 128, 205 136 Z" fill="url(#rib-lines)" />
                                    <path d="M 215 144 C 230 162, 270 162, 285 144" fill="none" stroke="#000000" stroke-opacity="0.2" stroke-dasharray="3,2" stroke-width="1" />
                                @endif
                            </g>

                            {{-- ==============================================================
                                 ÁREAS DE ESTAMPADO CON GUÍA Y ELEMENTOS PERSONALIZADOS
                                 ============================================================== --}}
                            {{-- Bounding box guía sutil que se activa para orientar al cliente --}}
                            <g id="zone-guide-group">
                                <rect id="zone-guide-rect" class="custom-target-rect" x="180" y="215" width="140" height="120" rx="3" style="display:none;" />
                            </g>

                            {{-- Grupo interactivo del diseño (arrastrable con pointer / touch) --}}
                            <g id="design-interactive-group" class="draggable-mark" transform="translate(250, 265)">
                                {{-- Imagen / Logotipo subido por el cliente --}}
                                <image id="design-svg-image" x="-45" y="-45" width="90" height="90" preserveAspectRatio="xMidYMid meet" href="" style="display:none; pointer-events:none;" />

                                {{-- Texto personalizado --}}
                                <text id="design-svg-text" x="0" y="4" text-anchor="middle" dominant-baseline="middle" fill="#FFFFFF" font-family="'Inter', sans-serif" font-size="20" font-weight="700" letter-spacing="1" style="pointer-events:none; filter:drop-shadow(0 2px 4px rgba(0,0,0,0.35));"></text>

                                {{-- Manejador visual interactivo sutil al posicionar el mouse --}}
                                <circle id="design-anchor-point" cx="0" cy="0" r="4" fill="#0055FF" style="display:none;" />
                            </g>
                        </svg>
                    </div>
                </div>

                {{-- Pie del visor con datos del modelo --}}
                <div class="preview-footer-info">
                    <div>
                        <strong style="color:var(--zyro-black); display:block; font-size:0.8125rem;">{{ $garmentTitle }}</strong>
                        <span>{{ $garmentSpec }}</span>
                    </div>
                    <div style="text-align:right;">
                        <span style="display:inline-block; font-size:0.625rem; font-weight:700; letter-spacing:0.1em; text-transform:uppercase; background:#E4E4E7; color:#27272A; padding:0.2rem 0.5rem; border-radius:2px;">
                            Renderizado SVG 2D de Alta Fidelidad
                        </span>
                    </div>
                </div>

            </div>
        </div>

        {{-- ═══════════════════════════════════════════════════════════════
             COLUMNA DERECHA: CONFIGURADOR DE OPCIONES EN 1 SOLA PANTALLA
             ═══════════════════════════════════════════════════════════════ --}}
        <div>
            <form class="config-card" method="POST" action="{{ route('cart.store', $product->id) }}" enctype="multipart/form-data" id="customizer-form">
                @csrf
                <input type="hidden" name="customized" value="1">

                {{-- PASO 1: SELECCIÓN DE COLOR --}}
                <div class="step-section">
                    <div class="step-header">
                        <span class="step-title">
                            <span class="step-badge">1</span> Color de la tela
                        </span>
                        <span id="active-color-name" style="font-size:0.75rem; font-weight:600; color:var(--zyro-black);">Negro Carbón</span>
                    </div>
                    <div class="color-swatch-grid">
                        @foreach ($colorMap as $key => $color)
                            <label class="color-swatch-card" title="{{ $color['name'] }}">
                                <input
                                    type="radio"
                                    name="custom_color"
                                    value="{{ $key }}"
                                    data-hex="{{ $color['hex'] }}"
                                    data-name="{{ $color['name'] }}"
                                    {{ $key === 'black' ? 'checked' : '' }}
                                >
                                <span class="swatch-circle" style="background-color: {{ $color['hex'] }};"></span>
                                <span class="swatch-name">{{ $color['name'] }}</span>
                            </label>
                        @endforeach
                    </div>
                </div>

                {{-- PASO 2: SELECCIÓN DE TALLA --}}
                <div class="step-section">
                    <div class="step-header">
                        <span class="step-title">
                            <span class="step-badge">2</span> Talla
                        </span>
                        <span style="font-size:0.6875rem; color:var(--zyro-muted);">Corte Streetwear Oversize</span>
                    </div>
                    <div class="sizes-row">
                        @foreach ($product->availableSizes() as $index => $size)
                            <label class="size-box">
                                <input
                                    type="radio"
                                    name="size"
                                    value="{{ $size }}"
                                    {{ $index === 0 ? 'checked' : '' }}
                                    required
                                >
                                {{ $size }}
                            </label>
                        @endforeach
                    </div>
                </div>

                {{-- PASO 3: DISEÑO (TEXTO / LOGOTIPO) --}}
                <div class="step-section">
                    <div class="step-header">
                        <span class="step-title">
                            <span class="step-badge">3</span> Diseño Personalizado
                        </span>
                    </div>

                    {{-- Pestañas internas Texto / Logo --}}
                    <div class="design-type-tabs" role="tablist">
                        <button type="button" class="design-type-btn active" id="tab-btn-text">Texto / Iniciales</button>
                        <button type="button" class="design-type-btn" id="tab-btn-image">Subir Logotipo / Imagen</button>
                    </div>

                    {{-- Opción A: Texto --}}
                    <div id="design-panel-text">
                        <div class="form-group" style="margin-bottom:1rem;">
                            <label class="form-label" for="custom-text">
                                Texto personalizado <span style="color:var(--zyro-muted); font-weight:400; text-transform:none;">(opcional, máx. 40 caracteres)</span>
                            </label>
                            <div style="position:relative;">
                                <input
                                    type="text"
                                    id="custom-text"
                                    name="custom_text"
                                    maxlength="40"
                                    placeholder="Ej. ZYRO 2026, INICIALES..."
                                    class="form-input"
                                    autocomplete="off"
                                >
                                <span id="char-counter" style="position:absolute; right:0.75rem; top:50%; transform:translateY(-50%); font-size:0.6875rem; color:var(--zyro-muted);">0/40</span>
                            </div>
                        </div>

                        {{-- Opciones de tipografía y color de estampado --}}
                        <div style="display:grid; grid-template-columns: 1fr 1fr; gap:0.75rem;">
                            <div>
                                <label class="form-label" for="font-select">Tipografía</label>
                                <select id="font-select" class="checkout-select" style="height:38px; font-size:0.75rem; padding:0.4rem 0.6rem;">
                                    <option value="'Inter', sans-serif">Moderna (Inter)</option>
                                    <option value="'Cormorant Garamond', serif">Elegante (Serif)</option>
                                    <option value="monospace">Técnica (Mono)</option>
                                </select>
                            </div>
                            <div>
                                <label class="form-label" for="ink-select">Tinta / Acabado</label>
                                <select id="ink-select" class="checkout-select" style="height:38px; font-size:0.75rem; padding:0.4rem 0.6rem;">
                                    <option value="#FFFFFF">Blanco Puro</option>
                                    <option value="#0F0F11">Negro Carbón</option>
                                    <option value="#D4AF37">Dorado ZYRO</option>
                                    <option value="#C5CAD6">Plata Reflectante</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    {{-- Opción B: Imagen / Logotipo --}}
                    <div id="design-panel-image" style="display:none;">
                        <label class="form-label">Archivo gráfico <span style="color:var(--zyro-muted); font-weight:400; text-transform:none;">(PNG, JPG o WEBP, máx. 5 MB)</span></label>
                        <div class="dropzone-box" id="image-dropzone">
                            <input
                                type="file"
                                id="custom-image"
                                name="custom_image"
                                accept="image/png,image/jpeg,image/webp"
                            >
                            <div id="dropzone-empty-state">
                                <span style="font-size:1.75rem; display:block; margin-bottom:0.35rem;">📁</span>
                                <strong style="font-size:0.8125rem; color:var(--zyro-black); display:block;">Haz clic o arrastra tu archivo aquí</strong>
                                <span style="font-size:0.6875rem; color:var(--zyro-muted);">Se recomienda PNG con fondo transparente</span>
                            </div>
                            <div id="dropzone-file-state" style="display:none; align-items:center; justify-content:space-between; gap:0.75rem;">
                                <div style="display:flex; align-items:center; gap:0.75rem;">
                                    <img id="image-preview-thumb" src="" alt="Vista previa del logo" style="width:40px; height:40px; object-fit:contain; background:#fff; border:1px solid var(--zyro-border); border-radius:2px; padding:2px;">
                                    <div style="text-align:left;">
                                        <p id="file-name-label" style="font-size:0.75rem; font-weight:600; color:var(--zyro-black); max-width:180px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">logo.png</p>
                                        <span id="file-size-label" style="font-size:0.6875rem; color:var(--zyro-muted);">240 KB</span>
                                    </div>
                                </div>
                                <button type="button" id="btn-remove-image" class="btn-cart-remove" style="font-size:0.6875rem;">Quitar</button>
                            </div>
                        </div>
                        @error('custom_image')
                            <p class="form-error" style="margin-top:0.4rem;">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                {{-- PASO 4: UBICACIÓN Y AJUSTES DE TAMAÑO --}}
                <div class="step-section">
                    <div class="step-header">
                        <span class="step-title">
                            <span class="step-badge">4</span> Ubicación & Escala
                        </span>
                        <span style="font-size:0.6875rem; color:var(--zyro-muted);">Arrastra en la prenda para ajustar</span>
                    </div>

                    <div class="location-grid">
                        <label class="location-card">
                            <input type="radio" name="custom_location" value="chest" checked>
                            <div>
                                <p class="location-name">Pecho Frontal</p>
                                <p class="location-hint">Centro del torso</p>
                            </div>
                        </label>

                        <label class="location-card">
                            <input type="radio" name="custom_location" value="back">
                            <div>
                                <p class="location-name">Espalda</p>
                                <p class="location-hint">Dorsal central</p>
                            </div>
                        </label>

                        <label class="location-card">
                            <input type="radio" name="custom_location" value="left_sleeve">
                            <div>
                                <p class="location-name">Manga Izquierda</p>
                                <p class="location-hint">Antebrazo exterior</p>
                            </div>
                        </label>

                        <label class="location-card">
                            <input type="radio" name="custom_location" value="right_sleeve">
                            <div>
                                <p class="location-name">Manga Derecha</p>
                                <p class="location-hint">Antebrazo exterior</p>
                            </div>
                        </label>
                    </div>

                    {{-- Control de tamaño / escala --}}
                    <div class="slider-row">
                        <span style="font-size:0.6875rem; font-weight:700; letter-spacing:0.08em; text-transform:uppercase; color:var(--zyro-muted); min-width:85px;">Tamaño diseño:</span>
                        <input type="range" id="scale-slider" class="custom-slider" min="50" max="150" value="100">
                        <span id="scale-label" style="font-size:0.75rem; font-weight:600; color:var(--zyro-black); min-width:38px;">100%</span>
                        <button type="button" id="btn-center-design" class="btn-secondary" style="font-size:0.6875rem; padding:0.3rem 0.6rem;" title="Centrar diseño en la zona">Centrar</button>
                    </div>
                </div>

                {{-- RESUMEN Y BOTÓN DE COMPRA --}}
                <div style="background:var(--zyro-light); border:1px solid var(--zyro-border); border-radius:2px; padding:1.25rem; margin-top:1.5rem;">
                    <div style="display:flex; justify-content:space-between; align-items:baseline; margin-bottom:0.75rem;">
                        <span style="font-size:0.8125rem; color:var(--zyro-muted);">Cantidad:</span>
                        <div style="display:flex; align-items:center; gap:0.4rem;">
                            <input
                                class="cart-qty-input"
                                id="quantity"
                                name="quantity"
                                type="number"
                                value="1"
                                min="1"
                                max="{{ $product->stock }}"
                                style="width:60px; height:36px;"
                            >
                            <span style="font-size:0.75rem; color:var(--zyro-muted);">disponibles: {{ $product->stock }}</span>
                        </div>
                    </div>

                    <div style="display:flex; justify-content:space-between; align-items:baseline; padding-top:0.75rem; border-top:1px solid var(--zyro-border); margin-bottom:1.25rem;">
                        <span style="font-size:0.875rem; font-weight:700; color:var(--zyro-black);">Total con personalización:</span>
                        <span style="font-family:'Cormorant Garamond',Georgia,serif; font-size:1.6rem; font-weight:700; color:var(--zyro-black);">
                            {{ $product->formattedPrice() }}
                        </span>
                    </div>

                    <button class="btn-primary" type="submit" style="width:100%; padding:0.95rem; font-size:0.75rem;">
                        Añadir personalizado al carrito · {{ $product->formattedPrice() }}
                    </button>
                </div>

                <p style="font-size:0.6875rem; color:var(--zyro-muted); text-align:center; margin-top:1rem; line-height:1.5;">
                    🔒 Confección artesanal individualizada. Tu prenda personalizada entrará a producción una vez aprobado el pedido.
                </p>
            </form>
        </div>

    </div>
</div>

{{-- ═══════════════════════════════════════════════════════════════
     MOTOR JAVASCRIPT DEL PERSONALIZADOR
     ═══════════════════════════════════════════════════════════════ --}}
<script>
(() => {
    // Definición de coordenadas y zonas permitidas por silueta
    const ZONES = {
        chest: {
            view: 'front',
            defaultX: 250,
            defaultY: 265,
            minX: 185,
            maxX: 315,
            minY: 215,
            maxY: 335,
            guide: { x: 185, y: 215, w: 130, h: 120 }
        },
        back: {
            view: 'back',
            defaultX: 250,
            defaultY: 285,
            minX: 175,
            maxX: 325,
            minY: 200,
            maxY: 410,
            guide: { x: 175, y: 200, w: 150, h: 210 }
        },
        left_sleeve: {
            view: 'front',
            defaultX: 110,
            defaultY: 330,
            minX: 85,
            maxX: 145,
            minY: 240,
            maxY: 420,
            guide: { x: 85, y: 240, w: 60, h: 180 }
        },
        right_sleeve: {
            view: 'front',
            defaultX: 390,
            defaultY: 330,
            minX: 355,
            maxX: 415,
            minY: 240,
            maxY: 420,
            guide: { x: 355, y: 240, w: 60, h: 180 }
        }
    };

    // Paleta de colores
    const COLORS = {
        black: '#141416',
        white: '#F4F4F0',
        gray:  '#656872',
        navy:  '#18243C',
        red:   '#871B26',
        beige: '#D4C4B0'
    };

    // Estado interactivo
    let state = {
        view: 'front',             // 'front' | 'back'
        location: 'chest',
        scale: 1.0,
        posX: ZONES.chest.defaultX,
        posY: ZONES.chest.defaultY,
        hasImage: false,
        hasText: false,
        zoom: 1.0,
        isDragging: false,
        dragStartX: 0,
        dragStartY: 0
    };

    // Elementos del DOM
    const svg = document.getElementById('customizer-svg');
    const transformWrapper = document.getElementById('svg-transform-wrapper');
    const groupFront = document.getElementById('view-group-front');
    const groupBack = document.getElementById('view-group-back');
    const btnViewFront = document.getElementById('btn-view-front');
    const btnViewBack = document.getElementById('btn-view-back');
    const zoomLabel = document.getElementById('zoom-label');
    const fabricPaths = document.querySelectorAll('.fabric-fill');
    const activeColorLabel = document.getElementById('active-color-name');

    // Elementos del diseño en SVG
    const designGroup = document.getElementById('design-interactive-group');
    const designImage = document.getElementById('design-svg-image');
    const designText = document.getElementById('design-svg-text');
    const zoneGuideRect = document.getElementById('zone-guide-rect');

    // Inputs del formulario
    const textInput = document.getElementById('custom-text');
    const fontSelect = document.getElementById('font-select');
    const inkSelect = document.getElementById('ink-select');
    const fileInput = document.getElementById('custom-image');
    const scaleSlider = document.getElementById('scale-slider');
    const scaleLabel = document.getElementById('scale-label');
    const charCounter = document.getElementById('char-counter');
    const btnCenter = document.getElementById('btn-center-design');

    // Pestañas de diseño
    const tabBtnText = document.getElementById('tab-btn-text');
    const tabBtnImage = document.getElementById('tab-btn-image');
    const panelText = document.getElementById('design-panel-text');
    const panelImage = document.getElementById('design-panel-image');

    // Dropzone elementos
    const dropzoneEmpty = document.getElementById('dropzone-empty-state');
    const dropzoneFile = document.getElementById('dropzone-file-state');
    const previewThumb = document.getElementById('image-preview-thumb');
    const fileNameLabel = document.getElementById('file-name-label');
    const fileSizeLabel = document.getElementById('file-size-label');
    const btnRemoveImage = document.getElementById('btn-remove-image');

    // Modal de foto
    const modalRef = document.getElementById('modal-ref-photo');
    const btnOpenRef = document.getElementById('btn-toggle-ref-photo');
    const btnCloseRef = document.getElementById('btn-close-ref-photo');

    // ─── 1. CAMBIO DE COLOR DE TELA ───
    document.querySelectorAll('input[name="custom_color"]').forEach(radio => {
        radio.addEventListener('change', () => {
            const val = radio.value;
            const hex = COLORS[val] || '#141416';
            activeColorLabel.textContent = radio.dataset.name || val;
            fabricPaths.forEach(path => {
                path.setAttribute('fill', hex);
            });
        });
    });

    // ─── 2. CAMBIO DE VISTA (FRONTAL / TRASERA) ───
    function setView(viewName) {
        state.view = viewName;
        if (viewName === 'front') {
            groupFront.style.display = 'inline';
            groupBack.style.display = 'none';
            btnViewFront.classList.add('active');
            btnViewBack.classList.remove('active');
        } else {
            groupFront.style.display = 'none';
            groupBack.style.display = 'inline';
            btnViewBack.classList.add('active');
            btnViewFront.classList.remove('active');
        }
        updateDesignVisibility();
        updateGuideBox();
    }

    btnViewFront.addEventListener('click', () => setView('front'));
    btnViewBack.addEventListener('click', () => setView('back'));

    // ─── 3. CAMBIO DE UBICACIÓN ───
    document.querySelectorAll('input[name="custom_location"]').forEach(radio => {
        radio.addEventListener('change', () => {
            const loc = radio.value;
            state.location = loc;
            const targetZone = ZONES[loc];
            if (targetZone) {
                // Auto-conmutar vista si la zona pertenece a la cara contraria
                if (targetZone.view !== state.view) {
                    setView(targetZone.view);
                }
                // Centrar coordenadas en la nueva zona
                state.posX = targetZone.defaultX;
                state.posY = targetZone.defaultY;
                applyTransform();
                updateGuideBox();
            }
        });
    });

    // ─── 4. VISIBILIDAD DEL DISEÑO SEGÚN CARA ACTIVA ───
    function updateDesignVisibility() {
        const zone = ZONES[state.location];
        let isVisibleOnCurrentView = true;

        if (state.location === 'chest' && state.view !== 'front') {
            isVisibleOnCurrentView = false;
        } else if (state.location === 'back' && state.view !== 'back') {
            isVisibleOnCurrentView = false;
        }

        designGroup.style.display = isVisibleOnCurrentView ? 'inline' : 'none';
        zoneGuideRect.style.display = isVisibleOnCurrentView ? 'inline' : 'none';
    }

    function updateGuideBox() {
        const zone = ZONES[state.location];
        if (zone && zone.guide) {
            zoneGuideRect.setAttribute('x', zone.guide.x);
            zoneGuideRect.setAttribute('y', zone.guide.y);
            zoneGuideRect.setAttribute('width', zone.guide.w);
            zoneGuideRect.setAttribute('height', zone.guide.h);
        }
    }

    // ─── 5. TRANSFORMACIÓN DEL DISEÑO (POSICIÓN Y ESCALA) ───
    function applyTransform() {
        designGroup.setAttribute('transform', `translate(${state.posX}, ${state.posY}) scale(${state.scale})`);
    }

    scaleSlider.addEventListener('input', e => {
        const val = parseInt(e.target.value, 10);
        state.scale = val / 100;
        scaleLabel.textContent = `${val}%`;
        applyTransform();
    });

    btnCenter.addEventListener('click', () => {
        const zone = ZONES[state.location];
        if (zone) {
            state.posX = zone.defaultX;
            state.posY = zone.defaultY;
            applyTransform();
        }
    });

    // ─── 6. ARRASTRE CON RATÓN Y TOUCH (POINTER EVENTS) ───
    function getSvgCoordinates(e) {
        const pt = svg.createSVGPoint();
        pt.x = e.clientX;
        pt.y = e.clientY;
        const screenCTM = svg.getScreenCTM();
        if (screenCTM) {
            return pt.matrixTransform(screenCTM.inverse());
        }
        return { x: 0, y: 0 };
    }

    designGroup.addEventListener('pointerdown', e => {
        e.preventDefault();
        state.isDragging = true;
        designGroup.setPointerCapture(e.pointerId);
        const svgCoords = getSvgCoordinates(e);
        state.dragStartX = svgCoords.x - state.posX;
        state.dragStartY = svgCoords.y - state.posY;
        zoneGuideRect.style.display = 'inline';
    });

    designGroup.addEventListener('pointermove', e => {
        if (!state.isDragging) return;
        const svgCoords = getSvgCoordinates(e);
        let newX = svgCoords.x - state.dragStartX;
        let newY = svgCoords.y - state.dragStartY;

        // Limitar dentro del Bounding Box de la zona activa
        const zone = ZONES[state.location];
        if (zone) {
            newX = Math.max(zone.minX, Math.min(zone.maxX, newX));
            newY = Math.max(zone.minY, Math.min(zone.maxY, newY));
        }

        state.posX = newX;
        state.posY = newY;
        applyTransform();
    });

    function stopDragging(e) {
        if (state.isDragging) {
            state.isDragging = false;
            try { designGroup.releasePointerCapture(e.pointerId); } catch(err){}
        }
    }
    designGroup.addEventListener('pointerup', stopDragging);
    designGroup.addEventListener('pointercancel', stopDragging);

    // ─── 7. TEXTO PERSONALIZADO ───
    textInput.addEventListener('input', e => {
        const val = e.target.value.trim();
        charCounter.textContent = `${e.target.value.length}/40`;
        designText.textContent = e.target.value;
        state.hasText = val.length > 0;
        updateDesignVisibility();
    });

    fontSelect.addEventListener('change', e => {
        designText.setAttribute('font-family', e.target.value);
    });

    inkSelect.addEventListener('change', e => {
        designText.setAttribute('fill', e.target.value);
    });

    // ─── 8. PESTAÑAS TEXTO / LOGO ───
    tabBtnText.addEventListener('click', () => {
        tabBtnText.classList.add('active');
        tabBtnImage.classList.remove('active');
        panelText.style.display = 'block';
        panelImage.style.display = 'none';
    });

    tabBtnImage.addEventListener('click', () => {
        tabBtnImage.classList.add('active');
        tabBtnText.classList.remove('active');
        panelImage.style.display = 'block';
        panelText.style.display = 'none';
    });

    // ─── 9. CARGA Y PREVIEW DE IMAGEN ───
    fileInput.addEventListener('change', e => {
        const file = e.target.files[0];
        if (!file) return;

        if (file.size > 5 * 1024 * 1024) {
            alert('El archivo seleccionado supera el límite de 5 MB.');
            fileInput.value = '';
            return;
        }

        const objectUrl = URL.createObjectURL(file);
        designImage.setAttribute('href', objectUrl);
        designImage.style.display = 'inline';
        state.hasImage = true;

        // UI en dropzone
        dropzoneEmpty.style.display = 'none';
        dropzoneFile.style.display = 'flex';
        previewThumb.src = objectUrl;
        fileNameLabel.textContent = file.name;
        fileSizeLabel.textContent = `${(file.size / 1024).toFixed(0)} KB`;

        updateDesignVisibility();
    });

    btnRemoveImage.addEventListener('click', e => {
        e.stopPropagation();
        fileInput.value = '';
        designImage.setAttribute('href', '');
        designImage.style.display = 'none';
        state.hasImage = false;

        dropzoneEmpty.style.display = 'block';
        dropzoneFile.style.display = 'none';
        updateDesignVisibility();
    });

    // ─── 10. CONTROLES DE ZOOM ───
    function applyZoom() {
        transformWrapper.style.transform = `scale(${state.zoom})`;
        zoomLabel.textContent = `${Math.round(state.zoom * 100)}%`;
    }

    document.getElementById('btn-zoom-in').addEventListener('click', () => {
        if (state.zoom < 2.0) {
            state.zoom = Math.min(2.0, state.zoom + 0.2);
            applyZoom();
        }
    });

    document.getElementById('btn-zoom-out').addEventListener('click', () => {
        if (state.zoom > 0.8) {
            state.zoom = Math.max(0.8, state.zoom - 0.2);
            applyZoom();
        }
    });

    document.getElementById('btn-zoom-reset').addEventListener('click', () => {
        state.zoom = 1.0;
        applyZoom();
    });

    // ─── 11. MODAL DE FOTO DE REFERENCIA ───
    btnOpenRef.addEventListener('click', () => {
        modalRef.style.display = 'flex';
    });
    btnCloseRef.addEventListener('click', () => {
        modalRef.style.display = 'none';
    });
    modalRef.addEventListener('click', e => {
        if (e.target === modalRef) modalRef.style.display = 'none';
    });

    // Inicialización
    applyTransform();
    updateGuideBox();
    updateDesignVisibility();
})();
</script>
@endsection
