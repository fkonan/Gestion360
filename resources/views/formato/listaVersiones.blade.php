@extends('layouts.dashboard')

@section('title','Lista versiones')

@section('content')
<div class="container-fluid p-0 bg-white border rounded">

    <div class="border rounded-top" style="background-color: #2C3643">
        <h5 class="text-left text-light p-2 ps-4">Versiones</h5>
    </div>
    
    <a class="btn btn-warning fw-bold ms-4 mt-4" href="{{ route('formatos.versions.create', ['id' => $formato->IdFormato]) }}">Nueva Version</a>
        
    <div class="row p-4 text-center">
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
