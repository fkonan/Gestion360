@extends('layouts.dashboard')

@section('title','Reportes')

@section('breadcrumb')
<x-breadcrumb :items="[
        ['name' => 'Inicio', 'url' => route('home')],
        ['name' => 'Reportes', 'url' => route('reportes.index')],
        ['name' => 'Pasajes']
    ]" />
@endsection

@section('content')
<br>
<div class="container-fluid p-0 border rounded sidebar-dark-primary tableContainer" style="min-height:150px">

  <x-sectionHeader
    titulo="Reportes de Pasajes"
    rutaVolver="{{ route('reportes.index') }}" />

  <div id="no-more-tables" class="table-responsive" style="padding:1.5em">
    <table
      id="reportesPasajes"
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
        {{-- Reporte fuera del reporteador --}}
        <x-reporteItem
          consecutivo="1"
          icono="fas fa-ticket-alt"
          area="Unidad pasajes"
          titulo="Impresión de tiquetes"
          descripcion="Visualiza y exporta el reporte de tiquetes impresos en un rango de fechas"
          formato="EXCEL"
          :onclick="'cargarModal(`' . route('reportes.tiquetes') . '`, `Impresión de tiquetes`, `#fechasReporteForm`, `modal-lg`)'" />

        {{-- Reporte fuera del reporteador --}}
        <x-reporteItem
          consecutivo="2"
          icono="fas fa-money-check-alt"
          area="Unidad pasajes"
          titulo="Esquema tarifario de pasajes"
          descripcion="Visualiza y exporta el detalle de tarifas aplicadas a los pasajes en diferentes rutas y fechas"
          formato="EXCEL"
          :onclick="'cargarModal(`' . route('esquemaTarifario.index') . '`, `Esquema tarifario pasajes`, `#esquemaTarifarioForm`, `modal-lg`)'" />

        @foreach ($reportes as $reporte)
        <x-reporteItem
          consecutivo="{{ $loop->iteration + 2 }}"
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
      '#reportesPasajes', {
        protegidas: ['']
      },
      'detalleReporte'
    );
  });
</script>
@endpushOnce
