@extends('layouts.dashboard')

@section('title','Lista personas')

@section('breadcrumb')
<x-breadcrumb :items="[
        ['name' => 'Inicio', 'url' => route('home')],
        ['name' => 'Personas'],
    ]" />
<br>
@endsection



@section('content')
<div class="container-fluid p-0 border rounded bg-white tableContainer" style="min-height:150px;">

    <x-cardHeader
        titulo="Personas registradas"
        crear="newpage"
        rutaVolver="{{ route('home') }}"
        crearRoute="{{ route('personas.create') }}"
        crearLabel="Registrar Persona"
    />

    <div id="no-more-tables" class="table-responsive " style="padding:1.5em">
        <table
            id="personasDataTable"
            class="table table-sm table-striped"
            data-toggle="table"
            data-page-size="25"
            data-search="true"
            data-locale="es-ES"
            data-pagination="true"
            data-responsive="true"
            data-detail-view="true"
            data-detail-formatter="detallePersona"
            data-check-on-init="true"
            data-side-pagination="server"
            data-url="{{ route('personas.cargarDatos') }}" >
            <thead class="table-primary">
                <tr>
                    <th data-field="PerNumDoc">Identificación</th>
                    <th data-field="nombreCompleto">Nombre Completo</th>
                    <th data-field="DepNom">Departamento</th>
                    <th data-field="PerTelefono">Telefono</th>
                    <th data-field="PerGenero" data-sortable="true">Genero</th>
                    <th class="text-center" data-field="PerFechReg" data-sortable="true">Fecha registro</th>
                    @permite('administracion.personas.actualizar')
                        <th class="text-center" data-sortable="true" data-field="PerEstado" data-formatter="estadoFormatter">Estado</th>
                        <th class="text-center" data-field="acciones" data-formatter="accionesFormatter" >Opciones</th>
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
    function estadoFormatter(value, row) {
        const checked = row.PerEstado === 'ACTIVO' ? 'checked' : '';
        const url = "{{ route('personas.cambiarEstado', ['id' => ':id']) }}".replace(':id', row.IdPersona);
        return `
            <div class="form-check form-switch d-flex justify-content-center">
                <input
                    onchange="actualizarEstado('${url}')"
                    class="form-check-input estado-switch"
                    type="checkbox"
                    role="switch"
                    data-id="${row.IdPersona}"
                    ${checked}>
            </div>
        `;
    }

    function accionesFormatter(index, row) {
        let ruta = "{{ route('personas.edit', ['id' => ':id']) }}"
        let rutaPersona = ruta.replace(':id',row.IdPersona);

        return `
            <div class="col-md-12">
                @permite('administracion.personas.actualizar')
                     <a class="text-decoration-none" href="${rutaPersona}"
                        title="Editar persona">
                        <img src="https://autogestion.copetran.com.co/gestion_2/aFrame/library/bower_components/Ionicons/png/512/new_editar.png" alt="Editar" style="width: 30px; height: 30px;">
                    </a>
                @endpermite
            </div>
        `;
    }

    document.addEventListener("DOMContentLoaded", () => {
        initTablaBootstrapTable(
            '#personasDataTable',
            {
                protegidas: ['PerNumDoc']
            },
            'detallePersona',
            {
                'acciones': accionesFormatter,
                'PerEstado': estadoFormatter,
            }
        );
    });

    </script>
@endpushOnce

