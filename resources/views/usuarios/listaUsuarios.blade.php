@extends('layouts.dashboard')

@section('title','Lista usuarios')

@section('breadcrumb')
<x-breadcrumb :items="[
        ['name' => 'Home', 'url' => route('home')],
        ['name' => 'Usuarios'],
    ]" />
<br>
@endsection

@section('content')
<div class="container-fluid p-0 border rounded bg-white" style="min-height:150px;">

    <div class="border rounded-top d-flex justify-content-between align-items-center px-4 bg-secondary">
        <span class="text-left text-light fs-5 fw-medium py-1">Usuarios registrados</span>
    </div>

    @permite('administracion.usuarios.crear')
        <a class="btn fw-bold ms-4 mt-4 text-light bg-warning botonBoostrapTable" 
            onclick="cargarModal(`{{ route('usuarios.create') }}`, 'Crear Usuario', '#formCrearUsuario', 'modal-lg')">
            Registrar usuario
        </a>
    @endpermite

    <div id="no-more-tables" class="row p-4">
        <table
            id="usuariosDataTable"
            class="table table-sm table-striped"
            data-toggle="table"
            data-page-size="10"
            data-search="true"
            data-locale="es-ES"
            data-pagination="true"
            data-responsive="true"
            data-mobile-responsive="true"
            data-check-on-init="true"
            data-url="{{ route('usuarios.cargarDatos') }}" 
            data-pagination-parts="['pageSize', 'pageList', 'pageNext', 'pagePrev']">   
            <thead class="table-primary">
                <tr>
                    <th data-field="persona.PerNumDoc" data-visible="true" data-priority="1">Identificación</th>
                    <th data-field="nombreCompleto" data-formatter="nombreCompletoFormatter" data-priority="1">Nombre Completo</th>
                    <th data-field="UsuFecReg" data-sortable="true" data-priority="3">Fecha registro</th>
                    <th data-field="UsuHorReg" data-sortable="true" data-priority="4">Hora registro</th>
                    <th class="text-center" data-field="UsuarioEstado" data-formatter="estadoFormatter" data-sortable="true" data-priority="2">Estado</th>
                    @if(
                        auth()->user()->can('administracion.usuarios.asignar_permisos') ||
                        auth()->user()->can('administracion.usuarios.asignar_roles') ||
                        auth()->user()->can('administracion.usuarios.actualizar')
                    )
                        <th data-field="acciones" data-formatter="accionesFormatter" data-priority="1">Acciones</th>
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
     var rutas = {
        permisos: "{{ route('permisos.edit', ['id' => ':id']) }}",
        roles: "{{ route('roles.edit', ['id' => ':id']) }}",
        usuarios: "{{ route('usuarios.edit', ['id' => ':id']) }}"
    };

    function estadoFormatter(value, row) {
        return `
        <span class="badge ${row.UsuarioEstado === 'ACTIVO' ? 'bg-success' : 'bg-danger'}">
            ${row.UsuarioEstado}
        </span>
        `;
    }

    function accionesFormatter(index, row) {
        let urlPermisos = rutas.permisos.replace(':id', row.IdUsuario);
        let urlRoles = rutas.roles.replace(':id', row.IdUsuario);
        let urlUsuarios = rutas.usuarios.replace(':id', row.IdUsuario);

        return `
            <div class="col-md-12 text-center">
                @permite('administracion.usuarios.asignar_permisos')
                    <a class="me-3 text-decoration-none" 
                        title="Haga click para gestionar los permisos del usuario"
                        onclick="cargarModal('${urlPermisos}', 'Permisos usuario', '#formPermisoUsuario', 'modal-xl')">
                        <img src="https://autogestion.copetran.com.co/gestion/aFrame/library/bower_components/Ionicons/png/512/Permiso00.png" alt="Permisos" style="width: 30px; height: 30px;">
                    </a>
                @endpermite
                @permite('administracion.usuarios.asignar_roles')
                    <a class="me-3 text-decoration-none" 
                        title="Haga click para gestionar los roles del usuario"
                        onclick="cargarModal('${urlRoles}', 'Roles usuario', '#formRolUsuario', 'modal-xl')">
                        <img src="{{ asset('img/rolesEdit.png') }}" alt="Roles" style="width: 32px; height: 32px;">
                    </a>
                @endpermite
                @permite('administracion.usuarios.actualizar')
                    <a 
                        title="Haga click para editar el usuario"
                        onclick="cargarModal('${urlUsuarios}', 'Editar Usuario', '#formEditUsuario', 'modal-lg')">
                        <img src="https://autogestion.copetran.com.co/gestion/aFrame/library/bower_components/Ionicons/png/512/new_editar.png" alt="Editar" style="width: 30px; height: 30px;">
                    </a>
                @endpermite
            </div>
        `;
    }

    function nombreCompletoFormatter(value, row) {
        return row.persona.PerNombres + ' ' + row.persona.PerApellidos;
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
</script>
@endpushOnce

