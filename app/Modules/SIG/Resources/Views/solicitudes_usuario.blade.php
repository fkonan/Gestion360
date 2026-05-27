@php
  use Illuminate\Support\Carbon;

  $solicitudes = collect($versiones ?? []);
  $totalSolicitudes = $solicitudes->count();
  $solicitudesDevueltas = $solicitudes->where('estado', 'DEVUELTO')->count();
  $solicitudesRevision = $solicitudes->where('estado', 'EN_REVISION')->count();
  $solicitudesResueltas = $solicitudes->whereIn('estado', ['APROBADO', 'RECHAZADO'])->count();
  $ultimaSolicitud = $solicitudes->pluck('fecha_elaboracion')->filter()->sortDesc()->first();
  $ultimaSolicitudLabel = $ultimaSolicitud
      ? Carbon::parse($ultimaSolicitud)->translatedFormat('d M Y')
      : 'Sin movimientos recientes';
@endphp

@extends('layouts.dashboard')

@section('title', 'Mis solicitudes')

@section('breadcrumb')
<x-breadcrumb :items="[
        ['name' => 'Inicio', 'url' => route('home')],
        ['name' => 'SIG', 'url' => route('mapa-procesos.index')],
        ['name' => 'Mis solicitudes']
    ]" />
<br>
@endsection

@section('content')
<div class="container-fluid p-0 border rounded sidebar-dark-primary tableContainer sig-user-requests" style="min-height:150px;">

  <x-sectionHeader
    titulo="Mis solicitudes"
    :rutaVolver="route('mapa-procesos.index')" />

  <section class="sig-user-requests__intro">
    <div class="sig-user-requests__summary">
      <article class="sig-user-requests__stat">
        <span class="sig-user-requests__stat-label">Total</span>
        <strong class="sig-user-requests__stat-value">{{ $totalSolicitudes }}</strong>
      </article>
      <article class="sig-user-requests__stat">
        <span class="sig-user-requests__stat-label">En revisión</span>
        <strong class="sig-user-requests__stat-value">{{ $solicitudesRevision }}</strong>
      </article>
      <article class="sig-user-requests__stat">
        <span class="sig-user-requests__stat-label">Devueltas</span>
        <strong class="sig-user-requests__stat-value">{{ $solicitudesDevueltas }}</strong>
      </article>
      <article class="sig-user-requests__stat">
        <span class="sig-user-requests__stat-label">Resueltas</span>
        <strong class="sig-user-requests__stat-value">{{ $solicitudesResueltas }}</strong>
      </article>
    </div>

    <aside class="sig-user-requests__aside">
      <span class="sig-user-requests__aside-label">Último movimiento</span>
      <strong class="sig-user-requests__aside-value">{{ $ultimaSolicitudLabel }}</strong>
    </aside>
  </section>

  <section class="sig-user-requests__table-section">
    <header class="sig-user-requests__table-head">
      <p class="sig-user-requests__table-text mb-0">Busca por código o nombre y abre el detalle para ver el seguimiento completo.</p>
      <span class="sig-user-requests__table-badge">{{ $totalSolicitudes }} registros</span>
    </header>

    <div id="no-more-tables" class="table-responsive px-3 pb-3">
      <table
        id="tablaDevueltas"
        class="table table-sm table-striped table-hover align-middle mb-0"
        data-page-size="12"
        data-toggle="table"
        data-locale="es-ES"
        data-search="true"
        data-pagination="true"
        data-detail-view="true"
        data-detail-formatter="detalleSolicitudesUsuario"
        data-data='@json($versiones)'
        data-puede-reenviar="{{ Auth::user()?->can(\App\Constants\Permisos::SIG_MAPA_PROCESOS_CREAR_EMISION) ? '1' : '0' }}">
        <thead class="table-primary">
          <tr>
            <th data-field="codigo" data-sortable="true" data-formatter="codigoFormatter" class="text-nowrap">C&oacute;digo</th>
            <th data-field="nombre" data-sortable="true" data-formatter="solicitudFormatter">Solicitud</th>
            <th data-field="version" data-align="center" data-formatter="emisionFormatter" class="text-center">Emisi&oacute;n</th>
            <th data-field="fecha_elaboracion" data-formatter="fechaSolicitudFormatter" data-sortable="true" class="text-nowrap">Fecha</th>
            <th data-field="estado" data-formatter="estadoFormatter" class="text-center">Estado</th>
            <th data-field="acciones" data-formatter="accionesSolicitudes" class="text-center">Acciones</th>
          </tr>
        </thead>
      </table>
    </div>
  </section>
