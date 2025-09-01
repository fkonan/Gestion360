@extends('layouts.dashboard')

@section('title','Lista ingreso/salida conductores')

@section('breadcrumb')
<x-breadcrumb :items="[
        ['name' => 'Inicio', 'url' => route('home')],
        ['name' => 'Reportes', 'url' => route('reportes.index')],
        ['name' => 'Conductores', 'url' => route('reportes.conductores')],
        ['name' => 'Descanso conductores'],
    ]" />
<br>
@endsection
    
@section('content')
<div class="container-fluid tableContainer p-0 border rounded sidebar-dark-primary" style="min-height:150px">

    <x-sectionHeader 
        titulo="Resultado descanso conductores"
        rutaVolver="{{ route('reportes.conductores') }}"
        excel="true"
        excelRoute="{{ route('ingresoSalida.cargarData') }}"
        excelName="Informe_descanso_conductores"
    />

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
                    <th data-formatter="numeroConsecutivo">Número</th>
                    <th data-field="IDENTIFICACION" data-sortable="true">Identificación</th>
                    <th data-field="CODIGO_CONDUCTOR" data-sortable="true">Código conductor</th>
                    <th class="text-nowrap" data-field="NOMBRE_COMPLETO" data-sortable="true">Nombre conductor</th>
                    <th class="text-nowrap" data-field="FECHA_SALIDA" data-sortable="true">Fecha salida</th>
                    <th class="text-nowrap" data-field="FECHA_REINTEGRO" data-sortable="true" data-formatter="formatearReintegro">Fecha reintegro</th>
                    <th class="text-nowrap" data-field="DIAS_DESCANSO" data-sortable="true" data-formatter="formatearDias">Dias descanso</th>
                    <th class="text-nowrap" data-field="VEHICULO" data-sortable="true">Vehículo</th>
                    <th class="text-nowrap" data-field="NOMBRE_ASOCIADO" data-sortable="true">Nombre asociado</th>
                </tr>
            </thead>
        </table>
    </div>
</div>
@endsection 

@pushOnce('script')
    @vite(['resources/js/cargarModal.js'])
    <script>
        function formatearReintegro(value) {
            return value ? value : 'En descanso';
        }

        function formatearDias(value) {
            return value ? value : 'No aplica';
        }

        function numeroConsecutivo(value, row, index) {
            return index + 1;
        }

        document.addEventListener("DOMContentLoaded", () => {
            initTablaBootstrapTable(
                '#ingresoSalidaTable',
                { protegidas: ['NOMBRE_COMPLETO'] },
                'detalleingresoSalida'
            );
        });
    </script>
@endpushOnce

