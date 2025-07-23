<!-- Componente que contiene el titulo de una seccion ademas de los botonos de volver y otros opcionales -->
@props([
    'titulo',                       // Título de la sección
    'rutaVolver' => route('home'),  // Ruta para el botón volver
    'crear' => false,               // Mostrar botón crear, 1. true => crear en modal , 2 "newpage" => el crear es en una nueva pagina
    'crearRoute' => '',             // Ruta para el botón crear
    'crearLabel' => 'Crear',        // Texto del botón crear
    'crearModalTarget' => '',       // Selector del formulario/modal
    'modalSize' => 'modal-lg',      // Tamaño del modal
    'excel' => false,               // Mostrar boton de descargar en excel 
    'excelRoute' => '',             // Ruta de la data del excel
    'excelName' => '',              // Nombre del archivo excel
])

<div class="border rounded-top d-flex justify-content-between align-items-center px-4 bg-primary-subtle">
    <span class="text-left text-light fs-5 fw-medium py-1">{{ $titulo }}</span>
</div>

<div class="botonBoostrapTable ms-4 mt-2">
    <a style="width: 150px;" class="btn btn-success fw-bold" href="{{ $rutaVolver }}">Regresar</a>

    @if($crear === 'newpage')
        <a style="min-width: 150px;" class="btn fw-bold bg-warning"
            href="{{ $crearRoute }}">
            {{ $crearLabel }}
        </a>
    @elseif($crear)
        <a style="min-width: 150px;" class="btn fw-bold bg-warning"
            onclick="cargarModal(`{{ $crearRoute }}`, '{{ $crearLabel }}', '{{ $crearModalTarget }}', '{{ $modalSize }}')">
            {{ $crearLabel }}
        </a>    
    @endif

    @if($excel)
        <a id="exportar" class="btn fw-bold btn-primary" 
            onclick="exportarExcel(`exportar`, '{{ $excelRoute }}', '{{ $excelName }}')"> 
            Descargar Excel 
        </a>
    @endif
</div>
