@extends('layouts.app')

@section('title','Lista formatos')

@section('content')
<div class="container mt-4">
    <h1 class="text-center">Lista de formatos</h1>
    <a class="btn btn-primary my-4" href="{{ route('formato.crearNuevoFormato') }}">Nuevo formato</a>
    
    <div class="row text-center">
        <table
            data-toggle="table"
            data-pagination="true"
            data-page-size="10"
            data-pagination-parts="['pageSize', 'pageList', 'pageNext', 'pagePrev']">
        <thead>
        <tr>
            <th>Código</th>
            <th>Nombre</th>
            <th>Tipo</th>
            <th>Ubicación</th>
            <th>Elaboró</th>
            <th>Revisó</th>
            <th>Aprobó</th>
            <th>Versión</th>
            <th></th>
            <th></th>
        </tr>
        </thead>
        <tbody>
        @foreach($formatos as $formato)
        <tr>
            <td class="text-nowrap">{{ $formato?->FormCod }}</td>
            <td>{{ $formato?->FormNom }}</td>
            <td>{{ $formato?->FormTipo }}</td>
            <td>{{ $formato?->FormUbicacion }}</td>
            <td>{{ $formato?->ultimaVersion?->VerElaboro }}</td>
            <td>{{ $formato?->ultimaVersion?->VerReviso }}</td>
            <td>{{ $formato?->ultimaVersion?->VerAprobo }}</td>
            <td>{{ $formato?->ultimaVersion?->Version }}</td>
            <th><a href="{{ asset('storage/' . $formato?->ultimaVersion?->Ruta) }}" target="_blank">
                <i class="fas fa-file-pdf fa-2x text-danger"></i></a>
            </th>
            <td>
                <a href="{{ route('formato.listaVersiones', ['id' => $formato?->IdFormato]) }}">
                <i class="fas fa-list-alt fa-2x"></i>
                </a>
            </td>
        </tr>
        @endforeach
        </tbody>
        </table>
    </div>
</div>
@endsection