</div>

@endsection

@pushOnce('css')
<style>
  .sig-user-requests__intro {
    display: grid;
    grid-template-columns: minmax(0, 1fr) minmax(220px, 280px);
    align-items: start;
    gap: 0.9rem;
    padding: 1rem 1.25rem 0;
  }

  .sig-user-requests__summary {
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    align-items: start;
    gap: 0.75rem;
  }

  .sig-user-requests__stat,
  .sig-user-requests__aside,
  .sig-user-requests__table-section,
  .sig-request-detail__hero,
  .sig-request-detail__panel,
  .sig-request-detail__metric {
    border: 1px solid var(--app-border);
    border-radius: 1rem;
    background-color: var(--app-surface);
    box-shadow: var(--app-shadow-soft);
  }

  .sig-user-requests__stat {
    display: flex;
    flex-direction: column;
    justify-content: center;
    gap: 0.15rem;
    padding: 0.8rem 0.95rem;
  }

  .sig-user-requests__stat-label,
  .sig-user-requests__aside-label,
  .sig-request-detail__eyebrow,
  .sig-request-detail__metric-label,
  .sig-request-detail__row-label,
  .sig-request-detail__timeline-label,
  .sig-request-detail__person-label {
    font-size: 0.7rem;
    font-weight: 700;
    letter-spacing: 0.05em;
    text-transform: uppercase;
    color: var(--bs-secondary-color);
  }

  .sig-user-requests__stat-value {
    font-size: 1.45rem;
    line-height: 1;
    color: var(--bs-heading-color);
  }

  .sig-user-requests__aside {
    display: flex;
    flex-direction: column;
    justify-content: center;
    align-self: start;
    gap: 0.2rem;
    padding: 0.85rem 1rem;
  }

  .sig-user-requests__aside-value {
    font-size: 1rem;
    line-height: 1.3;
    color: var(--bs-heading-color);
    font-weight: 700;
  }

  .sig-user-requests__table-section {
    margin: 0.9rem 1.25rem 1.25rem;
    overflow: hidden;
  }

  .sig-user-requests__table-head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 1rem;
    padding: 0.9rem 1rem 0.8rem;
    border-bottom: 1px solid var(--app-border);
  }

  .sig-user-requests__table-text {
    color: var(--bs-secondary-color);
    line-height: 1.4;
  }

  .sig-user-requests__table-badge {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-height: 2rem;
    padding: 0.4rem 0.8rem;
    border-radius: 999px;
    background-color: var(--app-surface-alt);
    border: 1px solid var(--app-border);
    color: var(--bs-body-color);
    font-size: 0.82rem;
    font-weight: 600;
    white-space: nowrap;
  }

  .sig-user-requests .bootstrap-table .fixed-table-toolbar {
    padding: 0.9rem 1rem 0;
  }

  .sig-user-requests .bootstrap-table .search input {
    min-width: 260px;
    border-color: var(--app-border);
    box-shadow: none;
  }

  .sig-user-requests .table thead th {
    font-size: 0.74rem;
    font-weight: 700;
    letter-spacing: 0.04em;
    text-transform: uppercase;
    vertical-align: middle;
  }

  .sig-user-requests .table tbody td {
    vertical-align: middle;
    padding-top: 0.8rem;
    padding-bottom: 0.8rem;
  }

  .sig-request-code {
    font-weight: 700;
    color: var(--bs-heading-color);
    white-space: nowrap;
  }

  .sig-request-cell {
    display: flex;
    flex-direction: column;
    gap: 0.38rem;
    min-width: 0;
  }

  .sig-request-cell__title {
    display: block;
    font-weight: 700;
    color: var(--bs-heading-color);
    line-height: 1.35;
  }

  .sig-request-cell__meta {
    display: flex;
    flex-wrap: wrap;
    gap: 0.4rem;
  }

  .sig-request-cell__meta span,
  .sig-request-emission {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-height: 1.8rem;
    padding: 0.28rem 0.6rem;
    border-radius: 999px;
    border: 1px solid var(--app-border);
    background-color: var(--app-surface-alt);
    color: var(--bs-body-color);
    font-size: 0.78rem;
    font-weight: 600;
  }

  .sig-request-date {
    display: flex;
    flex-direction: column;
    gap: 0.15rem;
    min-width: 120px;
  }

  .sig-request-date strong {
    color: var(--bs-heading-color);
    font-size: 0.93rem;
  }

  .sig-request-date span {
    color: var(--bs-secondary-color);
    font-size: 0.78rem;
  }

  .sig-request-status {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-height: 1.95rem;
    padding: 0.3rem 0.75rem;
    border-radius: 999px;
    font-size: 0.78rem;
    font-weight: 700;
    border: 1px solid transparent;
    white-space: nowrap;
  }

  .sig-request-status--info {
    background-color: rgba(var(--bs-info-rgb), 0.15);
    border-color: rgba(var(--bs-info-rgb), 0.24);
    color: var(--bs-info-text-emphasis);
  }

  .sig-request-status--danger {
    background-color: rgba(var(--bs-danger-rgb), 0.12);
    border-color: rgba(var(--bs-danger-rgb), 0.2);
    color: var(--bs-danger-text-emphasis);
  }

  .sig-request-status--success {
    background-color: rgba(var(--bs-success-rgb), 0.14);
    border-color: rgba(var(--bs-success-rgb), 0.22);
    color: var(--bs-success-text-emphasis);
  }

  .sig-request-status--warning {
    background-color: rgba(var(--bs-warning-rgb), 0.18);
    border-color: rgba(var(--bs-warning-rgb), 0.25);
    color: var(--bs-warning-text-emphasis);
  }

  .sig-request-status--secondary {
    background-color: rgba(var(--bs-secondary-rgb), 0.12);
    border-color: rgba(var(--bs-secondary-rgb), 0.2);
    color: var(--bs-secondary);
  }

  .sig-request-actions {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.55rem;
  }

  .sig-request-detail {
    display: flex;
    flex-direction: column;
    gap: 1rem;
  }

  .sig-request-detail__hero,
  .sig-request-detail__panel {
    padding: 1.05rem 1.15rem;
  }

  .sig-request-detail__hero {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 1rem;
    background:
      linear-gradient(135deg, rgba(var(--bs-primary-rgb), 0.08), transparent 62%),
      var(--app-surface);
  }

  .sig-request-detail__title {
    margin: 0.25rem 0 0;
    font-size: 1.2rem;
    line-height: 1.3;
    color: var(--bs-heading-color);
    font-weight: 700;
  }

  .sig-request-detail__subline {
    display: flex;
    flex-wrap: wrap;
    gap: 0.45rem 0.85rem;
    margin-top: 0.7rem;
    color: var(--bs-secondary-color);
    font-size: 0.9rem;
  }

  .sig-request-detail__metrics {
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: 0.85rem;
  }

  .sig-request-detail__metric {
    display: flex;
    flex-direction: column;
    gap: 0.45rem;
    padding: 0.95rem 1rem;
    background-color: var(--app-surface-alt);
  }

  .sig-request-detail__metric-value {
    color: var(--bs-heading-color);
    font-size: 1rem;
    font-weight: 700;
    line-height: 1.4;
    word-break: break-word;
  }

  .sig-request-detail__layout {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 1rem;
  }

  .sig-request-detail__panel-title {
    margin: 0 0 0.95rem;
    color: var(--bs-heading-color);
    font-size: 0.98rem;
    font-weight: 700;
  }

  .sig-request-detail__panel--wide {
    width: 100%;
  }

  .sig-request-detail__rows {
    display: grid;
    gap: 0;
  }

  .sig-request-detail__row {
    display: grid;
    grid-template-columns: 135px minmax(0, 1fr);
    gap: 0.9rem;
    padding: 0.72rem 0;
    border-top: 1px solid var(--app-border);
  }

  .sig-request-detail__row:first-child {
    border-top: none;
    padding-top: 0;
  }

  .sig-request-detail__row:last-child {
    padding-bottom: 0;
  }

  .sig-request-detail__row-value {
    color: var(--bs-body-color);
    font-weight: 600;
    line-height: 1.5;
    word-break: break-word;
  }

  .sig-request-detail__timeline {
    display: grid;
    gap: 0.9rem;
  }

  .sig-request-detail__timeline-item {
    position: relative;
    padding-left: 1rem;
    border-left: 2px solid rgba(var(--bs-primary-rgb), 0.22);
  }

  .sig-request-detail__timeline-item::before {
    content: '';
    position: absolute;
    left: -6px;
    top: 0.3rem;
    width: 10px;
    height: 10px;
    border-radius: 999px;
    background-color: rgba(var(--bs-primary-rgb), 0.7);
    box-shadow: 0 0 0 4px rgba(var(--bs-primary-rgb), 0.12);
  }

  .sig-request-detail__timeline-value {
    display: block;
    color: var(--bs-heading-color);
    font-weight: 700;
    line-height: 1.35;
    margin-top: 0.2rem;
  }

  .sig-request-detail__timeline-meta {
    display: block;
    color: var(--bs-secondary-color);
    font-size: 0.87rem;
    margin-top: 0.18rem;
    line-height: 1.45;
  }

  .sig-request-detail__people {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 0.85rem;
  }

  .sig-request-detail__person {
    display: flex;
    flex-direction: column;
    gap: 0.35rem;
    padding: 0.9rem 0.95rem;
    border: 1px solid var(--app-border);
    border-radius: 0.85rem;
    background-color: var(--app-surface-alt);
  }

  .sig-request-detail__person-value {
    color: var(--bs-body-color);
    font-weight: 600;
    line-height: 1.5;
    word-break: break-word;
  }

  .sig-request-detail__note {
    min-height: 170px;
    padding: 0.95rem 1rem;
    border: 1px solid var(--app-border);
    border-radius: 0.85rem;
    background-color: var(--app-surface-alt);
    color: var(--bs-body-color);
    line-height: 1.7;
    white-space: pre-wrap;
    overflow-wrap: anywhere;
    word-break: break-word;
  }

  .sig-request-detail__alert {
    padding: 0.9rem 1rem;
    border-left: 4px solid rgba(var(--bs-info-rgb), 0.5);
    border-radius: 0.85rem;
    background-color: rgba(var(--bs-info-rgb), 0.08);
    color: var(--bs-body-color);
  }

  body.dark-mode .sig-user-requests td .sig-request-cell,
  body.dark-mode .sig-user-requests td .sig-request-cell__meta,
  body.dark-mode .sig-user-requests td .sig-request-date,
  body.dark-mode .sig-user-requests td .sig-request-actions {
    background-color: transparent !important;
  }

  @media (max-width: 1199.98px) {
    .sig-user-requests__intro {
      grid-template-columns: 1fr;
    }

    .sig-request-detail__metrics,
    .sig-request-detail__layout,
    .sig-request-detail__people {
      grid-template-columns: repeat(2, minmax(0, 1fr));
    }
  }

  @media (max-width: 767.98px) {
    .sig-user-requests__summary,
    .sig-request-detail__metrics,
    .sig-request-detail__layout,
    .sig-request-detail__people {
      grid-template-columns: 1fr;
    }

    .sig-user-requests__table-head,
    .sig-request-detail__hero {
      flex-direction: column;
      align-items: stretch;
    }

    .sig-request-detail__row {
      grid-template-columns: 1fr;
      gap: 0.35rem;
    }

    .sig-user-requests .bootstrap-table .search input {
      min-width: 100%;
    }
  }
