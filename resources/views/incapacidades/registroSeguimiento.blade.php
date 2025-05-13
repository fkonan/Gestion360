@extends('layouts.dashboard')

@section('title','Registro Seguimiento')

@section('breadcrumb')
<x-breadcrumb :items="[
        ['name' => 'Home', 'url' => route('home')],
        ['name' => 'Gestión Empleado', 'url' => route('gestion-incapacidades.index')],
        ['name' => 'Seguimiento Incapacidades', 'url' => route('gestion-incapacidades.seguimiento')],
        ['name' => 'Registro Seguimiento']
    ]" />
<br>
@endsection
    
@section('content')
<div class="container-fluid p-0 border rounded" style="min-height:150px; background-color: white">
    <div class="border rounded-top d-flex justify-content-between align-items-center px-4 bg-secondary">
        <span class="text-left text-light fs-5 fw-medium py-1">Registro Seguimiento</span>
        <!-- <a class="btn fw-bold my-2 text-light bg-primary" onclick="window.history.back()">Volver</a> -->
    </div>

    <a class="btn fw-bold ms-4 mt-4 bg-warning botonBoostrapTable"
        onclick="cargarModal(`{{ route('gestion-incapacidades.seguimiento.detalle.crear', ['id' => $incapacidad->IdIncapacidad]) }}`, 'Nuevo Seguimiento','#formNuevoSeguimiento','modal-lg')"> 
            Nuevo Seguimiento
    </a>

    <div class="row p-4">
        <table
            class="table table-bordered align-middle"
            data-page-size="10"
            data-toggle="table"
            data-locale="es-ES"
            data-search="true"
            data-pagination="true"
            data-detail-formatter="detalleIncapacidad"
            data-mobile-responsive="true"
            data-check-on-init="true"
            data-pagination-parts="['pageSize', 'pageList', 'pageNext', 'pagePrev']">   
            <thead class="table-primary">
                <tr class="bg-primary">
                    <th>Observacion</th>
                    <th data-sortable="true">Fecha Registro</th>
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
                    <td>
                        <span class="badge {{ $item->Estado == 'ACTIVO' ? 'bg-success' : 'bg-danger' }}">
                            {{$item->Estado}}
                        </span>
                    </td>
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
