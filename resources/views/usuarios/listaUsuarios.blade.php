@extends('layouts.dashboard')

@section('title','Lista usuarios')

@section('content')
<div class="container-fluid p-0 border rounded">

    <div class="border rounded-top d-flex justify-content-between align-items-center px-4" style="background-color: #2C3643">
        <span class="text-left text-light fs-4 fw-medium">Usuarios registrados</span>
        <a class="btn fw-bold my-2 text-light" style="background-color: #007BFF" onclick="window.history.back()" >Volver</a>
    </div>


    <a class="btn fw-bold ms-4 mt-4 text-light" 
        style="position: absolute; top:150px; background-color: #007BFF"
        onclick="cargarModal(`{{ route('usuarios.create') }}`, 'crearUsuarioModal', '#formCrearUsuario')">
        Registrar usuario
    </a>

    <div class="row p-4">
        <table
            class="table table-striped"
            data-toggle="table"
            data-search="true"
            data-pagination="true"
            data-page-size="10"
            data-page-list="[]"
            data-pagination-parts="['pageSize', 'pageList', 'pageNext', 'pagePrev']">   
            <thead>
                <tr>
                    <th>Identificación</th>
                    <th>Nombre Completo</th>
                    <th>Fecha registro</th>
                    <th>Hora registro</th>
                    <th>Estado</th>   
                    <th>Permisos</th> 
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
                    <td class="text-center" style="width: 80px;">
                        <a class="btn btn-secondary p-0 px-2" onclick="cargarModal(`{{ route('permisos.edit', ['id' => $usuario->IdUsuario]) }}`, 'permisosUsuarioModal')">
                            <i class="nav-icon fas fa-unlock-alt"></i>
                        </a>
                    </td>
                    <td class="text-center" style="width: 80px;">
                        <a class="btn btn-primary p-0 px-2" onclick="cargarModal(`{{ route('usuarios.edit', ['id' => $usuario->IdUsuario]) }}`, 'editUsuarioModal', '#formEditUsuario')">
                            <i class="nav-icon fas fa-edit"></i>
                        </a>
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
        
        <!-- modales -->
        <x-modal id="editUsuarioModal" titulo="Editar Usuario" size="lg"/>
        <x-modal id="crearUsuarioModal" titulo="Crear Usuario" size="lg"/>
        <x-modal id="permisosUsuarioModal" titulo="Permisos Usuario" size="lg"/>
    </div>
</div>
@endsection


