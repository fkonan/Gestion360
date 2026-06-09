@extends('layouts.dashboard')

@section('title', 'Radicar incapacidad')

@section('breadcrumb')
<x-breadcrumb :items="[
        ['name' => 'Inicio', 'url' => route('home')],
        ['name' => 'Solicitudes y novedades', 'url' => route('gestionRRHH.solicitudes.index')],
        ['name' => 'Radicar solicitud', 'url' => route('gestionRRHH.solicitudes.radicar')],
        ['name' => 'Radicar incapacidad']
    ]" />
@endsection

@section('content')
<div class="container-fluid tableContainer p-0 border rounded sidebar-dark-primary permiso-radicar-page" style="min-height:150px">
  <x-sectionHeader
    titulo="Radicar incapacidad"
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

    @if(session('error'))
    <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    <form method="POST" action="{{ route('gestionRRHH.permisos.incapacidades.store') }}" class="incapacidad-form" enctype="multipart/form-data">
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
            <h6 class="permiso-panel-title">Periodo</h6>
            <div class="row g-3">
              <div class="col-12 col-md-6">
                <label for="fecha_inicio" class="form-label">Fecha inicio</label>
                <input
                  type="date"
                  class="form-control"
                  id="fecha_inicio"
                  name="fecha_inicio"
                  value="{{ old('fecha_inicio', now()->format('Y-m-d')) }}"
                  required>
              </div>

              <div class="col-12 col-md-6">
                <label for="fecha_fin" class="form-label">Fecha fin</label>
                <input
                  type="date"
                  class="form-control"
                  id="fecha_fin"
                  name="fecha_fin"
                  value="{{ old('fecha_fin', now()->format('Y-m-d')) }}"
                  required>
              </div>

              <div class="col-12 col-md-6">
                <label for="tipo_incapacidad" class="form-label">Tipo incapacidad</label>
                <select class="form-select" id="tipo_incapacidad" name="tipo_incapacidad" required>
                  @foreach($tiposIncapacidad as $codigo => $label)
                  <option value="{{ $codigo }}" {{ old('tipo_incapacidad', 'INICIO') === $codigo ? 'selected' : '' }}>
                    {{ $label }}
                  </option>
                  @endforeach
                </select>
              </div>

              <div class="col-12 col-md-6">
                <label for="dias_incapacidad" class="form-label">Días</label>
                <input type="text" class="form-control" id="dias_incapacidad" readonly>
              </div>
            </div>
          </div>
        </div>

        <div class="col-12">
          <div class="permiso-panel">
            <h6 class="permiso-panel-title">Datos médicos</h6>
            <div class="row g-3">
              <div class="col-12 col-lg-4">
                <label for="causa_id" class="form-label">Causa incapacidad</label>
                <select class="form-select select2" id="causa_id" name="causa_id" required>
                  <option value="">Seleccione...</option>
                  @foreach($causas as $id => $nombre)
                  <option value="{{ $id }}" {{ (string) old('causa_id') === (string) $id ? 'selected' : '' }}>
                    {{ $nombre }}
                  </option>
                  @endforeach
                </select>
              </div>

              <div class="col-12 col-lg-8">
                <label for="diagnostico_id" class="form-label">Código diagnóstico</label>
                <select class="form-select" id="diagnostico_id" name="diagnostico_id" required>
                  <option value="">Seleccione...</option>
                  @foreach($diagnosticos as $id => $nombre)
                  <option value="{{ $id }}" {{ (string) old('diagnostico_id') === (string) $id ? 'selected' : '' }}>
                    {{ $nombre }}
                  </option>
                  @endforeach
                </select>
              </div>

              <div class="col-12 col-md-6">
                <label for="eps_id" class="form-label">EPS</label>
                <select class="form-select select2" id="eps_id" name="eps_id" required>
                  <option value="">Seleccione...</option>
                  @foreach($eps as $id => $nombre)
                  <option value="{{ $id }}" {{ (string) old('eps_id') === (string) $id ? 'selected' : '' }}>
                    {{ $nombre }}
                  </option>
                  @endforeach
                </select>
              </div>

              <div class="col-12 col-md-6">
                <label for="arl_id" class="form-label">ARL</label>
                <select class="form-select select2" id="arl_id" name="arl_id" required>
                  <option value="">Seleccione...</option>
                  @foreach($arl as $id => $nombre)
                  <option value="{{ $id }}" {{ (string) old('arl_id') === (string) $id ? 'selected' : '' }}>
                    {{ $nombre }}
                  </option>
                  @endforeach
                </select>
              </div>

              <div class="col-12">
                <label for="observacion" class="form-label">Observación</label>
                <textarea
                  class="form-control"
                  id="observacion"
                  name="observacion"
                  rows="3"
                  maxlength="255">{{ old('observacion') }}</textarea>
              </div>
            </div>
          </div>
        </div>

        <div class="col-12">
          <div class="permiso-panel">
            <h6 class="permiso-panel-title">Documentos de soporte</h6>
            @error('adjuntos')
              <small class="text-danger d-block mb-2">{{ $message }}</small>
            @enderror
            <div id="adjuntosContainer" data-old-causa="{{ old('causa_id') }}">
            </div>
          </div>
        </div>

        <div class="col-12">
          <div class="permiso-actions">
            <button type="submit" class="btn btn-success px-4">Radicar incapacidad</button>
            <button type="reset" class="btn btn-outline-secondary">Limpiar</button>
          </div>
        </div>
      </div>
    </form>
  </div>
</div>
@endsection

@pushOnce('css')
@vite('resources/css/gestionrrhh/incapacidades-radicar.css')
@endPushOnce

