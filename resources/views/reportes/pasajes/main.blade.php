@extends('layouts.dashboard')

@section('title','Reportes')
    
@section('breadcrumb')
<x-breadcrumb :items="[
        ['name' => 'Inicio', 'url' => route('home')],
        ['name' => 'Reportes', 'url' => route('reportes.index')],
        ['name' => 'Pasajes']
    ]" />
@endsection

@section('content')
<br>
<div class="container-fluid p-0 border rounded tableContainer" style="min-height:150px; background-color: white">

     <x-cardHeader 
        titulo="Reportes de Pasajes"
        rutaVolver="{{ route('reportes.index') }}"
    />

    <div id="no-more-tables" class="table-responsive" style="padding:1.5em">
        <table 
            id="reportesPasajes" 
            class="table table-sm table-striped table-hover align-middle mb-0" 
            data-toggle="table"
            data-locale="es-ES"
            data-search="true">

            <thead class="table-primary m-0 p-0 border-bottom">
                <tr>
                    <th>Reporte</th>
                    <th>Descripción</th>
                    <th>Acción</th>
                </tr>
            </thead>
            <tbody id="lista-reportes">
                <x-reporteItem
                    titulo="Impresión de tiquetes" 
                    descripcion="Visualiza y exporta el reporte de tiquetes impresos en un rango de fechas"
                    :onclick="'cargarModal(`' . route('reportes.tiquetes') . '`, `Impresión de tiquetes`, `#fechasReporteForm`, `modal-lg`)'"
                />
                <x-reporteItem
                    titulo="Esquema tarifario de pasajes" 
                    descripcion="Visualiza y exporta el detalle de tarifas aplicadas a los pasajes en diferentes rutas y fechas"
                    :onclick="'cargarModal(`' . route('esquemaTarifario.index') . '`, `Esquema tarifario pasajes`, `#esquemaTarifarioForm`, `modal-lg`)'"
                />
                <x-reporteItem
                    titulo="Pasajes vendidos manuales" 
                    descripcion="Reporte con detalle de pasajes vendidos manuales en un rango de fecha"
                />
                <x-reporteItem
                    titulo="Pasajes sin facturar" 
                    descripcion="Reporte de pasajes vendidos sin facturar en un rango de fecha"
                />
                <x-reporteItem
                    titulo="Errores en documentos o nombres en tiquetes" 
                    descripcion="Tiquetes con errores en el número de documento o nombre/apellido del cliente, por fecha de operación"
                />
            </tbody>
        </table>
    </div>
</div>

@endsection

@pushOnce('script')
    @vite(['resources/js/cargarModal.js'])
@endpushOnce


