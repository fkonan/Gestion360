@php
$clases = match($estado ?? '') {
    'exitoso' => 'bg-success',
    'fallido' => 'bg-danger',
    'parcial' => 'bg-warning text-dark',
    default => 'bg-secondary',
};
@endphp
<span class="badge {{ $clases }}">{{ ucfirst($estado ?? '-') }}</span>
