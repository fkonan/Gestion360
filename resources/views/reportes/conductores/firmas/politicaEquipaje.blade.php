@extends('layouts.dashboard')

@section('title','Lista firmas')

@section('breadcrumb')
<x-breadcrumb :items="[
        ['name' => 'Inicio', 'url' => route('home')],
        ['name' => 'Reportes', 'url' => route('reportes.index')],
        ['name' => 'Conductores', 'url' => route('reportes.conductores')],
        ['name' => 'Firmas equipaje'],
    ]" />
<br>
@endsection

@section('content')
<div class="container-fluid tableContainer p-0 border rounded sidebar-dark-primary" style="min-height:150px">

  <x-sectionHeader
    titulo="Resultado firma equipaje"
    rutaVolver="{{ route('reportes.conductores') }}"
    excel="true"
    excelRoute="{{ route('firmaEquipaje.cargarData') }}{{ request()->getQueryString() ? '?' . request()->getQueryString() : '' }}"
    excelName="Firmas_Conductores_Politica_Equipaje" />

  <div style="padding:1.5em">
    <table
      id="reporteFirmaEquipaje"
      class="table table-sm table-striped"
      data-page-size="25"
      data-toggle="table"
      data-locale="es-ES"
      data-search="true"
      data-pagination="true"
      data-detail-view="true"
      data-detail-formatter="detalleReporteFirmaEquipaje"
      data-check-on-init="true"
      data-url="{{ route('firmaEquipaje.cargarData') }}{{ request()->getQueryString() ? '?' . request()->getQueryString() : '' }}">
      <thead class="table-primary">
        <tr class="bg-primary">
          <th data-field="nombre_completo" data-sortable="true">Nombre Completo</th>
          <th data-field="identificacion" data-sortable="true">Identificación</th>
          <th data-field="codigo" data-sortable="true">Codigo</th>
          <th class="text-center" data-field="fecha_registro" data-sortable="true">Fecha firma</th>
          <th class="text-center" data-field="hora_registro" data-sortable="true">Hora firma</th>
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
      '#reporteFirmaEquipaje', {
        protegidas: ['NomCon']
      },
      'detalleReporteFirmaEquipaje'
    );
  });
</script>
@endpushOnce
