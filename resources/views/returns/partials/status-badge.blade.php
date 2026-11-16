@php
    use App\Enums\ReturnRequestStatus;
    $labels = [
        ReturnRequestStatus::Recibida->value   => ['text' => 'Recibida', 'bg' => '#FEF3C7', 'color' => '#92400E'],
        ReturnRequestStatus::EnRevision->value => ['text' => 'En revisión', 'bg' => '#E0E7FF', 'color' => '#3730A3'],
        ReturnRequestStatus::Resuelta->value   => ['text' => 'Resuelta', 'bg' => '#D1FAE5', 'color' => '#065F46'],
        ReturnRequestStatus::Rechazada->value  => ['text' => 'Rechazada', 'bg' => '#FEE2E2', 'color' => '#991B1B'],
    ];
    $val = $status instanceof ReturnRequestStatus ? $status->value : (string) $status;
    $badge = $labels[$val] ?? ['text' => ucfirst($val), 'bg' => '#F3F4F6', 'color' => '#374151'];
@endphp
<span style="background:{{ $badge['bg'] }};color:{{ $badge['color'] }};padding:2px 8px;border-radius:2px;font-size:.75rem;font-weight:600;white-space:nowrap;">
    {{ $badge['text'] }}
</span>
