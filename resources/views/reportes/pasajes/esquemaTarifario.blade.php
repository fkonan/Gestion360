@extends('layouts.dashboard')

@section('title','Esquema tarifario')

@section('breadcrumb')
<x-breadcrumb :items="[
        ['name' => 'Inicio', 'url' => route('home')],
        ['name' => 'Reportes', 'url' => route('reportes.index')],
        ['name' => 'Pasajes', 'url' => route('reportes.pasajes')],
        ['name' => 'Esquema tarifario'],
    ]" />
<br>
@endsection

@section('content')
<div class="container-fluid tableContainer p-0 border sidebar-dark-primary rounded" style="min-height:150px;">

  <x-sectionHeader
    titulo="Esquema tarifario"
    rutaVolver="{{ route('reportes.pasajes') }}"
    excel="true"
    excelRoute="{{ route('esquemaTarifario.cargarData') }}"
    excelName="Esquema_tarifario" />

  <div id="no-more-tables" class="table-responsive" style="padding:1.5em">
    <table
      id="esquemaTarifarioTable"
      class="table table-sm table-striped"
      data-page-size="25"
      data-toggle="table"
      data-locale="es-ES"
      data-search="true"
      data-detail-view="true"
      data-detail-formatter="detalleTarifario"
      data-pagination="true"
      data-url="{{ route('esquemaTarifario.cargarData') }}"
      data-check-on-init="true">
      <thead class="table-primary">
        <tr>
          <th data-field="origen">Origen</th>
          <th data-field="destino">Destino</th>
          <th data-field="servicio">Servicio</th>
          <th class="text-center" data-field="precio" data-sortable="true" data-formatter="precioFormateo">Valor tarifa</th>
          <th class="text-center" data-field="fechaInicial" data-sortable="true">Fecha inicial esquema</th>
          <th class="text-center" data-field="fechaFinal" data-sortable="true">Fecha final esquema</th>
          <th data-field="estado" data-formatter="estadoActual">Estado</th>
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
      '#esquemaTarifarioTable', {
        protegidas: ['origen']
      },
      'detalleTarifario', {
        'precio': precioFormateo,
        'estado': estadoActual
      }
    );
  });

  function estadoActual(value, row, index) {
    return value == 0 ? 'VENCIDO' : 'VIGENTE';
  }

  function precioFormateo(value, row, index) {
    return parseInt(value);
  }
</script>
@endpushOnce
