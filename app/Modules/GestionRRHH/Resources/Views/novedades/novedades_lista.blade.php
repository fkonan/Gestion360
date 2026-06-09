@extends('layouts.dashboard')

@section('title', $tituloVista ?? 'Listado')

@section('breadcrumb')
<x-breadcrumb :items="[
        ['name' => 'Inicio', 'url' => route('home')],
        ['name' => 'Solicitudes y novedades', 'url' => route('gestionRRHH.solicitudes.index')],
        ['name' => $breadcrumbFinal ?? ($tituloVista ?? 'Listado')]
    ]" />
@endsection

@php
  $modoVista = $modoVista ?? 'global';
  $tituloVista = $tituloVista ?? 'Listado';
  $forzarAprobadas = !empty($forzarAprobadas);
@endphp

@section('content')
<div class="container-fluid tableContainer p-0 border rounded sidebar-dark-primary" style="min-height:150px">
  <x-sectionHeader
    :titulo="$tituloVista"
    rutaVolver="{{ route('gestionRRHH.solicitudes.index') }}"
    :clasePosition="false" />

  <div class="novedades-central-content">
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

    <form method="GET" action="{{ request()->url() }}" class="row g-3 mb-3">
      @if(in_array($modoVista, ['global', 'rrhh'], true))
      <div class="col-12 col-md-4">
        <label for="persona" class="form-label">Persona</label>
        <input type="text" class="form-control" id="persona" name="persona" value="{{ $filtros['persona'] ?? '' }}" placeholder="Documento o nombre">
      </div>
      @endif

      <div class="col-12 col-md-3">
        <label for="tipo" class="form-label">Tipo</label>
        <select class="form-select" id="tipo" name="tipo">
          <option value="">Todos</option>
          @foreach($tiposDisponibles as $codigo => $label)
          <option value="{{ $codigo }}" @selected(($filtros['tipo'] ?? '') === $codigo)>{{ $label }}</option>
          @endforeach
        </select>
      </div>

      <!-- <div class="col-12 col-md-3">
        <label for="estado" class="form-label">Estado</label>
        <select class="form-select" id="estado" name="estado">
          @if(!$forzarAprobadas)
          <option value="">Todos</option>
          @endif
          @foreach($estadosDisponibles as $codigo => $label)
          <option value="{{ $codigo }}" @selected(($filtros['estado'] ?? '') === $codigo)>{{ $label }}</option>
          @endforeach
        </select>
      </div>
 -->
      @if($modoVista === 'global')
      <div class="col-12 col-md-2 d-flex align-items-end">
        <div class="w-100">
          <label for="vigencia" class="form-label">Vigencia</label>
          <select class="form-select" id="vigencia" name="vigencia">
            <option value="todas" @selected(($filtros['vigencia'] ?? 'todas') === 'todas')>Todas</option>
            <option value="inician_hoy" @selected(($filtros['vigencia'] ?? '') === 'inician_hoy')>Inician hoy</option>
            <option value="activas_hoy" @selected(($filtros['vigencia'] ?? '') === 'activas_hoy')>Activas hoy</option>
          </select>
        </div>
      </div>
      @endif

      <div class="col-12 d-flex gap-2">
        <button type="submit" class="btn btn-success">Buscar</button>
        <a href="{{ request()->url() }}" class="btn btn-dark">Limpiar</a>
      </div>
    </form>

    <div id="no-more-tables" class="table-responsive">
      <table
        id="novedadesCentralTable"
        class="table table-sm table-striped table-hover align-middle"
        data-toggle="table"
        data-locale="es-ES"
        data-search="false"
        data-pagination="false"
        data-detail-view="true"
        data-detail-formatter="detalleNovedadesCentral">
        <thead class="table-primary">
          <tr>
            <th data-field="tipo">Tipo</th>
            <th data-field="documento">Documento</th>
            <th data-field="empleado">Empleado</th>
            <th data-field="radicado_por">Radicado por</th>
            <th data-field="detalle">Detalle</th>
            <th data-field="motivo">Motivo</th>
            <th data-field="otro_motivo">Otro motivo</th>
            <th data-field="jornada">Jornada</th>
            <th data-field="horario_fijo">Horario fijo</th>
            <th data-field="horario_j1">Horario J1</th>
            <th data-field="horario_j2">Horario J2</th>
            <th data-field="causa">Causa</th>
            <th data-field="diagnostico">Diagnostico</th>
            <th data-field="eps">EPS</th>
            <th data-field="arl">ARL</th>
            <th data-field="adjuntos">Adjuntos</th>
            <th data-field="fecha_inicio">Fecha inicio</th>
            <th data-field="fecha_fin">Fecha fin</th>
            <th data-field="estado">Estado</th>
            <th data-field="acciones" class="text-center">Acciones</th>
          </tr>
        </thead>
        <tbody>
          @forelse($novedades as $novedad)
          <tr>
            <td class="text-nowrap">{{ $novedad->vista_tipo }}</td>
            <td class="text-nowrap">{{ $novedad->vista_documento }}</td>
            <td class="text-nowrap">{{ $novedad->vista_empleado }}</td>
            <td class="text-nowrap">
              {{ $novedad->vista_radicado_por }}
              @if($novedad->vista_radicado_por_documento !== '')
              <div class="small text-muted">{{ $novedad->vista_radicado_por_documento }}</div>
              @endif
            </td>
            <td>{{ $novedad->vista_detalle }}</td>
            <td>{{ $novedad->vista_motivo }}</td>
            <td>{{ $novedad->vista_otro_motivo }}</td>
            <td>{{ $novedad->vista_jornada }}</td>
            <td>{{ $novedad->vista_horario_fijo }}</td>
            <td>{{ $novedad->vista_horario_j1 }}</td>
            <td>{{ $novedad->vista_horario_j2 }}</td>
            <td>{{ $novedad->vista_causa }}</td>
            <td>{{ $novedad->vista_diagnostico }}</td>
            <td>{{ $novedad->vista_eps }}</td>
            <td>{{ $novedad->vista_arl }}</td>
            <td>{{ $novedad->vista_adjuntos }}</td>
            <td class="text-nowrap">{{ $novedad->vista_fecha_inicio }}</td>
            <td class="text-nowrap">{{ $novedad->vista_fecha_fin }}</td>
            <td class="text-nowrap">{{ $novedad->vista_estado }}</td>
            <td class="text-nowrap text-center">
              <div class="permiso-actions-group">
                @if($novedad->vista_tipo_codigo === 'PERMISO')
                <a
                  class="permiso-action-trigger d-inline-flex align-items-center justify-content-center p-0 border-0 bg-transparent text-decoration-none"
                  href="{{ route('gestionRRHH.permisos.pdf', ['idNovedad' => $novedad->id_novedad]) }}"
                  target="_blank"
                  title="Ver PDF"
                  data-bs-toggle="tooltip">
                  <img src="{{ asset('img/descargarPDF.png') }}" alt="PDF" class="permiso-action-icon">
                </a>
                @endif

                @unless($novedad->vista_es_incapacidad_gestion_rrhh)
                <a
                  class="js-ver-adjuntos-permiso permiso-action-trigger d-inline-flex align-items-center justify-content-center p-0 border-0 bg-transparent text-decoration-none"
                  href="{{ route('gestionRRHH.permisos.documentos', ['idNovedad' => $novedad->id_novedad, 'return_to' => request()->fullUrl(), 'return_label' => $tituloVista]) }}"
                  data-adjuntos-url="{{ route('gestionRRHH.permisos.documentos', ['idNovedad' => $novedad->id_novedad]) }}"
                  title="Ver adjuntos"
                  data-bs-toggle="tooltip"
                  data-has-adjuntos="{{ !empty($novedad->tiene_adjuntos) ? '1' : '0' }}">
                  <img src="{{ asset('img/verDetalle.png') }}" alt="Adjuntos" class="permiso-action-icon">
                </a>
                @endunless

                @if($modoVista === 'mis')
                <a
                  class="js-ver-trazabilidad-novedad permiso-action-trigger d-inline-flex align-items-center justify-content-center p-0 border-0 bg-transparent text-decoration-none"
                  href="{{ route('gestionRRHH.permisos.trazabilidad', ['idNovedad' => $novedad->id_novedad]) }}"
                  data-trazabilidad-url="{{ route('gestionRRHH.permisos.trazabilidad', ['idNovedad' => $novedad->id_novedad]) }}"
                  title="Ver trazabilidad"
                  data-bs-toggle="tooltip">
                  <img src="{{ asset('img/seguimiento.png') }}" alt="Trazabilidad" class="permiso-action-icon permiso-action-icon-seguimiento">
                </a>
                @endif


                @if($modoVista === 'mis' && !empty($novedad->puede_anular))
                <form method="POST" class="js-anular-permiso-form" action="{{ route('gestionRRHH.permisos.anular', ['idNovedad' => $novedad->id_novedad]) }}">
                  @csrf
                  <input type="hidden" name="motivo_anulacion" value="">
                  <button type="button" class="js-abrir-modal-anulacion permiso-action-trigger d-inline-flex align-items-center justify-content-center p-0 border-0 bg-transparent" title="Anular novedad" data-bs-toggle="tooltip">
                    <img src="{{ asset('img/anular.png') }}" alt="Anular" class="permiso-action-icon">
                  </button>
                </form>
                @endif

                @if($modoVista === 'jefe' && !empty($novedad->puede_aprobar_jefe))
                <form method="POST" class="js-aprobar-jefe-permiso-form d-inline" data-accion-loader="Aprobando solicitud" action="{{ route('gestionRRHH.permisos.aprobar-jefe', ['idNovedad' => $novedad->id_novedad]) }}">
                  @csrf
                  <button type="submit" class="permiso-action-trigger d-inline-flex align-items-center justify-content-center p-0 border-0 bg-transparent" title="Aprobar por jefe" data-bs-toggle="tooltip">
                    <img src="{{ asset('img/aprobar.png') }}" alt="Aprobar" class="permiso-action-icon">
                  </button>
                </form>
                @endif

                @if(in_array($modoVista, ['jefe', 'rrhh'], true) && $novedad->vista_puede_rechazar_actual && !$novedad->vista_es_incapacidad_gestion_rrhh)
                <form
                  method="POST"
                  class="js-rechazar-permiso-form d-inline"
                  data-accion-loader="Rechazando solicitud"
                  action="{{ $novedad->vista_tipo_codigo === 'INCAPACIDAD'
                    ? route('gestionRRHH.permisos.incapacidades.rechazar', ['idNovedad' => $novedad->id_novedad])
                    : route('gestionRRHH.permisos.rechazar', ['idNovedad' => $novedad->id_novedad]) }}">
                  @csrf
                  @if($novedad->vista_tipo_codigo !== 'INCAPACIDAD')
                  <input type="hidden" name="nivel" value="{{ $modoVista === 'jefe' ? 'jefe' : 'rrhh' }}">
                  @endif
                  <input type="hidden" name="motivo_rechazo" value="">
                  <button type="button" class="js-abrir-modal-rechazo permiso-action-trigger d-inline-flex align-items-center justify-content-center p-0 border-0 bg-transparent" title="Rechazar" data-bs-toggle="tooltip">
                    <img src="{{ asset('img/rechazar.png') }}" alt="Rechazar" class="permiso-action-icon">
                  </button>
                </form>
                @endif

                @if($modoVista === 'rrhh' && !empty($novedad->puede_aprobar_rrhh) && !$novedad->vista_es_incapacidad_gestion_rrhh)
                <form
                  method="POST"
                  class="js-aprobar-rrhh-permiso-form d-inline"
                  data-accion-loader="Aprobando solicitud"
                  action="{{ $novedad->vista_tipo_codigo === 'INCAPACIDAD'
                    ? route('gestionRRHH.permisos.incapacidades.aprobar', ['idNovedad' => $novedad->id_novedad])
                    : route('gestionRRHH.permisos.aprobar-rrhh', ['idNovedad' => $novedad->id_novedad]) }}">
                  @csrf
                  <button type="submit" class="permiso-action-trigger d-inline-flex align-items-center justify-content-center p-0 border-0 bg-transparent" title="Aprobar por RRHH" data-bs-toggle="tooltip">
                    <img src="{{ asset('img/aprobar.png') }}" alt="Aprobar RRHH" class="permiso-action-icon">
                  </button>
                </form>
                @endif

                @if(in_array($modoVista, ['rrhh', 'global'], true) && !empty($novedad->puede_ver_gestion) && !empty($novedad->origen_gestion_url))
                <a
                  class="permiso-action-trigger d-inline-flex align-items-center justify-content-center p-0 border-0 bg-transparent text-decoration-none"
                  href="{{ $novedad->origen_gestion_url }}"
                  title="Seguimiento incapacidad"
                  data-bs-toggle="tooltip">
                  <img src="{{ asset('img/seguimiento.png') }}" alt="Seguimiento" class="permiso-action-icon permiso-action-icon-seguimiento">
                </a>
                @endif
              </div>
            </td>
          </tr>
          @empty
          <tr>
            <td colspan="20" class="text-center py-3">
              {{ $forzarAprobadas ? 'No hay novedades aprobadas para la consulta.' : 'No hay solicitudes para la consulta.' }}
            </td>
          </tr>
          @endforelse
        </tbody>
      </table>
    </div>

    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2 mt-3">
      <small class="text-muted">
        Mostrando {{ $novedades->firstItem() ?? 0 }} - {{ $novedades->lastItem() ?? 0 }} de {{ $novedades->total() }} registros.
      </small>
      {{ $novedades->links('pagination::bootstrap-5') }}
    </div>
  </div>
