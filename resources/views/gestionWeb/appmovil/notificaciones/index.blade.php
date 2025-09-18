@extends('layouts.dashboard')

@section('title','Gestión appmovil')

@section('breadcrumb')
<x-breadcrumb :items="[
        ['name' => 'Inicio', 'url' => route('home')],
        ['name' => 'Gestión appmovil', 'url' => route('gestion-appmovil.index')],
        ['name' => 'Notificaciones'],
    ]" />
<br>
@endsection

@section('content')
<div class="container-fluid p-0 border rounded sidebar-dark-primary tableContainer" style="min-height:150px">

  <x-sectionHeader
    titulo="Notificaciones"
    rutaVolver="{{ route('gestion-appmovil.index') }}"
    crear="newpage"
    crearRoute="{{ route('notificaciones.create') }}"
    crearLabel="Registrar notificación" />

  <div class="table-responsive" style="padding:1.5em">
    <table
      id="notificacionesTable"
      class="table table-sm table-striped"
      data-page-size="25"
      data-toggle="table"
      data-locale="es-ES"
      data-search="true"
      data-pagination="true"
      data-detail-view="true"
      data-detail-formatter="detalleNotificacion"
      data-check-on-init="true"
      data-side-pagination="server"
      data-url="{{ route('notificaciones.cargarDatos') }}">
      <thead class="table-primary">
        <tr>
          <th class="text-nowrap" data-field="titulo" data-sortable="true" data-escape="true">Título</th>
          <th class="text-nowrap" data-field="usuarioCrea" data-sortable="true" data-escape="true">Usuario crea</th>
          <th class="text-nowrap" data-field="createdAt" data-sortable="true" data-escape="true">Fecha Creación</th>
          <th class="text-nowrap" data-field="programada" data-sortable="true" data-escape="true">Fecha programada</th>
          <th class="text-nowrap" data-field="estado" data-sortable="true" data-escape="true" data-formatter="estadoFormatter">Estado</th>
          <th class="text-nowrap" data-field="privacidad" data-sortable="true" data-escape="true">Privacidad</th>
          <th class="text-nowrap" data-field="proceso" data-sortable="true" data-escape="true"> Proceso</th>
          <th class="text-nowrap" data-field="destino" data-sortable="true" data-escape="true">Destinatarios</th>
          <th class="text-nowrap" data-field="bodyPush" data-escape="true">Resumen Push</th>
          <th class="text-nowrap" data-field="bodyCompleto" data-escape="true">Descripción</th>
          <th data-field="acciones" data-formatter="accionesFormatter">Acciones</th>
        </tr>
      </thead>
    </table>
  </div>
</div>
@endsection

@pushOnce('script')
<script>
  document.addEventListener("DOMContentLoaded", () => {
    initTablaBootstrapTable(
      '#notificacionesTable', {
        protegidas: ['titulo']
      },
      'detalleNotificacion', {
        'estado': estadoFormatter,
        'acciones': accionesFormatter
      }
    );
  });

  function accionesFormatter(index, row) {
    let ruta = "{{ route('notificaciones.edit', ['id' => ':id']) }}"
    let rutaNotificion = ruta.replace(':id', row.id);

    return `
            <div class="col-md-12">
                <a class="text-decoration-none" href="${rutaNotificion}"
                    style="cursor: pointer;"
                    title="Editar notificación">
                    <img src="{{ asset('img/edit.png') }}" alt="Editar notificación" style="width: 32px; height: 32px;">
                </a>
            </div>
        `;
  }

  function estadoFormatter(value, row) {
    const checked = row.estado === 'activo' ? 'checked' : '';
    const url = "{{ route('notificaciones.cambiarEstado', ['id' => ':id']) }}".replace(':id', row.id);
    return `
            <div class="form-check form-switch d-flex justify-content-center">
                <input
                    onchange="actualizarEstado('${url}')"
                    class="form-check-input estado-switch"
                    type="checkbox"
                    role="switch"
                    data-id="${row.id}"
                    ${checked}>
            </div>
        `;
  }
</script>
@endpushOnce
