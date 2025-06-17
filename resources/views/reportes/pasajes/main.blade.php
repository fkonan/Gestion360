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
<div class="container-fluid mt-5 mx-1 p-0 optionsMenu">
    <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 row-cols-xl-4 g-2">
        <div class="col-lg-4 col-xl-3" onclick="cargarModal(`{{ route('reportes.tiquetes') }}`, 'Impresión de Tiquetes','#fechasReporteForm','modal-lg')">
            <x-card color="bg-dark" 
                    titulo="Impresión de Tiquetes" 
                    descripcion="Reporte" 
                    icono="fa-ticket-alt" 
                    ruta="#"
            />
        </div>
        <div class="col-lg-4 col-xl-3" onclick="cargarModal(`{{ route('esquemaTarifario.index') }}`, 'Esquema Tarifario Pasajes','#esquemaTarifarioForm','modal-lg')">
            <x-card color="bg-dark" 
                    titulo="Esquema Tarifario Pasajes" 
                    descripcion="Reporte" 
                    icono="fa-suitcase-rolling" 
                    ruta="#"
            />
        </div>
        <div class="col-lg-4 col-xl-3">
            <x-card color="bg-dark" 
                    titulo="Pasajes Vendidos Manuales" 
                    descripcion="Reporte" 
                    icono="fas fa-clipboard-list" 
                    ruta="#"
            />
        </div>
        <div class="col-lg-4 col-xl-3">
            <x-card color="bg-dark" 
                    titulo="Pasajes sin Facturar" 
                    descripcion="Reporte" 
                    icono="fas fa-file-excel" 
                    ruta="#"
            />
        </div>
        <div class="col-lg-4 col-xl-3">
            <x-card color="bg-dark" 
                    titulo="Errores en Documentos o Nombres en Tiquetes" 
                    descripcion="Reporte" 
                    icono="fas fa-exclamation-triangle" 
                    ruta="#"
            />
        </div>
    </div>
</div> 
@endsection

@pushOnce('script')
    @vite(['resources/js/cargarModal.js'])
@endpushOnce