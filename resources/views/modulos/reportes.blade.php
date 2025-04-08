@extends('layouts.dashboard')

@section('title','Reportes')
    
@section('breadcrumb')
<x-breadcrumb :items="[
        ['name' => 'Home', 'url' => route('home')],
        ['name' => 'Reportes'],
    ]" />
@endsection

@section('content')
<div class="container-fluid pt-4" style="min-height:150px">
    <div class="row row-cols-1 row-cols-md-2 row-cols-lg-4">
        <div class="col" onclick="cargarModal(`{{ route('reportes.tiquetes') }}`, 'Impresión de Tiquetes','#fechasReporteForm','modal-lg')">
            <x-card color="bg-dark" 
                    titulo="Impresión de Tiquetes" 
                    descripcion="Reportes" 
                    icono="fa-ticket-alt" 
                    ruta="#"
            />
        </div>
        <div class="col">
            <x-card color="bg-dark" 
                    titulo="Esquema Tarifario Pasajes" 
                    descripcion="Reportes" 
                    icono="fa-suitcase-rolling" 
                    ruta="#"
            />
        </div>
        <div class="col">
            <x-card color="bg-dark" 
                    titulo="Ingresos y Salidas Conductores" 
                    descripcion="Reportes" 
                    icono="fa-fingerprint" 
                    ruta="#"
            />
        </div>
        <div class="col">
            <x-card color="bg-dark" 
                    titulo="Actualizacin Estado Conductores en FICS" 
                    descripcion="Reportes" 
                    icono="fa-id-badge" 
                    ruta="#"
            />
        </div>
        <div class="col">
            <x-card color="bg-dark" 
                    titulo="Firma Conductores" 
                    descripcion="Reportes" 
                    icono="fa-id-badge" 
                    ruta="#"
            />
        </div>
        <div class="col">
            <x-card color="bg-dark" 
                    titulo="Descanso Conductores" 
                    descripcion="Reportes" 
                    icono="fa-bed" 
                    ruta="#"
            />
        </div>
    </div>
</div>
@endsection

@pushOnce('script')
    @vite(['resources/js/cargarModal.js'])
@endpushOnce
