@extends('layouts.dashboard')

@section('title','Lista actualización de datos')

@section('breadcrumb')
<x-breadcrumb :items="[
        ['name' => 'Inicio', 'url' => route('home')],
        ['name' => 'Reportes', 'url' => route('reportes.index')],
        ['name' => 'Personas', 'url' => route('reportes.area', ['area' => 'personas'])],
        ['name' => 'Actualización de datos'],
    ]" />
<br>
@endsection

@section('content')
<div class="container-fluid tableContainer p-0 border rounded sidebar-dark-primary" style="min-height:150px">

  <x-sectionHeader
    titulo="Reporte actualización de datos empleados"
    rutaVolver="{{ route('reportes.area', ['area' => 'personas']) }}"
    excel="true"
    excelRoute="{{ route('actDatos.cargarData') }}"
    excelName="Actualizacion_datos" />

  <div style="padding:1.5em">
    <table id="reporteActDatos" class="table table-sm table-striped" data-page-size="25" data-toggle="table"
      data-locale="es-ES" data-search="true" data-pagination="true" data-detail-view="true"
      data-detail-formatter="detalleReporteAct" data-check-on-init="true"
      data-url="{{ route('actDatos.cargarData') }}">
      <thead class="table-primary">
        <tr class="bg-primary">
          <th class="text-nowrap" data-field="nombre" data-sortable="true">Nombre completo</th>
          <th class="text-nowrap" data-field="documento" data-sortable="true">Documento</th>
          <th class="text-nowrap" data-field="contrato" data-sortable="true">Fecha contrato</th>
          <th class="text-nowrap" data-field="cargo" data-sortable="true">Cargo</th>
          <th class="text-nowrap" data-field="correo" data-sortable="true">Correo</th>
          <th class="text-nowrap" data-field="telefono" data-sortable="true">Telefono</th>
          <th class="text-nowrap" data-field="direccion" data-sortable="true">Dirección</th>
          <th class="text-nowrap" data-field="municipio_firma" data-sortable="true">Municipio de firma</th>
          <th class="text-nowrap" data-field="departamento_firma" data-sortable="true">Departamento de firma</th>
          <th class="text-nowrap" data-field="fecha_firma" data-sortable="true">Fecha de registro</th>
          <th class="text-nowrap" data-field="hora_firma" data-sortable="true">Hora de registro</th>
          <th class="text-nowrap" data-field="ip_firma" data-sortable="true">Firma ip</th>
          <th class="text-nowrap" data-field="acciones" data-formatter="comprobantePDF" class="text-center">Acciones</th>
        </tr>
      </thead>
    </table>
  </div>
</div>
@endsection

@pushOnce('script')
@vite(['resources/js/cargarModal.js'])
<script>
  document.addEventListener("DOMContentLoaded", () => {
    initTablaBootstrapTable(
      '#reporteActDatos', {
      protegidas: ['DocNumPer']
    },
      'detalleReporteAct',{
      'acciones': comprobantePDF
    }
    );
  });

  const rutaDescargar = "{{ route('decargar.pdf.actualizacionDatos') }}";

  function comprobantePDF(value, row) {
    return `
      <a href="${rutaDescargar}?firma_id=${row.id}"
         class="btn btn-sm btn-danger"
         target="_blank">
         Ver PDF
      </a>
    `;
  }


</script>
@endpushOnce
