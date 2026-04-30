@extends('layouts.dashboard')

@section('title', 'Radicar permiso')

@section('breadcrumb')
<x-breadcrumb :items="[
        ['name' => 'Inicio', 'url' => route('home')],
        ['name' => 'Empleados', 'url' => route('empleados.index')],
        ['name' => 'Radicar permiso']
    ]" />
@endsection

@section('content')
<div class="container-fluid tableContainer p-0 border rounded sidebar-dark-primary permiso-radicar-page" style="min-height:150px">
  <x-sectionHeader
    titulo="Radicar permiso de salida"
    rutaVolver="{{ route('empleados.index') }}"
    :clasePosition="false" />

  <div style="padding:1.5em">
    @if ($errors->any())
    <div class="alert alert-danger">
      <ul class="mb-0">
        @foreach ($errors->all() as $error)
        <li>{{ $error }}</li>
        @endforeach
      </ul>
    </div>
    @endif

    @if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    @if(session('error'))
    <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    <div class="d-flex justify-content-end mb-3">
      <a href="{{ route('empleados.permisos.mis-solicitudes') }}" class="btn btn-outline-dark">Ver mis solicitudes</a>
    </div>

    <form method="POST" action="{{ route('empleados.permisos.store') }}" class="permiso-form">
      @csrf

      <div class="row g-3">
        <div class="col-12 col-xl-7">
          <div class="permiso-panel h-100">
            <h6 class="permiso-panel-title">Empleado</h6>
            <div class="row g-3">
              <div class="col-12 col-md-6">
                <label for="identificacion" class="form-label">Documento</label>
                <div class="input-group">
                  <input
                    type="text"
                    class="form-control"
                    id="identificacion"
                    name="identificacion"
                    maxlength="50"
                    value="{{ old('identificacion') }}"
                    required>
                  <button type="button" class="btn btn-outline-dark" id="consultarPersonaBtn">Consultar</button>
                </div>
                <small class="text-muted">Consulta automática al salir del campo.</small>
                <div id="estado_persona" class="form-text"></div>
              </div>

              <div class="col-12 col-md-6">
                <label for="persona_nombre" class="form-label">Nombre completo</label>
                <input type="text" class="form-control" id="persona_nombre" readonly>
              </div>

              <div class="col-6 col-md-3">
                <label for="persona_codigo" class="form-label">Código</label>
                <input type="text" class="form-control" id="persona_codigo" readonly>
              </div>

              <div class="col-6 col-md-5">
                <label for="persona_seccion" class="form-label">Sección</label>
                <input type="text" class="form-control" id="persona_seccion" readonly>
              </div>

              <div class="col-6 col-md-2">
                <label for="persona_sexo" class="form-label">Sexo</label>
                <input type="text" class="form-control" id="persona_sexo" readonly>
              </div>

              <div class="col-6 col-md-2">
                <label for="persona_edad" class="form-label">Edad</label>
                <input type="text" class="form-control" id="persona_edad" readonly>
              </div>
            </div>
          </div>
        </div>

        <div class="col-12 col-xl-5">
          <div class="permiso-panel h-100">
            <h6 class="permiso-panel-title">Horario del permiso</h6>
            <div class="row g-3">
              <div class="col-12">
                <label for="fecha_permiso" class="form-label">Fecha</label>
                <input
                  type="date"
                  class="form-control"
                  id="fecha_permiso"
                  name="fecha_permiso"
                  value="{{ old('fecha_permiso', now()->format('Y-m-d')) }}"
                  required>
              </div>

              <div class="col-12 col-md-6">
                <label for="hora_salida" class="form-label">Hora salida</label>
                <input
                  type="time"
                  class="form-control"
                  id="hora_salida"
                  name="hora_salida"
                  value="{{ old('hora_salida') }}"
                  required>
              </div>

              <div class="col-12 col-md-6">
                <label for="hora_ingreso" class="form-label">Hora ingreso <span class="text-muted">(opcional)</span></label>
                <input
                  type="time"
                  class="form-control"
                  id="hora_ingreso"
                  name="hora_ingreso"
                  value="{{ old('hora_ingreso') }}">
              </div>
            </div>
          </div>
        </div>

        <div class="col-12 col-lg-5">
          <div class="permiso-panel h-100">
            <h6 class="permiso-panel-title">Motivo</h6>
            <div class="row g-3">
              <div class="col-12">
                <label for="motivo" class="form-label">Tipo de motivo</label>
                <select class="form-select" id="motivo" name="motivo" required>
                  <option value="">Seleccione...</option>
                  @foreach($opcionesMotivo as $codigo => $label)
                  <option value="{{ $codigo }}" {{ old('motivo') === $codigo ? 'selected' : '' }}>
                    {{ $label }}
                  </option>
                  @endforeach
                </select>
              </div>

              <div class="col-12" id="contenedor_otro_motivo" style="{{ old('motivo') === 'OTROS' ? '' : 'display:none;' }}">
                <label for="otro_motivo" class="form-label">Detalle de otros</label>
                <input
                  type="text"
                  class="form-control"
                  id="otro_motivo"
                  name="otro_motivo"
                  maxlength="255"
                  value="{{ old('otro_motivo') }}">
              </div>
            </div>
          </div>
        </div>

        <div class="col-12 col-lg-7">
          <div class="permiso-panel h-100">
            <h6 class="permiso-panel-title">Actividad a realizar</h6>
            <textarea
              class="form-control"
              id="actividad"
              name="actividad"
              rows="6"
              maxlength="1000"
              required>{{ old('actividad') }}</textarea>
            <small class="text-muted">Describe brevemente la actividad.</small>
          </div>
        </div>

        <div class="col-12">
          <div class="permiso-actions">
            <button type="submit" class="btn btn-success px-4">Radicar permiso</button>
            <button type="reset" class="btn btn-outline-secondary">Limpiar</button>
          </div>
        </div>
      </div>
    </form>
  </div>
