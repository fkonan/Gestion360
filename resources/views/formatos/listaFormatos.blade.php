@extends('layouts.dashboard')

@section('title','Lista procesos')

@section('content')
<div class="container-fluid p-0 border rounded">
    <div class="border rounded-top d-flex justify-content-between align-items-center px-4" style="background-color: #2C3643">
        <span class="text-left text-light fs-4 fw-medium">Procesos</span>
        <a class="btn fw-bold my-2 text-light" style="background-color: #007BFF" onclick="window.history.back()" >Volver</a>
    </div>
    
    @if(auth()->user()->can('crear-gestion-documental'))
    <a class="btn fw-bold text-light ms-4 mt-4" 
        style="position: absolute; top:150px; background-color: #007BFF" 
        href="{{ route('formatos.create') }}">
        Crear Proceso
    </a>
    @endif

    <div class="row p-4">
        <table
            class="table table-sm table-striped"
            data-page-size="10"
            data-toggle="table"
            data-search="true"
            data-pagination="true"
            data-page-list="[]"
            data-page-size="10"
            data-pagination-parts="['pageSize', 'pageList', 'pageNext', 'pagePrev']">
        <thead style="background-color:#0070C0;color: white; text-align:center;">
        <tr>
            <th>Código</th>
            <th>Nombre</th>
            <th>Tipo documento</th>
            <th>Tipo proceso</th>
            <th>Ubicación</th>
            <th>Elaboró</th>
            <th>Revisó</th>
            <th>Aprobó</th>
            <th>Versión</th>
            <th>PDF</th>
            @if(auth()->user()->can('editar-gestion-documental'))
            <th>Opciones</th>
            @endif
        </tr>
        </thead>
        <tbody>
        @foreach($formatos as $formato)
        <tr>
            <td class="text-nowrap">{{ $formato?->FormCod }}</td>
            <td>{{ $formato?->FormNom }}</td>
            <td>{{ $formato?->tipoDocumento->Nombre }}</td>
            <td>{{ $formato?->tipoProceso->Nombre }}</td>
            <td>{{ $formato?->FormUbicacion }}</td>
            <td>{{ $formato?->ultimaVersion?->VerElaboro }}</td>
            <td>{{ $formato?->ultimaVersion?->VerReviso }}</td>
            <td>{{ $formato?->ultimaVersion?->VerAprobo }}</td>
            <td class="text-center">{{ $formato?->ultimaVersion?->Version }}</td>
            <th class="text-center"><a href="{{ asset('storage/' . $formato?->ultimaVersion?->Ruta) }}" target="_blank">
                <i class="fas fa-file-pdf fa-2x text-danger"></i></a>
            </th>
            @if(auth()->user()->can('editar-gestion-documental'))
            <td class="text-center">
                <a href="{{ route('formatos.versions.index', ['id' => $formato?->IdFormato]) }}">
                <i class="fas fa-list-alt fa-2x"></i>
                </a>
            </td>
            @endif
        </tr>
        @endforeach
        </tbody>
        </table>
    </div>
</div>
@endsection
