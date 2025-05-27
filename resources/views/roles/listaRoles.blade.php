@extends('layouts.dashboard')

@section('title','Gestión Sistema')
    
@section('breadcrumb')
<x-breadcrumb :items="[
        ['name' => 'Inicio', 'url' => route('home')],
        ['name' => 'Gestión Sistema' , 'url' => route('gestion-sistema.index')],
        ['name' => 'Administrar Roles'],
    ]" />
<br>
@endsection

@section('content')
<div class="container-fluid p-0 border rounded" style="min-height:150px; background-color: white">

    <div class="border rounded-top d-flex justify-content-between align-items-center px-4 bg-secondary">
        <span class="text-left text-light fs-5 fw-medium py-1">Roles</span>
    </div>

    @permite('configuracion.gestion_sistema.crear')
        <a class="btn fw-bold ms-4 mt-4 bg-warning botonBoostrapTable" 
            href="{{ route('roles.create') }}"> 
            Crear Rol
        </a>
    @endpermite

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
            data-responsive="true"
            data-mobile-responsive="true"
            data-check-on-init="true"
            data-pagination-parts="['pageSize', 'pageList', 'pageNext', 'pagePrev']">   
            <thead class="table-primary">
                <tr class="bg-primary">
                    <th data-sortable="true">ID</th>
                    <th>Nombre</th>
                    @permite('configuracion.gestion_sistema.actualizar')
                        <th>Acciones</th>
                    @endpermite
                </tr>
            </thead>
            <tbody>
            @foreach($roles as $rol)
                <tr>
                    <td>{{ $rol->id }}</td>
                    <td class="text-nowrap">
                        <i class="fas {{ $icons[$rol->name] ?? 'fa-user' }} me-2 text-primary"></i> {{ $rol->name }}
                    </td>
                    @permite('configuracion.gestion_sistema.actualizar')
                        <td class="text-center" style="width: 80px;">
                            <a class="p-0 px-2" 
                                href="{{ route('roles.permisos', ['id' => $rol->id]) }}">
                                <img src="https://autogestion.copetran.com.co/gestion/aFrame/library/bower_components/Ionicons/png/512/new_editar.png" alt="Editar" style="width: 30px; height: 30px;">
                            </a>
                        </td>
                    @endpermite
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