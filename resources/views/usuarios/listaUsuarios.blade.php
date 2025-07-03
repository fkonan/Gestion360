@extends('layouts.dashboard')

@section('title','Lista usuarios')

@section('breadcrumb')
<x-breadcrumb :items="[
        ['name' => 'Inicio', 'url' => route('home')],
        ['name' => 'Usuarios'],
    ]" />
<br>
@endsection

@section('content')
<div class="container-fluid p-0 border rounded bg-white tableContainer" style="min-height:150px;">

    <x-cardHeader 
        titulo="Usuarios registrados"
        :crear="true"
        rutaVolver="{{ route('home') }}"
        crearRoute="{{ route('usuarios.create') }}"
        crearLabel="Crear Usuario"
        crearModalTarget="#formCrearUsuario"
    />
    
    <div id="no-more-tables" class="table-responsive" style="padding:1.5em">
        <table
            id="usuariosDataTable"
            class="table table-sm table-striped"
            data-toggle="table"
            data-page-size="25"
            data-search="true"
            data-locale="es-ES"
            data-pagination="true"
            data-responsive="true"
            data-check-on-init="true"
            data-detail-view="true"
            data-detail-formatter="detalleUsuario"
            data-side-pagination="server"
            data-url="{{ route('usuarios.cargarDatos') }}">   
            <thead class="table-primary">
                <tr>
                    <th data-field="PerNumDoc" data-visible="true">Identificación</th>
                    <th data-field="nombreCompleto">Nombre y apellidos</th>
                    <th class="text-center" data-field="fechaHoraRegistro" data-sortable="true">Fecha registro</th>
                    <th data-field="rol">Rol</th>
                    @permite('administracion.usuarios.actualizar')
                        <th class="text-center" data-field="estado" data-formatter="estadoFormatter" data-sortable="true">Estado</th>
                    @endpermite
                    @if(
                        auth()->user()->can('administracion.usuarios.asignar_permisos') ||
                        auth()->user()->can('administracion.usuarios.asignar_roles') ||
                        auth()->user()->can('administracion.usuarios.actualizar')
                    )
                        <th data-field="acciones" data-formatter="accionesFormatter">Acciones</th>
                    @endif

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
        const checked = row.estado === 'ACTIVO' ? 'checked' : '';
        const url = "{{ route('usuarios.cambiarEstado', ['id' => ':id']) }}".replace(':id', row.IdUsuario);
        return `
            <div class="form-check form-switch d-flex justify-content-center">
                <input 
                    onchange="actualizarEstado('${url}')"
                    class="form-check-input estado-switch" 
                    type="checkbox" 
                    role="switch"
                    data-id="${row.IdUsuario}"
                    ${checked}>
            </div>
        `;
    }

    function accionesFormatter(index, row) {
        let urlPermisos = "{{ route('permisos.edit', ['id' => ':id']) }}".replace(':id', row.IdUsuario);
        let urlRoles = "{{ route('roles.edit', ['id' => ':id']) }}".replace(':id', row.IdUsuario);
        let urlUsuarios = "{{ route('usuarios.edit', ['id' => ':id']) }}".replace(':id', row.IdUsuario);

        return `
            <div class="d-flex flex-row align-items-center justify-content-center gap-2" style="flex-wrap:nowrap;">
            @permite('administracion.usuarios.asignar_permisos')
                <a class="text-decoration-none" 
                title="Gestionar permisos del usuario"
                onclick="cargarModal('${urlPermisos}', 'Permisos usuario', '#formPermisoUsuario', 'modal-xl')">
                <img src="https://autogestion.copetran.com.co/gestion_2/aFrame/library/bower_components/Ionicons/png/512/Permiso00.png" alt="Permisos" style="width: 30px; height: 30px;">
                </a>
            @endpermite
            @permite('administracion.usuarios.asignar_roles')
                <a class="text-decoration-none" 
                title="Gestionar roles del usuario"
                onclick="cargarModal('${urlRoles}', 'Roles usuario', '#formRolUsuario', 'modal-xl')">
                <img src="{{ asset('img/rolesEdit.png') }}" alt="Roles" style="width: 32px; height: 32px;">
                </a>
            @endpermite
            @permite('administracion.usuarios.actualizar')
                <a 
                title="Editar usuario"
                onclick="cargarModal('${urlUsuarios}', 'Editar Usuario', '#formEditUsuario', 'modal-lg')">
                <img src="https://autogestion.copetran.com.co/gestion_2/aFrame/library/bower_components/Ionicons/png/512/new_editar.png" alt="Editar" style="width: 30px; height: 30px;">
                </a>
            @endpermite
            </div>
        `;
    }

    function togglePasswordVisibility() {
        let passwordField = document.getElementById("Password");
        if (passwordField.disabled) {
            passwordField.disabled = false;
            passwordField.type = "text";
            passwordField.dataset.previousValue = passwordField.value;
            passwordField.value = ""; 
        } else {
            passwordField.disabled = true;
            passwordField.type = "password";
            passwordField.value = passwordField.dataset.previousValue; 
        }
    }

    document.addEventListener("DOMContentLoaded", () => {
        initTablaBootstrapTable(
            '#usuariosDataTable', 
            { protegidas: ['persona.PerNumDoc'] }, 
            'detalleUsuario', 
            { 'acciones': accionesFormatter,
                'estado': estadoFormatter,
            }
        );
    });
</script>
@endpushOnce

