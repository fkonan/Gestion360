@extends('layouts.dashboard')

@section('title', 'Gestionar incapacidad')

@section('breadcrumb')
<x-breadcrumb :items="[
        ['name' => 'Inicio', 'url' => route('home')],
        ['name' => 'Solicitudes y novedades', 'url' => route('gestionRRHH.solicitudes.index')],
        ['name' => 'Gestión RRHH', 'url' => route('gestionRRHH.permisos.rrhh')],
        ['name' => 'Gestionar incapacidad']
    ]" />
@endsection

@section('content')
<div class="container-fluid tableContainer p-0 border rounded sidebar-dark-primary" style="min-height:150px">
  <x-sectionHeader
    titulo="Gestionar incapacidad"
    rutaVolver="{{ route('gestionRRHH.permisos.rrhh') }}"
    :clasePosition="false" />

  <div class="p-4">
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

    <div class="row g-4">
      <div class="col-12 col-xl-8">
        <form method="POST" class="js-gestion-incapacidad-form" data-accion-loader="Guardando cambios" action="{{ route('gestionRRHH.permisos.incapacidades.update', ['idNovedad' => $novedad->id_novedad]) }}">
          @csrf
          @method('PUT')

          <div class="permiso-panel mb-4">
            <h6 class="permiso-panel-title">Resumen de la solicitud</h6>
            <div class="row g-3">
              <div class="col-md-3">
                <label class="form-label">Novedad</label>
                <input type="text" class="form-control" value="{{ $novedad->id_novedad }}" readonly>
              </div>
              <div class="col-md-3">
                <label class="form-label">Detalle</label>
                <input type="text" class="form-control" value="{{ $novedad->id_origen }}" readonly>
              </div>
              <div class="col-md-3">
                <label class="form-label">Estado</label>
                <input type="text" class="form-control" value="{{ $novedad->estado }}" readonly>
              </div>
              <div class="col-md-3">
                <label class="form-label">Días</label>
                <input type="text" class="form-control" value="{{ $novedad->dias_incapacidad ?? 'N/A' }}" readonly>
              </div>
            </div>
          </div>

          <div class="permiso-panel mb-4">
            <h6 class="permiso-panel-title">Empleado</h6>
            <div class="row g-3">
              <div class="col-md-4">
                <label class="form-label">Documento</label>
                <input type="text" class="form-control" value="{{ $novedad->documento_persona ?? $novedad->id_persona }}" readonly>
              </div>
              <div class="col-md-8">
                <label class="form-label">Nombre</label>
                <input type="text" class="form-control" value="{{ data_get($novedad, 'persona.nombre', 'Sin nombre') }}" readonly>
              </div>
              <div class="col-md-4">
                <label class="form-label">Radicado por</label>
                <input type="text" class="form-control" value="{{ $novedad->documento_radica ?? 'N/A' }}" readonly>
              </div>
              <div class="col-md-4">
                <label class="form-label">Código</label>
                <input type="text" class="form-control" value="{{ data_get($novedad, 'persona.codigo', '') }}" readonly>
              </div>
              <div class="col-md-4">
                <label class="form-label">Sección</label>
                <input type="text" class="form-control" value="{{ data_get($novedad, 'persona.seccion', '') }}" readonly>
              </div>
            </div>
          </div>

          <div class="permiso-panel mb-4">
            <h6 class="permiso-panel-title">Datos de incapacidad</h6>
            <div class="row g-3">
              <div class="col-md-6">
                <label for="causa_id" class="form-label">Causa incapacidad</label>
                <select class="form-select select2" id="causa_id" name="causa_id" required>
                  <option value="">Seleccione...</option>
                  @foreach($causas as $id => $nombre)
                  <option value="{{ $id }}" @selected(old('causa_id', $novedad->id_causa_incapacidad) === $id)>{{ $nombre }}</option>
                  @endforeach
                </select>
              </div>
              <div class="col-md-6">
                <label for="diagnostico_id" class="form-label">Diagnóstico</label>
                <select class="form-select" id="diagnostico_id" name="diagnostico_id" required>
                  <option value="">Seleccione...</option>
                  @foreach($diagnosticos as $id => $nombre)
                  <option value="{{ $id }}" @selected(old('diagnostico_id', $novedad->id_diagnostico) === $id)>{{ $nombre }}</option>
                  @endforeach
                </select>
              </div>
              <div class="col-md-6">
                <label for="eps_id" class="form-label">EPS</label>
                <select class="form-select select2" id="eps_id" name="eps_id" required>
                  <option value="">Seleccione...</option>
                  @foreach($eps as $id => $nombre)
                  <option value="{{ $id }}" @selected(old('eps_id', $novedad->id_eps) === $id)>{{ $nombre }}</option>
                  @endforeach
                </select>
              </div>
              <div class="col-md-6">
                <label for="arl_id" class="form-label">ARL</label>
                <select class="form-select select2" id="arl_id" name="arl_id" required>
                  <option value="">Seleccione...</option>
                  @foreach($arl as $id => $nombre)
                  <option value="{{ $id }}" @selected(old('arl_id', $novedad->id_arl) === $id)>{{ $nombre }}</option>
                  @endforeach
                </select>
              </div>
              <div class="col-md-4">
                <label for="tipo_incapacidad" class="form-label">Tipo incapacidad</label>
                <select class="form-select select2" id="tipo_incapacidad" name="tipo_incapacidad" required>
                  @foreach($tiposIncapacidad as $codigo => $label)
                  <option value="{{ $codigo }}" @selected(old('tipo_incapacidad', $novedad->tipo_incapacidad) === $codigo)>{{ $label }}</option>
                  @endforeach
                </select>
              </div>
              <div class="col-md-4">
                <label for="fecha_inicio" class="form-label">Fecha inicio</label>
                <input type="date" class="form-control" id="fecha_inicio" name="fecha_inicio" value="{{ old('fecha_inicio', $novedad->fecha_inicio ? \Illuminate\Support\Carbon::parse($novedad->fecha_inicio)->format('Y-m-d') : '') }}" required>
              </div>
              <div class="col-md-4">
                <label for="fecha_fin" class="form-label">Fecha fin</label>
                <input type="date" class="form-control" id="fecha_fin" name="fecha_fin" value="{{ old('fecha_fin', $novedad->fecha_fin ? \Illuminate\Support\Carbon::parse($novedad->fecha_fin)->format('Y-m-d') : '') }}" required>
              </div>
              <div class="col-12">
                <label for="observacion" class="form-label">Observación</label>
                <textarea class="form-control" id="observacion" name="observacion" rows="3" maxlength="255">{{ old('observacion', $novedad->observacion) }}</textarea>
              </div>
            </div>
          </div>

          <div class="d-flex gap-2">
            <button type="submit" class="btn btn-warning">Guardar cambios</button>
            <a href="{{ route('gestionRRHH.permisos.rrhh') }}" class="btn btn-outline-secondary">Volver</a>
          </div>
        </form>
      </div>

      <div class="col-12 col-xl-4">
        <div class="permiso-panel mb-4">
          <h6 class="permiso-panel-title">Soportes</h6>
          @if($adjuntos->isEmpty())
          <div class="alert alert-light border mb-0">Esta solicitud no tiene adjuntos registrados.</div>
          @else
          <ul class="list-group">
            @foreach($adjuntos as $adjunto)
            <li class="list-group-item d-flex justify-content-between align-items-center">
              <div>
                <div class="fw-semibold">{{ $adjunto->tipo_documento ?? $adjunto->id_tipo_documento }}</div>
                <small class="text-muted">{{ \Illuminate\Support\Carbon::parse($adjunto->fecha_creacion)->format('d/m/Y H:i') }}</small>
              </div>
              <a href="{{ route('gestionRRHH.permisos.documentos.ver', ['idDocumento' => $adjunto->id]) }}" target="_blank" class="btn btn-sm btn-outline-dark">Ver</a>
            </li>
            @endforeach
          </ul>
          @endif
        </div>

        <div class="permiso-panel mb-4">
          <h6 class="permiso-panel-title">Seguimiento RRHH</h6>
          @if(!empty($puedeRegistrarSeguimiento))
          <form method="POST" class="mb-3 js-seguimiento-incapacidad-form" action="{{ route('gestionRRHH.permisos.incapacidades.seguimiento.store', ['idNovedad' => $novedad->id_novedad]) }}">
            @csrf
            <label for="observacion_seguimiento" class="form-label">Observación de seguimiento</label>
            <textarea id="observacion_seguimiento" name="observacion_seguimiento" class="form-control" rows="3" maxlength="1000" required>{{ old('observacion_seguimiento') }}</textarea>
            <button type="submit" class="btn btn-outline-primary w-100 mt-2">Registrar seguimiento</button>
          </form>
          @else
          <div class="alert alert-light border">
            El seguimiento solo se habilita cuando la incapacidad esta en estado <strong>APROBADO</strong>.
          </div>
          @endif

          @if(empty($seguimientos))
          <div class="alert alert-light border mb-0">Aun no hay observaciones de seguimiento registradas.</div>
          @else
          <ul class="list-group">
            @foreach($seguimientos as $seguimiento)
            <li class="list-group-item">
              <div class="d-flex justify-content-between align-items-start gap-2">
                <div>
                  <div class="fw-semibold">
                    {{ $seguimiento->nombre_actor !== '' ? $seguimiento->nombre_actor : ($seguimiento->documento_actor !== '' ? $seguimiento->documento_actor : 'RRHH') }}
                  </div>
                  @if($seguimiento->nombre_actor !== '' && $seguimiento->documento_actor !== '')
                  <small class="text-muted">{{ $seguimiento->documento_actor }}</small>
                  @endif
                </div>
                <small class="text-muted text-nowrap">
                  {{ $seguimiento->fecha_cambio ? \Illuminate\Support\Carbon::parse($seguimiento->fecha_cambio)->format('d/m/Y H:i') : 'N/A' }}
                </small>
              </div>
              <div class="mt-2">{{ $seguimiento->observacion }}</div>
            </li>
            @endforeach
          </ul>
          @endif
        </div>

        @if($novedad->estado === 'RADICADO')
        <div class="permiso-panel">
          <h6 class="permiso-panel-title">Respuesta RRHH</h6>
          <div class="d-grid gap-2">
            <form method="POST" class="js-gestion-incapacidad-form" data-accion-loader="Aprobando solicitud" action="{{ route('gestionRRHH.permisos.incapacidades.aprobar', ['idNovedad' => $novedad->id_novedad]) }}">
              @csrf
              <button type="submit" class="btn btn-success w-100">Aprobar incapacidad</button>
            </form>

            <button type="button" class="btn btn-danger" data-bs-toggle="collapse" data-bs-target="#rechazoIncapacidadForm">Rechazar incapacidad</button>

            <div class="collapse mt-2" id="rechazoIncapacidadForm">
              <form method="POST" class="js-gestion-incapacidad-form" data-accion-loader="Rechazando solicitud" action="{{ route('gestionRRHH.permisos.incapacidades.rechazar', ['idNovedad' => $novedad->id_novedad]) }}">
                @csrf
                <div class="mb-2">
                  <label for="motivo_rechazo" class="form-label">Motivo de rechazo</label>
                  <textarea id="motivo_rechazo" name="motivo_rechazo" class="form-control" rows="3" maxlength="255" required>{{ old('motivo_rechazo') }}</textarea>
                </div>
                <button type="submit" class="btn btn-danger w-100">Confirmar rechazo</button>
              </form>
            </div>
          </div>
        </div>
        @endif
      </div>
    </div>
  </div>