</style>
@endpushOnce

@pushOnce('script')
@vite(['resources/js/cargarModal.js'])
<script>
  document.addEventListener('DOMContentLoaded', () => {
    const tabla = document.getElementById('tablaDevueltas');
    const puedeReenviar = tabla?.dataset.puedeReenviar === '1';
    const rutaNueva = "{{ route('mapa-procesos.documento.emision.nueva', ['id' => ':id']) }}";
    const solicitudesData = JSON.parse(tabla?.getAttribute('data-data') || '[]');

    const escapeHtml = (value) => String(value)
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;')
      .replace(/'/g, '&#39;');

    const capitalizarTexto = (value) => {
      if (!value) return '';
      const texto = String(value).toLowerCase();
      return texto.charAt(0).toUpperCase() + texto.slice(1);
    };

    const fechaOrdenableFormatter = (value) => {
      if (!value) return '';
      const date = new Date(value);
      if (isNaN(date.getTime())) return value;
      const year = date.getFullYear();
      const month = String(date.getMonth() + 1).padStart(2, '0');
      const day = String(date.getDate()).padStart(2, '0');
      return `${year}-${month}-${day}`;
    };

    const fechaHumana = (value) => {
      if (!value) return '';
      const date = new Date(value);
      if (isNaN(date.getTime())) return value;
      return new Intl.DateTimeFormat('es-CO', {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
      }).format(date);
    };

    const obtenerTipoSolicitud = (row) => (
      row?.tipo_solicitud === 'NUEVO_DOCUMENTO' ? 'Documento nuevo' : 'Emisión'
    );

    const obtenerEtiquetaEstado = (value) => {
      const texto = String(value || 'DEVUELTO');
      return texto === 'EN_REVISION' ? 'En revisión' : capitalizarTexto(texto.replaceAll('_', ' '));
    };

    const obtenerClaseEstado = (value) => {
      const texto = String(value || 'DEVUELTO');
      if (texto === 'DEVUELTO') return 'info';
      if (texto === 'RECHAZADO') return 'danger';
      if (texto === 'APROBADO') return 'success';
      if (texto === 'EN_REVISION') return 'warning';
      return 'secondary';
    };

    const codigoFormatter = (value) => (
      `<span class="sig-request-code">${escapeHtml(value || 'Sin código')}</span>`
    );

    const solicitudFormatter = (value, row) => {
      const nombre = capitalizarTexto(value || 'Solicitud sin nombre');
      const tipoSolicitud = obtenerTipoSolicitud(row);
      const paginas = row?.paginas
        ? `${row.paginas} ${Number(row.paginas) === 1 ? 'página' : 'páginas'}`
        : 'Páginas no registradas';

      return `
        <div class="sig-request-cell">
          <span class="sig-request-cell__title">${escapeHtml(nombre)}</span>
          <div class="sig-request-cell__meta">
            <span>${escapeHtml(tipoSolicitud)}</span>
            <span>${escapeHtml(paginas)}</span>
          </div>
        </div>
      `;
    };

    const emisionFormatter = (value) => {
      if (!value) {
        return '<span class="sig-request-emission">Sin emisión</span>';
      }

      return `<span class="sig-request-emission">Emisión ${escapeHtml(String(value))}</span>`;
    };

    const fechaSolicitudFormatter = (value) => {
      const fecha = fechaHumana(value) || 'Sin fecha';
      return `
        <div class="sig-request-date">
          <strong>${escapeHtml(fecha)}</strong>
          <span>Fecha de solicitud</span>
        </div>
      `;
    };

    const estadoFormatter = (value) => {
      const color = obtenerClaseEstado(value);
      return `<span class="sig-request-status sig-request-status--${color}">${escapeHtml(obtenerEtiquetaEstado(value))}</span>`;
    };

    const renderMetric = (label, value) => `
      <article class="sig-request-detail__metric">
        <span class="sig-request-detail__metric-label">${escapeHtml(label)}</span>
        <strong class="sig-request-detail__metric-value">${escapeHtml(String(value))}</strong>
      </article>
    `;

    const renderInfoRow = (label, value) => `
      <div class="sig-request-detail__row">
        <span class="sig-request-detail__row-label">${escapeHtml(label)}</span>
        <strong class="sig-request-detail__row-value">${escapeHtml(String(value))}</strong>
      </div>
    `;

    const renderTimelineItem = (label, fecha, responsable) => `
      <article class="sig-request-detail__timeline-item">
        <span class="sig-request-detail__timeline-label">${escapeHtml(label)}</span>
        <strong class="sig-request-detail__timeline-value">${escapeHtml(fecha)}</strong>
        <span class="sig-request-detail__timeline-meta">${escapeHtml(responsable)}</span>
      </article>
    `;

    const construirSeguimientoEmision = (row) => {
      const fechaCarga = fechaHumana(row.fecha_elaboracion) || 'Sin fecha';
      const fechaGestion = fechaHumana(row.fecha_revision) || 'Pendiente';
      const fechaAprobacion = fechaHumana(row.fecha_aprobacion) || fechaGestion;
      const revisoAsignado = row.reviso || 'No asignado';
      const apruebaAsignado = row.aprueba || 'No asignado';
      const eventos = [
        renderTimelineItem('Cargada', fechaCarga, 'Subida por el solicitante'),
      ];

      if (row.estado === 'EN_REVISION') {
        eventos.push(
          renderTimelineItem(
            'En revisión',
            fechaCarga,
            revisoAsignado !== 'No asignado'
              ? `Pendiente de validación por: ${revisoAsignado}`
              : 'Pendiente de validación'
          )
        );

        return eventos.join('');
      }

      eventos.push(
        renderTimelineItem(
          'En revisión',
          fechaCarga,
          revisoAsignado !== 'No asignado'
            ? `Asignada a: ${revisoAsignado}`
            : 'Ingresó al flujo de revisión'
        )
      );

      if (row.estado === 'DEVUELTO') {
        eventos.push(
          renderTimelineItem(
            'Devuelta',
            fechaGestion,
            revisoAsignado !== 'No asignado'
              ? `Gestionada por: ${revisoAsignado}`
              : 'Devuelta con observaciones'
          )
        );
      } else if (row.estado === 'RECHAZADO') {
        eventos.push(
          renderTimelineItem(
            'Rechazada',
            fechaGestion,
            revisoAsignado !== 'No asignado'
              ? `Gestionada por: ${revisoAsignado}`
              : 'Rechazada en revisión'
          )
        );
      } else if (row.estado === 'APROBADO' || row.estado === 'HISTORICO') {
        eventos.push(
          renderTimelineItem(
            'Aprobada',
            fechaAprobacion,
            apruebaAsignado !== 'No asignado'
              ? `Aprobada por: ${apruebaAsignado}`
              : 'Aprobación registrada'
          )
        );
      } else {
        eventos.push(
          renderTimelineItem(
            obtenerEtiquetaEstado(row.estado),
            fechaGestion,
            'Estado registrado para esta emisión'
          )
        );
      }

      return eventos.join('');
    };

    const renderPersonCard = (label, value) => `
      <article class="sig-request-detail__person">
        <span class="sig-request-detail__person-label">${escapeHtml(label)}</span>
        <strong class="sig-request-detail__person-value">${escapeHtml(String(value))}</strong>
      </article>
    `;

    const mostrarDetalleSolicitud = (id) => {
      const row = solicitudesData.find(item => String(item.id) === String(id));
      if (!row) {
        return;
      }

      const codigo = row.codigo || 'Sin código asignado';
      const nombre = capitalizarTexto(row.nombre || 'Solicitud sin nombre');
      const tipoSolicitud = obtenerTipoSolicitud(row);
      const estadoLabel = obtenerEtiquetaEstado(row.estado);
      const estadoClase = obtenerClaseEstado(row.estado);
      const emision = row.version ? `Emisión ${row.version}` : 'Sin emisión asignada';
      const paginas = row.paginas ? String(row.paginas) : 'No registradas';
      const fechaElaboracion = fechaHumana(row.fecha_elaboracion) || 'Sin fecha';
      const fechaRevision = fechaHumana(row.fecha_revision) || 'Pendiente';
      const fechaAprobacion = fechaHumana(row.fecha_aprobacion) || 'Pendiente';
      const archivo = row.archivo_url || 'Sin archivo asociado';
      const elaboro = row.elaboro || 'No asignado';
      const reviso = row.reviso || 'No asignado';
      const aprueba = row.aprueba || 'No asignado';
      const observacion = row.comentario_revision
        ? escapeHtml(String(row.comentario_revision))
        : 'Sin observación registrada.';

      const alertaHtml = row.estado === 'DEVUELTO' && puedeReenviar
        ? `
          <div class="sig-request-detail__alert">
            Puedes reenviar esta solicitud desde el botón de reenviar disponible en la tabla.
          </div>
        `
        : '';

      const html = `
        <div class="sig-request-detail text-start">
          <section class="sig-request-detail__hero">
            <div>
              <span class="sig-request-detail__eyebrow">Solicitud SIG</span>
              <h5 class="sig-request-detail__title">${escapeHtml(nombre)}</h5>
              <div class="sig-request-detail__subline">
                <span>${escapeHtml(codigo)}</span>
                <span>Solicitud #${escapeHtml(String(row.id))}</span>
                <span>Documento #${escapeHtml(String(row.documento_id || 'N/D'))}</span>
              </div>
            </div>
            <span class="sig-request-status sig-request-status--${estadoClase}">${escapeHtml(estadoLabel)}</span>
          </section>

          ${alertaHtml}

          <section class="sig-request-detail__metrics">
            ${renderMetric('Tipo de solicitud', tipoSolicitud)}
            ${renderMetric('Emisión', emision)}
            ${renderMetric('Páginas', paginas)}
            ${renderMetric('Fecha de solicitud', fechaElaboracion)}
          </section>

          <section class="sig-request-detail__layout">
            <article class="sig-request-detail__panel">
              <h6 class="sig-request-detail__panel-title">Documento y archivo</h6>
              <div class="sig-request-detail__rows">
                ${renderInfoRow('Código', codigo)}
                ${renderInfoRow('Archivo', archivo)}
                ${renderInfoRow('Tipo', tipoSolicitud)}
                ${renderInfoRow('Documento', `#${row.documento_id || 'N/D'}`)}
              </div>
            </article>

            <article class="sig-request-detail__panel">
              <h6 class="sig-request-detail__panel-title">Seguimiento de esta emisión</h6>
              <div class="sig-request-detail__timeline">
                ${construirSeguimientoEmision(row)}
              </div>
            </article>
          </section>

          <section class="sig-request-detail__panel sig-request-detail__panel--wide">
            <h6 class="sig-request-detail__panel-title">Responsables</h6>
            <div class="sig-request-detail__people">
              ${renderPersonCard('Elaboró', elaboro)}
              ${renderPersonCard('Revisó', reviso)}
              ${renderPersonCard('Aprueba', aprueba)}
            </div>
          </section>

          <section class="sig-request-detail__panel sig-request-detail__panel--wide">
            <h6 class="sig-request-detail__panel-title">Observación</h6>
            <div class="sig-request-detail__note">${observacion}</div>
          </section>
        </div>
      `;

      const modalEl = document.getElementById('globalModal');
      const modalTitle = document.getElementById('globalModalTitle');
      const modalContent = document.getElementById('globalModalContent');
      if (!modalEl || !modalTitle || !modalContent || !window.bootstrap?.Modal) {
        return;
      }

      modalTitle.textContent = 'Detalle de solicitud';
      modalContent.innerHTML = html;

      const dialogEl = modalEl.querySelector('.modal-dialog');
      if (dialogEl) {
        dialogEl.classList.remove('modal-lg', 'modal-sm');
        dialogEl.classList.add('modal-xl', 'modal-dialog-scrollable');
      }

      const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
      modal.show();

      const resetDialog = () => {
        if (dialogEl) {
          dialogEl.classList.remove('modal-lg', 'modal-sm', 'modal-dialog-scrollable');
          dialogEl.classList.add('modal-xl');
        }
        modalEl.removeEventListener('hidden.bs.modal', resetDialog);
      };
      modalEl.addEventListener('hidden.bs.modal', resetDialog);
    };

    const accionesSolicitudes = (value, row) => {
      const url = `${rutaNueva.replace(':id', row.documento_id)}?version_id=${row.id}`;
      const botonReenviar = puedeReenviar && row.estado === 'DEVUELTO'
        ? `
          <button type="button"
            class="btn btn-link p-0 sig-action-btn"
            data-title="Reenviar solicitud"
            aria-label="Reenviar solicitud"
            onclick="cargarModal('${url}', 'Reenviar solicitud', '#formNuevaEmision', 'modal-lg')">
            <img src="{{ asset('img/nuevaEmision.png') }}" alt="Reenviar" style="width: 32px; height: 32px;">
          </button>
        `
        : '';

      return `
        <div class="sig-request-actions">
          <button type="button"
            class="btn btn-link p-0 sig-action-btn"
            data-title="Ver detalle"
            aria-label="Ver detalle"
            onclick="mostrarDetalleSolicitud(${row.id})">
            <img src="{{ asset('img/verDetalle.png') }}" alt="Ver detalle" style="width: 26px; height: 26px;">
          </button>
          ${botonReenviar}
        </div>
      `;
    };

    initTablaBootstrapTable(
      '#tablaDevueltas',
      { protegidas: ['codigo', 'nombre', 'estado', 'acciones'] },
      'detalleSolicitudesUsuario',
      {
        codigo: codigoFormatter,
        nombre: solicitudFormatter,
        version: emisionFormatter,
        estado: estadoFormatter,
        fecha_elaboracion: fechaOrdenableFormatter,
        acciones: accionesSolicitudes
      }
    );

    window.codigoFormatter = codigoFormatter;
    window.solicitudFormatter = solicitudFormatter;
    window.emisionFormatter = emisionFormatter;
    window.fechaSolicitudFormatter = fechaSolicitudFormatter;
    window.estadoFormatter = estadoFormatter;
    window.accionesSolicitudes = accionesSolicitudes;
    window.mostrarDetalleSolicitud = mostrarDetalleSolicitud;
  });
</script>
@endpushOnce
