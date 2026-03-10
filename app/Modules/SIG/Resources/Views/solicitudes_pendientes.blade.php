@extends('layouts.dashboard')

@section('title', 'Solicitudes pendientes')

@section('breadcrumb')
<x-breadcrumb :items="[
        ['name' => 'Inicio', 'url' => route('home')],
        ['name' => 'SIG', 'url' => route('home')],
        ['name' => 'Solicitudes pendientes']
    ]" />
<br>
@endsection

@section('content')
<div class="container-fluid p-0 border rounded sidebar-dark-primary tableContainer" style="min-height:150px;">

  <x-sectionHeader
    titulo="Solicitudes pendientes"
    rutaVolver="javascript:history.back()" />

  <div id="no-more-tables" class="table-responsive" style="padding:1em 1.25em">
    <table
      id="tablaPendientes"
      class="table table-sm table-striped"
      data-page-size="25"
      data-toggle="table"
      data-locale="es-ES"
      data-search="true"
      data-detail-formatter="detalleSolicitudesPendientes"
      data-pagination="true"
      data-data='@json($versiones)'>
      <thead class="table-primary">
        <tr>
          <th data-field="codigo" data-sortable="true" class="text-nowrap">C&oacute;digo</th>
          <th data-field="nombre" data-sortable="true" data-formatter="capitalizarFormatter">Nombre</th>
          <th data-field="tipo_solicitud" data-sortable="true" data-formatter="tipoSolicitudFormatter" class="text-nowrap">Tipo</th>
          <th data-field="version" data-sortable="true" data-align="center">Emisi&oacute;n</th>
          <th data-field="comentario_revision" data-formatter="observacionFormatter">Comentario</th>
          <th data-field="elaboro" data-formatter="capitalizarFormatter">Elabor&oacute;</th>
          <th data-field="reviso" data-formatter="capitalizarFormatter">Revis&oacute;</th>
          <th data-field="aprueba" data-formatter="capitalizarFormatter">Aprueba</th>
          <th data-field="fecha_elaboracion" data-sortable="true" data-formatter="fechaFormatter">Fecha elaboraci&oacute;n</th>
          <th data-field="estado" data-formatter="estadoFormatter" class="text-center">Estado</th>
          <th data-field="acciones" data-formatter="accionesPendientes" class="text-center">Acciones</th>
        </tr>
      </thead>
    </table>
  </div>
</div>

@endsection

@pushOnce('script')
<script>
  document.addEventListener('DOMContentLoaded', () => {
    const escapeHtml = (value) => String(value)
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;')
      .replace(/'/g, '&#39;');

    const capitalizarFormatter = (value) => {
      if (!value) return '';
      const texto = String(value).toLowerCase();
      return texto.charAt(0).toUpperCase() + texto.slice(1);
    };

    const tipoSolicitudFormatter = (value) => {
      if (!value) return '';
      return String(value) === 'NUEVO_DOCUMENTO' ? 'Nuevo documento' : 'Emisi\u00f3n';
    };

    const fechaFormatter = (value) => {
      if (!value) return '';
      const date = new Date(value);
      if (isNaN(date.getTime())) return value;
      const year = date.getFullYear();
      const month = String(date.getMonth() + 1).padStart(2, '0');
      const day = String(date.getDate()).padStart(2, '0');
      return `${year}-${month}-${day}`;
    };

    const estadoFormatter = (value) => {
      const texto = value || 'EN_REVISION';
      let color = 'secondary';
      if (texto === 'EN_REVISION') color = 'warning';
      else if (texto === 'RECHAZADO') color = 'danger';
      else if (texto === 'APROBADO') color = 'success';
      else if (texto === 'DEVUELTO') color = 'info';
      const textoMostrar = texto === 'EN_REVISION' ? 'EN_REVISI\u00d3N' : texto;
      return `<span class="badge bg-${color}">${textoMostrar}</span>`;
    };

    const observacionFormatter = (value) => {
      if (!value) return '';
      const texto = String(value);
      const limite = 80;
      const resumen = texto.length > limite ? `${texto.slice(0, limite)}...` : texto;
      return `<span title="${escapeHtml(texto)}">${escapeHtml(resumen)}</span>`;
    };

    const rutaRevision = "{{ route('mapa-procesos.emisiones.pendientes.revision', ['id' => ':id']) }}";

    const accionesPendientes = (value, row) => {
      const url = rutaRevision.replace(':id', row.id);

      return `
        <div class="d-flex justify-content-center">
          <a href="${url}"
            class="btn p-0 sig-action-btn"
            data-title="Revisar solicitud pendiente"
            aria-label="Revisar solicitud">
            <img src="{{ asset('img/verDetalle.png') }}" alt="Revisar solicitud" style="width: 26px; height: 26px;">
          </a>
        </div>
      `;
    };

    initTablaBootstrapTable(
      '#tablaPendientes',
      { protegidas: ['codigo', 'nombre'], ocultas: ['acciones'], forzarDetalle: true },
      'detalleSolicitudesPendientes',
      {
        acciones: accionesPendientes,
        tipo_solicitud: tipoSolicitudFormatter,
        nombre: capitalizarFormatter,
        comentario_revision: observacionFormatter,
        elaboro: capitalizarFormatter,
        reviso: capitalizarFormatter,
        aprueba: capitalizarFormatter,
        fecha_elaboracion: fechaFormatter,
        estado: estadoFormatter
      }
    );

    window.capitalizarFormatter = capitalizarFormatter;
    window.tipoSolicitudFormatter = tipoSolicitudFormatter;
    window.fechaFormatter = fechaFormatter;
    window.estadoFormatter = estadoFormatter;
    window.accionesPendientes = accionesPendientes;
    window.observacionFormatter = observacionFormatter;
  });
</script>
@endpushOnce
