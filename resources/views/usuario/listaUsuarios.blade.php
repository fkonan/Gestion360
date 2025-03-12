@extends('layouts.dashboard')

@section('title','Lista usuarios')

@section('content')
<div class="container-fluid p-0 bg-white border rounded">

    <div class="border rounded-top d-flex justify-content-between align-items-center" style="background-color: #2C3643">
        <h5 class="text-left text-light p-2 ps-4">Usuarios registrados</h5>
        <a class="btn btn-warning fw-bold m-2" onclick="window.history.back()" >Volver</a>
    </div>

    <a class="btn btn-warning fw-bold ms-4 mt-4" 
        style="position: absolute; top:150px"
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
                    <td class="text-center" style="width: 100px;">
                        <a class="btn btn-secondary p-0 px-2">
                            <i class="nav-icon fas fa-unlock-alt"></i>
                        </a>
                    </td>
                    <td class="text-center" style="width: 100px;">
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
    </div>
</div>
@endsection


