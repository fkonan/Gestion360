@extends('layouts.dashboard')

@section('title','Lista firmas')

@section('breadcrumb')
<x-breadcrumb :items="[
        ['name' => 'Inicio', 'url' => route('home')],
        ['name' => 'Reportes', 'url' => route('reportes.index')],
        ['name' => 'Firmas equipaje'],
    ]" />
<br>
@endsection
    
@section('content')
<div class="container-fluid p-0 border rounded" style="min-height:150px; background-color: white">
    <div class="border rounded-top d-flex justify-content-between align-items-center px-4 bg-secondary">
        <span class="text-left text-light fs-5 fw-medium py-1">Resultado firma equipaje</span>
    </div>

    <button id="exportar" 
        onclick="exportarExcel('exportar', `{{ route('firmaEquipaje.cargarData') }}` , 'Firmas_Conductores_Politica_Equipaje')"
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
            data-url="{{ route('firmaEquipaje.cargarData') }}"
            data-pagination-parts="['pageSize', 'pageList', 'pageNext', 'pagePrev']">   
            <thead class="table-primary">
                <tr class="bg-primary">
                    <th data-field="NomCon" data-sortable="true">Nombre y apellidos</th>
                    <th data-field="DocCon" data-sortable="true">Identificación</th>
                    <th data-field="CodCon" data-sortable="true">Codigo</th>
                    <th data-field="FirFecReg" data-sortable="true">Fecha firma</th>
                    <th data-field="FirHorReg" data-sortable="true">Hora firma</th>
                </tr>
            </thead>
        </table>
    </div>
</div>
@endsection 

@pushOnce('script')
    @vite(['resources/js/cargarModal.js'])
@endpushOnce

