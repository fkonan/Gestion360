@extends('layouts.dashboard')

@section('title','Lista versiones')

@section('content')
<div class="container-fluid p-4">
    <h1 class="text-center">Historial versiones</h1>
    <a class="btn btn-primary my-4" href="{{ route('formatos.versions.create', ['id' => $formato->IdFormato]) }}">Nueva Version</a>
        
    <div class="row text-center">
        <table
            class="table table-striped"
            data-toggle="table"
            data-pagination="true"
            data-page-size="10"
            data-page-list="[]"
            data-pagination-parts="['pageSize', 'pageList', 'pageNext', 'pagePrev']">
        <thead>
        <tr>
            <th>Versión</th>
            <th>Elaboró</th>
            <th>Revisó</th>
            <th>Aprobó</th>
            <th>Fecha Registro</th>
            <th>Hora Registro</th>
            <th></th>
        </tr>
        </thead>
        <tbody>
        @foreach($versiones as $version)
        <tr>
            <td>{{ $version?->Version }}</td>
            <td>{{ $version?->VerElaboro }}</td>
            <td>{{ $version?->VerReviso }}</td>
            <td>{{ $version?->VerAprobo }}</td>
            <td>{{ $version?->VerFecReg }}</td>
            <td>{{ $version?->VerHorReg }}</td>
            <td>
            <a href="{{ asset('storage/' . $version->Ruta) }}" target="_blank">
                <i class="fas fa-file-pdf fa-2x text-danger"></i>
            </a>
            </td>
        </tr>
        @endforeach
        </tbody>
        </table>
    </div>
</div>
@endsection
