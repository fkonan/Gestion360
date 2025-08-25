@extends('layouts.dashboard')

@section('title','Reportes')
    
@section('breadcrumb')
<x-breadcrumb :items="[
        ['name' => 'Inicio', 'url' => route('home')],
        ['name' => 'Reportes', 'url' => route('reportes.index')],
        ['name' => 'Empleados']
    ]" />
@endsection

@section('content')
<br>
<div class="container-fluid p-0 border rounded sidebar-dark-primary tableContainer" style="min-height:150px">

     <x-sectionHeader 
        titulo="Reportes de empleados"
        rutaVolver="{{ route('reportes.index') }}"
    />

    <div id="no-more-tables" class="table-responsive" style="padding:1.5em">
        <table 
            id="reportesCarga" 
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
                    titulo="Firma empleados preingreso SIPLAFT" 
                    descripcion="Reporte de los empleados que firmaron las normas correspondiente preingreso a la empresa"
                    onclick="cargarModal(`{{ route('empleados.firmaNormas') }}`, 'Reporte firma normas SIPLAFT','#firmaNormasForm','modal-md')" 
                />
            </tbody>
        </table>
    </div>
</div>

@endsection

@pushOnce('script')
    @vite(['resources/js/cargarModal.js'])
@endpushOnce
