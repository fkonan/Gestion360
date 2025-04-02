@extends('layouts.dashboard')

@section('title','Seguimiento detalle')
    
@section('content')
<div class="container-fluid p-0 border rounded" style="min-height:150px; background-color: white">
    <div class="border rounded-top d-flex justify-content-between align-items-center px-4 bg-secondary">
        <span class="text-left text-light fs-5 fw-bold">Seguimiento Detalle</span>
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
                <tr class="bg-primary">
                    <th>Observacion</th>
                    <th>Fecha Registro</th>
                    <th>Hora Registro</th>
                    <th>Usuario Registro</th>
                    <th>Estado</th>
                </tr>
            </thead>
            <tbody>
                @foreach($listaSeguimiento as $item)
                <tr>
                    <td>{{$item->Observacion}}</td>
                    <td>{{$item->SegFecReg}}</td>
                    <td>{{$item->SegHorReg}}</td>
                    <td>{{$item->UserRegistra}}</td>
                    <td>{{$item->Estado}}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection 

