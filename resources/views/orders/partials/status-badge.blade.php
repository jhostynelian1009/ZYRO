@php
    use App\Enums\OrderStatus;
    $labels = [
        OrderStatus::PendingApproval->value => ['text' => 'Pendiente', 'bg' => '#FEF3C7', 'color' => '#92400E'],
        OrderStatus::Approved->value        => ['text' => 'Aprobado', 'bg' => '#D1FAE5', 'color' => '#065F46'],
        OrderStatus::Rejected->value        => ['text' => 'Rechazado', 'bg' => '#FEE2E2', 'color' => '#991B1B'],
        OrderStatus::Paid->value            => ['text' => 'Pagado', 'bg' => '#EDE9FE', 'color' => '#4C1D95'],
        OrderStatus::Cancelled->value       => ['text' => 'Cancelado', 'bg' => '#F3F4F6', 'color' => '#374151'],
    ];
    $val = $status instanceof OrderStatus ? $status->value : (string) $status;
    $badge = $labels[$val] ?? ['text' => $val, 'bg' => '#F3F4F6', 'color' => '#374151'];
@endphp
<span style="background:{{ $badge['bg'] }};color:{{ $badge['color'] }};padding:2px 8px;border-radius:2px;font-size:.75rem;font-weight:500;white-space:nowrap;">
    {{ $badge['text'] }}
</span>