</div>

<div class="modal fade" id="modalRechazoNovedad" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Rechazar novedad</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
      </div>
      <div class="modal-body">
        <label for="motivoRechazoNovedad" class="form-label">Motivo de rechazo</label>
        <textarea id="motivoRechazoNovedad" class="form-control" rows="4" maxlength="500"></textarea>
        <small id="motivoRechazoNovedadError" class="text-danger d-none"></small>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
        <button type="button" id="confirmarRechazoNovedadBtn" class="btn btn-danger">Confirmar rechazo</button>
      </div>
    </div>
  </div>
</div>

<div class="modal fade" id="modalAnulacionNovedad" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Anular novedad</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
      </div>
      <div class="modal-body">
        <label for="motivoAnulacionNovedad" class="form-label">Motivo de anulación</label>
        <textarea id="motivoAnulacionNovedad" class="form-control" rows="4" maxlength="500"></textarea>
        <small id="motivoAnulacionNovedadError" class="text-danger d-none"></small>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
        <button type="button" id="confirmarAnulacionNovedadBtn" class="btn btn-danger">Confirmar anulación</button>
      </div>
    </div>
  </div>
</div>

@endsection

@pushOnce('css')
@vite('resources/css/gestionrrhh/novedades-lista.css')
@endPushOnce

