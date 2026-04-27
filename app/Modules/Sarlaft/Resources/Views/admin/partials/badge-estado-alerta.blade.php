@php
$clases = match($estado ?? '') {
    'pendiente' => 'bg-warning text-dark',
    'en_revision' => 'bg-info text-dark',
    'atendida' => 'bg-success',
    'descartada' => 'bg-secondary',
    default => 'bg-secondary',
};
@endphp
<span class="badge {{ $clases }}">{{ str_replace('_', ' ', ucfirst($estado ?? '-')) }}</span>
