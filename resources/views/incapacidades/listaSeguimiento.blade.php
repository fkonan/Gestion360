@extends('layouts.dashboard')

@section('title','Seguimiento incapacidades')

@section('breadcrumb')
<x-breadcrumb :items="[
        ['name' => 'Inicio', 'url' => route('home')],
        ['name' => 'Gestión Empleado', 'url' => route('gestion-incapacidades.index')],
        ['name' => 'Seguimiento Incapacidades'],
    ]" />
<br>
@endsection

@section('content')
<div class="container-fluid p-0 border sidebar-dark-primary rounded" style="min-height:150px;">

  <x-sectionHeader
    titulo="Seguimiento incapacidades"
    rutaVolver="{{ route('gestion-incapacidades.index') }}" />

  <div style="padding:1.5em">
    <table
      id="incapacidadesDataTable"
      class="table table-sm table-striped"
      data-page-size="25"
      data-toggle="table"
      data-locale="es-ES"
      data-search="true"
      data-pagination="true"
      data-sort-name="IncFecIni"
      data-sort-order="desc"
      data-detail-view="true"
      data-detail-formatter="detalleIncapacidad"
      data-check-on-init="true"
      data-url="{{ route('gestion-empleado.seguimiento.cargarDatos') }}">
      <thead class="table-primary">
        <tr>
          <th class="text-nowrap" data-field="IncPerNom">Nombre</th>
          <th data-field="PerNumDoc">Documento</th>
          <th class="text-nowrap" data-field="causaDes">Causa Incapacidad</th>
          <th class="text-nowrap" data-field="epsNombre">EPS</th>
          <th class="text-nowrap" data-field="arlNombre">ARL</th>
          <th class="text-center" data-field="IncFecIni" data-sortable="true">Fecha Inicio</th>
          <th class="text-center" data-field="IncFecFin" data-sortable="true">Fecha Fin</th>
          <th class="text-nowrap" data-field="DiagnosticoDes">Diagnóstico</th>
          <th class="text-center" data-field="diasIncapacidad" data-formatter="diasIncapacidadFormatter">Días Incapacidad</th>
          <th data-field="IncapacidadEstado">Estado</th>
          <th class="text-center" data-field="IncFecReg">Fecha Registro</th>
          <th class="text-center" data-field="IncHorReg">Hora Registro</th>
          <th data-field="IdIncapacidad" data-formatter="accionesFormatter">Acciones</th>
        </tr>
      </thead>
    </table>
  </div>
</div>
@endsection

@pushOnce('script')
@vite(['resources/js/cargarModal.js'])
<script>
  function diasIncapacidadFormatter(value, row) {
    const fechaInicio = new Date(row.IncFecIni);
    const fechaFin = new Date(row.IncFecFin);
    const dias = Math.ceil((fechaFin - fechaInicio) / (1000 * 60 * 60 * 24)) + 1;
    return dias;
  };

  function accionesFormatter(value, row) {
    const urlAdjuntos = "{{ route('gestion-empleado.seguimiento.adjuntos', ['id' => ':id']) }}".replace(':id', value);
    const urlDatos = "{{ route('gestion-empleado.incapacidades.edit', ['id' => ':id']) }}".replace(':id', value);
    const urlSeguimientoDetalle = "{{ route('gestion-empleado.seguimiento.detalle', ['id' => ':id']) }}".replace(':id', value);

    return `
                <div class="d-flex flex-wrap gap-3 justify-content-center">
                    <a class="text-decoration-none"  title="Ver adjuntos"
                        style="cursor: pointer;"
                        onclick="cargarModal('${urlAdjuntos}', 'Documentos Incapacidad', '', 'modal-lg')">
                        <img src="https://autogestion.copetran.com.co/gestion_2/aFrame/library/bower_components/Ionicons/png/512/carpetas.png"
                            alt="Ver Adjuntos" style="width: 32px; height: 32px;">
                    </a>
                    <a class="text-decoration-none"  title="Editar incapacidad"
                        style="cursor: pointer;"
                        onclick="cargarModal('${urlDatos}', 'Revisión datos incapacidad', '#formIncapacidad', 'modal-xl')">
                        <img src="{{ asset('img/edit.png') }}" alt="Editar Incapacidad" style="width: 32px; height: 32px;">
                    </a>
                    <a class="text-decoration-none"  title="Seguimiento"
                        style="cursor: pointer;"
                        href="${urlSeguimientoDetalle}">
                        <img src="https://autogestion.copetran.com.co/gestion_2/aFrame/library/bower_components/Ionicons/png/512/seguimiento.png"
                            alt="Seguimiento" style="width: 32px; height: 32px;">
                    </a>
                </div>
            `;
  };

  function habilitarInputs() {
    document.querySelectorAll('#formIncapacidad input, #formIncapacidad select').forEach(element => {
      if (element.id === 'IdIncapacidad') {
        return;
      }
      element.disabled = false;
    });
  }

  document.addEventListener("DOMContentLoaded", () => {
    initTablaBootstrapTable(
      '#incapacidadesDataTable', {
        protegidas: ['PerNumDoc']
      },
      'detalleIncapacidad', {
        'IdIncapacidad': accionesFormatter,
        'diasIncapacidad': diasIncapacidadFormatter
      }
    );
  });
</script>
@endpushOnce
