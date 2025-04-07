@extends('layouts.dashboard')

@section('title','Resultado Tiquetes')

@section('breadcrumb')
<x-breadcrumb :items="[
        ['name' => 'Home', 'url' => route('home')],
        ['name' => 'Reportes', 'url' => route('reportes.index')],
        ['name' => 'Resultado Tiquetes'],
    ]" />
<br>
@endsection
    
@section('content')
<div class="container-fluid p-0 border rounded" style="min-height:150px; background-color: white">
    <div class="border rounded-top d-flex justify-content-between align-items-center px-4 bg-secondary">
        <span class="text-left text-light fs-5 fw-bold">Resultado Tiquetes</span>
        <a class="btn fw-bold my-2 text-light bg-primary" onclick="window.history.back()">Volver</a>
    </div>

    <div class="row p-4">
        <table
            class="table table-sm table-striped"
            data-page-size="25"
            data-toggle="table"
            data-locale="es-ES"
            data-search="true"
            data-pagination="true"
            data-url="{{ route('reportes.cargarData') }}"
            data-pagination-parts="['pageSize', 'pageList', 'pageNext', 'pagePrev']">   
            <thead class="table-primary">
                <tr class="bg-primary">
                    <th data-field="NumDocPer">Documento</th>
                    <th data-field="NumeroPasaje">Tiquete</th>
                    <th data-field="TerminalOrigen">Terminal Origen</th>
                    <th data-field="TerminalDestino">Terminal Destino</th>
                    <th data-field="FechaSalida">Fecha Salida</th>
                    <th data-field="NumerodeViaje">Numero de Viaje</th>
                    <th data-field="PrecioBase">Precio Base</th>
                    <th data-field="Descuento">Descuento</th>
                    <th data-field="PrecioTotal">Precio Total</th>
                </tr>
            </thead>
        </table>
    </div>
</div>
@endsection 