@pushOnce('script')
<script>
document.addEventListener('DOMContentLoaded', function () {
  const modoVistaActual = @json($modoVista);
  const forzarAprobadasActual = @json($forzarAprobadas);

  const mostrarLoaderGestion = function (accion) {
    if (window.Swal && typeof window.Swal.fire === 'function') {
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
          window.Swal.showLoading();
        }
      });
      return;
    }
  };

  const marcarFormularioEnProceso = function (formulario, accion) {
    if (!formulario || formulario.dataset.processing === '1') {
      return false;
    }

    formulario.dataset.processing = '1';
    const botones = formulario.querySelectorAll('button, input[type="submit"]');
    botones.forEach(function (boton) {
      boton.disabled = true;
    });

    mostrarLoaderGestion(accion);
    return true;
  };

  const escapeHtml = function (value) {
    return String(value ?? '')
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;')
      .replace(/'/g, '&#039;');
  };

  if (typeof initTablaBootstrapTable === 'function') {
    const columnasOcultasFijas = [
      'acciones',
      'motivo',
      'otro_motivo',
      'jornada',
      'horario_fijo',
      'horario_j1',
      'horario_j2',
      'causa',
      'diagnostico',
      'eps',
      'arl',
      'adjuntos'
    ];

    if (modoVistaActual === 'jefe' || modoVistaActual === 'rrhh' || modoVistaActual === 'mis' || forzarAprobadasActual) {
      columnasOcultasFijas.push('detalle');
    }

    initTablaBootstrapTable(
      '#novedadesCentralTable',
      {
        protegidas: ['tipo', 'documento'],
        ocultas: columnasOcultasFijas,
        forzarDetalle: true
      },
      'detalleNovedadesCentral'
    );
  }

  const inicializarTooltips = function (scope = document) {
    if (!window.bootstrap || !window.bootstrap.Tooltip || !scope) {
      return;
    }

    const tooltipNodes = scope.querySelectorAll('[data-bs-toggle="tooltip"]');
    tooltipNodes.forEach(function (node) {
      if (typeof window.bootstrap.Tooltip.getOrCreateInstance === 'function') {
        window.bootstrap.Tooltip.getOrCreateInstance(node);
        return;
      }

      const instance = window.bootstrap.Tooltip.getInstance(node);
      if (instance) {
        instance.dispose();
      }

      new window.bootstrap.Tooltip(node);
    });
  };

  const tablaNovedadesElement = document.getElementById('novedadesCentralTable');
  inicializarTooltips(tablaNovedadesElement || document);

  if (window.jQuery && tablaNovedadesElement) {
    const $tablaNovedades = window.jQuery(tablaNovedadesElement);
    $tablaNovedades.on('post-body.bs.table', function () {
      inicializarTooltips(tablaNovedadesElement);
    });

    $tablaNovedades.on('expand-row.bs.table', function (event, index, row, $detail) {
      const scope = $detail && $detail.length ? $detail[0] : tablaNovedadesElement;
      inicializarTooltips(scope);
    });
  }

  const formatearFechaAdjunto = function (valor) {
    const texto = String(valor || '').trim();
    if (texto === '') {
      return '';
    }

    const fecha = new Date(texto);
    if (Number.isNaN(fecha.getTime())) {
      return texto;
    }

    return fecha.toLocaleString('es-CO', {
      year: 'numeric',
      month: '2-digit',
      day: '2-digit',
      hour: '2-digit',
      minute: '2-digit'
    });
  };

  const globalModalElement = document.getElementById('globalModal');
  const globalModalTitle = document.getElementById('globalModalTitle');
  const globalModalContent = document.getElementById('globalModalContent');
  const globalModalDialog = globalModalElement ? globalModalElement.querySelector('.modal-dialog') : null;
  const globalModal = (window.bootstrap && globalModalElement)
    ? window.bootstrap.Modal.getOrCreateInstance(globalModalElement)
    : null;
  const pdfIconUrl = @json(asset('img/descargarPDF.png'));

  const aplicarModoGlobalModal = function (modo) {
    if (!globalModalElement || !globalModalDialog) {
      return;
    }

    globalModalElement.classList.remove('is-trazabilidad-modal', 'is-adjuntos-modal');
    globalModalDialog.classList.remove('modal-xl', 'modal-lg');

    if (modo === 'trazabilidad') {
      globalModalElement.classList.add('is-trazabilidad-modal');
      globalModalDialog.classList.add('modal-xl');
      return;
    }

    globalModalElement.classList.add('is-adjuntos-modal');
    globalModalDialog.classList.add('modal-lg');
  };

  const renderTablaAdjuntosHtml = function (adjuntos, mensajeError = '') {
    const totalAdjuntos = Array.isArray(adjuntos) ? adjuntos.length : 0;

    if (mensajeError !== '') {
      return `
        <div class="alert alert-danger mb-0">${escapeHtml(mensajeError)}</div>
      `;
    }

    const filas = totalAdjuntos > 0
      ? adjuntos.map(function (adjunto) {
          const tipo = escapeHtml(adjunto.tipo_documento || 'Documento');
          const nombre = escapeHtml(adjunto.nombre_archivo || adjunto.ruta_documento || 'Archivo');
          const fecha = escapeHtml(formatearFechaAdjunto(adjunto.fecha_creacion) || 'N/A');
          const urlVer = String(adjunto.ver_documento_url || '').trim();
          const accion = urlVer !== ''
            ? `<a href="${escapeHtml(urlVer)}" target="_blank" class="d-inline-flex align-items-center justify-content-center" title="Ver PDF">
                <img src="${escapeHtml(pdfIconUrl)}" alt="Ver PDF" class="permiso-action-icon">
              </a>`
            : '<span class="text-muted small">N/A</span>';

          return `
            <tr>
              <td>${tipo}</td>
              <td class="text-break">${nombre}</td>
              <td class="text-nowrap">${fecha}</td>
              <td class="text-center">${accion}</td>
            </tr>
          `;
        }).join('')
      : '<tr><td colspan="4" class="text-center text-muted">No hay adjuntos registrados para esta solicitud.</td></tr>';

    return `
      <div class="d-flex justify-content-between align-items-center mb-2">
        <small class="text-muted">Total adjuntos: ${totalAdjuntos}</small>
      </div>
      <div class="table-responsive">
        <table class="table table-sm table-striped align-middle mb-0">
          <thead>
            <tr>
              <th style="min-width: 180px;">Tipo de documento</th>
              <th>Archivo</th>
              <th style="min-width: 160px;">Fecha</th>
              <th class="text-center" style="min-width: 110px;">Acción</th>
            </tr>
          </thead>
          <tbody>${filas}</tbody>
        </table>
      </div>
    `;
  };

  const abrirModalAdjuntos = function (urlAdjuntos) {
    const destino = String(urlAdjuntos || '').trim();
    if (destino === '' || !globalModal || !globalModalTitle || !globalModalContent) {
      return;
    }

    aplicarModoGlobalModal('adjuntos');
    globalModalTitle.textContent = 'Adjuntos de la solicitud';
    globalModalContent.innerHTML = `
      <div class="d-flex flex-column align-items-center justify-content-center py-4">
        <i class="fas fa-spinner fa-spin fa-2x mb-2 text-primary"></i>
        <p class="mb-0 text-muted">Cargando adjuntos...</p>
      </div>
    `;
    globalModal.show();

    window.fetch(destino, {
      headers: {
        'X-Requested-With': 'XMLHttpRequest',
        'Accept': 'application/json'
      },
      credentials: 'same-origin'
    })
      .then(function (response) {
        return response.json().then(function (data) {
          return { status: response.status, ok: response.ok, data: data };
        });
      })
      .then(function (result) {
        if (!result.ok || !result.data || result.data.ok !== true) {
          const msg = (result.data && result.data.message) ? result.data.message : 'No fue posible cargar los adjuntos.';
          throw new Error(msg);
        }

        const adjuntos = Array.isArray(result.data.adjuntos) ? result.data.adjuntos : [];
        globalModalContent.innerHTML = renderTablaAdjuntosHtml(adjuntos);
        inicializarTooltips(globalModalContent);
      })
      .catch(function (error) {
        const mensaje = (error && error.message) ? error.message : 'No fue posible cargar los adjuntos.';
        globalModalContent.innerHTML = renderTablaAdjuntosHtml([], mensaje);
      });
  };

  const renderTrazabilidadHtmlLegacy = function (trazabilidad, mensajeError = '') {
    if (mensajeError !== '') {
      return `
        <div class="alert alert-danger mb-0">${escapeHtml(mensajeError)}</div>
      `;
    }

    const data = trazabilidad && typeof trazabilidad === 'object' ? trazabilidad : {};
    const empleado = data.empleado && typeof data.empleado === 'object' ? data.empleado : {};
    const eventos = Array.isArray(data.eventos) ? data.eventos : [];
    const notas = Array.isArray(data.notas)
      ? data.notas.map(function (nota) { return String(nota || '').trim(); }).filter(Boolean)
      : [];

    const tipoLabel = String(data.tipo_label || data.tipo_codigo || 'Solicitud').trim();
    const empleadoNombre = String(empleado.nombre || '').trim();
    const empleadoDocumento = String(empleado.documento || '').trim();
    const estadoActual = String(data.estado_actual_label || data.estado_actual || 'N/A').trim();
    const estadoActualCodigo = String(data.estado_actual || '').trim().toUpperCase();
    const encabezadoEmpleadoLegacy = empleadoNombre !== ''
      ? `${escapeHtml(empleadoNombre)}${empleadoDocumento !== '' ? ` - ${escapeHtml(empleadoDocumento)}` : ''}`
      : (empleadoDocumento !== '' ? escapeHtml(empleadoDocumento) : 'Sin persona asociada');
    const primerEvento = eventos.length > 0 ? eventos[0] : null;
    const ultimoEvento = eventos.length > 0 ? eventos[eventos.length - 1] : null;

    const encabezadoEmpleado = empleadoNombre !== ''
      ? `${escapeHtml(empleadoNombre)}${empleadoDocumento !== '' ? ` · ${escapeHtml(empleadoDocumento)}` : ''}`
      : (empleadoDocumento !== '' ? escapeHtml(empleadoDocumento) : 'Sin persona asociada');

    const notasHtml = notas.length > 0
      ? `
        <div class="alert alert-info py-2 px-3 mb-0">
          ${notas.map(function (nota) {
            return `<div>${escapeHtml(nota)}</div>`;
          }).join('')}
        </div>
      `
      : '';

    const timelineHtml = eventos.length > 0
      ? eventos.map(function (evento) {
          const estadoLabel = String(evento.estado_label || evento.estado || 'Estado').trim();
          const actor = String(evento.actor_nombre || evento.actor_documento || '').trim();
          const fecha = String(evento.fecha || '').trim();
          const detalle = String(evento.detalle || '').trim();
          const metaPlano = [fecha || 'Fecha no disponible', actor || 'Actor no identificado'].join(' - ');
          const meta = [fecha || 'Fecha no disponible', actor || 'Actor no identificado'].join(' · ');
          const badgeHtml = evento.es_estado_actual
            ? '<span class="badge text-bg-success">Actual</span>'
            : '<span class="badge text-bg-light text-dark">Registrado</span>';

          return `
            <div class="trazabilidad-item ${evento.es_estado_actual ? 'is-current' : ''}">
              <div class="trazabilidad-marker"></div>
              <div class="trazabilidad-card">
                <div class="d-flex flex-wrap justify-content-between align-items-start gap-2">
                  <div>
                    <div class="fw-semibold">${escapeHtml(estadoLabel)}</div>
                    <div class="small text-muted">${escapeHtml(metaPlano)}</div>
                  </div>
                  ${badgeHtml}
                </div>
                ${detalle !== '' ? `<div class="trazabilidad-detail">${escapeHtml(detalle)}</div>` : ''}
              </div>
            </div>
          `;
        }).join('')
      : `
        <div class="alert alert-light border mb-0">
          No hay eventos de trazabilidad registrados para esta solicitud.
        </div>
      `;

    return `
      <div class="d-flex flex-column gap-3">
        <div class="trazabilidad-summary">
          <div class="d-flex flex-wrap justify-content-between align-items-start gap-2">
            <div>
              <div class="fw-semibold">${escapeHtml(tipoLabel)}</div>
              <div class="small text-muted">${encabezadoEmpleadoLegacy}</div>
            </div>
            <span class="badge text-bg-primary">${escapeHtml(estadoActual)}</span>
          </div>
        </div>
        ${notasHtml}
        <div class="trazabilidad-timeline">${timelineHtml}</div>
      </div>
    `;
  };

  const renderTrazabilidadClaraHtml = function (trazabilidad, mensajeError = '') {
    if (mensajeError !== '') {
      return `
        <div class="alert alert-danger mb-0">${escapeHtml(mensajeError)}</div>
      `;
    }

    const data = trazabilidad && typeof trazabilidad === 'object' ? trazabilidad : {};
    const empleado = data.empleado && typeof data.empleado === 'object' ? data.empleado : {};
    const eventos = Array.isArray(data.eventos) ? data.eventos : [];
    const notas = Array.isArray(data.notas)
      ? data.notas.map(function (nota) { return String(nota || '').trim(); }).filter(Boolean)
      : [];

    const tipoLabel = String(data.tipo_label || data.tipo_codigo || 'Solicitud').trim();
    const empleadoNombre = String(empleado.nombre || '').trim();
    const empleadoDocumento = String(empleado.documento || '').trim();
    const estadoActual = String(data.estado_actual_label || data.estado_actual || 'N/A').trim();
    const encabezadoEmpleado = empleadoNombre !== ''
      ? `${escapeHtml(empleadoNombre)}${empleadoDocumento !== '' ? ` - ${escapeHtml(empleadoDocumento)}` : ''}`
      : (empleadoDocumento !== '' ? escapeHtml(empleadoDocumento) : 'Sin persona asociada');
    const primerEvento = eventos.length > 0 ? eventos[0] : null;
    const ultimoEvento = eventos.length > 0 ? eventos[eventos.length - 1] : null;

    const resolverClaseEstado = function (estado, esActual) {
      const codigo = String(estado || '').trim().toUpperCase();
      if (codigo === 'RECHAZADO') {
        return 'rechazado';
      }
      if (codigo === 'ANULADO') {
        return 'anulado';
      }
      if (esActual) {
        return 'actual';
      }
      if (codigo === 'APROBADO') {
        return 'aprobado';
      }
      if (codigo === 'JEFE_APROBADO') {
        return 'intermedio';
      }
      return 'base';
    };

    const resolverIconoEstado = function (estado) {
      const codigo = String(estado || '').trim().toUpperCase();
      if (codigo === 'RADICADO') {
        return 'far fa-file-alt';
      }
      if (codigo === 'JEFE_APROBADO') {
        return 'fas fa-user-check';
      }
      if (codigo === 'APROBADO') {
        return 'fas fa-check-circle';
      }
      if (codigo === 'RECHAZADO') {
        return 'fas fa-times-circle';
      }
      if (codigo === 'ANULADO') {
        return 'fas fa-ban';
      }
      return 'fas fa-circle';
    };

    const claseEstadoActual = resolverClaseEstado(data.estado_actual || '', true);
    const resumenMeta = [
      `Inicio: ${String(primerEvento?.fecha || 'Sin fecha registrada')}`,
      `Ultimo movimiento: ${String(ultimoEvento?.fecha || 'Sin fecha registrada')}`,
      `Eventos: ${String(eventos.length)}`
    ];

    const notasHtml = notas.length > 0
      ? `
        <div class="trazabilidad-notes">
          <div class="trazabilidad-notes-title">Aclaraciones del historial</div>
          ${notas.map(function (nota) {
            return `<div class="trazabilidad-note-item">${escapeHtml(nota)}</div>`;
          }).join('')}
        </div>
      `
      : '';

    const timelineHtml = eventos.length > 0
      ? eventos.map(function (evento) {
          const estadoLabel = String(evento.estado_label || evento.estado || 'Estado').trim();
          const estadoCodigo = String(evento.estado || '').trim().toUpperCase();
          const actor = String(evento.actor_nombre || evento.actor_documento || '').trim();
          const fecha = String(evento.fecha || '').trim();
          const detalle = String(evento.detalle || '').trim();
          const claseEstado = resolverClaseEstado(estadoCodigo, !!evento.es_estado_actual);
          const iconoEstado = resolverIconoEstado(estadoCodigo);
          const metaPartes = [
            `Fecha: ${fecha || 'Sin fecha registrada'}`,
            `Responsable: ${actor || 'Sin actor registrado'}`
          ];

          return `
            <div class="trazabilidad-item is-${claseEstado} ${evento.es_estado_actual ? 'is-current' : ''}">
              <div class="trazabilidad-marker">
                <i class="${iconoEstado}"></i>
              </div>
              <div class="trazabilidad-card">
                <div class="trazabilidad-card-head">
                  <h6 class="trazabilidad-step-title">${escapeHtml(estadoLabel)}</h6>
                  ${evento.es_estado_actual ? '<span class="trazabilidad-pill is-actual">Actual</span>' : ''}
                </div>
                <div class="trazabilidad-meta-line">
                  ${metaPartes.map(function (parte) {
                    return `<span>${escapeHtml(parte)}</span>`;
                  }).join('')}
                </div>
                ${detalle !== '' ? `
                  <div class="trazabilidad-detail">${escapeHtml(detalle)}</div>
                ` : ''}
              </div>
            </div>
          `;
        }).join('')
      : `
        <div class="alert alert-light border mb-0">
          No hay eventos de trazabilidad registrados para esta solicitud.
        </div>
      `;

    return `
      <div class="trazabilidad-shell">
        <div class="trazabilidad-summary">
          <div class="trazabilidad-summary-main">
            <h5 class="trazabilidad-title">${escapeHtml(tipoLabel)}</h5>
            <p class="trazabilidad-subtitle mb-0">${encabezadoEmpleado}</p>
            <div class="trazabilidad-summary-meta">
              ${resumenMeta.map(function (item) {
                return `<span>${escapeHtml(item)}</span>`;
              }).join('')}
            </div>
          </div>
          <div class="trazabilidad-summary-state">
            <span class="trazabilidad-summary-label">Estado actual</span>
            <span class="trazabilidad-pill is-${claseEstadoActual}">${escapeHtml(estadoActual)}</span>
          </div>
        </div>

        ${notasHtml}
        <div class="trazabilidad-timeline">${timelineHtml}</div>
      </div>
    `;
  };

  const abrirModalTrazabilidad = function (urlTrazabilidad) {
    const destino = String(urlTrazabilidad || '').trim();
    if (destino === '' || !globalModal || !globalModalTitle || !globalModalContent) {
      return;
    }

    aplicarModoGlobalModal('trazabilidad');
    globalModalTitle.textContent = 'Trazabilidad de la solicitud';
    globalModalContent.innerHTML = `
      <div class="d-flex flex-column align-items-center justify-content-center py-4">
        <i class="fas fa-spinner fa-spin fa-2x mb-2 text-primary"></i>
        <p class="mb-0 text-muted">Cargando trazabilidad...</p>
      </div>
    `;
    globalModal.show();

    window.fetch(destino, {
      headers: {
        'X-Requested-With': 'XMLHttpRequest',
        'Accept': 'application/json'
      },
      credentials: 'same-origin'
    })
      .then(function (response) {
        return response.json().then(function (data) {
          return { status: response.status, ok: response.ok, data: data };
        });
      })
      .then(function (result) {
        if (!result.ok || !result.data || result.data.ok !== true) {
          const msg = (result.data && result.data.message) ? result.data.message : 'No fue posible cargar la trazabilidad.';
          throw new Error(msg);
        }

        globalModalContent.innerHTML = renderTrazabilidadClaraHtml(result.data.data);
      })
      .catch(function (error) {
        const mensaje = (error && error.message) ? error.message : 'No fue posible cargar la trazabilidad.';
        globalModalContent.innerHTML = renderTrazabilidadClaraHtml({}, mensaje);
      });
  };

  let rejectForm = null;
  let cancelForm = null;
  const rejectModalElement = document.getElementById('modalRechazoNovedad');
  const cancelModalElement = document.getElementById('modalAnulacionNovedad');
  const rejectModal = window.bootstrap && rejectModalElement ? new window.bootstrap.Modal(rejectModalElement) : null;
  const cancelModal = window.bootstrap && cancelModalElement ? new window.bootstrap.Modal(cancelModalElement) : null;
  const rejectInput = document.getElementById('motivoRechazoNovedad');
  const cancelInput = document.getElementById('motivoAnulacionNovedad');
  const rejectError = document.getElementById('motivoRechazoNovedadError');
  const cancelError = document.getElementById('motivoAnulacionNovedadError');

  document.addEventListener('click', function (event) {
    const linkTrazabilidad = event.target.closest('.js-ver-trazabilidad-novedad');
    if (linkTrazabilidad) {
      event.preventDefault();
      abrirModalTrazabilidad(
        linkTrazabilidad.dataset.trazabilidadUrl || linkTrazabilidad.getAttribute('href') || ''
      );
      return;
    }

    const linkAdjuntos = event.target.closest('.js-ver-adjuntos-permiso');
    if (linkAdjuntos) {
      event.preventDefault();
      abrirModalAdjuntos(
        linkAdjuntos.dataset.adjuntosUrl || linkAdjuntos.getAttribute('href') || ''
      );
      return;
    }

    const rejectButton = event.target.closest('.js-abrir-modal-rechazo');
    if (rejectButton) {
      event.preventDefault();
      rejectForm = rejectButton.closest('form.js-rechazar-permiso-form');
      if (rejectInput) { rejectInput.value = ''; }
      if (rejectError) { rejectError.classList.add('d-none'); rejectError.textContent = ''; }
      if (rejectModal) { rejectModal.show(); }
      return;
    }

    const cancelButton = event.target.closest('.js-abrir-modal-anulacion');
    if (cancelButton) {
      event.preventDefault();
      cancelForm = cancelButton.closest('form.js-anular-permiso-form');
      if (cancelInput) { cancelInput.value = ''; }
      if (cancelError) { cancelError.classList.add('d-none'); cancelError.textContent = ''; }
      if (cancelModal) { cancelModal.show(); }
    }
  });

  const rejectConfirm = document.getElementById('confirmarRechazoNovedadBtn');
  if (rejectConfirm) {
    rejectConfirm.addEventListener('click', function () {
      const motivo = (rejectInput?.value || '').trim();
      if (motivo === '') {
        if (rejectError) { rejectError.textContent = 'Debes indicar el motivo del rechazo.'; rejectError.classList.remove('d-none'); }
        return;
      }
      if (!rejectForm) { return; }
      const hiddenInput = rejectForm.querySelector('input[name=\"motivo_rechazo\"]');
      if (hiddenInput) { hiddenInput.value = motivo; }
      const textoAccion = rejectForm.dataset.accionLoader || 'Rechazando solicitud';
      if (!marcarFormularioEnProceso(rejectForm, textoAccion)) {
        return;
      }
      rejectForm.submit();
    });
  }

  const cancelConfirm = document.getElementById('confirmarAnulacionNovedadBtn');
  if (cancelConfirm) {
    cancelConfirm.addEventListener('click', function () {
      const motivo = (cancelInput?.value || '').trim();
      if (motivo === '') {
        if (cancelError) { cancelError.textContent = 'Debes indicar el motivo de la anulación.'; cancelError.classList.remove('d-none'); }
        return;
      }
      if (!cancelForm) { return; }
      const hiddenInput = cancelForm.querySelector('input[name=\"motivo_anulacion\"]');
      if (hiddenInput) { hiddenInput.value = motivo; }
      const textoAccion = cancelForm.dataset.accionLoader || 'Anulando solicitud';
      if (!marcarFormularioEnProceso(cancelForm, textoAccion)) {
        return;
      }
      cancelForm.submit();
    });
  }

  document.addEventListener('submit', function (event) {
    const formulario = event.target;
    if (!(formulario instanceof HTMLFormElement)) {
      return;
    }

    const esFormularioAccion = formulario.matches('form.js-aprobar-jefe-permiso-form, form.js-aprobar-rrhh-permiso-form, form.js-rechazar-permiso-form, form.js-anular-permiso-form');
    if (!esFormularioAccion) {
      return;
    }

    const textoAccion = formulario.dataset.accionLoader
      || (formulario.classList.contains('js-rechazar-permiso-form')
        ? 'Rechazando solicitud'
        : (formulario.classList.contains('js-anular-permiso-form') ? 'Anulando solicitud' : 'Aprobando solicitud'));

    if (!marcarFormularioEnProceso(formulario, textoAccion)) {
      event.preventDefault();
    }
  });
});
</script>
@endPushOnce
