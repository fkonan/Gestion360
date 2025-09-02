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
<div class="container-fluid p-0 border rounded sidebar-dark-primary tableContainer" style="min-height:150px">

     <x-sectionHeader 
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
                    <th>Número</th>
                    <th>Reporte</th>
                    <th>Área</th>
                    <th>Descripción</th>
                    <th>Formato de salida</th>
                    <th>Acción</th> 
                </tr>
            </thead>
            <tbody id="lista-reportes">
                <x-reporteItem
                    consecutivo="1"
                    area="Unidad pasajes"
                    titulo="Impresión de tiquetes" 
                    descripcion="Visualiza y exporta el reporte de tiquetes impresos en un rango de fechas"
                    formato="fas fa-file-excel fa-2x"
                    :onclick="'cargarModal(`' . route('reportes.tiquetes') . '`, `Impresión de tiquetes`, `#fechasReporteForm`, `modal-lg`)'"
                />
                <x-reporteItem
                    consecutivo="2"
                    area="Unidad pasajes"
                    titulo="Esquema tarifario de pasajes" 
                    descripcion="Visualiza y exporta el detalle de tarifas aplicadas a los pasajes en diferentes rutas y fechas"
                    formato="fas fa-file-excel fa-2x"
                    :onclick="'cargarModal(`' . route('esquemaTarifario.index') . '`, `Esquema tarifario pasajes`, `#esquemaTarifarioForm`, `modal-lg`)'"
                />
                <x-reporteItem
                    consecutivo="3"
                    area="Unidad pasajes"
                    titulo="Pasajes vendidos manuales" 
                    descripcion="Reporte con detalle de pasajes vendidos manuales en un rango de fecha"
                    formato="fas fa-file-excel fa-2x"
                    :onclick="'cargarModal(`' . route('reportes.formulario', ['id' => 2]) . '`, `Pasajes vendidos manuales`, ``, `modal-lg`)'"
                />
                <x-reporteItem
                    consecutivo="4"
                    area="Unidad pasajes"
                    titulo="Pasajes sin facturar" 
                    descripcion="Reporte de pasajes vendidos sin facturar en un rango de fecha"
                    formato="fas fa-file-excel fa-2x"
                    :onclick="'cargarModal(`' . route('reportes.formulario', ['id' => 3]) . '`, `Pasajes sin facturar`, ``, `modal-lg`)'"
                />
                <x-reporteItem
                    consecutivo="5"
                    area="Unidad pasajes"
                    titulo="Errores en documentos o nombres en tiquetes" 
                    descripcion="Tiquetes con errores en el número de documento o nombre/apellido del cliente, por fecha de operación"
                    formato="fas fa-file-excel fa-2x"
                    :onclick="'cargarModal(`' . route('reportes.formulario', ['id' => 4]) . '`, `Errores en documentos`, ``, `modal-lg`)'"
                />
            </tbody>
        </table>
    </div>
</div>

@endsection

@pushOnce('script')
    @vite(['resources/js/cargarModal.js'])
@endpushOnce


