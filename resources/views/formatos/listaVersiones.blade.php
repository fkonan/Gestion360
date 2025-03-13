@extends('layouts.dashboard')

@section('title','Lista versiones')

@section('content')
<div class="container-fluid p-0 border rounded">
    <div class="border rounded-top d-flex justify-content-between align-items-center px-4" style="background-color: #2C3643">
        <span class="text-left text-light fs-4 fw-medium">Versiones</span>
        <a class="btn fw-bold my-2 text-light" style="background-color: #007BFF" onclick="window.history.back()" >Volver</a>
    </div>

    <a class="btn fw-bold ms-4 mt-4 text-light" 
        style="background-color: #007BFF"
        href="{{ route('formatos.versions.create', ['id' => $formato->IdFormato]) }}">
        Nueva Version
    </a>
        
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
