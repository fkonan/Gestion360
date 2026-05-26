@extends('layouts.dashboard')

@section('title','Gestion appmovil')

@section('breadcrumb')
<x-breadcrumb :items="[
        ['name' => 'Inicio', 'url' => route('home')],
        ['name' => 'Gestion appmovil', 'url' => route('gestion-appmovil.index')],
        ['name' => 'Gestion personas'],
    ]" />
<br>
@endsection

@section('content')
<div class="container-fluid p-0 border rounded sidebar-dark-primary tableContainer" style="min-height:150px;">

  <x-sectionHeader
    titulo="Gestion de personas appmovil"
    rutaVolver="{{ route('gestion-appmovil.index') }}" />

  <div id="no-more-tables" class="table-responsive" style="padding:1.5em">
    <table
      id="personasAppmovilDataTable"
      class="table table-sm table-striped"
      data-toggle="table"
      data-page-size="25"
      data-search="true"
      data-locale="es-ES"
      data-pagination="true"
      data-responsive="true"
      data-detail-view="true"
      data-check-on-init="true"
      data-sort-name="fechaRegistro"
      data-sort-order="desc"
      data-detail-formatter="detallePersonaAppmovil"
      data-side-pagination="server"
      data-url="{{ route('personas-appmovil.cargarDatos') }}">
      <thead class="table-primary">
        <tr>
          <th data-field="nombreCompleto">Nombre y apellidos</th>
          <th data-field="PerNumDoc">Identificacion</th>
          <th data-field="PerTelefono">Telefono</th>
          <th data-field="PerEmail">Correo electronico</th>
          <th data-field="PerEstado">Estado persona</th>
          <th data-field="UsuarioEstado">Estado usuario</th>
          <th data-field="Verificado">Verificado</th>
          <th class="text-center" data-field="fechaRegistro" data-sortable="true">Fecha registro</th>
          <th class="text-center" data-field="acciones" data-formatter="accionesFormatter">Acciones</th>
        </tr>
      </thead>
    </table>
  </div>
</div>
@endsection

@pushOnce('script')
<script>
  function accionesFormatter(index, row) {
    const rutaBase = "{{ route('personas-appmovil.edit', ['id' => ':id']) }}";
    const rutaEditar = rutaBase.replace(':id', row.IdPersona);

    return `
            <div class="col-md-12">
                <a href="${rutaEditar}"
                    class="sig-action-btn"
                    data-title="Editar"
                    aria-label="Editar"
                    style="cursor: pointer;"
                    title="Editar persona">
                    <img src="{{ asset('img/edit.png') }}" alt="Editar Persona" style="width: 32px; height: 32px;">
                </a>
            </div>
        `;
  }

  document.addEventListener("DOMContentLoaded", () => {
    initTablaBootstrapTable(
      '#personasAppmovilDataTable', {
        protegidas: ['PerNumDoc']
      },
      'detallePersonaAppmovil', {
        'acciones': accionesFormatter,
      }
    );
  });
</script>
@endpushOnce
