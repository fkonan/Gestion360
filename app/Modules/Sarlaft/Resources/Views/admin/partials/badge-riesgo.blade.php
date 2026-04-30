@php
$clases = match($nivel ?? 'ninguno') {
    'critico' => 'bg-dark',
    'alto' => 'bg-danger',
    'medio' => 'bg-warning text-dark',
    'bajo' => 'bg-info text-dark',
    default => 'bg-success',
};
@endphp
<span class="badge {{ $clases }}">{{ ucfirst($nivel ?? 'ninguno') }}</span>