@pushOnce('script')
<script>
  const rutaPersonaTemplate = @json(route('gestionRRHH.permisos.incapacidades.persona', ['documento' => '__DOC__']));
  const rutaDiagnosticosTemplate = @json(route('gestionRRHH.permisos.incapacidades.diagnosticos'));
  const tiposDocumentoPorCausa = @json($tiposDocumentoPorCausa);

  document.addEventListener('DOMContentLoaded', function() {
    if (window.jQuery && window.jQuery.fn && window.jQuery.fn.select2) {
      window.jQuery('.select2').select2({
        width: '100%'
      });

      const diagnosticoSelect = window.jQuery('#diagnostico_id');
      if (diagnosticoSelect.length) {
        diagnosticoSelect.select2({
          width: '100%',
          ajax: {
            url: rutaDiagnosticosTemplate,
            dataType: 'json',
            delay: 250,
            data: function(params) {
              return {
                q: params.term || '',
                limit: 10,
              };
            },
            processResults: function(data) {
              return {
                results: Array.isArray(data.results) ? data.results : [],
              };
            },
            cache: true
          },
          minimumInputLength: 0
        });
      }
    }

    const incapacidadForm = document.querySelector('form.incapacidad-form');
    const identificacionInput = document.getElementById('identificacion');
    const consultarPersonaBtn = document.getElementById('consultarPersonaBtn');
    const estadoPersona = document.getElementById('estado_persona');
    const personaNombre = document.getElementById('persona_nombre');
    const personaCodigo = document.getElementById('persona_codigo');
    const personaSeccion = document.getElementById('persona_seccion');
    const personaSexo = document.getElementById('persona_sexo');
    const personaEdad = document.getElementById('persona_edad');
    const fechaInicio = document.getElementById('fecha_inicio');
    const fechaFin = document.getElementById('fecha_fin');
    const diasIncapacidad = document.getElementById('dias_incapacidad');
    const causaSelect = document.getElementById('causa_id');
    const adjuntosContainer = document.getElementById('adjuntosContainer');

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

    const actualizarDias = () => {
      if (!fechaInicio.value || !fechaFin.value) {
        diasIncapacidad.value = '';
        return;
      }

      const inicio = new Date(`${fechaInicio.value}T00:00:00`);
      const fin = new Date(`${fechaFin.value}T00:00:00`);
      if (Number.isNaN(inicio.getTime()) || Number.isNaN(fin.getTime()) || fin < inicio) {
        diasIncapacidad.value = '';
        return;
      }

      diasIncapacidad.value = String(Math.floor((fin - inicio) / 86400000) + 1);
    };

    if (identificacionInput) {
      identificacionInput.addEventListener('blur', cargarDatosPersona);
      identificacionInput.addEventListener('keydown', function(event) {
        if (event.key === 'Enter') {
          event.preventDefault();
          cargarDatosPersona();
        }
      });

      if (identificacionInput.value.trim() !== '') {
        cargarDatosPersona();
      }
    }

    if (consultarPersonaBtn) {
      consultarPersonaBtn.addEventListener('click', cargarDatosPersona);
    }

    fechaInicio.addEventListener('change', actualizarDias);
    fechaFin.addEventListener('change', actualizarDias);
    actualizarDias();

    const escapeHtml = (value) => String(value)
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;')
      .replace(/'/g, '&#039;');

    const crearFilaAdjunto = (index, tipoId, descripcion) => {
      const wrapper = document.createElement('div');
      wrapper.className = 'adjunto-row border rounded p-2 mb-2';
      wrapper.innerHTML = `
        <div class="row g-2 align-items-end">
          <div class="col-12 col-md-5">
            <label class="form-label">Documento requerido</label>
            <input type="text" class="form-control" value="${escapeHtml(descripcion)}" readonly>
            <input type="hidden" name="adjuntos[${index}][tipo_documento_id]" value="${escapeHtml(tipoId)}">
          </div>
          <div class="col-12 col-md-7">
            <label class="form-label">Archivo</label>
            <input type="file" class="form-control" name="adjuntos[${index}][archivo]" accept=".pdf,.jpg,.jpeg,.png">
          </div>
        </div>
      `;

      return wrapper;
    };

    const renderAdjuntosPorCausa = () => {
      if (!adjuntosContainer || !causaSelect) {
        return;
      }

      adjuntosContainer.innerHTML = '';
      const causaId = String(causaSelect.value || '');
      const config = tiposDocumentoPorCausa[causaId] || null;
      const documentos = config && config.documentos ? config.documentos : {};
      const entries = Object.entries(documentos);

      if (entries.length === 0) {
        adjuntosContainer.innerHTML = '<div class="alert alert-light border mb-0">Seleccione una causa de incapacidad.</div>';
        return;
      }

      entries.forEach(([tipoId, descripcion], index) => {
        adjuntosContainer.appendChild(crearFilaAdjunto(index, tipoId, descripcion));
      });
    };

    if (causaSelect) {
      causaSelect.addEventListener('change', renderAdjuntosPorCausa);
      if (window.jQuery) {
        window.jQuery(causaSelect).on('change', renderAdjuntosPorCausa);
      }
      renderAdjuntosPorCausa();
    }

    const mostrarLoaderRadicacion = () => {
      if (!window.Swal || typeof window.Swal.fire !== 'function') {
        return;
      }

      window.Swal.fire({
        title: 'Radicando incapacidad...',
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

    if (incapacidadForm) {
      incapacidadForm.addEventListener('submit', function(event) {
        if (incapacidadForm.dataset.submitting === '1') {
          event.preventDefault();
          return;
        }

        incapacidadForm.dataset.submitting = '1';
        const elementos = incapacidadForm.querySelectorAll('button, input[type="submit"]');
        elementos.forEach((elemento) => {
          elemento.disabled = true;
        });

        mostrarLoaderRadicacion();
      });
    }
  });
</script>
@endPushOnce
