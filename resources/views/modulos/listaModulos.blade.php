@extends('layouts.dashboard')

@section('title','Lista modulos')

@section('content')
<div class="container-fluid p-0 border rounded">

    <div class="border rounded-top d-flex justify-content-between align-items-center px-4" style="background-color: #2C3643">
        <span class="text-left text-light fs-4 fw-medium">Modulos</span>
        <a class="btn fw-bold my-2 text-light" style="background-color: #007BFF" onclick="window.history.back()" >Volver</a>
    </div>

    @if(auth()->user()->can('crear-gestion-modulos'))
        <a class="btn fw-bold ms-4 mt-4 text-light" 
            style="position: absolute; top:150px; background-color: #007BFF" 
            onclick="cargarModal(`{{ route('modulos.create') }}`, 'crearModuloModal', '#formFormato')">
                Crear Modulo
        </a>
    @endif

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
                    <th>Nombre</th>
                    <th>Descripción</th>
                    <th>Estado</th>
                    <th>Modulo Padre</th>    
                    <th>Fecha Registro</th>
                    <th>Hora Registro</th>
                    @if(auth()->user()->can('editar-gestion-modulos'))
                        <th>Opciones</th>
                    @endif
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
                    @if(auth()->user()->can('editar-gestion-modulos'))
                        <th class="text-center" style="width: 80px;">
                            <a class="btn btn-secondary p-0 px-2" onclick="cargarModal(`{{ route('modulos.edit', ['id' => $modulo->IdModulo]) }}`, 'editModuloModal', '#formFormato')">
                                <i class="nav-icon fas fa-edit"></i>
                            </a>
                        </th>
                    @endif
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>

    <!-- modales -->
    <x-modal id="editModuloModal" titulo="Editar Módulo" size="xl"/>
    <x-modal id="crearModuloModal" titulo="Crear Módulo" size="xl"/>

</div>
@endsection
