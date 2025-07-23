@extends('layouts.dashboard')

@section('title','Lista modulos')

@section('breadcrumb')
<x-breadcrumb :items="[
        ['name' => 'Inicio', 'url' => route('home')],
        ['name' => 'Gestión Sistema', 'url' => route('gestion-sistema.index')],
        ['name' => 'Administrar Modulos']
    ]" />
<br>
@endsection

@section('content')
<div class="container-fluid p-0 border rounded sidebar-dark-primary tableContainer" style="min-height:150px">

    <x-sectionHeader 
        titulo="Módulos"
        rutaVolver="{{ route('gestion-sistema.index') }}"
        :crear="true"
        crearRoute="{{ route('modulos.create') }}"
        crearLabel="Crear Modulo"
        crearModalTarget="#formFormato"
        modalSize="modal-xl"
    />

    <div id="no-more-tables" class="table-responsive" style="padding:1.5em">
        <table
            id="modulosDataTable"
            class="table table-sm table-striped"
            data-page-size="25"
            data-toggle="table"
            data-locale="es-ES"
            data-search="true"
            data-pagination="true"
            data-detail-view="true"
            data-detail-formatter="detalleModulo"
            data-check-on-init="true"
            data-side-pagination="server"
            data-url="{{ route('modulos.cargarDatos') }}">
            <thead class="table-primary">
                <tr>
                    <th data-field="ModNom">Nombre</th>
                    <th data-field="ModDesc">Descripción</th>
                    <th data-field="ModFecReg">Fecha Registro</th>
                    <th data-field="ModHorReg">Hora Registro</th>
                    <th data-field="ModEstado" data-formatter="estadoFormatter">Estado</th>
                    @permite('configuracion.gestion_sistema.actualizar')
                        <th data-field="acciones" data-formatter="accionesFormatter">Opciones</th>
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
    function estadoFormatter(value, row) {s
        const checked = row.ModEstado === 'ACTIVO' ? 'checked' : '';
        const url = "{{ route('modulos.cambiarEstado', ['id' => ':id']) }}".replace(':id', row.IdModulo);
        return `
            <div class="form-check form-switch d-flex justify-content-center">
                <input 
                    onchange="actualizarEstado('${url}')"
                    class="form-check-input estado-switch" 
                    type="checkbox" 
                    role="switch"
                    data-id="${row.IdModulo}"
                    ${checked}>
            </div>
        `;
    }   

    function accionesFormatter(index, row) {
        let urlEditar = "{{ route('modulos.edit', ['id' => ':id']) }}".replace(':id', row.IdModulo);
        return `
            <div class="d-flex flex-wrap gap-3 justify-content-center">
                @permite('configuracion.gestion_sistema.actualizar')
                    <a class="text-decoration-none" 
                        title="Editar modulo"
                        style="cursor: pointer;"
                        onclick="cargarModal('${urlEditar}', 'Editar Modulo', '#formFormato')">
                        <img src="{{ asset('img/edit.png') }}" alt="Editar Modulo" style="width: 32px; height: 32px;">
                    </a>
                @endpermite
            </div>
        `;
    }

    document.addEventListener("DOMContentLoaded", () => {
        initTablaBootstrapTable(
            '#modulosDataTable', 
            { protegidas: ['ModNom'] }, 
            'detalleModulo', 
            { 'acciones': accionesFormatter,
                'ModEstado': estadoFormatter,
            }
        );
    });
    </script>
@endpushOnce

