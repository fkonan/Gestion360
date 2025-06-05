@extends('layouts.dashboard')

@section('title','Esquema tarifario')

@section('breadcrumb')
<x-breadcrumb :items="[
        ['name' => 'Inicio', 'url' => route('home')],
        ['name' => 'Reportes', 'url' => route('reportes.index')],
        ['name' => 'Esquema tarifario'],
    ]" />
<br>
@endsection

@section('content')
<div class="container-fluid p-0 border rounded" style="min-height:150px; background-color: white">

    <div class="border rounded-top d-flex justify-content-between align-items-center px-4 bg-secondary">
        <span class="text-left text-light fs-5 fw-medium py-1">Esquema tarifario</span>
        <a class="btn fw-bold my-2 bg-primary" onclick="window.history.back()">Volver</a>
    </div>

    <button id="exportar" 
        onclick="exportarExcel('exportar', `{{ route('esquemaTarifario.cargarData') }}` , 'Esquema_tarifario')"
        class="btn fw-bold ms-4 mt-4 bg-success botonBoostrapTable"> 
        Descargar Excel 
    </button>

    <div id="no-more-tables" class="table-responsive" style="padding:1.5em">
        <table
            id="esquemaTarifarioTable"
            class="table table-sm table-striped"
            data-page-size="25"
            data-toggle="table"
            data-locale="es-ES"
            data-search="true"
            data-detail-view="true"
            data-detail-formatter="detalleTarifario"
            data-pagination="true"
            data-url="{{ route('esquemaTarifario.cargarData') }}"
            data-check-on-init="true">   
            <thead class="table-primary">
                <tr>
                    <th data-field="origen">Origen</th>
                    <th data-field="destino">Destino</th>
                    <th data-field="servicio">Servicio</th>
                    <th data-field="precio" data-sortable="true" data-formatter="precioFormateo">Valor tarifa</th>
                    <th data-field="fechaInicial" data-sortable="true">Fecha inicial esquema</th>
                    <th data-field="fechaFinal" data-sortable="true">Fecha final esquema</th>
                    <th data-field="estado" data-formatter="estadoActual">Estado</th>
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
                '#esquemaTarifarioTable',
                { protegidas: ['origen'] },
                'detalleTarifario',
                { 'precio': precioFormateo,
                    'estado': estadoActual
                }
            );
        });

        function estadoActual(value, row, index) {
            return value == 0 ? 'VENCIDO' : 'VIGENTE';
        }

        function precioFormateo(value, row, index) {
            return parseInt(value);
        }
    </script>
@endpushOnce


