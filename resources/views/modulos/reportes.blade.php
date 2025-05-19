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
        <div class="col" onclick="cargarModal(`{{ route('conductor.estado') }}`, 'Activacion o Suspensión Conductores','#estadoConductorForm','modal-md')">
            <x-card color="bg-dark" 
                    titulo="Actualización Estado Conductores en FICS" 
                    descripcion="Reportes" 
                    icono="fa-id-badge" 
                    ruta="#"
            />
        </div>
        <div class="col" onclick="cargarModal(`{{ route('conductor.firmaEquipaje') }}`, 'Reporte para listar los conductores que han firmado y/o aceptado la política de equipaje','#firmaEquipajeForm','modal-md')">
            <x-card color="bg-dark" 
                    titulo="Firma Conductores" 
                    descripcion="Reportes" 
                    icono="fa-id-badge" 
                    ruta="#"
            />
        </div>
        <div class="col" onclick="cargarModal(`{{ route('conductor.descanso') }}`, 'Aqui puede registrar eventos no reportados de descanso de conductores','#descansoConductorForm','modal-md')">
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
    <script>
        function habilitarOpciones() {   
            var opciones = document.getElementById('opciones');
            var opcionEvento = document.getElementById('opcionEvento');
            var opcionEventoLabel = document.getElementById('opcionEventoLabel');
            var evento = document.getElementById('evento');
            
            opciones.style.display = 'block';
            opcionEvento.style.display = 'block';

            if(evento.value == '50'){
                opcionEventoLabel.innerHTML = 'Fecha de salida a descanso';
            }else{
                opcionEventoLabel.innerHTML = 'Fecha de reingreso de descanso';
            }   
        }
    </script>
@endpushOnce
