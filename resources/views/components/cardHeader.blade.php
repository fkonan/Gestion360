<!-- Componente que contiene el titulo de una seccion ademas de los botonos de volver y crear, siendo el boton de crear opcional -->
@props([
    'titulo', // Título de la sección
    'rutaVolver' => route('home'), // Ruta para el botón volver
    'crear' => false, // Mostrar botón crear (bool o 'newpage')
    'crearRoute' => '', // Ruta para el botón crear
    'crearLabel' => 'Crear', // Texto del botón crear
    'crearModalTarget' => '', // Selector del formulario/modal
    'modalSize' => 'modal-lg', // Tamaño del modal
])

<div class="border rounded-top d-flex justify-content-between align-items-center px-4 bg-primary-subtle">
    <span class="text-left text-light fs-5 fw-medium py-2">{{ $titulo }}</span>
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
</div>
