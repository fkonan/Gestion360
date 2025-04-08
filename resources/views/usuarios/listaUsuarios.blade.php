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
        <span class="text-left text-light fs-5 fw-bold">Usuarios registrados</span>
        <a class="btn fw-bold my-2 text-light bg-primary" onclick="window.history.back()" >Volver</a>
    </div>

    <a class="btn fw-bold ms-4 mt-4 text-light bg-warning botonBoostrapTable" 
        onclick="cargarModal(`{{ route('usuarios.create') }}`, 'Crear Usuario', '#formCrearUsuario', 'modal-lg')">
        Registrar usuario
    </a>

    <div class="row p-4">
        <table
            id="usuariosDataTable"
            class="table table-sm table-striped"
            data-toggle="table"
            data-page-size="10"
            data-search="true"
            data-locale="es-ES"
            data-pagination="true"
            data-url="{{ route('usuarios.cargarDatos') }}" 
            data-pagination-parts="['pageSize', 'pageList', 'pageNext', 'pagePrev']">   
            <thead class="table-primary">
                <tr>
                    <th data-field="persona.PerNumDoc">Identificación</th>
                    <th data-field="nombreCompleto" data-formatter="nombreCompletoFormatter">Nombre Completo</th>
                    <th data-field="UsuFecReg" data-sortable="true">Fecha registro</th>
                    <th data-field="UsuHorReg" data-sortable="true">Hora registro</th>
                    <th data-field="UsuarioEstado" data-sortable="true">Estado</th>   
                    <th data-field="acciones" data-formatter="accionesFormatter">Acciones</th>
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

    function accionesFormatter(index, row) {
        let urlPermisos = rutas.permisos.replace(':id', row.IdUsuario);
        let urlRoles = rutas.roles.replace(':id', row.IdUsuario);
        let urlUsuarios = rutas.usuarios.replace(':id', row.IdUsuario);

        return `
            <div class="col-md-12 text-center">
                <a class="btn btn-secondary btn-sm me-3" 
                    title="Haga click para gestionar los permisos del usuario"
                    onclick="cargarModal('${urlPermisos}', 'Permisos usuario', '#formPermisoUsuario', 'modal-xl')">
                    <i class="fas fa-lock"></i>
                </a>
                <a class="btn btn-secondary btn-sm me-3" 
                    title="Haga click para gestionar los roles del usuario"
                    onclick="cargarModal('${urlRoles}', 'Roles usuario', '#formRolUsuario', 'modal-lg')">
                    <i class="fas fa-user"></i>
                </a>
                <a class="btn btn-secondary btn-sm" 
                    title="Haga click para editar el usuario"
                    onclick="cargarModal('${urlUsuarios}', 'Editar Usuario', '#formEditUsuario', 'modal-lg')">
                    <i class="fas fa-edit"></i>
                </a>
            </div>
        `;
    }

    function nombreCompletoFormatter(value, row) {
        return row.persona.PerNombres + ' ' + row.persona.PerApellidos;
    }
</script>
@endpushOnce

