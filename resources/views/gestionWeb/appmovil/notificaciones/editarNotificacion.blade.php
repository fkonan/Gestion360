@extends('layouts.dashboard')

@section('title','Gestión appmovil')
    
@section('breadcrumb')
<x-breadcrumb :items="[
        ['name' => 'Inicio', 'url' => route('home')],
        ['name' => 'Gestión appmovil', 'url' => route('gestion-appmovil.index')],
        ['name' => 'Notificaciones', 'url' => route('notificaciones.index')],
        ['name' => 'Editar notificación']
    ]" />
<br>
@endsection

@section('content')

<div class="container-fluid p-0 border rounded sidebar-dark-primary tableContainer" style="min-height:150px">
    <x-sectionHeader 
        titulo="Gestión de Notificaciones"
        rutaVolver="{{ route('notificaciones.index') }}"
        :clasePosition=false
    />

    <form id="formNotificacion"
        class="py-5"
        action="{{ route('notificaciones.update', ['id' => $notificacion->id]) }}" 
        method="POST"
        enctype="multipart/form-data"
        onsubmit="deshabilitarSubmit(this)">
        
        @csrf
        @method('PUT')
        @if (!$editable)
          <div class="alert alert-info mb-4 mx-4">
            <strong>Nota:</strong> Editar una notificación ya enviada o en proceso <u>no generará una nueva notificación push</u>; solo actualizará el contenido en el historial de notificaciones para los usuarios que la recibieron.
        </div>
        @endif
        <div class="row justify-content-center mx-2 gy-5">
            <!-- Formulario -->
            <div class="col-md-7">
            <div class="card shadow rounded-3 p-4">
                <h5 class="card-title fw-medium text-muted mb-2">Datos de la notificación - Editar</h5>
                <hr>

                <div class="mb-4">
                <label for="titulo" class="form-label">Título</label>
                <input type="text" class="form-control form-control-sm" id="titulo" name="titulo" maxlength="50" required value="{{ $notificacion->titulo }}">
                <div class="d-flex justify-content-between small">
                    <span class="error text-danger fw-bold" id="error-titulo"></span>
                    <span class="text-muted" id="count-titulo">{{ strlen($notificacion->titulo) }} / 50</span>
                </div>
                </div>

                <div class="mb-4">
                <label for="bodyPush" class="form-label">Resumen</label>
                <textarea class="form-control form-control-sm" id="bodyPush" name="bodyPush" rows="2" maxlength="140" required>{{ $notificacion->bodyPush }}</textarea>
                <div class="d-flex justify-content-between small">
                    <span class="error text-danger fw-bold" id="error-bodyPush"></span>
                    <span class="text-muted" id="count-bodyPush">{{ strlen($notificacion->bodyPush) }} / 140</span>
                </div>
                </div>

                <div class="mb-4">
                <label for="bodyCompleto" class="form-label">Cuerpo completo</label>
                <textarea class="form-control form-control-sm" id="bodyCompleto" name="bodyCompleto" rows="4" required>{{ $notificacion->bodyCompleto }}</textarea>
                <div class="d-flex justify-content-between small">
                    <span class="error text-danger fw-bold" id="error-bodyCompleto"></span>
                    <span class="text-muted" id="count-bodyCompleto">{{ strlen($notificacion->bodyCompleto) }} caracteres</span>
                </div>
                </div>

                <div class="mb-4">
                <label class="form-label fw-bold">Destino de la notificación</label>

                <div class="alert alert-warning small">
                    <strong>Nota:</strong> El destino no se puede ser modificado luego de ser creada la notificacion.
                </div>
               
                <div class="row g-3 mt-4">
                    <div class="col-md-6">
                    <label for="usuarios" class="form-label">Seleccionar Usuarios</label>
                    <select id="usuarios" name="usuarios[]" class="form-select form-select-sm" multiple disabled></select>
                    <span class="error text-danger fw-bold" id="error-usuarios"></span>
                    </div>
                    <div class="col-md-6">
                    <label for="grupos" class="form-label">Seleccionar Grupos</label>
                    <select id="grupos" name="grupos[]" class="form-select form-select-sm select2" multiple disabled>
                        <option value="clientes (todos)" {{ in_array('clientes', $gruposSeleccionados) ? 'selected' : '' }}>Clientes (todos)</option>
                        <option value="socios" {{ in_array('socios', $gruposSeleccionados) ? 'selected' : '' }}>Socios</option>
                        <option value="empleados" {{ in_array('empleados', $gruposSeleccionados) ? 'selected' : '' }}>Empleados</option>
                    </select>
                    <span class="error text-danger fw-bold" id="error-grupos"></span>
                    </div>
                </div>
                <span class="error text-danger fw-bold" id="error-destino"></span>
                </div>

                <div class="mb-4 mt-4">
                <label for="programada" class="form-label">Fecha programada <span class="text-muted">(opcional)</span></label>
                <input type="datetime-local" class="form-control form-control-sm" id="programada" name="programada"
                    value="{{ $notificacion->programada ?? '' }}"
                    {{ $editable ? '' : 'disabled' }}>
                <span class="error text-danger fw-bold" id="error-programada"></span>
                </div>

                <div class="mt-4">
                <button type="submit" class="btn btn-primary w-100 fw-bold py-2">
                    <i class="fas fa-save me-1"></i> Guardar cambios
                </button>
                </div>
            </div>
            </div>

            <!-- Vista previa -->
            <div class="col-md-5">
            <div class="card shadow-sm p-4 mb-4 rounded-3">
                <h6 class="text-center fw-bold text-primary mb-2">Vista previa Push (resumen)</h6>
                <hr class="my-2">
                <div class="ps-2">
                <div class="fw-bold" id="previewTituloResumen">{{ $notificacion->titulo }}</div>
                <p class="mb-0 mt-2 small text-muted" id="previewBodyResumen">{{ $notificacion->bodyPush }}</p>
                </div>
            </div>

            <div class="card shadow-sm p-4 rounded-3">
                <h6 class="text-center fw-bold text-primary mb-2">Vista completa en la app</h6>
                <hr class="my-2">
                <div class="ps-2">
                <div class="fw-bold" id="previewTituloCompleto">{{ $notificacion->titulo }}</div>
                <p class="mt-2 small text-muted" id="previewBodyCompleto">{{ $notificacion->bodyCompleto }}</p>
                </div>
            </div>
            </div>
        </div>
    </form>
