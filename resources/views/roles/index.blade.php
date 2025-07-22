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
<div class="container-fluid p-0 border sidebar-dark-primary rounded tableContainer" style="min-height:150px;">

    <x-sectionHeader 
        titulo="Roles"
        rutaVolver="{{ route('gestion-sistema.index') }}"
        crear="newpage"
        crearRoute="{{ route('roles.create') }}"
        crearLabel="Crear Rol"
    />

    @php
    $icons = [
        'ADMIN' => 'fa-user-shield',
        'SOPORTE' => 'fa-headset',
        'SUPER-ADMIN' => 'fa-crown',
        'DESARROLLADOR' => 'fa-code',
    ];
    @endphp
    
    <div class="row mx-0 py-4">    
        <table
            id="administrarRoles"
            class="table table-striped table-sm table-hover align-middle table-bordered"
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
                        <i class="fas {{ $icons[$rol->name] ?? 'fa-user' }} me-2 text-secondary"></i> {{ $rol->name }}
                    </td>
                    @permite('configuracion.gestion_sistema.actualizar')
                        <td class="text-center" style="width: 80px;">
                            <a class="p-0 px-2" 
                                title="Editar Rol"
                                href="{{ route('roles.permisos', ['id' => $rol->id]) }}">
                                <img src="{{ asset('img/edit.png') }}" alt="Editar Rol" style="width: 32px; height: 32px;">
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