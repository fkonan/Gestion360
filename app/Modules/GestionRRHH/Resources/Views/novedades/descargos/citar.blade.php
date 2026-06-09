@extends('layouts.dashboard')

@section('title', 'Citar a descargos')

@section('breadcrumb')
<x-breadcrumb :items="[
        ['name' => 'Inicio', 'url' => route('home')],
        ['name' => 'Solicitudes y novedades', 'url' => route('gestionRRHH.solicitudes.index')],
        ['name' => 'Citar a descargos']
    ]" />
@endsection

@section('content')
<div class="container-fluid tableContainer p-0 border rounded sidebar-dark-primary permiso-radicar-page" style="min-height:150px">
  <x-sectionHeader
    titulo="Citar a descargos"
    rutaVolver="{{ route('gestionRRHH.solicitudes.index') }}"
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

    @if(session('alert'))
    <div class="alert alert-{{ session('alert.type', 'info') }}">
      <strong>{{ session('alert.title') }}</strong>
      @if(!empty(session('alert.description')))
      <div>{!! session('alert.description') !!}</div>
      @endif
    </div>
    @endif

    <form method="POST" action="{{ route('gestionRRHH.descargos.citaciones.store') }}" class="permiso-form" id="formCitarDescargos">
      @csrf

      <div class="row g-3">
        <div class="col-12 col-xl-7">
          <div class="permiso-panel h-100">
            <h6 class="permiso-panel-title">Empleado</h6>
            <div class="row g-3">
              <div class="col-12 col-md-6">
                <label for="documento_persona" class="form-label">Documento</label>
                <div class="input-group">
                  <input
                    type="text"
                    class="form-control @error('documento_persona') is-invalid @enderror"
                    id="documento_persona"
                    name="documento_persona"
                    maxlength="100"
                    value="{{ old('documento_persona') }}"
                    required>
                  <button type="button" class="btn btn-outline-dark" id="consultarPersonaBtn">Consultar</button>
                </div>
                <small class="text-muted">Consulta automatica al salir del campo.</small>
                <div id="estado_persona" class="form-text"></div>
              </div>

              <div class="col-12 col-md-6">
                <label for="persona_nombre" class="form-label">Nombre completo</label>
                <input type="text" class="form-control" id="persona_nombre" readonly>
              </div>

              <div class="col-6 col-md-3">
                <label for="persona_codigo" class="form-label">Codigo</label>
                <input type="text" class="form-control" id="persona_codigo" readonly>
              </div>

              <div class="col-6 col-md-5">
                <label for="persona_seccion" class="form-label">Seccion</label>
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
            <h6 class="permiso-panel-title">Datos de citacion</h6>
            <div class="row g-3">
              <div class="col-12">
                <label for="fecha_citacion" class="form-label">Fecha y hora citacion</label>
                <input
                  type="datetime-local"
                  class="form-control @error('fecha_citacion') is-invalid @enderror"
                  id="fecha_citacion"
                  name="fecha_citacion"
                  value="{{ old('fecha_citacion') }}"
                  required>
              </div>
            </div>
          </div>
        </div>

        <div class="col-12">
          <label for="observacion" class="form-label">Observacion</label>
          <textarea
            class="form-control @error('observacion') is-invalid @enderror"
            id="observacion"
            name="observacion"
            rows="4">{{ old('observacion') }}</textarea>
        </div>
      </div>

      <div class="d-flex gap-2 mt-4">
        <button type="submit" class="btn btn-success">Registrar citacion</button>
        <a href="{{ route('gestionRRHH.solicitudes.index') }}" class="btn btn-dark">Cancelar</a>
      </div>
    </form>
  </div>
</div>
@endsection

@pushOnce('css')
@vite('resources/css/gestionrrhh/novedades-radicar.css')
@endPushOnce

@pushOnce('script')
<script>
document.addEventListener('DOMContentLoaded', function () {
  const rutaPersonaTemplate = @json(route('gestionRRHH.permisos.persona', ['documento' => '__DOC__']));
  const form = document.getElementById('formCitarDescargos');
  const identificacionInput = document.getElementById('documento_persona');
  const consultarPersonaBtn = document.getElementById('consultarPersonaBtn');
  const estadoPersona = document.getElementById('estado_persona');
  const personaNombre = document.getElementById('persona_nombre');
  const personaCodigo = document.getElementById('persona_codigo');
  const personaSeccion = document.getElementById('persona_seccion');
  const personaSexo = document.getElementById('persona_sexo');
  const personaEdad = document.getElementById('persona_edad');

  const limpiarDatosPersona = function () {
    personaNombre.value = '';
    personaCodigo.value = '';
    personaSeccion.value = '';
    personaSexo.value = '';
    personaEdad.value = '';
  };

  const setEstadoPersona = function (mensaje, tipo = 'muted') {
    estadoPersona.className = `form-text text-${tipo}`;
    estadoPersona.textContent = mensaje;
  };

  const cargarDatosPersona = async function () {
    const documento = (identificacionInput.value || '').trim();
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
          'X-Requested-With': 'XMLHttpRequest'
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

  if (!form) {
    return;
  }

  identificacionInput.addEventListener('blur', cargarDatosPersona);
  identificacionInput.addEventListener('keydown', function (event) {
    if (event.key === 'Enter') {
      event.preventDefault();
      cargarDatosPersona();
    }
  });
  identificacionInput.addEventListener('input', function () {
    if ((identificacionInput.value || '').trim() === '') {
      limpiarDatosPersona();
      setEstadoPersona('');
    }
  });
  consultarPersonaBtn.addEventListener('click', cargarDatosPersona);

  if ((identificacionInput.value || '').trim() !== '') {
    cargarDatosPersona();
  }

  form.addEventListener('submit', function () {
    if (window.Swal && typeof window.Swal.fire === 'function') {
      window.Swal.fire({
        title: 'Registrando citacion',
        text: 'Por favor espera...',
        allowOutsideClick: false,
        allowEscapeKey: false,
        showConfirmButton: false,
        customClass: {
          popup: 'swalAlert'
        },
        didOpen: function () {
          window.Swal.showLoading();
        }
      });
    }
  });
});
</script>
@endPushOnce
