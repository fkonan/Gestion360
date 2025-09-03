@props([
    'consecutivo' => '',
    'area' => '',
    'icono' => '',
    'titulo',
    'descripcion' => '',
    'formato' => '',
    'onclick' => null,
])

<tr class="align-middle reporte-item" data-titulo="{{ $titulo }}">
    {{-- Columna 1: Consecutivo --}}
    <td class="py-2 px-2">
        <div class="d-flex align-items-center gap-2">
            <span class="fw-medium">{{ $consecutivo }}</span>
        </div>
    </td>

    {{-- Columna 2: Título --}}
    <td class="py-2 px-2">
        <div class="d-flex align-items-center gap-2">
           {{--  <i class="{{ $icono}} fs-5 text-secondary"></i> --}}
            <span class="fw-medium">{{ $titulo }}</span>
        </div>
    </td>

    {{-- Columna 3: Area --}}
    <td class="py-2 px-2">
        <div class="d-flex align-items-center gap-2">
            <span class="fw-medium">{{ $area }}</span>
        </div>
    </td>

    {{-- Columna 4: Descripción --}}
    <td class="text-muted small py-2 px-2">
        {{ $descripcion }}
    </td>

    {{-- Columna 5: Formato salida --}}
    <td class="py-2 px-2">
        <div class="d-flex align-items-center gap-2">
            <span class="fw-medium">EXCEL, PDF</span>
        </div>
    </td>

    {{-- Columna 6: Acción (icono botón) --}}
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

