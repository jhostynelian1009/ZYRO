@extends('layouts.store')

@section('title', 'Solicitud ' . $returnRequest->request_number . ' — ZYRO')

@section('content')
<div class="orders-page-wrapper container">
    <div class="cart-header">
        <p style="font-size:0.6875rem;font-weight:700;letter-spacing:0.2em;text-transform:uppercase;color:var(--zyro-muted);margin-bottom:0.25rem;">
            <a href="{{ route('customer.returns.index') }}" style="color:var(--zyro-muted);">Mis Devoluciones</a> &rsaquo; Detalle
        </p>
        <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:1rem;">
            <div style="display:flex;align-items:center;gap:1rem;">
                <h1 style="font-family:'Cormorant Garamond',Georgia,serif;font-size:2.25rem;font-weight:600;line-height:1.15;color:var(--zyro-black);">
                    {{ $returnRequest->request_number }}
                </h1>
                @include('returns.partials.status-badge', ['status' => $returnRequest->status])
            </div>
            <a href="{{ route('customer.returns.index') }}" class="btn-action">
                &larr; Volver al Listado
            </a>
        </div>
    </div>

    {{-- Estado actual explicativo --}}
    @if ($returnRequest->status->value === 'recibida')
        <div style="background:#FFFBEB;border:1px solid #FCD34D;border-left:3px solid #F59E0B;padding:1rem 1.25rem;margin-bottom:1.5rem;font-size:0.8125rem;color:#92400E;border-radius:2px;">
            <strong>Solicitud recibida:</strong> Estamos revisando la información. Por favor entrega o envía la prenda a nuestra sucursal para dar inicio a la inspección física de control de calidad.
        </div>
    @elseif ($returnRequest->status->value === 'en_revision')
        <div style="background:#EEF2FF;border:1px solid #C7D2FE;border-left:3px solid #6366F1;padding:1rem 1.25rem;margin-bottom:1.5rem;font-size:0.8125rem;color:#3730A3;border-radius:2px;">
            <strong>En inspección física:</strong> La prenda se encuentra en verificación de laboratorio y estado textil por parte de nuestro equipo. Recibirás respuesta a la brevedad.
        </div>
    @elseif ($returnRequest->status->value === 'resuelta')
        <div style="background:#ECFDF5;border:1px solid #A7F3D0;border-left:3px solid #10B981;padding:1rem 1.25rem;margin-bottom:1.5rem;font-size:0.8125rem;color:#065F46;border-radius:2px;">
            <strong>Solicitud resuelta:</strong> Tu solicitud fue autorizada y procesada.
            @if ($returnRequest->admin_decision)
                <div style="margin-top:0.35rem;font-weight:normal;">Dictamen: &ldquo;{{ $returnRequest->admin_decision }}&rdquo;</div>
            @endif
        </div>
    @elseif ($returnRequest->status->value === 'rechazada')
        <div style="background:#FEF2F2;border:1px solid #FECACA;border-left:3px solid #DC2626;padding:1rem 1.25rem;margin-bottom:1.5rem;font-size:0.8125rem;color:#991B1B;border-radius:2px;">
            <strong>Solicitud no admitida:</strong> Tras la evaluación, la solicitud no cumplió los requisitos de cambio/garantía.
            @if ($returnRequest->admin_decision)
                <div style="margin-top:0.35rem;font-weight:normal;">Motivo: &ldquo;{{ $returnRequest->admin_decision }}&rdquo;</div>
            @endif
        </div>
    @endif

    <div style="display:grid;grid-template-columns:1fr;gap:2rem;">
        <div style="background:#FFFFFF;border:1px solid var(--zyro-border);padding:1.5rem;border-radius:2px;">
            <h2 style="font-size:0.6875rem;font-weight:700;letter-spacing:0.15em;text-transform:uppercase;color:var(--zyro-muted);margin-bottom:1rem;">
                Información del Caso
            </h2>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:1.5rem;font-size:0.8125rem;">
                <div>
                    <span style="color:var(--zyro-muted);display:block;margin-bottom:0.15rem;">Prenda:</span>
                    <strong style="color:var(--zyro-black);font-size:0.9375rem;">{{ $returnRequest->product->name ?? '—' }}</strong>
                    <div style="color:var(--zyro-muted);margin-top:0.15rem;">Talla: {{ $returnRequest->size ?: 'Única' }} &bull; Cantidad: {{ $returnRequest->quantity }}</div>
                </div>
                <div>
                    <span style="color:var(--zyro-muted);display:block;margin-bottom:0.15rem;">Pedido original:</span>
                    <a href="{{ route('orders.show', $returnRequest->order_id) }}" style="font-family:monospace;font-weight:600;text-decoration:underline;">
                        {{ $returnRequest->order->order_number ?? '—' }}
                    </a>
                </div>
                <div>
                    <span style="color:var(--zyro-muted);display:block;margin-bottom:0.15rem;">Solución solicitada:</span>
                    <strong>{{ $returnRequest->requested_solution?->label() ?? $returnRequest->requested_solution }}</strong>
                </div>
                <div>
                    <span style="color:var(--zyro-muted);display:block;margin-bottom:0.15rem;">Fecha de solicitud:</span>
                    <span>{{ $returnRequest->created_at->format('d/m/Y H:i') }}</span>
                </div>
            </div>

            <div style="margin-top:1.25rem;padding-top:1.25rem;border-top:1px solid var(--zyro-border);">
                <span style="color:var(--zyro-muted);display:block;margin-bottom:0.25rem;font-size:0.75rem;font-weight:700;text-transform:uppercase;">Motivo declarado:</span>
                <p style="font-weight:600;font-size:0.875rem;margin-bottom:0.35rem;">{{ $returnRequest->reason }}</p>
                <div style="background:#F9FAFB;border:1px solid var(--zyro-border);padding:0.75rem;border-radius:2px;font-size:0.8125rem;color:#4B5563;">
                    &ldquo;{{ $returnRequest->problem_description }}&rdquo;
                </div>
            </div>

            @if ($returnRequest->replacementProduct)
                <div style="margin-top:1.25rem;padding-top:1.25rem;border-top:1px solid var(--zyro-border);">
                    <span style="color:var(--zyro-muted);display:block;margin-bottom:0.25rem;font-size:0.75rem;font-weight:700;text-transform:uppercase;">Prenda de reemplazo asignada:</span>
                    <strong style="color:var(--zyro-black);">{{ $returnRequest->replacementProduct->name }}</strong>
                    @if ($returnRequest->replacement_size)
                        <span>(Talla: {{ $returnRequest->replacement_size }})</span>
                    @endif
                </div>
            @endif

            {{-- Historial de eventos visibles al cliente --}}
            <div style="margin-top:1.5rem;padding-top:1.5rem;border-top:1px solid var(--zyro-border);">
                <h3 style="font-size:0.75rem;font-weight:700;text-transform:uppercase;color:var(--zyro-muted);margin-bottom:0.75rem;">
                    Línea de tiempo de la solicitud
                </h3>
                <div style="display:flex;flex-direction:column;gap:0.75rem;">
                    @foreach ($returnRequest->histories as $hist)
                        <div style="display:flex;align-items:flex-start;gap:0.75rem;font-size:0.8125rem;">
                            <span style="font-size:0.75rem;color:var(--zyro-muted);min-width:120px;">{{ $hist->created_at->format('d/m/Y H:i') }}</span>
                            <div>
                                <strong style="color:var(--zyro-black);">{{ ucfirst(str_replace('_', ' ', $hist->to_status)) }}:</strong>
                                <span style="color:#4B5563;">{{ $hist->note }}</span>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            <div style="margin-top:1.5rem;padding-top:1rem;border-top:1px solid var(--zyro-border);display:flex;align-items:center;justify-content:space-between;">
                <a href="{{ route('customer.returns.index') }}" class="btn-action">
                    &larr; Volver a Mis Devoluciones
                </a>
                <a href="{{ app(\App\Contracts\WhatsAppLinkGeneratorInterface::class)->generateOrderInquiryLink($returnRequest->order) }}" target="_blank" class="btn-action btn-action-primary">
                    💬 Consultar vía WhatsApp
                </a>
            </div>
        </div>
    </div>
</div>
@endsection
