@extends('layouts.admin')

@section('title', 'Solicitud ' . $returnRequest->request_number . ' — Admin — ZYRO')
@section('section_title', 'Detalle de Devolución')

@section('content')
    <div class="admin-page-header">
        <div>
            <div style="display:flex; align-items:center; gap:0.75rem; margin-bottom:0.35rem;">
                <p class="admin-kicker" style="margin-bottom:0;">Operaciones &bull; Devoluciones</p>
                @include('returns.partials.status-badge', ['status' => $returnRequest->status])
            </div>
            <h1 class="admin-title">{{ $returnRequest->request_number }}</h1>
            <p class="admin-subtitle">Registrada el {{ $returnRequest->created_at->format('d/m/Y \a \l\a\s H:i') }}</p>
        </div>
        <div style="display:flex; gap:0.75rem;">
            <a href="{{ route('admin.returns.index') }}" class="btn-action">
                <span>&larr;</span>
                <span>Todas las Solicitudes</span>
            </a>
            @if ($returnRequest->order && $returnRequest->order->phone)
                <a href="{{ app(\App\Contracts\WhatsAppLinkGeneratorInterface::class)->generateAgentContactLink($returnRequest->order) }}" target="_blank" class="btn-action btn-action-primary">
                    📞 WhatsApp Cliente
                </a>
            @endif
        </div>
    </div>

    {{-- Banner de Cuarentena Preventiva --}}
    <div style="background:#FFFBEB; border:1px solid #FCD34D; color:#92400E; padding:1rem 1.25rem; border-radius:2px; margin-bottom:1.5rem; font-size:0.8125rem;">
        <strong>🛡️ Protocolo de Cuarentena de Inventario:</strong>
        Las prendas devueltas permanecen aisladas físicamente. Ningún artículo reingresa al inventario vendible hasta que la inspección física sea completada y el reabastecimiento sea explícitamente autorizado.
    </div>

    <div style="display:grid; grid-template-columns: 1.4fr 1fr; gap: 2rem; align-items: start;">
        {{-- Columna Izquierda: Detalles del Producto, Motivo y Resolución --}}
        <div style="display:flex; flex-direction:column; gap:1.5rem;">
            <div class="card-table-wrapper" style="margin-bottom:0;">
                <div class="card-table-head">
                    <h2 class="card-table-title">Detalles de la Prenda y Motivo</h2>
                </div>
                <div style="padding:1.25rem 1.5rem; font-size:0.875rem; display:flex; flex-direction:column; gap:0.75rem;">
                    <div>
                        <span style="font-size:0.6875rem; font-weight:700; text-transform:uppercase; color:var(--zyro-muted); display:block;">Producto Solicitado</span>
                        <strong style="font-size:1rem; color:var(--zyro-black);">{{ $returnRequest->product->name ?? '—' }}</strong>
                        <div style="font-size:0.75rem; color:var(--zyro-muted); margin-top:0.15rem;">
                            SKU: {{ $returnRequest->product->sku ?? 'ZYR-'.$returnRequest->product_id }} &bull; Talla registrada: <strong>{{ $returnRequest->size ?: 'Única' }}</strong>
                        </div>
                    </div>

                    <div style="border-top:1px solid var(--zyro-border); padding-top:0.75rem; display:flex; justify-content:space-between;">
                        <div>
                            <span style="font-size:0.6875rem; font-weight:700; text-transform:uppercase; color:var(--zyro-muted); display:block;">Cantidad solicitada</span>
                            <strong>{{ $returnRequest->quantity }} unidad(es)</strong>
                        </div>
                        <div>
                            <span style="font-size:0.6875rem; font-weight:700; text-transform:uppercase; color:var(--zyro-muted); display:block;">Solución pretendida</span>
                            <span style="background:var(--zyro-light); border:1px solid var(--zyro-border); padding:2px 8px; border-radius:2px; font-weight:600; font-size:0.75rem;">
                                {{ $returnRequest->requested_solution?->label() ?? $returnRequest->requested_solution }}
                            </span>
                        </div>
                    </div>

                    <div style="border-top:1px solid var(--zyro-border); padding-top:0.75rem;">
                        <span style="font-size:0.6875rem; font-weight:700; text-transform:uppercase; color:var(--zyro-muted); display:block;">Motivo del Cliente</span>
                        <div style="font-weight:600; margin-bottom:0.25rem;">{{ $returnRequest->reason }}</div>
                        <div style="background:#F9FAFB; border:1px solid var(--zyro-border); padding:0.75rem; border-radius:2px; font-size:0.8125rem; color:#4B5563;">
                            &ldquo;{{ $returnRequest->problem_description }}&rdquo;
                        </div>
                    </div>
                </div>
            </div>

            {{-- Formulario de Inspección y Resolución --}}
            @if ($returnRequest->status->value === 'recibida')
                <div class="card-table-wrapper" style="margin-bottom:0;">
                    <div class="card-table-head">
                        <h2 class="card-table-title">Paso 1: Iniciar Inspección Física</h2>
                    </div>
                    <div style="padding:1.25rem 1.5rem;">
                        <p style="font-size:0.8125rem; color:var(--zyro-muted); margin-bottom:1rem;">
                            Al recibir el paquete o prenda física, inicia el proceso de verificación para documentar su estado.
                        </p>
                        <form method="POST" action="{{ route('admin.returns.start-inspection', $returnRequest) }}">
                            @csrf
                            <div style="margin-bottom:1rem;">
                                <label style="display:block; font-size:0.75rem; font-weight:600; margin-bottom:0.25rem;">Notas iniciales de recepción (opcional)</label>
                                <input type="text" name="note" class="admin-input" placeholder="Ej: Prenda recibida en sucursal con empaque sellado..." style="width:100%; padding:0.5rem; border:1px solid var(--zyro-border);">
                            </div>
                            <button type="submit" class="btn-action btn-action-primary">
                                🔍 Iniciar Inspección Física
                            </button>
                        </form>
                    </div>
                </div>
            @elseif ($returnRequest->status->value === 'en_revision')
                <div class="card-table-wrapper" style="margin-bottom:0;">
                    <div class="card-table-head">
                        <h2 class="card-table-title">Paso 2: Dictamen y Resolución Administrativa</h2>
                    </div>
                    <div style="padding:1.25rem 1.5rem;">
                        <form method="POST" action="{{ route('admin.returns.resolve', $returnRequest) }}">
                            @csrf
                            <div style="display:grid; grid-template-columns:1fr 1fr; gap:1rem; margin-bottom:1rem;">
                                <div>
                                    <label style="display:block; font-size:0.75rem; font-weight:700; margin-bottom:0.35rem;">Decisión Final *</label>
                                    <select name="status" class="admin-input" required style="width:100%; padding:0.5rem; border:1px solid var(--zyro-border);">
                                        <option value="resuelta">✓ Aprobar (Resuelta)</option>
                                        <option value="rechazada">✗ Rechazar Solicitud</option>
                                    </select>
                                </div>
                                <div>
                                    <label style="display:block; font-size:0.75rem; font-weight:700; margin-bottom:0.35rem;">Condición Inspeccionada *</label>
                                    <select name="inspected_condition" class="admin-input" required style="width:100%; padding:0.5rem; border:1px solid var(--zyro-border);">
                                        @foreach ($conditions as $cond)
                                            <option value="{{ $cond->value }}">{{ $cond->label() }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>

                            <div style="margin-bottom:1rem;">
                                <label style="display:block; font-size:0.75rem; font-weight:700; margin-bottom:0.35rem;">Explicación y Dictamen Administrativo *</label>
                                <textarea name="admin_decision" rows="3" required class="admin-input" placeholder="Detalla los hallazgos de la inspección física y el motivo de la resolución..." style="width:100%; padding:0.5rem; border:1px solid var(--zyro-border);"></textarea>
                            </div>

                            <div style="margin-bottom:1.25rem; padding:0.875rem; background:#F4F4F5; border-radius:2px;">
                                <label style="display:flex; align-items:center; gap:0.5rem; font-size:0.8125rem; font-weight:600; cursor:pointer;">
                                    <input type="checkbox" name="should_restock" value="1">
                                    <span>¿Reincorporar prenda al inventario activo vendible? (Solo si estado es óptimo)</span>
                                </label>
                                <p style="font-size:0.6875rem; color:var(--zyro-muted); margin-left:1.5rem; margin-top:0.25rem;">
                                    Si no se marca, la prenda permanece en inventario de merma/garantía y no suma stock al catálogo.
                                </p>
                            </div>

                            <div style="border-top:1px solid var(--zyro-border); padding-top:1rem; margin-bottom:1rem;">
                                <h3 style="font-size:0.75rem; font-weight:700; text-transform:uppercase; margin-bottom:0.5rem; color:var(--zyro-muted);">
                                    Prenda de Reemplazo (si aplica cambio)
                                </h3>
                                <div style="display:grid; grid-template-columns:2fr 1fr; gap:1rem;">
                                    <div>
                                        <select name="replacement_product_id" class="admin-input" style="width:100%; padding:0.5rem; border:1px solid var(--zyro-border);">
                                            <option value="">-- Sin prenda de reemplazo --</option>
                                            @foreach ($availableProducts as $prod)
                                                <option value="{{ $prod->id }}">{{ $prod->name }} (Stock: {{ $prod->stock }})</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div>
                                        <input type="text" name="replacement_size" placeholder="Talla (ej: M)" class="admin-input" style="width:100%; padding:0.5rem; border:1px solid var(--zyro-border);">
                                    </div>
                                </div>
                            </div>

                            <div style="display:flex; justify-content:flex-end;">
                                <button type="submit" class="btn-action btn-action-primary">
                                    Registrar Resolución Final
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            @else
                <div class="card-table-wrapper" style="margin-bottom:0;">
                    <div class="card-table-head">
                        <h2 class="card-table-title">Dictamen Emitido</h2>
                    </div>
                    <div style="padding:1.25rem 1.5rem; font-size:0.8125rem;">
                        <p style="margin-bottom:0.5rem;"><strong>Condición registrada:</strong> {{ $returnRequest->inspected_condition?->label() ?? '—' }}</p>
                        <p style="margin-bottom:0.5rem;"><strong>Dictamen:</strong> {{ $returnRequest->admin_decision }}</p>
                        <p style="margin-bottom:0.5rem;">
                            <strong>Reincorporado a stock:</strong>
                            {{ $returnRequest->is_restocked ? 'Sí, reincorporado el ' . $returnRequest->restocked_at?->format('d/m/Y H:i') : 'No reincorporado a inventario vendible' }}
                        </p>
                        @if ($returnRequest->replacementProduct)
                            <p><strong>Prenda de reemplazo:</strong> {{ $returnRequest->replacementProduct->name }} (Talla: {{ $returnRequest->replacement_size }})</p>
                        @endif
                    </div>
                </div>
            @endif

            {{-- Historial de Actuaciones --}}
            <div class="card-table-wrapper" style="margin-bottom:0;">
                <div class="card-table-head">
                    <h2 class="card-table-title">Historial de Actuaciones</h2>
                </div>
                <div class="table-responsive">
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>Fecha</th>
                                <th>Estado</th>
                                <th>Responsable</th>
                                <th>Notas</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($returnRequest->histories as $hist)
                                <tr>
                                    <td style="color:var(--zyro-muted);">{{ $hist->created_at->format('d/m/Y H:i') }}</td>
                                    <td><span style="font-family:monospace; font-weight:600;">{{ $hist->to_status }}</span></td>
                                    <td>{{ $hist->changedBy->name ?? 'Sistema' }}</td>
                                    <td>{{ $hist->note }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="4" style="text-align:center; color:var(--zyro-muted);">Sin movimientos registrados.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- Columna Derecha: Pedido, Cliente y Revisor --}}
        <div style="display:flex; flex-direction:column; gap:1.5rem;">
            <div class="card-table-wrapper" style="margin-bottom:0;">
                <div class="card-table-head">
                    <h2 class="card-table-title">Pedido Vinculado</h2>
                </div>
                <div style="padding:1.25rem 1.5rem; font-size:0.8125rem; display:flex; flex-direction:column; gap:0.5rem;">
                    <div>
                        <span style="font-size:0.6875rem; font-weight:700; text-transform:uppercase; color:var(--zyro-muted);">Número de Pedido</span>
                        <div>
                            <a href="{{ route('admin.orders.show', $returnRequest->order_id) }}" style="font-family:monospace; font-weight:700; font-size:1rem; text-decoration:underline;">
                                {{ $returnRequest->order->order_number ?? '—' }}
                            </a>
                        </div>
                    </div>
                    <div>
                        <span style="font-size:0.6875rem; font-weight:700; text-transform:uppercase; color:var(--zyro-muted);">Canal de Venta Original</span>
                        <div style="font-weight:600;">{{ $returnRequest->order->channel_origin?->label() ?? 'Web' }}</div>
                    </div>
                    <div>
                        <span style="font-size:0.6875rem; font-weight:700; text-transform:uppercase; color:var(--zyro-muted);">Estado del Pedido</span>
                        <div>@include('orders.partials.status-badge', ['status' => $returnRequest->order->status])</div>
                    </div>
                </div>
            </div>

            <div class="card-table-wrapper" style="margin-bottom:0;">
                <div class="card-table-head">
                    <h2 class="card-table-title">Cliente Solicitante</h2>
                </div>
                <div style="padding:1.25rem 1.5rem; font-size:0.8125rem; display:flex; flex-direction:column; gap:0.5rem;">
                    <div style="font-weight:600; font-size:0.9375rem;">{{ $returnRequest->order->customer_name ?: ($returnRequest->user->name ?? '—') }}</div>
                    <div style="color:var(--zyro-muted);">Email: {{ $returnRequest->user->email ?? '—' }}</div>
                    <div style="color:var(--zyro-muted);">Teléfono: {{ $returnRequest->order->phone ?? '—' }}</div>
                </div>
            </div>

            <div class="card-table-wrapper" style="margin-bottom:0;">
                <div class="card-table-head">
                    <h2 class="card-table-title">Operador / Revisor</h2>
                </div>
                <div style="padding:1.25rem 1.5rem; font-size:0.8125rem;">
                    @if ($returnRequest->reviewer)
                        <div style="font-weight:600;">{{ $returnRequest->reviewer->name }}</div>
                        <div style="color:var(--zyro-muted); font-size:0.75rem;">{{ $returnRequest->reviewer->email }}</div>
                    @else
                        <span style="color:var(--zyro-muted);">Sin revisor asignado aún.</span>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection
