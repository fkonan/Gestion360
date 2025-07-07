@extends('layouts.dashboard')

@section('title','Lista Sub Modulos')

@section('breadcrumb')
<x-breadcrumb :items="[
        ['name' => 'Inicio', 'url' => route('home')],
        ['name' => 'Gestión Sistema', 'url' => route('gestion-sistema.index')],
        ['name' => 'Administrar SubModulos']
    ]" />
<br>
@endsection

@section('content')
<div class="container-fluid p-0 border rounded tableContainer" style="min-height:150px; background-color: white">

    <x-cardHeader 
        titulo="Sub-Módulos"
        rutaVolver="{{ route('gestion-sistema.index') }}"
        :crear="true"
        crearRoute="{{ route('submodulos.create') }}"
        crearLabel="Crear SubModulo"
        crearModalTarget="#formSubModulo"
    />
    
    <div id="no-more-tables" class="table-responsive" style="padding:1.5em">
        <table
            id="subModulosDataTable"
            class="table table-sm table-striped"
            data-page-size="25"
            data-toggle="table"
            data-locale="es-ES"
            data-search="true"
            data-pagination="true"
            data-detail-view="true"
            data-detail-formatter="detalleSubModulo"
            data-check-on-init="true"
            data-url="{{ route('submodulos.cargarDatos') }}"> 
            <thead class="table-primary">
                <tr>
                    <th class="text-nowrap" data-field="SubModNom">Nombre</th>
                    <th data-field="SubModDes">Descripción</th>
                    <th data-field="ModPadreNom">Modulo Padre</th>    
                    <th data-field="SubModFecReg">Fecha Registro</th>
                    <th data-field="SubModHoReg">Hora Registro</th>
                    <th data-field="SubModuloEstado" data-formatter="estadoFormatter">Estado</th>
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
        document.addEventListener("DOMContentLoaded", () => {
            initTablaBootstrapTable(
                '#subModulosDataTable',
                { protegidas: ['SubModNom'] },
                'detalleSubModulo',
                { 'acciones': accionesFormatter,
                    'SubModuloEstado': estadoFormatter
                }
            );
        });

        function estadoFormatter(value, row) {
            const checked = row.SubModuloEstado === 'ACTIVO' ? 'checked' : '';
            const url = "{{ route('submodulos.cambiarEstado', ['id' => ':id']) }}".replace(':id', row.IdSubModulo);
            return `
                <div class="form-check form-switch d-flex justify-content-center">
                    <input 
                        onchange="actualizarEstado('${url}')"
                        class="form-check-input estado-switch" 
                        type="checkbox" 
                        role="switch"
                        data-id="${row.IdSubModulo}"
                        ${checked}>
                </div>
            `;
        }   

        function accionesFormatter(index, row) {
            let urlEditar = "{{ route('submodulos.edit', ['id' => ':id']) }}".replace(':id', row.IdSubModulo);
            return `
                <div class="d-flex flex-wrap gap-3 justify-content-center">
                    @permite('configuracion.gestion_sistema.actualizar')
                        <a class="text-decoration-none" 
                            title="Editar submodulo"
                            style="cursor: pointer;"
                            onclick="cargarModal('${urlEditar}', 'Editar SubModulo', '#formFormato')">
                            <img src="{{ asset('img/edit.png') }}" alt="Editar SubModulo" style="width: 32px; height: 32px;">
                        </a>
                    @endpermite
                </div>
            `;
        }
    </script>
@endpushOnce

