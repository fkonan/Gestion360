@extends('layouts.dashboard')

@section('title','Gestión Sistema')
    
@section('breadcrumb')
<x-breadcrumb :items="[
        ['name' => 'Home', 'url' => route('home')],
        ['name' => 'Gestión Sistema' , 'url' => route('gestion-sistema.index')],
        ['name' => 'Administrar Roles'],
    ]" />
<br>
@endsection

@section('content')
<div class="container-fluid p-0 border rounded" style="min-height:150px; background-color: white">

    <div class="border rounded-top d-flex justify-content-between align-items-center px-4 bg-secondary">
        <span class="text-left text-light fs-5 fw-bold">Roles</span>
        <a class="btn fw-bold my-2 text-light bg-primary" onclick="window.history.back()" >Volver</a>
    </div>

    <a class="btn fw-bold ms-4 mt-4 bg-warning botonBoostrapTable"  
        onclick="">
            Crear Rol
    </a>

    @php
    $icons = [
        'ADMIN' => 'fa-user-shield',
        'SOPORTE' => 'fa-headset',
        'SUPER-ADMIN' => 'fa-crown',
        'DESARROLLADOR' => 'fa-code',
    ];
    @endphp
    
    <div class="row p-4">
        <table
            id="administrarRoles"
            class="table table-striped table-hover align-middle table-bordered"
            data-page-size="10"
            data-toggle="table"
            data-locale="es-ES"
            data-search="true"
            data-pagination="true"
            data-pagination-parts="['pageSize', 'pageList', 'pageNext', 'pagePrev']">   
            <thead class="table-primary">
                <tr class="bg-primary">
                    <th data-sortable="true">ID</th>
                    <th>Nombre</th>
                    <th>Permisos</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
            @foreach($roles as $rol)
                <tr>
                    <td>{{ $rol->id }}</td>
                    <td class="text-nowrap">
                        <i class="fas {{ $icons[$rol->name] ?? 'fa-user' }} me-2 text-primary"></i> {{ $rol->name }}
                    </td>
                    <td>
                        @php
                            $permisos = $rol->getPermissionNames();
                            $limit = 5;
                        @endphp

                        @if($permisos->count() > 0)
                            @foreach($permisos->take($limit) as $permiso)
                                <span style="background-color:#D6D6D6" class="badge text-dark mb-1">{{ $permiso }}</span>
                            @endforeach

                            @if($permisos->count() > $limit)
                                <span class="badge bg-secondary text-light mb-1 ver-mas"
                                    data-bs-toggle="tooltip"
                                    title="{{ $permisos->slice($limit)->implode(', ') }}">
                                    +{{ $permisos->count() - $limit }} más
                                </span>
                            @endif
                        @else
                            <span class="badge bg-secondary">Sin permisos</span>
                        @endif
                    </td>  

                    <td class="text-center" style="width: 80px;">
                        <a class="btn btn-secondary p-0 px-2" 
                            onclick="cargarModal(`{{ route('roles.permisos', ['id' => $rol->id]) }}`, 'Permisos Rol', '#formPermisoRol')">
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
    @vite(['resources/js/cargarModal.js'])
@endpushOnce