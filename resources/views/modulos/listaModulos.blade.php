@extends('layouts.dashboard')

@section('title','Lista modulos')

@section('breadcrumb')
<x-breadcrumb :items="[
        ['name' => 'Inicio', 'url' => route('home')],
        ['name' => 'Gestión Sistema', 'url' => route('gestion-sistema.index')],
        ['name' => 'Administrar Modulos']
    ]" />
<br>
@endsection

@section('content')
<div class="container-fluid p-0 border rounded" style="min-height:150px; background-color: white">

    <div class="border rounded-top d-flex justify-content-between align-items-center px-4 bg-secondary">
        <span class="text-left text-light fs-5 fw-medium py-1">Módulos</span>
        <a class="btn fw-bold my-2 bg-primary" onclick="window.history.back()">Volver</a>
    </div>

    @permite('configuracion.gestion_sistema.crear')
    <a class="btn fw-bold ms-4 mt-4 bg-warning botonBoostrapTable"  
        onclick="cargarModal(`{{ route('modulos.create') }}`, 'Crear Modulo', '#formFormato')">
            Crear Modulo
    </a>
    @endpermite
    
    <div id="no-more-tables" class="table-responsive" style="padding:1.5em">
        <table
            id="modulosDataTable"
            class="table table-sm table-striped"
            data-page-size="25"
            data-toggle="table"
            data-locale="es-ES"
            data-search="true"
            data-pagination="true"
            data-mobile-responsive="true"
            data-check-on-init="true"
            data-pagination-parts="['pageSize', 'pageList', 'pageNext', 'pagePrev']">   
            <thead class="table-primary">
                <tr>
                    <th>Nombre</th>
                    <th>Descripción</th>
                    <th>Fecha Registro</th>
                    <th>Hora Registro</th>
                    <th>Estado</th>
                    @permite('configuracion.gestion_sistema.actualizar')
                        <th>Opciones</th>
                    @endpermite
                </tr>
            </thead>
            <tbody>
            @foreach($modulos as $modulo)
                <tr>
                    <td data-label="Nombre"class="text-nowrap">{{ mb_strtoupper($modulo->ModNom) }}</td>
                    <td data-label="Descripcion"title="{{ ucfirst(mb_strtolower($modulo->ModDesc)) }}">{{ ucfirst(mb_strtolower($modulo->ModDesc)) }}</td>
                    <td data-label="Fecha Registro">{{ $modulo->ModFechReg}}</td>
                    <td data-label="Hora Registro">{{ $modulo->ModHorReg}}</td>
                    <td class="text-left" data-label="Estado">
                        <div class="form-check form-switch d-flex justify-content-center">
                            <input 
                                onchange="actualizarEstado(`{{ route('modulos.cambiarEstado', ['id' => $modulo->IdModulo]) }}`)"
                                class="form-check-input estado-switch" 
                                type="checkbox" 
                                role="switch"
                                data-id="{{ $modulo->IdModulo }}"
                                {{ $modulo->ModEstado == 'ACTIVO' ? 'checked' : '' }}>
                        </div>
                    </td>
                    @permite('configuracion.gestion_sistema.actualizar')
                        <td data-label="Opciones" class="text-center" style="width: 80px;">
                            <a class="p-0 px-2" onclick="cargarModal(`{{ route('modulos.edit', ['id' => $modulo->IdModulo]) }}`, 'Editar Modulo', '#formFormato')">
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

