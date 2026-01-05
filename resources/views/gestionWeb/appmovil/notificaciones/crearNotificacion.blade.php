@extends('layouts.dashboard')

@section('title','Gestión appmovil')

@section('breadcrumb')
<x-breadcrumb :items="[
        ['name' => 'Inicio', 'url' => route('home')],
        ['name' => 'Gestión appmovil', 'url' => route('gestion-appmovil.index')],
        ['name' => 'Notificaciones', 'url' => route('notificaciones.index')],
        ['name' => 'Crear notificación']
    ]" />
<br>
@endsection

@section('content')
<div class="container-fluid p-0 border rounded sidebar-dark-primary tableContainer" style="min-height:150px">

  <x-sectionHeader
    titulo="Gestión de Notificaciones Push"
    rutaVolver="{{ route('notificaciones.index') }}"
    :clasePosition=false />

  <form id="formNotificacion"
    class="py-5"
    action="{{ route('notificaciones.registrar') }}"
    method="POST"
    enctype="multipart/form-data"
    onsubmit="deshabilitarSubmit(this)">

    @csrf
    <div class="row justify-content-center mx-2 gy-5">
      <!-- Formulario -->
      <div class="col-md-7">
        <div class="card shadow rounded-3 p-4">
          <h5 class="card-title fw-medium text-muted mb-2">Datos de la notificación</h5>
          <hr>

          <div class="mb-4">
            <label for="titulo" class="form-label">Título</label>
            <input type="text" class="form-control form-control-sm" id="titulo" name="titulo" maxlength="50" required>
            <div class="d-flex justify-content-between small">
              <span class="error text-danger fw-bold" id="error-titulo"></span>
              <span class="text-muted" id="count-titulo">0 / 50</span>
            </div>
          </div>

          <div class="mb-4">
            <label for="bodyPush" class="form-label">Resumen</label>
            <textarea class="form-control form-control-sm" id="bodyPush" name="bodyPush" rows="2" maxlength="140" required></textarea>
            <div class="d-flex justify-content-between small">
              <span class="error text-danger fw-bold" id="error-bodyPush"></span>
              <span class="text-muted" id="count-bodyPush">0 / 140</span>
            </div>
          </div>

          <div class="mb-4">
            <label for="bodyCompleto" class="form-label">Cuerpo completo</label>
            <textarea class="form-control form-control-sm" id="bodyCompleto" name="bodyCompleto" rows="4"></textarea>
            <div class="d-flex justify-content-between small">
              <span class="error text-danger fw-bold" id="error-bodyCompleto"></span>
              <span class="text-muted" id="count-bodyCompleto">0 caracteres</span>
            </div>
          </div>

          <div class="mb-4">
            <label class="form-label fw-bold">Destino de la notificación</label>

            <div class="alert alert-info small border-start border-3 border-primary">
              <ul class="mb-0 ps-3">
                <li>Puedes enviar a <strong>usuarios individuales</strong> o a <strong>grupos</strong> (Clientes, Socios o Empleados), no ambos.</li>
                <li>Solo se enviará a usuarios con la app instalada y sesión iniciada al menos una vez.</li>
                <li><strong>Importante:</strong> Si seleccionas <strong>Clientes</strong>, se enviará a <u>todos los usuarios</u>, incluyendo Socios y Empleados.</li>
              </ul>
            </div>

            <div class="row g-3 mt-4">
              <div class="col-md-6">
                <label for="usuarios" class="form-label">Seleccionar Usuarios</label>
                <select id="usuarios" name="usuarios[]" class="form-select form-select-sm" multiple></select>
                <span class="error text-danger fw-bold" id="error-usuarios"></span>
              </div>
              <div class="col-md-6">
                <label for="grupos" class="form-label">Seleccionar Grupos</label>
                <select id="grupos" name="grupos[]" class="form-select form-select-sm select2" multiple>
                  <option value="clientes">Clientes (todos)</option>
                  <option value="socios">Socios</option>
                  <option value="empleados">Empleados</option>
                </select>
                <span class="error text-danger fw-bold" id="error-grupos"></span>
              </div>
            </div>
            <span class="error text-danger fw-bold" id="error-destino"></span>
          </div>

          <div class="mb-4 mt-4">
            <label for="programada" class="form-label">Fecha programada <span class="text-muted">(opcional)</span></label>
            <input type="datetime-local" class="form-control form-control-sm" id="programada" name="programada">
            <span class="error text-danger fw-bold" id="error-programada"></span>
          </div>

          <div class="mt-4">
            <button type="submit" class="btn btn-primary w-100 fw-bold py-2">
              <i class="fas fa-paper-plane me-1"></i> Enviar notificación
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
            <div class="fw-bold" id="previewTituloResumen">Título</div>
            <p class="mb-0 mt-2 small text-muted" id="previewBodyResumen">Resumen de la notificación</p>
          </div>
        </div>

        <div class="card shadow-sm p-4 rounded-3">
          <h6 class="text-center fw-bold text-primary mb-2">Vista completa en la app</h6>
          <hr class="my-2">
          <div class="ps-2">
            <div class="fw-bold" id="previewTituloCompleto">Título</div>
            <p class="mt-2 small text-muted" id="previewBodyCompleto">Aquí se mostrará el cuerpo completo de la notificación...</p>
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
  document.addEventListener('DOMContentLoaded', () => {
    //Actualizar previsualizacion de la notificacion
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

    // Actualizar contador de caracteres
    const updateCounter = (input, counterEl, max = null) => {
      input.addEventListener("input", () => {
        const length = input.value.length;
        counterEl.textContent = max ? `${length} / ${max}` : `${length} caracteres`;
      });
    };

    updateCounter(document.getElementById("titulo"), document.getElementById("count-titulo"), 50);
    updateCounter(document.getElementById("bodyPush"), document.getElementById("count-bodyPush"), 140);
    updateCounter(document.getElementById("bodyCompleto"), document.getElementById("count-bodyCompleto"));
  });

  //Cargar usuarios con app movil
  $(document).ready(function() {
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
        data: function(params) {
          return {
            query: params.term
          };
        },
        processResults: function(data) {
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

    $('#grupos').on('change', function() {
      if (manualChange) return; // evitar bucle infinito

      let selected = $(this).val() || [];

      if (selected.includes('clientes') && selected.length > 1) {
        manualChange = true;
        $(this).val(['clientes']).trigger('change');
        manualChange = false;
      } else if (!selected.includes('clientes') && selected.length > 0) {
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
