@props([
    'titulo',
    'descripcion' => '',
    'onclick' => null,
])

<tr class="align-middle reporte-item" data-titulo="{{ $titulo }}">
    {{-- Columna 1: Título --}}
    <td class="text-dark py-2 px-2">
        <div class="d-flex align-items-center gap-2">
            <span class="fw-medium">{{ $titulo }}</span>
        </div>
    </td>

    {{-- Columna 2: Descripción --}}
    <td class="text-muted small py-2 px-2">
        {{ $descripcion }}
    </td>

    {{-- Columna 3: Acción (icono botón) --}}
    <td class="text-center align-middle py-2 px-2" style="width: 50px;">
        @if($onclick)
            <button 
                class="btn btn-link p-0 m-0" 
                onclick="{{ $onclick }}"
                title="Ver reporte"
            >
                <i class="fas fa-eye fs-5 text-secondary"></i>
            </button>
        @endif
    </td>
</tr>

