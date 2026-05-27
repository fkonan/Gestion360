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
  $forzarNoAprobadas = !empty($forzarNoAprobadas);
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
            <th data-field="fecha_inicio">Fecha inicio</th>
            <th data-field="fecha_fin">Fecha fin</th>
            <th data-field="estado">Estado</th>
            <th data-field="acciones" class="text-center">Acciones</th>
          </tr>
        </thead>
        <tbody>
          @forelse($novedades as $novedad)
          @php
            $tipoCodigo = strtoupper(trim((string) ($novedad->tipo_codigo ?? '')));
            $detalle = trim((string) ($novedad->detalle_descripcion ?? ''));
            $radicadoPorDocumento = trim((string) ($novedad->radicado_por_documento ?? $novedad->documento_radica ?? ''));
            $radicadoPorNombre = trim((string) ($novedad->radicado_por_nombre ?? ''));
            $detalleJornadaLabel = match(trim((string) ($novedad->jornada ?? ''))) {
              '1' => 'Jornada 1 (manana)',
              '2' => 'Jornada 2 (tarde)',
              '1,2' => 'Ambas jornadas',
              'MANANA', 'MAÑANA' => 'Manana (Jornada 1)',
              'TARDE' => 'Tarde (Jornada 2)',
              'AMBAS' => 'Ambas jornadas',
              default => trim((string) ($novedad->jornada ?? '')),
            };
            $detalleHtml = collect([
              '<div><strong>Tipo:</strong> '.e((string) ($novedad->tipo_label ?? $tipoCodigo)).'</div>',
              '<div><strong>Estado:</strong> '.e((string) ($estadosDisponibles[$novedad->estado] ?? $novedad->estado)).'</div>',
              '<div><strong>Empleado:</strong> '.e((string) ($novedad->persona_nombre ?? 'Sin nombre')).' ('.e((string) ($novedad->documento_persona ?? $novedad->id_persona)).')</div>',
              '<div><strong>Radicado por:</strong> '.e($radicadoPorNombre !== '' ? $radicadoPorNombre : ($radicadoPorDocumento !== '' ? $radicadoPorDocumento : 'N/A')).($radicadoPorNombre !== '' && $radicadoPorDocumento !== '' ? ' ('.e($radicadoPorDocumento).')' : '').'</div>',
              '<div><strong>Fecha inicio:</strong> '.e($novedad->fecha_inicio?->format('d/m/Y h:i A') ?? 'N/A').'</div>',
              '<div><strong>Fecha fin:</strong> '.e($novedad->fecha_fin?->format('d/m/Y h:i A') ?? 'N/A').'</div>',
              $detalle !== '' ? '<div><strong>Detalle:</strong> '.e($detalle).'</div>' : null,
              $tipoCodigo === 'PERMISO' ? '<div><strong>Motivo:</strong> '.e((string) ($novedad->motivo ?? 'N/A')).'</div>' : null,
              $tipoCodigo === 'PERMISO' && trim((string) ($novedad->otro_motivo ?? '')) !== '' ? '<div><strong>Otro motivo:</strong> '.e((string) $novedad->otro_motivo).'</div>' : null,
              $tipoCodigo === 'PERMISO_PERMANENTE' && $detalleJornadaLabel !== '' ? '<div><strong>Jornada:</strong> '.e($detalleJornadaLabel).'</div>' : null,
              $tipoCodigo === 'PERMISO_PERMANENTE' ? '<div><strong>Horario fijo:</strong> '.((int) ($novedad->horario_fijo ?? 0) === 1 ? 'SI' : 'NO').'</div>' : null,
              $tipoCodigo === 'PERMISO_PERMANENTE' && trim((string) ($novedad->hora_salida_j1 ?? '')) !== '' ? '<div><strong>Horario J1:</strong> '.e((string) $novedad->hora_salida_j1).' - '.e((string) ($novedad->hora_ingreso_j1 ?? '')).'</div>' : null,
              $tipoCodigo === 'PERMISO_PERMANENTE' && trim((string) ($novedad->hora_salida_j2 ?? '')) !== '' ? '<div><strong>Horario J2:</strong> '.e((string) $novedad->hora_salida_j2).' - '.e((string) ($novedad->hora_ingreso_j2 ?? '')).'</div>' : null,
              $tipoCodigo === 'INCAPACIDAD' && trim((string) ($novedad->causa ?? '')) !== '' ? '<div><strong>Causa:</strong> '.e((string) $novedad->causa).'</div>' : null,
              $tipoCodigo === 'INCAPACIDAD' && trim((string) ($novedad->diagnostico_codigo ?? '')) !== '' ? '<div><strong>Diagnóstico:</strong> '.e((string) $novedad->diagnostico_codigo).' - '.e((string) ($novedad->diagnostico_descripcion ?? '')).'</div>' : null,
              $tipoCodigo === 'INCAPACIDAD' && trim((string) ($novedad->eps_nombre ?? '')) !== '' ? '<div><strong>EPS:</strong> '.e((string) $novedad->eps_nombre).'</div>' : null,
              $tipoCodigo === 'INCAPACIDAD' && trim((string) ($novedad->arl_nombre ?? '')) !== '' ? '<div><strong>ARL:</strong> '.e((string) $novedad->arl_nombre).'</div>' : null,
              '<div><strong>Adjuntos:</strong> '.((int) ($novedad->adjuntos_count ?? 0)).'</div>',
            ])->filter()->implode('');
            $puedeRechazarActual = $modoVista === 'jefe'
              ? !empty($novedad->puede_rechazar_jefe)
              : !empty($novedad->puede_rechazar_rrhh);
            $esIncapacidadGestionRrhh = $modoVista === 'rrhh' && $tipoCodigo === 'INCAPACIDAD';
          @endphp
          <tr>
            <td class="text-nowrap">{{ $novedad->tipo_label ?? $tipoCodigo }}</td>
            <td class="text-nowrap">{{ $novedad->documento_persona ?? $novedad->id_persona }}</td>
            <td class="text-nowrap">{{ $novedad->persona_nombre ?? 'Sin nombre' }}</td>
            <td class="text-nowrap">
              {{ $radicadoPorNombre !== '' ? $radicadoPorNombre : ($radicadoPorDocumento !== '' ? $radicadoPorDocumento : 'N/A') }}
              @if($radicadoPorNombre !== '' && $radicadoPorDocumento !== '')
              <div class="small text-muted">{{ $radicadoPorDocumento }}</div>
              @endif
            </td>
            <td>
              @if($detalle !== '')
              {{ $detalle }}
              @elseif($tipoCodigo === 'INCAPACIDAD')
              {{ trim((string) ($novedad->tipo_incapacidad ?? '')) !== '' ? $novedad->tipo_incapacidad : 'N/A' }}
              @else
              N/A
              @endif
            </td>
            <td class="text-nowrap">
              {{ $novedad->fecha_inicio?->format('d/m/Y h:i A') ?? 'N/A' }}
            </td>
            <td class="text-nowrap">
              {{ $novedad->fecha_fin?->format('d/m/Y h:i A') ?? 'N/A' }}
            </td>
            <td class="text-nowrap">{{ $estadosDisponibles[$novedad->estado] ?? $novedad->estado }}</td>
            <td class="text-nowrap text-center">
              <div class="permiso-actions-group">
                @if($tipoCodigo === 'PERMISO')
                <a
                  class="permiso-action-trigger d-inline-flex align-items-center justify-content-center p-0 border-0 bg-transparent text-decoration-none"
                  href="{{ route('gestionRRHH.permisos.pdf', ['idNovedad' => $novedad->id_novedad]) }}"
                  target="_blank"
                  title="Ver PDF"
                  data-bs-toggle="tooltip">
                  <img src="{{ asset('img/descargarPDF.png') }}" alt="PDF" class="permiso-action-icon">
                </a>
                @endif

                @unless($esIncapacidadGestionRrhh)
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

                <button
                  type="button"
                  class="js-ver-detalle-solicitud permiso-action-trigger d-inline-flex align-items-center justify-content-center p-0 border-0 bg-transparent"
                  title="Ver detalle solicitud"
                  data-bs-toggle="tooltip"
                  data-detalle-html="{{ $detalleHtml }}">
                  <img src="{{ asset('img/edit.png') }}" alt="Detalle" class="permiso-action-icon">
                </button>

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

                @if(in_array($modoVista, ['jefe', 'rrhh'], true) && $puedeRechazarActual && !$esIncapacidadGestionRrhh)
                <form
                  method="POST"
                  class="js-rechazar-permiso-form d-inline"
                  data-accion-loader="Rechazando solicitud"
                  action="{{ $tipoCodigo === 'INCAPACIDAD'
                    ? route('gestionRRHH.permisos.incapacidades.rechazar', ['idNovedad' => $novedad->id_novedad])
                    : route('gestionRRHH.permisos.rechazar', ['idNovedad' => $novedad->id_novedad]) }}">
                  @csrf
                  @if($tipoCodigo !== 'INCAPACIDAD')
                  <input type="hidden" name="nivel" value="{{ $modoVista === 'jefe' ? 'jefe' : 'rrhh' }}">
                  @endif
                  <input type="hidden" name="motivo_rechazo" value="">
                  <button type="button" class="js-abrir-modal-rechazo permiso-action-trigger d-inline-flex align-items-center justify-content-center p-0 border-0 bg-transparent" title="Rechazar" data-bs-toggle="tooltip">
                    <img src="{{ asset('img/rechazar.png') }}" alt="Rechazar" class="permiso-action-icon">
                  </button>
                </form>
                @endif

                @if($modoVista === 'rrhh' && !empty($novedad->puede_aprobar_rrhh) && !$esIncapacidadGestionRrhh)
                <form
                  method="POST"
                  class="js-aprobar-rrhh-permiso-form d-inline"
                  data-accion-loader="Aprobando solicitud"
                  action="{{ $tipoCodigo === 'INCAPACIDAD'
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
            <td colspan="9" class="text-center py-3">
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

  if (typeof initTablaBootstrapTable === 'function') {
    const columnasOcultasFijas = (modoVistaActual === 'jefe' || modoVistaActual === 'rrhh' || modoVistaActual === 'mis' || forzarAprobadasActual)
      ? ['acciones', 'detalle']
      : ['acciones'];

    initTablaBootstrapTable(
      '#novedadesCentralTable',
      {
        protegidas: ['tipo', 'documento', 'empleado', 'estado'],
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

  const escapeHtml = function (value) {
    return String(value ?? '')
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;')
      .replace(/'/g, '&#039;');
  };

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
  const globalModal = (window.bootstrap && globalModalElement)
    ? window.bootstrap.Modal.getOrCreateInstance(globalModalElement)
    : null;
  const pdfIconUrl = @json(asset('img/descargarPDF.png'));

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
    const botonDetalle = event.target.closest('.js-ver-detalle-solicitud');
    if (botonDetalle) {
      event.preventDefault();
      const detalleHtml = String(botonDetalle.dataset.detalleHtml || '').trim();
      if (window.Swal && typeof window.Swal.fire === 'function') {
        window.Swal.fire({
          icon: 'info',
          title: 'Detalle de solicitud',
          html: detalleHtml !== '' ? `<div class="text-start">${detalleHtml}</div>` : 'Sin información detallada disponible.',
          customClass: {
            popup: 'swalAlert'
          }
        });
      } else {
        window.alert('Detalle de solicitud disponible en la tabla expandida.');
      }
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
