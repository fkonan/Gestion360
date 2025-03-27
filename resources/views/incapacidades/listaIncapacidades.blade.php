@extends('layouts.dashboard')

@section('title','Incapacidades')
    
@section('content')
<div class="container-fluid p-0 border rounded" style="min-height:150px">
    <div class="border rounded-top d-flex justify-content-between align-items-center px-4 bg-secondary">
        <span class="text-left text-light fs-5 fw-bold">Incapacidades</span>
        <a class="btn fw-bold my-2 text-light bg-primary" onclick="window.history.back()" >Volver</a>
    </div>

    <div class="row p-4">
        <table
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
                    <th>Documento</th>
                    <th>Causa Incapacidad</th>
                    <th>Observación</th>    
                    <th>Fecha Inicio</th>
                    <th>Fecha Fin</th>
                    <th>Estado</th>
                </tr>
            </thead>
            <tbody>
            @foreach($incapacidades as $incapacidad)
                <tr>
                    <td>{{ $incapacidad->IncPerNom }}</td>
                    <td>{{ $incapacidad->PerNumDoc }}</td>
                    <td>{{ $incapacidad->causaIncapacidad->ParDes }}</td>
                    <td>{{ $incapacidad->Observacion }}</td>
                    <td>{{ $incapacidad->IncFecIni }}</td>
                    <td>{{ $incapacidad->IncFecFin }}</td>
                    <td>{{ $incapacidad->IncapacidadEstado }}</td>   
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection 