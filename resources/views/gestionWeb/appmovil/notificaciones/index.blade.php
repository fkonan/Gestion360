@extends('layouts.dashboard')

@section('title','Gestión appmovil')
    
@section('breadcrumb')
<x-breadcrumb :items="[
        ['name' => 'Inicio', 'url' => route('home')],
        ['name' => 'Gestión appmovil', 'url' => route('gestion-appmovil.index')],
        ['name' => 'Notificaciones'],
    ]" />
<br>
@endsection

@section('content')
<div class="container-fluid p-0 border rounded sidebar-dark-primary tableContainer" style="min-height:150px">
    
    <x-sectionHeader 
        titulo="Gestión de Notificaciones Push"
        rutaVolver="{{ route('gestion-appmovil.index') }}"
        :clasePosition=false
    />

    <form id="formNotificacion" 
        class="p-4"
        action="{{ route('notificaciones.registrar') }}" 
        method="POST" 
        enctype="multipart/form-data"
        onsubmit="deshabilitarSubmit(this)">

        @csrf
        <div class="row justify-content-center">
            <!-- Formulario -->
            <div class="col-md-7">
                <div class="card shadow-sm p-4">
                    <div class="mb-3">
                        <label for="titulo" class="form-label">Título</label>
                        <input type="text" class="form-control" id="titulo" name="titulo" maxlength="100" required>
                        <span class="error text-danger fw-bold" id="error-titulo"></span>
                    </div>

                    <div class="mb-3">
                        <label for="bodyPush" class="form-label">Resumen</label>
                        <textarea class="form-control" id="bodyPush" name="bodyPush" rows="2" maxlength="140" required></textarea>
                        <span class="error text-danger fw-bold" id="error-bodyPush"></span>
                    </div>

                    <div class="mb-3">
                        <label for="bodyCompleto" class="form-label">Cuerpo completo</label>
                        <textarea class="form-control" id="bodyCompleto" name="bodyCompleto" rows="4" required></textarea>
                        <span class="error text-danger fw-bold" id="error-bodyCompleto"></span>
                    </div>

                    <div class="mb-4">
                        <label class="form-label fw-bold">Destino de la notificación</label>
                        <div class="alert alert-info py-2 px-3 small">
                            Puedes enviar la notificación a <strong>usuarios individuales</strong> o a <strong>grupos</strong> (Clientes, Socios o Empleados), pero no a ambos al mismo tiempo.<br>
                            <strong>Importante:</strong> Si seleccionas <strong>Clientes</strong>, la notificación se enviará a <u>todos los usuarios</u>, incluyendo Socios y Empleados.<br>
                            Solo se enviará a quienes tengan la app instalada y hayan iniciado sesión al menos una vez.
                        </div>

                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="usuarios" class="form-label">Seleccionar Usuarios</label>
                                <select id="usuarios" name="usuarios[]" class="form-select" multiple></select>
                                <span class="error text-danger fw-bold" id="error-usuarios"></span>
                            </div>

                            <div class="col-md-6">
                                <label for="grupos" class="form-label">Seleccionar Grupos</label>
                                <select id="grupos" name="grupos[]" class="form-select select2" multiple>
                                    <option value="clientes">Clientes (todos)</option>
                                    <option value="socios">Socios</option>
                                    <option value="empleados">Empleados</option>
                                </select>
                                <span class="error text-danger fw-bold" id="error-grupos"></span>
                            </div>
                        </div>
                        <span class="error text-danger fw-bold" id="error-destino"></span>
                    </div>


                    <div class="mb-3">
                        <label for="programada" class="form-label">Fecha programada</label>
                        <input type="datetime-local" class="form-control" id="programada" name="programada">
                        <span class="error text-danger fw-bold" id="error-programada"></span>
                    </div>

                    <div class="mb-3">
                        <button type="submit" class="btn btn-primary w-100">Guardar notificación</button>
                    </div>
                </div>
            </div>

            <!-- Previsualización -->
            <div class="col-md-5 mt-4 mt-md-0">
                <div class="card shadow-sm mb-4 p-3">
                    <h6 class="text-center">Vista previa Push (resumen)</h6>
                    <div>
                        <div class="fw-bold" id="previewTituloResumen">Título</div>
                        <p class="mb-0 mt-2" id="previewBodyResumen">Resumen de la notificación</p>
                    </div>
                </div>

                <div class="card shadow-sm p-3">
                    <h6 class="text-center">Vista completa dentro de la app</h6>
                    <div>
                        <div class="fw-bold" id="previewTituloCompleto">Título</div>
                        <p class="mt-2" id="previewBodyCompleto">Aquí se mostrará el cuerpo completo de la notificación...</p>
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
    //Actualizar previsualizacion de la notificacion
    document.addEventListener('DOMContentLoaded', () => {
        const tituloInput = document.getElementById('titulo');
        const bodyPushInput = document.getElementById('bodyPush');
        const bodyCompletoInput = document.getElementById('bodyCompleto');

        [tituloInput, bodyPushInput, bodyCompletoInput].forEach(input => {
            input.addEventListener('input', actualizarPreview);
        });

        function actualizarPreview() {
            const titulo = tituloInput.value || 'Título';
            const resumen = bodyPushInput.value || 'Resumen de la notificación';
            const completo = bodyCompletoInput.value || 'Aquí se mostrará el cuerpo completo de la notificación...';

            document.getElementById('previewTituloResumen').innerText = titulo;
            document.getElementById('previewBodyResumen').innerText = resumen;

            document.getElementById('previewTituloCompleto').innerText = titulo;
            document.getElementById('previewBodyCompleto').innerText = completo;
        }
    });

    //Cargar usuarios con app movil
    $(document).ready(function () {
        validarFormulario("#formNotificacion");

        $('.select2').select2({
            theme: 'bootstrap-5',
            width: '100%',
            placeholder: 'Seleccione los grupos',
        });

        $('#usuarios').select2({
            theme: 'bootstrap-5',
            language: {
                inputTooShort: () => 'Introduce más caracteres',
                noResults: () => 'No se encontraron resultados',
                searching: () => 'Buscando...',
                loadingMore: () => 'Cargando más resultados...',
            },
            placeholder: 'Buscar usuarios por nombre o cédula',
            minimumInputLength: 4,
            ajax: {
                url: '{{ route("notificaciones.usuarios-disponibles") }}', 
                dataType: 'json',
                delay: 250,
                data: function (params) {
                    return {
                        query: params.term 
                    };
                },
                processResults: function (data) {
                    return {
                        results: data
                    };
                },
                cache: true
            },
            width: '100%',
            allowClear: true,
            dropdownPosition: 'below'
        });

        //Validar que al seleccionar "clientes" como grupos no se puedan seleccionar más
        let manualChange = false; // bandera para evitar bucles

        $('#grupos').on('change', function () {
            if (manualChange) return; // evitar bucle infinito

            let selected = $(this).val() || [];

            if (selected.includes('clientes') && selected.length > 1) {
                manualChange = true;
                $(this).val(['clientes']).trigger('change');
                manualChange = false;
            }

            else if (!selected.includes('clientes') && selected.length > 0) {
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




