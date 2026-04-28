@extends('layouts.dashboard')

@section('title','Administración de permisos')

@section('breadcrumb')
<x-breadcrumb :items="[
        ['name' => 'Inicio', 'url' => route('home')],
        ['name' => 'Gestión Sistema', 'url' => route('gestion-sistema.index')],
        ['name' => 'Administrar Permisos']
    ]" />
<br>
@endsection

@section('content')
<div class="container-fluid p-0 border rounded sidebar-dark-primary tableContainer" style="min-height:150px;">

  <x-sectionHeader
    titulo="Permisos"
    rutaVolver="{{ route('gestion-sistema.index') }}" />

  <div id="no-more-tables" class="table-responsive" style="padding:1.5em">
    <table
      id="permisosDataTable"
      class="table table-sm table-striped"
      data-page-size="25"
      data-toggle="table"
      data-locale="es-ES"
      data-search="true"
      data-pagination="true"
      data-sort-name="id"
      data-sort-order="asc"
      data-detail-view="true"
      data-detail-formatter="detallePermiso"
      data-check-on-init="true"
      data-url="{{ route('gestion-permisos.cargarDatos') }}">
      <thead class="table-primary">
        <tr>
          <th data-field="id" data-sortable="true">ID</th>
          <th data-field="nombre_limpio">Nombre limpio</th>
          <th data-field="name">Nombre</th>
          <th data-field="guard_name">Guard</th>
          <th data-field="updated_at">Actualizado</th>
          @permite(\App\Constants\Permisos::CONFIGURACION_GESTION_SISTEMA_ACTUALIZAR)
          <th data-field="acciones" data-formatter="accionesFormatter">Acciones</th>
          @endpermite
        </tr>
      </thead>
    </table>
  </div>
</div>
@endsection

@pushOnce('script')
@vite(['resources/js/cargarModal.js'])
<script>
  function accionesFormatter(index, row) {
    let urlEditar = "{{ route('gestion-permisos.edit', ['id' => ':id']) }}".replace(':id', row.id);
    return `
            <div class="d-flex flex-wrap gap-3 justify-content-center">
                @permite(\App\Constants\Permisos::CONFIGURACION_GESTION_SISTEMA_ACTUALIZAR)
                    <a class="text-decoration-none"
                        title="Editar permiso"
                        style="cursor: pointer;"
                        onclick="cargarModal('${urlEditar}', 'Editar permiso', '#formPermiso', 'modal-lg', 'PUT')">
                        <img src="{{ asset('img/edit.png') }}" alt="Editar permiso" style="width: 32px; height: 32px;">
                    </a>
                @endpermite
            </div>
        `;
  }

  document.addEventListener("DOMContentLoaded", () => {
    initTablaBootstrapTable(
      '#permisosDataTable', {
        protegidas: ['id']
      },
      'detallePermiso', {
        'acciones': accionesFormatter,
      }
    );
  });
</script>
@endpushOnce