</div>
@endsection

@pushOnce('script')
<script>
  document.addEventListener('DOMContentLoaded', function () {
    if (window.jQuery && window.jQuery.fn && window.jQuery.fn.select2) {
      window.jQuery('.select2').select2({ width: '100%' });

      const diagnosticoSelect = window.jQuery('#diagnostico_id');
      if (diagnosticoSelect.length) {
        diagnosticoSelect.select2({
          width: '100%',
          ajax: {
            url: @json(route('gestionRRHH.permisos.incapacidades.diagnosticos')),
            dataType: 'json',
            delay: 250,
            data: function (params) {
              return {
                q: params.term || '',
                limit: 10
              };
            },
            processResults: function (data) {
              return {
                results: Array.isArray(data.results) ? data.results : []
              };
            },
            cache: true
          },
          minimumInputLength: 0
        });
      }
    }

    const mostrarLoaderGestion = function (accion) {
      if (!window.Swal || typeof window.Swal.fire !== 'function') {
        return;
      }

      window.Swal.fire({
        title: accion,
        text: 'Por favor espera...',
        allowOutsideClick: false,
        allowEscapeKey: false,
        showConfirmButton: false,
        customClass: {
          popup: 'swalAlert'
        },
        didOpen: function () {
          if (typeof window.Swal.showLoading === 'function') {
            window.Swal.showLoading();
          }
        }
      });
    };

    const marcarFormularioEnProceso = function (formulario, accion) {
      if (!formulario || formulario.dataset.processing === '1') {
        return false;
      }

      formulario.dataset.processing = '1';
      formulario.querySelectorAll('button, input[type="submit"]').forEach(function (boton) {
        boton.disabled = true;
      });

      mostrarLoaderGestion(accion);
      return true;
    };

    document.querySelectorAll('.js-seguimiento-incapacidad-form').forEach(function (formulario) {
      formulario.addEventListener('submit', function () {
        const boton = formulario.querySelector('button[type="submit"]');
        if (boton) {
          boton.disabled = true;
          boton.textContent = 'Guardando...';
        }
      });
    });

    document.querySelectorAll('form.js-gestion-incapacidad-form').forEach(function (formulario) {
      formulario.addEventListener('submit', function (event) {
        const textoAccion = formulario.dataset.accionLoader || 'Procesando solicitud';
        if (!marcarFormularioEnProceso(formulario, textoAccion)) {
          event.preventDefault();
        }
      });
    });

  });
</script>
@endPushOnce
