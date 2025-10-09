@extends('layouts.dashboard')

@section('title','Reportes')

@section('breadcrumb')
<x-breadcrumb :items="[
        ['name' => 'Inicio', 'url' => route('home')],
        ['name' => 'Reportes', 'url' => route('reportes.index')],
        ['name' => 'Conductores']
    ]" />
@endsection

@section('content')
<br>
<div class="container-fluid p-0 border rounded sidebar-dark-primary tableContainer" style="min-height:150px">

  <x-sectionHeader
    titulo="Reportes de Conductores"
    rutaVolver="{{ route('reportes.index') }}" />

  <div id="no-more-tables" class="table-responsive" style="padding:1.5em">
    <table
      id="reportesConductores"
      class="table table-sm table-striped table-hover align-middle mb-0"
      data-toggle="table"
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
      </thead>
      <tbody id="lista-reportes">
        <x-reporteItem
          consecutivo="1"
          titulo="Descanso conductores"
          area="RRHH"
          descripcion="Reporte de conductores que han registrado ingresos y salidas para periodos de descanso"
          formato="EXCEL"
          icono="fas fa-fingerprint"
          :onclick="'cargarModal(`' . route('reportes.formulario', ['id' => 9]) . '`, `Reporte salidas a descanso`, ``, `modal-lg`)'" />

        <x-reporteItem
          consecutivo="2"
          titulo="Novedades preoperacionales COP"
          area="Unidad pasajes"
          descripcion="Reporte de conductores con bloqueos preoperacionales levantados por el COP"
          formato="EXCEL"
          icono="fas fa-hourglass-half"
          onclick="cargarModal(`{{ route('conductor.preoperacional.reporte') }}`, 'Reporte preoperacionales reportados COP','#firmaPreOpeForm','modal-md')" />
      </tbody>
    </table>
  </div>
</div>
@endsection

@pushOnce('script')
@vite(['resources/js/cargarModal.js'])
<script>
  //para el reporte descanso conductores
  function habilitarOpciones() {
    const opciones = document.getElementById('opciones');
    const opcionEvento = document.getElementById('opcionEvento');
    const opcionEventoLabel = document.getElementById('opcionEventoLabel');
    const evento = document.getElementById('evento');

    opciones.style.display = 'block';
    opcionEvento.style.display = 'block';

    if (evento.value == '50') {
      opcionEventoLabel.innerHTML = 'Fecha de salida a descanso';
    } else {
      opcionEventoLabel.innerHTML = 'Fecha de reingreso de descanso';
    }
  }

  //para el reporte ingreso y salidas conductores
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
