@extends('layouts.dashboard')

@section('title','Lista ingreso/salida conductores')

@section('breadcrumb')
<x-breadcrumb :items="[
        ['name' => 'Inicio', 'url' => route('home')],
        ['name' => 'Reportes', 'url' => route('reportes.index')],
        ['name' => 'Ingreso-Salida conductores'],
    ]" />
<br>
@endsection
    
@section('content')
<div class="container-fluid p-0 border rounded" style="min-height:150px; background-color: white">
    <div class="border rounded-top d-flex justify-content-between align-items-center px-4 bg-secondary">
        <span class="text-left text-light fs-5 fw-medium py-1">Resultado ingreso-salida conductores</span>
    </div>

    <button id="exportar" 
        onclick="exportarExcel('exportar', `{{ route('ingresoSalida.cargarData') }}` , 'Informe_ingreso_salida_conductores')"
        class="btn fw-bold ms-4 mt-4 bg-success botonBoostrapTable"> 
        Descargar Excel 
    </button>
   
    <div class="row p-4">
        <table
            class="table table-sm table-striped"
            data-page-size="25"
            data-toggle="table"
            data-locale="es-ES"
            data-search="true"
            data-pagination="true"
            data-mobile-responsive="true"
            data-check-on-init="true"
            data-url="{{ route('ingresoSalida.cargarData') }}"
            data-pagination-parts="['pageSize', 'pageList', 'pageNext', 'pagePrev']">   
            <thead class="table-primary">
                <tr class="bg-primary">
                    <th data-field="identificacion" data-sortable="true">Identificación</th>
                    <th data-field="codigo" data-sortable="true">Código</th>
                    <th data-field="nombreCompleto" data-sortable="true">Conductor</th>
                    <th data-field="evento" data-sortable="true">Evento</th>
                    <th data-field="fechaEvento" data-sortable="true">Fecha de evento</th>
                    <!-- <th data-field="agencia" data-sortable="true">Agencia registro de evento</th> -->
                </tr>
            </thead>
        </table>
    </div>
</div>
@endsection 

@pushOnce('script')
    @vite(['resources/js/cargarModal.js'])
@endpushOnce

