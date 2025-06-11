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

    <div class="border rounded-top d-flex justify-content-between align-items-center px-4 bg-secondary">
        <span class="text-left text-light fs-5 fw-medium py-1">SubMódulos</span>
        <a class="btn fw-bold my-2 bg-primary" onclick="window.history.back()">Volver</a>
    </div>

    @permite('configuracion.gestion_sistema.crear')
        <a class="btn fw-bold ms-4 mt-4 bg-warning botonBoostrapTable"  
            onclick="cargarModal(`{{ route('submodulos.create') }}`, 'Crear SubModulo', '#formSubModulo')">
                Crear SubModulo
        </a>
    @endpermite
    
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
                            onclick="cargarModal('${urlEditar}', 'Editar SubModulo', '#formFormato')">
                            <img src="https://autogestion.copetran.com.co/gestion/aFrame/library/bower_components/Ionicons/png/512/new_editar.png" alt="Editar" style="width: 30px; height: 30px;">
                        </a>
                    @endpermite
                </div>
            `;
        }
    </script>
@endpushOnce

