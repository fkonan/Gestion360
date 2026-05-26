@extends('layouts.dashboard')

@section('title', 'Radicar permiso')

@section('breadcrumb')
<x-breadcrumb :items="[
        ['name' => 'Inicio', 'url' => route('home')],
        ['name' => 'Solicitudes y novedades', 'url' => route('gestionRRHH.solicitudes.index')],
        ['name' => 'Radicar solicitud', 'url' => route('gestionRRHH.solicitudes.radicar')],
        ['name' => 'Radicar permiso']
    ]" />
@endsection

@section('content')
@php
  $adjuntosOld = old('adjuntos');
  if (!is_array($adjuntosOld)) {
      $adjuntosOld = [['tipo_documento_id' => '']];
  }
  $adjuntosIndices = array_map('intval', array_keys($adjuntosOld));
  $nextAdjuntoIndex = $adjuntosIndices === [] ? 0 : (max($adjuntosIndices) + 1);
@endphp
<div class="container-fluid tableContainer p-0 border rounded sidebar-dark-primary permiso-radicar-page" style="min-height:150px">
  <x-sectionHeader
    titulo="Radicar permiso de salida"
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

    @if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    @if(session('error'))
    <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    <div class="d-flex justify-content-end mb-3">
      <a href="{{ route('gestionRRHH.permisos.mis-solicitudes') }}" class="btn btn-outline-dark">Ver mis solicitudes</a>
    </div>

    <form method="POST" action="{{ route('gestionRRHH.permisos.store') }}" class="permiso-form" enctype="multipart/form-data">
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
          <div class="permiso-panel">
            <div class="d-flex justify-content-between align-items-center gap-2 flex-wrap mb-2">
              <h6 class="permiso-panel-title mb-0">Documentos de soporte</h6>
              <button type="button" class="btn btn-outline-dark btn-sm" id="agregarAdjuntoBtn">Agregar adjunto</button>
            </div>

            <div id="adjuntosContainer" data-next-index="{{ $nextAdjuntoIndex }}">
              @foreach($adjuntosOld as $index => $adjuntoOld)
              <div class="adjunto-row border rounded p-2 mb-2">
                <div class="row g-2 align-items-end">
                  <div class="col-12 col-md-5">
                    <label class="form-label">Tipo de documento</label>
                    <select class="form-select" name="adjuntos[{{ $index }}][tipo_documento_id]">
                      <option value="">Seleccione...</option>
                      @foreach($tiposDocumento as $tipoId => $tipoDescripcion)
                      <option value="{{ $tipoId }}" {{ (($adjuntoOld['tipo_documento_id'] ?? '') === $tipoId) ? 'selected' : '' }}>
                        {{ $tipoDescripcion }}
                      </option>
                      @endforeach
                    </select>
                    @error("adjuntos.$index.tipo_documento_id")
                    <small class="text-danger d-block mt-1">{{ $message }}</small>
                    @enderror
                  </div>
                  <div class="col-12 col-md-6">
                    <label class="form-label">Archivo</label>
                    <input
                      type="file"
                      class="form-control"
                      name="adjuntos[{{ $index }}][archivo]"
                      accept=".pdf,.jpg,.jpeg,.png">
                    @error("adjuntos.$index.archivo")
                    <small class="text-danger d-block mt-1">{{ $message }}</small>
                    @enderror
                  </div>
                  <div class="col-12 col-md-1 text-md-end">
                    <button type="button" class="btn btn-outline-secondary btn-sm js-remove-adjunto-row">Quitar</button>
                  </div>
                </div>
              </div>
              @endforeach
            </div>

            <small class="text-muted">Adjunto opcional. Formatos permitidos: PDF, JPG, JPEG y PNG (max. 10 MB por archivo).</small>
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
@vite('resources/css/gestionrrhh/novedades-radicar.css')
@endPushOnce

@pushOnce('script')
<script>
  const rutaPersonaTemplate = @json(route('gestionRRHH.permisos.persona', ['documento' => '__DOC__']));
  const tiposDocumentoAdjunto = @json($tiposDocumento);

  document.addEventListener('DOMContentLoaded', function() {
    const permisoForm = document.querySelector('form.permiso-form');
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
    const adjuntosContainer = document.getElementById('adjuntosContainer');
    const agregarAdjuntoBtn = document.getElementById('agregarAdjuntoBtn');

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

    const escapeHtml = (value) => String(value)
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;')
      .replace(/'/g, '&#039;');

    const construirOpcionesTipos = () => {
      let opciones = '<option value="">Seleccione...</option>';

      Object.entries(tiposDocumentoAdjunto).forEach(([id, descripcion]) => {
        opciones += `<option value="${escapeHtml(id)}">${escapeHtml(descripcion)}</option>`;
      });

      return opciones;
    };

    const crearFilaAdjunto = (index) => {
      const wrapper = document.createElement('div');
      wrapper.className = 'adjunto-row border rounded p-2 mb-2';
      wrapper.innerHTML = `
        <div class="row g-2 align-items-end">
          <div class="col-12 col-md-5">
            <label class="form-label">Tipo de documento</label>
            <select class="form-select" name="adjuntos[${index}][tipo_documento_id]">
              ${construirOpcionesTipos()}
            </select>
          </div>
          <div class="col-12 col-md-6">
            <label class="form-label">Archivo</label>
            <input type="file" class="form-control" name="adjuntos[${index}][archivo]" accept=".pdf,.jpg,.jpeg,.png">
          </div>
          <div class="col-12 col-md-1 text-md-end">
            <button type="button" class="btn btn-outline-secondary btn-sm js-remove-adjunto-row">Quitar</button>
          </div>
        </div>
      `;

      return wrapper;
    };

    if (agregarAdjuntoBtn && adjuntosContainer) {
      agregarAdjuntoBtn.addEventListener('click', () => {
        const nextIndex = Number(adjuntosContainer.dataset.nextIndex || '0');
        adjuntosContainer.appendChild(crearFilaAdjunto(nextIndex));
        adjuntosContainer.dataset.nextIndex = String(nextIndex + 1);
      });
    }

    document.addEventListener('click', (event) => {
      const btnQuitar = event.target.closest('.js-remove-adjunto-row');
      if (!btnQuitar) {
        return;
      }

      const fila = btnQuitar.closest('.adjunto-row');
      if (fila) {
        fila.remove();
      }
    });

    const mostrarLoaderRadicacion = () => {
      if (!window.Swal || typeof window.Swal.fire !== 'function') {
        return;
      }

      window.Swal.fire({
        title: 'Radicando permiso...',
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

    if (permisoForm) {
      permisoForm.addEventListener('submit', function(event) {
        if (permisoForm.dataset.submitting === '1') {
          event.preventDefault();
          return;
        }

        permisoForm.dataset.submitting = '1';
        const elementos = permisoForm.querySelectorAll('button, input[type="submit"]');
        elementos.forEach((elemento) => {
          elemento.disabled = true;
        });

        mostrarLoaderRadicacion();
      });
    }
  });
</script>
@endPushOnce
