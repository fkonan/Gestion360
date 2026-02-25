<!-- Componente que contiene el titulo de una seccion ademas de los botonos de volver y otros opcionales -->
@props([
    'titulo',                       // Título de la sección
    'rutaVolver' => route('home'),  // Ruta para el botón volver
    'crear' => false,               // Mostrar botón crear, 1. true => crear en modal , 2 "newpage" => el crear es en una nueva pagina
    'crearRoute' => '',             // Ruta para el botón crear
    'crearLabel' => 'Crear',        // Texto del botón crear
    'permisoCrear' => null,         // Permiso para mostrar el botón crear
    'crearModalTarget' => '',       // Selector del formulario/modal
    'modalSize' => 'modal-lg',      // Tamaño del modal
    'excel' => false,               // Mostrar boton de descargar en excel
    'excelReporte' => false,        // Test excel reportes
    'excelRoute' => '',             // Ruta de la data del excel
    'excelName' => '',              // Nombre del archivo excel
    'clasePosition' => true,        // Clase "botonBoostrapTable" la cual ayuda a que los botones se vean bien si estan antes de una tabla
])

<div class="border rounded-top d-flex justify-content-between align-items-center px-4 bg-primary-subtle">
    <span class="text-left text-light fs-5 fw-medium py-1">{{ $titulo }}</span>
</div>

<div class="{{ $clasePosition ? 'botonBoostrapTable mt-2' : 'mt-3' }} ms-4">
    <!-- <a style="width: 150px;" class="btn btn-success fw-bold" href="{{ $rutaVolver }}">Regresar</a> -->
    @if (trim($slot))
        <span class="ms-2 d-inline-flex align-items-center gap-2">
            {{ $slot }}
        </span>
    @endif

    @if((!$permisoCrear || auth()->user()->can('$permisoCrear')) && $crear)
        @if($crear === 'newpage')
            <a style="min-width: 150px;" class="btn fw-bold bg-warning"
                href="{{ $crearRoute }}">
                {{ $crearLabel }}
            </a>
        @else
            <a style="min-width: 150px;" class="btn fw-bold bg-warning"
                onclick="cargarModal(`{{ $crearRoute }}`, '{{ $crearLabel }}', '{{ $crearModalTarget }}', '{{ $modalSize }}')">
                {{ $crearLabel }}
            </a>
        @endif
    @endif


    @if($excel)
        <button id="exportar" class="btn fw-bold btn-primary"
            onclick="exportarExcel(`exportar`, '{{ $excelRoute }}', '{{ $excelName }}')">
            Descargar Excel
        </button>
    @endif

    @if($excelReporte)
        <button id="exportar"
            class="btn fw-bold btn-primary"
            data-url="{!! $excelRoute !!}"
            data-name="{{ $excelName }}"
            onclick="exportarExcel(this.id, this.dataset.url, this.dataset.name)">
            Descargar Excel
        </button>
    @endif
</div>
