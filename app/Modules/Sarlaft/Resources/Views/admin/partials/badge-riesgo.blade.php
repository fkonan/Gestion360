@php
$nivelNormalizado = strtolower(trim((string) ($nivel ?? '')));
[$clases, $texto] = match(true) {
    in_array($nivelNormalizado, ['vinculante', 'alto'])    => ['bg-danger', 'Lista Vinculante'],
    in_array($nivelNormalizado, ['restrictiva', 'medio'])  => ['bg-secondary', 'Lista Restrictiva'],
    default                                                => ['bg-light text-dark', 'Coincidencia'],
};
@endphp
<span class="badge {{ $clases }}">{{ $texto }}</span>
