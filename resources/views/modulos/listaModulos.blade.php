@extends('layouts.dashboard')

@section('title','Lista modulos')

@section('breadcrumb')
<x-breadcrumb :items="[
        ['name' => 'Home', 'url' => route('home')],
        ['name' => 'Gestión Sistema', 'url' => route('gestion-sistema.index')],
        ['name' => 'Administrar Modulos']
    ]" />
<br>
@endsection

@section('content')
<div class="container-fluid p-0 border rounded" style="min-height:150px; background-color: white">

    <div class="border rounded-top d-flex justify-content-between align-items-center px-4 bg-secondary">
        <span class="text-left text-light fs-5 fw-bold">Módulos</span>
        <a class="btn fw-bold my-2 text-light bg-primary" onclick="window.history.back()" >Volver</a>
    </div>

    <a class="btn fw-bold ms-4 mt-4 bg-warning botonBoostrapTable"  
        onclick="cargarModal(`{{ route('modulos.create') }}`, 'Crear Modulo', '#formFormato')">
            Crear Modulo
    </a>
    
    <div class="row p-4">
        <table
            id="modulosDataTable"
            class="table table-sm table-striped"
            data-page-size="10"
            data-toggle="table"
            data-locale="es-ES"
            data-search="true"
            data-pagination="true"
            data-pagination-parts="['pageSize', 'pageList', 'pageNext', 'pagePrev']">   
            <thead class="table-primary">
                <tr>
                    <th>Nombre</th>
                    <th>Descripción</th>
                    <th>Estado</th>
                    <th>Modulo Padre</th>    
                    <th>Fecha Registro</th>
                    <th>Hora Registro</th>
                    <th>Opciones</th>
                </tr>
            </thead>
            <tbody>
            @foreach($modulos as $modulo)
                <tr>
                   <th class="text-nowrap">{{ mb_strtoupper($modulo->ModNom) }}</th>
                   <th title="{{ ucfirst(mb_strtolower($modulo->ModDesc)) }}">{{ Str::limit(ucfirst(mb_strtolower($modulo->ModDesc)), 50, '...') }}</th>
                   <th>{{ $modulo->ModEstado}}</th>
                   <th>{{ $modulo->padre->ModNom ?? "MODULO PRINCIPAL" }}</th>
                   <th>{{ $modulo->ModFechReg}}</th>
                   <th>{{ $modulo->ModHorReg}}</th>
                    <th class="text-center" style="width: 80px;">
                        <a class="btn btn-secondary p-0 px-2" onclick="cargarModal(`{{ route('modulos.edit', ['id' => $modulo->IdModulo]) }}`, 'Editar Modulo', '#formFormato')">
                            <i class="nav-icon fas fa-edit"></i>
                        </a>
                    </th>
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