</div>
@endsection

@pushOnce('script')
@vite(['resources/js/cargarModal.js'])
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
    // Función para actualizar la vista previa en tiempo real 
    document.addEventListener('DOMContentLoaded', () => {
        const tituloInput = document.getElementById('titulo');
        const bodyPushInput = document.getElementById('bodyPush');
        const bodyCompletoInput = document.getElementById('bodyCompleto');

        [tituloInput, bodyPushInput, bodyCompletoInput].forEach(input => {
            input.addEventListener('input', actualizarPreview);
        });

        function actualizarPreview() {
            document.getElementById('previewTituloResumen').innerText = tituloInput.value || 'Título';
            document.getElementById('previewBodyResumen').innerText = bodyPushInput.value || 'Resumen de la notificación';
            document.getElementById('previewTituloCompleto').innerText = tituloInput.value || 'Título';
            document.getElementById('previewBodyCompleto').innerText = bodyCompletoInput.value || 'Aquí se mostrará el cuerpo completo de la notificación...';
        }

        const updateCounter = (input, counterEl, max = null) => {
            input.addEventListener("input", () => {
                const length = input.value.length;
                counterEl.textContent = max ? `${length} / ${max}` : `${length} caracteres`;
            });
        };

        updateCounter(tituloInput, document.getElementById("count-titulo"), 50);
        updateCounter(bodyPushInput, document.getElementById("count-bodyPush"), 140);
        updateCounter(bodyCompletoInput, document.getElementById("count-bodyCompleto"));
    });

   
    $(document).ready(function () {
        validarFormulario("#formNotificacion");

        $('.select2').select2({
            theme: 'bootstrap-5',
            width: '100%',
            placeholder: 'Seleccione los grupos',
        });

         // Cargar usuarios segun nombre o cédula 
        $('#usuarios').select2({
            theme: 'bootstrap-5',
            placeholder: 'Buscar usuarios por nombre o cédula',
            minimumInputLength: 4,
            ajax: {
                url: '{{ route("notificaciones.usuarios-disponibles") }}',
                dataType: 'json',
                delay: 250,
                data: params => ({ query: params.term }),
                processResults: data => ({ results: data }),
                cache: true
            },
            width: '100%',
            allowClear: true,
            dropdownPosition: 'below'
        });

        // Maneja que no se pueda seleccionar "clientes" junto a otros grupos
        let manualChange = false;
        $('#grupos').on('change', function () {
            if (manualChange) return;

            let selected = $(this).val() || [];

            if (selected.includes('clientes') && selected.length > 1) {
                manualChange = true;
                $(this).val(['clientes']).trigger('change');
                manualChange = false;
            } else if (!selected.includes('clientes')) {
                const filtered = selected.filter(v => v !== 'clientes');
                if (filtered.length !== selected.length) {
                    manualChange = true;
                    $(this).val(filtered).trigger('change');
                    manualChange = false;
                }
            }
        });
    });
</script>
@endpushOnce

