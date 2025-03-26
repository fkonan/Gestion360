@extends('layouts.dashboard')

@section('title','Lista usuarios')

@section('content')
<!-- Spinner component -->
<x-spinner />

<div class="container-fluid p-0 border rounded" style="min-height:150px">

    <div class="border rounded-top d-flex justify-content-between align-items-center px-4 bg-secondary">
        <span class="text-left text-light fs-5 fw-bold">Usuarios registrados</span>
        <a class="btn fw-bold my-2 text-light bg-primary" onclick="window.history.back()" >Volver</a>
    </div>


    <a class="btn fw-bold ms-4 mt-4 text-light bg-primary" 
        style="position: absolute; top:150px;"
        onclick="cargarModal(`{{ route('usuarios.create') }}`, 'Crear Usuario', '#formCrearUsuario', 'modal-lg')">
        Registrar usuario
    </a>

    <div class="row p-4">
        <table
            id="usuariosDataTable"
            class="table table-striped d-none"
            data-toggle="table"
            data-page-size="10"
            data-search="true"
            data-locale="es-ES"
            data-pagination="true"
            data-pagination-parts="['pageSize', 'pageList', 'pageNext', 'pagePrev']">   
            <thead>
                <tr>
                    <th>Identificación</th>
                    <th>Nombre Completo</th>
                    <th>Fecha registro</th>
                    <th>Hora registro</th>
                    <th>Estado</th>   
                    <th>Permisos</th> 
                    <th>Roles</th>
                    <th>Opciones</th>
                </tr>
            </thead>
            <tbody>
            @foreach($usuarios as $usuario)
                <tr>
                    <td>{{ $usuario?->persona->PerNumDoc }}</td>
                    <td>{{ $usuario?->persona->PerNombres }} {{ $usuario?->persona->PerApellidos }}</td>
                    <td>{{ $usuario?->UsuFecReg }}</td>
                    <td>{{ $usuario?->UsuHorReg }}</td>
                    <td>{{ $usuario?->UsuarioEstado }}</td>
                    <td class="text-center" style="width: 70px;">
                        <a class="btn btn-dark btn-sm p-0 px-2" data-bs-toggle="tooltip" data-bs-placement="top" title="Permisos usuario"
                            onclick="cargarModal(`{{ route('permisos.edit', ['permiso' => $usuario->IdUsuario]) }}`, 'Permisos usuario', '#formPermisoUsuario', 'modal-xl')">
                            <i class="nav-icon fas fa-lock"></i>
                        </a>
                    </td>
                    <td class="text-center" style="width: 70px;">
                        <a class="btn btn-dark btn-sm p-0 px-2" data-bs-toggle="tooltip" data-bs-placement="top" title="Roles usuario"
                            onclick="cargarModal(`{{ route('roles.edit', ['role' => $usuario->IdUsuario]) }}`, 'Roles usuario', '#formRolUsuario', 'modal-lg')">
                            <i class="nav-icon fas fa-user"></i>
                        </a>
                    </td>
                    <td class="text-center" style="width: 70px;">
                        <a class="btn btn-secondary btn-sm p-0 px-2" data-bs-toggle="tooltip" data-bs-placement="top" title="Editar usuario"
                            onclick="cargarModal(`{{ route('usuarios.edit', ['usuario' => $usuario->IdUsuario]) }}`, 'Editar Usuario', '#formEditUsuario', 'modal-lg')">
                            <i class="nav-icon fas fa-edit"></i>
                        </a>
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection

@pushOnce('script')
<script>
document.addEventListener("DOMContentLoaded", function () {
    let table = document.getElementById("usuariosDataTable");

    //Cargar spinner
    $(table).on('post-body.bs.table', function () {
        document.getElementById("loadingSpinner").classList.add("d-none"); 
        table.classList.remove("d-none"); 
    });
});
</script>
@endpushOnce
