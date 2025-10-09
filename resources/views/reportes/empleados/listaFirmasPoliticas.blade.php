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
    excelName="Firmas_Empleados_Politica_Preingreso" />

  <div style="padding:1.5em">
    <table id="reporteFirmasPoliticas" class="table table-sm table-striped" data-page-size="25" data-toggle="table"
      data-locale="es-ES" data-search="true" data-pagination="true" data-detail-view="true"
      data-detail-formatter="detalleReporteFirmas" data-check-on-init="true"
      data-url="{{ route('firmaPoliticas.cargarData') }}">
      <thead class="table-primary">
        <tr class="bg-primary">
          <th data-field="NomCon" data-sortable="true" class="text-nowrap">Nombre completo</th>
          <th data-field="DocCon" data-sortable="true">Identificación</th>
          <th data-field="CodCon" data-sortable="true">Codigo</th>
          <th data-field="Cargo" data-sortable="true" class="text-nowrap">Cargo</th>
          <th data-field="nombre_politica" data-sortable="true" class="text-nowrap">Politica firmada</th>
          <th data-field="FirFecReg" data-sortable="true">Fecha de firma</th>
          <th data-field="FirHorReg" data-sortable="true">Hora de firma</th>
          <th data-field="Correo" data-sortable="true" class="text-nowrap">Correo</th>
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
      protegidas: ['NombreCompleto']
    },
      'detalleReporteFirmas', {
      'acciones': comprobantePDF
    }
    );
  });

  function comprobantePDF(value, row) {
    const url = `{{ route('firmaNormas.comprobantePDF', ':id') }}`.replace(':id', row.Id);
    return `
            <a href="${url}" target="_blank" title="Descargar comprobante">
                <img src="{{ asset('img/descargarPDF.png') }}"
                     alt="Descargar PDF"
                     style="width:35px;height:35px;">
            </a>
        `;
  }
</script>
@endpushOnce
