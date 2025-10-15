@extends('layouts.dashboard')

@section('title','Lista firmas')

@section('breadcrumb')
<x-breadcrumb :items="[
        ['name' => 'Inicio', 'url' => route('home')],
        ['name' => 'Reportes', 'url' => route('reportes.index')],
        ['name' => 'Empleados', 'url' => route('reportes.empleados')],
        ['name' => 'Firmas politicas'],
    ]" />
<br>
@endsection

@section('content')
<div class="container-fluid tableContainer p-0 border rounded sidebar-dark-primary" style="min-height:150px">

  <x-sectionHeader titulo="Resultado firmas polticas empleados" rutaVolver="{{ route('reportes.empleados') }}"
    excel="true" excelRoute="{{ route('firmaPoliticas.cargarData') }}"
    excelName="Firmas_Empleados_Politicas" />

  <div style="padding:1.5em">
    <table id="reporteFirmasPoliticas" class="table table-sm table-striped" data-page-size="25" data-toggle="table"
      data-locale="es-ES" data-search="true" data-pagination="true" data-detail-view="true"
      data-detail-formatter="detalleReporteFirmas" data-check-on-init="true"
      data-url="{{ route('firmaPoliticas.cargarData') }}">
      <thead class="table-primary">
        <tr class="bg-primary">
          <th class="text-nowrap" data-field="Nombre del Empleado" data-sortable="true">Nombre completo</th>
          <th class="text-nowrap" data-field="Documento" data-sortable="true">Identificación</th>
          <th class="text-nowrap" data-field="Código" data-sortable="true">Código</th>
          <th class="text-nowrap" data-field="Cargo" data-sortable="true">Cargo</th>
          <th class="text-nowrap" data-field="Nombre Política" data-sortable="true">Política firmada</th>
          <th class="text-nowrap" data-field="Fecha de Firma" data-sortable="true">Fecha de firma</th>
          <th class="text-nowrap" data-field="Correo Electrónico" data-sortable="true">Correo</th>
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
      '#reporteFirmasPoliticas', {
      protegidas: ['DocCon']
    },
      'detalleReporteFirmas', {
      'acciones': comprobantePDF
    }
    );
  });

  const rutaDescargar = "{{ route('firmas.descargar') }}";

  function comprobantePDF(value, row) {
    return `
      <a href="${rutaDescargar}?firma_id=${row.IdFirma}"
         class="btn btn-sm btn-danger"
         target="_blank">
         Ver PDF
      </a>
    `;
  }

</script>
@endpushOnce