</div>
@endsection

@pushOnce('css')
<style>
  .permiso-form .form-label {
    font-weight: 600;
    margin-bottom: 0.35rem;
  }

  .permiso-panel {
    border: 1px solid #d9dee7;
    border-radius: 0.6rem;
    background: #fff;
    padding: 1rem;
    box-shadow: 0 6px 18px rgba(15, 23, 42, 0.06);
  }

  .permiso-panel-title {
    margin-bottom: 0.85rem;
    font-weight: 700;
    color: #1f2d3d;
    font-size: 0.98rem;
  }

  .permiso-actions {
    border-top: 1px solid #e6e9ef;
    padding-top: 1rem;
    display: flex;
    gap: 0.5rem;
    flex-wrap: wrap;
  }

  [data-bs-theme="dark"] .permiso-radicar-page .permiso-panel,
  body.dark-mode .permiso-radicar-page .permiso-panel {
    background: var(--bs-tertiary-bg, #2b3035);
    border-color: var(--bs-light-border-subtle, #495057);
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.35);
  }

  [data-bs-theme="dark"] .permiso-radicar-page .permiso-panel-title,
  [data-bs-theme="dark"] .permiso-radicar-page .form-label,
  body.dark-mode .permiso-radicar-page .permiso-panel-title,
  body.dark-mode .permiso-radicar-page .form-label {
    color: var(--bs-body-color, #dee2e6);
  }

  [data-bs-theme="dark"] .permiso-radicar-page .permiso-actions,
  body.dark-mode .permiso-radicar-page .permiso-actions {
    border-top-color: var(--bs-border-color, #495057);
  }

  [data-bs-theme="dark"] .permiso-radicar-page .form-control,
  [data-bs-theme="dark"] .permiso-radicar-page .form-select,
  body.dark-mode .permiso-radicar-page .form-control,
  body.dark-mode .permiso-radicar-page .form-select {
    background-color: var(--bs-secondary-bg, #343a40);
    border-color: var(--bs-border-color, #495057);
    color: var(--bs-body-color, #dee2e6);
  }

  [data-bs-theme="dark"] .permiso-radicar-page .form-control:focus,
  [data-bs-theme="dark"] .permiso-radicar-page .form-select:focus,
  body.dark-mode .permiso-radicar-page .form-control:focus,
  body.dark-mode .permiso-radicar-page .form-select:focus {
    border-color: var(--bs-primary-border-subtle, #084298);
    box-shadow: 0 0 0 0.2rem var(--bs-focus-ring-color, rgba(13, 110, 253, 0.25));
  }

  [data-bs-theme="dark"] .permiso-radicar-page .form-control[readonly],
  body.dark-mode .permiso-radicar-page .form-control[readonly] {
    background-color: var(--bs-tertiary-bg, #2b3035);
    color: var(--bs-body-color, #dee2e6);
  }

  [data-bs-theme="dark"] .permiso-radicar-page .text-muted,
  [data-bs-theme="dark"] .permiso-radicar-page .form-text,
  body.dark-mode .permiso-radicar-page .text-muted,
  body.dark-mode .permiso-radicar-page .form-text {
    color: var(--bs-secondary-color, rgba(222, 226, 230, 0.75)) !important;
  }

  [data-bs-theme="dark"] .permiso-radicar-page .btn-outline-dark,
  body.dark-mode .permiso-radicar-page .btn-outline-dark {
    color: var(--bs-body-color, #dee2e6);
    border-color: var(--bs-border-color, #495057);
    background: var(--bs-secondary-bg, #343a40);
  }

  [data-bs-theme="dark"] .permiso-radicar-page .btn-outline-dark:hover,
  body.dark-mode .permiso-radicar-page .btn-outline-dark:hover {
    color: var(--bs-emphasis-color, #fff);
    border-color: var(--bs-border-color, #495057);
    background: var(--bs-tertiary-bg, #2b3035);
  }
</style>
@endPushOnce

@pushOnce('script')
<script>
  const rutaPersonaTemplate = @json(route('empleados.permisos.persona', ['documento' => '__DOC__']));

  document.addEventListener('DOMContentLoaded', function() {
    const identificacionInput = document.getElementById('identificacion');
    const consultarPersonaBtn = document.getElementById('consultarPersonaBtn');
    const estadoPersona = document.getElementById('estado_persona');
    const personaNombre = document.getElementById('persona_nombre');
    const personaCodigo = document.getElementById('persona_codigo');
    const personaSeccion = document.getElementById('persona_seccion');
    const personaSexo = document.getElementById('persona_sexo');
    const personaEdad = document.getElementById('persona_edad');
    const motivoSelect = document.getElementById('motivo');
    const contenedorOtro = document.getElementById('contenedor_otro_motivo');
    const inputOtro = document.getElementById('otro_motivo');

    const limpiarDatosPersona = () => {
      personaNombre.value = '';
      personaCodigo.value = '';
      personaSeccion.value = '';
      personaSexo.value = '';
      personaEdad.value = '';
    };

    const setEstadoPersona = (mensaje, tipo = 'muted') => {
      estadoPersona.className = `form-text text-${tipo}`;
      estadoPersona.textContent = mensaje;
    };

    const cargarDatosPersona = async () => {
      const documento = identificacionInput.value.trim();
      if (documento === '') {
        limpiarDatosPersona();
        setEstadoPersona('');
        return;
      }

      setEstadoPersona('Consultando empleado...', 'secondary');
      consultarPersonaBtn.disabled = true;

      try {
        const url = rutaPersonaTemplate.replace('__DOC__', encodeURIComponent(documento));
        const response = await fetch(url, {
          method: 'GET',
          headers: {
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
          },
        });

        const payload = await response.json();
        if (!response.ok || !payload || !payload.ok || !payload.data) {
          limpiarDatosPersona();
          setEstadoPersona((payload && payload.message) ? payload.message : 'No se encontro la persona activa.', 'danger');
          return;
        }

        const data = payload.data;
        personaNombre.value = data.nombre ?? '';
        personaCodigo.value = data.codigo ?? '';
        personaSeccion.value = data.seccion ?? '';
        personaSexo.value = data.sexo ?? '';
        personaEdad.value = data.edad ?? '';
        setEstadoPersona('Datos cargados correctamente.', 'success');
      } catch (error) {
        limpiarDatosPersona();
        setEstadoPersona('No fue posible consultar la persona en este momento.', 'danger');
      } finally {
        consultarPersonaBtn.disabled = false;
      }
    };

    const toggleOtroMotivo = () => {
      const esOtros = motivoSelect.value === 'OTROS';
      contenedorOtro.style.display = esOtros ? '' : 'none';
      if (!esOtros) {
        inputOtro.value = '';
      }
    };

    if (motivoSelect) {
      motivoSelect.addEventListener('change', toggleOtroMotivo);
      toggleOtroMotivo();
    }

    if (identificacionInput) {
      identificacionInput.addEventListener('blur', cargarDatosPersona);
      identificacionInput.addEventListener('keydown', function(event) {
        if (event.key === 'Enter') {
          event.preventDefault();
          cargarDatosPersona();
        }
      });
      identificacionInput.addEventListener('input', function() {
        if (identificacionInput.value.trim() === '') {
          limpiarDatosPersona();
          setEstadoPersona('');
        }
      });

      if (identificacionInput.value.trim() !== '') {
        cargarDatosPersona();
      }
    }

    if (consultarPersonaBtn) {
      consultarPersonaBtn.addEventListener('click', cargarDatosPersona);
    }
  });
</script>
@endPushOnce
