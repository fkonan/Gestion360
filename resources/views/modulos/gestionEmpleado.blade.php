@extends('layouts.dashboard')

@section('title','Gestión empleados')

@section('breadcrumb')
<x-breadcrumb :items="[
        ['name' => 'Inicio', 'url' => route('home')],
        ['name' => 'Gestión Empleado'],
    ]" />
@endsection
    
@section('content')
<div class="container optionsMenu mt-4 p-0" style="max-width: 1500px;">
    <div class="row row-cols-1 row-cols-md-2 row-cols-lg-4 g-2">
        <div class="col">
            <x-card color="bg-success" 
                    titulo="Incapacidades" 
                    descripcion="Gestión" 
                    icono="fa-procedures" 
                    ruta="{{ route('gestion-empleado.incapacidades') }}"
            />
        </div>
        <div class="col">
            <x-card color="bg-warning" 
                    titulo="Incapacidades" 
                    descripcion="Seguimiento" 
                    icono="fa-phone" 
                    ruta="{{ route('gestion-empleado.seguimiento') }}"
            />
        </div>

        <div class="col" onclick="cargarModal(`{{ route('conductor.descanso') }}`, 'Aqui puede registrar eventos no reportados de descanso de conductores','#descansoConductorForm','modal-md')">
            <x-card color="bg-secondary" 
                    titulo="Descanso Conductores"
                    descripcion="Gestión"
                    icono="fas fa-bed"
                    ruta="#"
            />
        </div>

        <div class="col" onclick="cargarModal(`{{ route('politicas.index') }}`, 'Politicas firmadas por el conductor','#formPoliticas','modal-lg')">
            <x-card color="bg-danger" 
                    titulo="Firmas conductores" 
                    descripcion="Politicas" 
                    icono="fa-signature" 
                    ruta="#"
            />
        </div>

        <div class="col" onclick="cargarModal(`{{ route('empleado.nuevoIngreso') }}`, 'Solicitud nuevo ingreso empleado','#formNuevoIngreso','modal-md')">
            <x-card color="bg-success" 
                    titulo="Solicitud nuevo ingreso" 
                    descripcion="Gestión" 
                    icono="fa-user-plus" 
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
            const opciones = document.getElementById('opciones');
            const opcionEvento = document.getElementById('opcionEvento');
            const opcionEventoLabel = document.getElementById('opcionEventoLabel');
            const evento = document.getElementById('evento');
            
            opciones.style.display = 'block';
            opcionEvento.style.display = 'block';

            if(evento.value == '50'){
                opcionEventoLabel.innerHTML = 'Fecha de salida a descanso';
            }else{
                opcionEventoLabel.innerHTML = 'Fecha de reingreso de descanso';
            }   
        }
    </script>
@endPushOnce