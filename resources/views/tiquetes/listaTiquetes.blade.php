@extends('layouts.dashboard')

@section('title','Resultado Tiquetes')

@section('breadcrumb')
<x-breadcrumb :items="[
        ['name' => 'Inicio', 'url' => route('home')],
        ['name' => 'Reportes', 'url' => route('reportes.index')],
        ['name' => 'Resultado Tiquetes'],
    ]" />
<br>
@endsection
    
@section('content')
<div class="container-fluid p-0 border rounded" style="min-height:150px; background-color: white">
    <div class="border rounded-top d-flex justify-content-between align-items-center px-4 bg-secondary">
        <span class="text-left text-light fs-5 fw-medium py-1">Resultado Tiquetes</span>
        <a class="btn fw-bold my-2 bg-primary" onclick="window.history.back()">Volver</a>
    </div>

    <button id="exportar"
        onclick="exportarExcel('exportar', `{{ route('reportes.cargarData') }}` , 'Tiquetes_impresos')" 
        class="btn fw-bold ms-4 mt-4 bg-success botonBoostrapTable"> 
        Descargar Excel 
    </button>

    <div class="row p-4 g-2">
        <table
            id="miTabla"
            class="table table-sm table-striped"
            data-page-size="25"
            data-toggle="table"
            data-locale="es-ES"
            data-search="true"
            data-pagination="true"
            data-detail-view="true"
            data-check-on-init="true"
            data-detail-formatter="detalleTiquete"
            data-detail-filter="filtrarDetalle"
            data-url="{{ route('reportes.cargarData') }}">   
            <thead class="table-primary">
                <tr class="bg-primary">
                    <th data-field="NumDocPer">Documento</th>
                    <th data-field="NumeroPasaje">Tiquete</th>
                    <th data-field="TerminalOrigen">Terminal Origen</th>
                    <th data-field="TerminalDestino">Terminal Destino</th>
                    <th data-field="Agencia">Agencia</th>
                    <th data-field="FechaSalida" data-sortable="true">Fecha Salida</th>
                    <th data-field="NumerodeViaje" data-force-hide="true">Numero de Viaje</th>
                    <th data-field="PrecioBase" data-sortable="true">Precio Base</th>
                    <th data-field="Descuento" data-sortable="true">Descuento</th>
                    <th data-field="PrecioTotal" data-sortable="true">Precio Total</th>
                    <th data-field="Asiento" data-sortable="true">Asiento</th>
                    <th data-field="ImpFecReg" data-sortable="true">Fecha Impresión</th>
                    <th data-field="ImpHorReg" data-sortable="true">Hora Impresión</th>
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
            initTablaBootstrapTable('#miTabla', {
                protegidas: ['NumDocPer']
            },'detalleTiquete');
        });
    </script>
@endpushOnce

