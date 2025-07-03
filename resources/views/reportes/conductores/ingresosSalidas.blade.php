@extends('layouts.dashboard')

@section('title','Lista ingreso/salida conductores')

@section('breadcrumb')
<x-breadcrumb :items="[
        ['name' => 'Inicio', 'url' => route('home')],
        ['name' => 'Reportes', 'url' => route('reportes.index')],
        ['name' => 'Conductores', 'url' => route('reportes.conductores')],
        ['name' => 'Ingreso-Salida conductores'],
    ]" />
<br>
@endsection
    
@section('content')
<div class="container-fluid tableContainer p-0 border rounded" style="min-height:150px; background-color: white">
    <div class="border rounded-top d-flex justify-content-between align-items-center px-4 bg-secondary">
        <span class="text-left text-light fs-5 fw-medium py-1">Resultado ingreso-salida conductores</span>
        <a class="btn fw-bold my-2 bg-primary" href="{{ route('reportes.conductores') }}">Volver</a>
    </div>

    <button id="exportar" 
        onclick="exportarExcel('exportar', `{{ route('ingresoSalida.cargarData') }}` , 'Informe_ingreso_salida_conductores')"
        class="btn fw-bold ms-4 mt-4 bg-success botonBoostrapTable"> 
        Descargar Excel 
    </button>
   
    <div style="padding:1.5em">
        <table
            id="ingresoSalidaTable"
            class="table table-sm table-striped"
            data-page-size="25"
            data-toggle="table"
            data-locale="es-ES"
            data-search="true"
            data-pagination="true"
            data-detail-view="true"
            data-detail-formatter="detalleingresoSalida"
            data-check-on-init="true"
            data-url="{{ route('ingresoSalida.cargarData') }}">   
            <thead class="table-primary">
                <tr class="bg-primary">
                    <th data-field="identificacion" data-sortable="true">Identificación</th>
                    <th data-field="codigo" data-sortable="true">Código</th>
                    <th class="text-nowrap" data-field="nombre_completo" data-sortable="true">Conductor</th>
                    <th class="text-nowrap" data-field="evento" data-sortable="true">Evento</th>
                    <th class="text-nowrap" data-field="fecha_evento" data-sortable="true">Fecha de evento</th>
                    <th class="text-nowrap" data-field="agencia_registra_evento" data-sortable="true">Agencia</th>
                    <th class="text-nowrap" data-field="nombre_asociado" data-sortable="true">Nombre Asociado</th>
                    <th class="text-nowrap" data-field="vehiculo" data-sortable="true">Vehículo</th>
                </tr>
            </thead>
        </table>
    </div>
</div>
@endsection 

@pushOnce('script')
    @vite(['resources/js/cargarModal.js'])
    <script>
        document.addEventListener("DOMContentLoaded", () => {
            initTablaBootstrapTable(
                '#ingresoSalidaTable',
                { protegidas: ['identificacion'] },
                'detalleingresoSalida'
            );
        });
    </script>
@endpushOnce

