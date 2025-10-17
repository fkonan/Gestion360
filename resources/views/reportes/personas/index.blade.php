@extends('layouts.dashboard')

@section('title','Reportes')

@section('breadcrumb')
<x-breadcrumb :items="[
        ['name' => 'Inicio', 'url' => route('home')],
        ['name' => 'Reportes', 'url' => route('reportes.index')],
        ['name' => 'Personas']
    ]" />
@endsection

@section('content')
<br>
<div class="container-fluid p-0 border rounded sidebar-dark-primary tableContainer" style="min-height:150px">

  <x-sectionHeader
    titulo="Reportes de personas"
    rutaVolver="{{ route('reportes.index') }}" />

  <div id="no-more-tables" class="table-responsive" style="padding:1.5em">
    <table
      id="reportesPersonas"
      class="table table-sm table-striped table-hover align-middle mb-0"
      data-toggle="table"
      data-detail-view="true"
      data-detail-formatter="detalleReporte"
      data-locale="es-ES"
      data-search="true">

      <thead class="table-primary m-0 p-0 border-bottom">
        <tr>
          <th>Número</th>
          <th>Reporte</th>
          <th>Área</th>
          <th>Descripción</th>
          <th>Formato de salida</th>
          <th>Acción</th>
        </tr>
      </thead>
      <tbody id="lista-reportes">
       <!--  <x-reporteItem
          consecutivo="1"
          titulo="Novedades preoperacionales COP"
          area="RRHH"
          descripcion="Reporte de conductores con bloqueos preoperacionales levantados por el COP"
          formato="EXCEL"
          onclick="cargarModal(`{{ route('conductor.preoperacional.reporte') }}`, 'Reporte preoperacionales reportados COP','#firmaPreOpeForm','modal-md')" /> -->

        <x-reporteItem
          consecutivo="1"
          titulo="Firma politicas"
          area="RRHH"
          descripcion="Reporte de los empleados que han firmado las politicas de la empresa"
          formato="EXCEL"
          onclick="cargarModal(`{{ route('empleados.firmaPoliticas') }}`, 'Reporte firma politicas','#firmaPoliticasForm','modal-lg')" />

        @foreach ($reportesPersonas as $reporte)
          <x-reporteItem
            consecutivo="{{ $loop->iteration + 1 }}"
            area="{{ $reporte->area }}"
            titulo="{{ $reporte->nombre }}"
            descripcion="{{ $reporte->descripcion }}"
            formato="{{ $reporte->formato_salida }}"
            :onclick="'cargarModal(`' . route('reportes.formulario', ['id' => $reporte->id]) . '`, `' . $reporte->nombre . '`, ``, `modal-lg`)'" />
        @endforeach
      </tbody>
    </table>
  </div>
</div>

@endsection

@pushOnce('script')
@vite(['resources/js/cargarModal.js'])
<script>
  document.addEventListener("DOMContentLoaded", () => {
    initTablaBootstrapTable(
      '#reportesPersonas', {
        protegidas: ['']
      },
      'detalleReporte'
    );
  });
</script>
@endpushOnce
