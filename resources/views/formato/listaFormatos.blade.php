@extends('layouts.dashboard')

@section('title','Lista procesos')

@section('content')
<div class="container-fluid p-0 bg-white border rounded">
    <div class="border rounded-top d-flex justify-content-between align-items-center" style="background-color: #2C3643">
        <h5 class="text-left text-light p-2 ps-4">Procesos</h5>
        <a class="btn btn-warning fw-bold m-2" onclick="window.history.back()" >Volver</a>
    </div>
    
    @if(auth()->user()->can('crear-gestion-documental'))
    <a class="btn btn-warning fw-bold ms-4 mt-4" style="position: absolute; top:150px" href="{{ route('formatos.create') }}">Crear Proceso</a>
    @endif

    <div class="row p-4">
        <table
            class="table table-striped"
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
