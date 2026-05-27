@extends('layouts.dashboard')

@section('title', 'Radicar vacaciones')

@section('breadcrumb')
<x-breadcrumb :items="[
        ['name' => 'Inicio', 'url' => route('home')],
        ['name' => 'Solicitudes y novedades', 'url' => route('gestionRRHH.solicitudes.index')],
        ['name' => 'Radicar solicitud', 'url' => route('gestionRRHH.solicitudes.radicar')],
        ['name' => 'Radicar vacaciones']
    ]" />
@endsection

@section('content')
<div class="container-fluid tableContainer p-0 border rounded sidebar-dark-primary permiso-radicar-page" style="min-height:150px">
  <x-sectionHeader
    titulo="Radicar solicitud de vacaciones"
    rutaVolver="{{ route('gestionRRHH.solicitudes.radicar') }}"
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

    @if(session('alert'))
    <div class="alert alert-{{ session('alert.type', 'info') }}">
      <strong>{{ session('alert.title') }}</strong>
      @if(!empty(session('alert.description')))
      <div>{!! session('alert.description') !!}</div>
      @endif
    </div>
    @endif

    @if(session('error'))
    <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    <form method="POST" action="{{ route('gestionRRHH.permisos.vacaciones.store') }}" class="permiso-form" enctype="multipart/form-data">
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
                    value="{{ old('identificacion', $documentoUsuarioDefault ?? '') }}"
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
            <h6 class="permiso-panel-title">Rango de vacaciones</h6>
            <div class="row g-3">
              <div class="col-12">
                <label for="fecha_inicio" class="form-label">Fecha inicio</label>
                <input
                  type="date"
                  class="form-control"
                  id="fecha_inicio"
                  name="fecha_inicio"
                  value="{{ old('fecha_inicio') }}"
                  required>
              </div>
              <div class="col-12">
                <label for="fecha_fin" class="form-label">Fecha fin</label>
                <input
                  type="date"
                  class="form-control"
                  id="fecha_fin"
                  name="fecha_fin"
                  value="{{ old('fecha_fin') }}"
                  required>
              </div>
            </div>
          </div>
        </div>

        <div class="col-12 col-lg-7">
          <div class="permiso-panel h-100">
            <h6 class="permiso-panel-title">Observación</h6>
            <textarea
              class="form-control"
              id="observacion"
              name="observacion"
              rows="6"
              maxlength="1000">{{ old('observacion') }}</textarea>
            <small class="text-muted">Opcional.</small>
          </div>
        </div>

        <div class="col-12 col-lg-5">
          <div class="permiso-panel h-100">
            <h6 class="permiso-panel-title">Carta de solicitud</h6>
            <div class="mb-2">
              <label for="carta" class="form-label">Archivo</label>
              <input
                type="file"
                class="form-control"
                id="carta"
                name="carta"
                accept=".pdf,.jpg,.jpeg,.png"
                required>
            </div>
            <small class="text-muted">Obligatorio. Formatos permitidos: PDF, JPG, JPEG y PNG (max. 10 MB).</small>
          </div>
        </div>

        <div class="col-12">
          <div class="permiso-actions">
            <button type="submit" class="btn btn-success px-4">Radicar vacaciones</button>
            <button type="reset" class="btn btn-outline-secondary">Limpiar</button>
          </div>
        </div>
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
  const rutaPersonaTemplate = @json(route('gestionRRHH.permisos.persona', ['documento' => '__DOC__']));

  document.addEventListener('DOMContentLoaded', function() {
    const form = document.querySelector('form.permiso-form');
    const identificacionInput = document.getElementById('identificacion');
    const consultarPersonaBtn = document.getElementById('consultarPersonaBtn');
    const estadoPersona = document.getElementById('estado_persona');
    const personaNombre = document.getElementById('persona_nombre');
    const personaCodigo = document.getElementById('persona_codigo');
    const personaSeccion = document.getElementById('persona_seccion');
    const personaSexo = document.getElementById('persona_sexo');
    const personaEdad = document.getElementById('persona_edad');

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
          headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
        });

        const payload = await response.json();
        if (!response.ok || !payload || !payload.ok || !payload.data) {
          limpiarDatosPersona();
          setEstadoPersona((payload && payload.message) ? payload.message : 'No se encontró la persona activa.', 'danger');
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

    identificacionInput?.addEventListener('blur', cargarDatosPersona);
    consultarPersonaBtn?.addEventListener('click', cargarDatosPersona);

    if (identificacionInput && identificacionInput.value.trim() !== '') {
      cargarDatosPersona();
    }

    const mostrarLoaderRadicacion = () => {
      if (!window.Swal || typeof window.Swal.fire !== 'function') {
        return;
      }

      window.Swal.fire({
        title: 'Radicando vacaciones...',
        text: 'Por favor espera...',
        allowOutsideClick: false,
        allowEscapeKey: false,
        showConfirmButton: false,
        customClass: {
          popup: 'swalAlert'
        },
        didOpen: () => {
          if (typeof window.Swal.showLoading === 'function') {
            window.Swal.showLoading();
          }
        }
      });
    };

    form?.addEventListener('submit', function(event) {
      if (form.dataset.submitting === '1') {
        event.preventDefault();
        return;
      }

      form.dataset.submitting = '1';
      const elementos = form.querySelectorAll('button, input[type="submit"]');
      elementos.forEach((elemento) => {
        elemento.disabled = true;
      });

      mostrarLoaderRadicacion();
    });
  });
</script>
@endPushOnce
