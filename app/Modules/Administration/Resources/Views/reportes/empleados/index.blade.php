@extends('layouts.dashboard')

@section('title','Reportes')

@section('breadcrumb')
<x-breadcrumb :items="[
        ['name' => 'Inicio', 'url' => route('home')],
        ['name' => 'Reportes', 'url' => route('reportes.index')],
        ['name' => 'Empleados']
    ]" />
@endsection

@section('content')
<br>
<div class="container-fluid p-0 border rounded sidebar-dark-primary tableContainer" style="min-height:150px">

  <x-sectionHeader titulo="Reportes de empleados" rutaVolver="{{ route('reportes.index') }}" />

  <div id="no-more-tables" class="table-responsive" style="padding:1.5em">
    <table id="reportesCarga" class="table table-sm table-striped table-hover align-middle mb-0" data-toggle="table"
      data-locale="es-ES" data-search="true">

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
        <x-reporteItem consecutivo="1" area="RRHH" titulo="Firma politicas"
          descripcion="Reporte de los empleados que han firmado las politicas de la empresa" formato="EXCEL"
          onclick="cargarModal(`{{ route('empleados.firmaPoliticas') }}`, 'Reporte firma politicas','#firmaPoliticasForm','modal-lg')" />

        <x-reporteItem consecutivo="2" titulo="Conductores y empleados activos" area="RRHH"
          descripcion="Reporte de los conductores y empleados activos." formato="EXCEL" icono="fas fa-fingerprint"
          :onclick="'cargarModal(`' . route('reportes.formulario', ['id' => 18]) . '`, `Reporte de los conductores y empleados activos`, ``, `modal-lg`)'" />

        <x-reporteItem consecutivo="3" titulo="Conductores y empleados sin firma políticas" area="RRHH"
          descripcion="Reporte de los conductores y empleados activos que no tienen registro de firma para las políticas de la empresa." formato="EXCEL" icono="fas fa-fingerprint"
          :onclick="'cargarModal(`' . route('reportes.formulario', ['id' => 19]) . '`, `Reporte de los conductores y empleados sin firma de políticas`, ``, `modal-lg`)'" />
      </tbody>
    </table>
  </div>
</div>

@endsection

@pushOnce('script')
@vite(['resources/js/cargarModal.js'])
<script>
  function habilitarInputFiltro(checkElement) {
    const parametro = document.getElementById('parametro');
    const parametroLabel = parametro ? parametro.querySelector('label') : null;

    if (checkElement.id === 'filtroCedula') {
      parametro.style.display = '';
      parametroLabel.innerHTML = 'Ingrese la cédula';

    } else if (checkElement.id === 'filtroCodigo') {
      parametro.style.display = '';
      parametroLabel.innerHTML = 'Ingrese el código';

    } else if (checkElement.id === 'filtroTodos') {
      parametro.style.display = 'none';
    }
  }
</script>

@endpushOnce
