@extends('layouts.dashboard')

@section('title','Reportes')
    
@section('breadcrumb')
<x-breadcrumb :items="[
        ['name' => 'Inicio', 'url' => route('home')],
        ['name' => 'Reportes', 'url' => route('reportes.index')],
        ['name' => 'Conductores']
    ]" />
@endsection

@section('content')
<div class="container-fluid mt-5 mx-1 p-0 optionsMenu">
    <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 row-cols-xl-4 g-2">
        <div class="col-lg-4 col-xl-3" onclick="cargarModal(`{{ route('conductor.ingresoSalidas') }}`, 'Reporte de entradas y salidas conductores','#ingSalConForm','modal-lg')">
            <x-card color="bg-dark" 
                    titulo="Ingresos y Salidas Conductores" 
                    descripcion="Reporte" 
                    icono="fa-fingerprint" 
                    ruta="#"
            />
        </div>
        <div class="col-lg-4 col-xl-3" onclick="cargarModal(`{{ route('conductor.estado') }}`, 'Activacion o Suspensión Conductores','#estadoConductorForm','modal-md')">
            <x-card color="bg-dark" 
                    titulo="Actualización Estado Conductores en FICS" 
                    descripcion="Reporte" 
                    icono="fa-id-badge" 
                    ruta="#"
            />
        </div>
        <div class="col-lg-4 col-xl-3" onclick="cargarModal(`{{ route('conductor.firmaEquipaje') }}`, 'Reporte para listar los conductores que han firmado y/o aceptado la política de equipaje','#firmaEquipajeForm','modal-md')">
            <x-card color="bg-dark" 
                    titulo="Firma Politica Equipaje" 
                    descripcion="Reporte" 
                    icono="fa-file-signature" 
                    ruta="#"
            />
        </div>
        <div class="col-lg-4 col-xl-3" onclick="cargarModal(`{{ route('conductor.descanso') }}`, 'Aqui puede registrar eventos no reportados de descanso de conductores','#descansoConductorForm','modal-md')">
            <x-card color="bg-dark" 
                    titulo="Descanso Conductores" 
                    descripcion="Reportes" 
                    icono="fa-bed" 
                    ruta="#"
            />
        </div>
        <div class="col-lg-4 col-xl-3">
            <x-card color="bg-dark" 
                    titulo="Documentos Conductores Pasajes por Vencer" 
                    descripcion="Reporte" 
                    icono="fa-hourglass-half" 
                    ruta="#"
            />
        </div>
        <div class="col-lg-4 col-xl-3">
            <x-card color="bg-dark" 
                    titulo="Documentos Conductores Carga por Vencer" 
                    descripcion="Reporte" 
                    icono="fa-hourglass-half" 
                    ruta="#"
            />
        </div>
    </div>
</div> 
@endsection

@pushOnce('script')
    @vite(['resources/js/cargarModal.js'])
    <script>
        //para el reporte descanso conductores
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

        //para el reporte ingreso y salidas conductores
        function habilitarInputFiltro(checkElement){
            const parametro = document.getElementById('parametro');
            const parametroLabel = parametro ? parametro.querySelector('label') : null;

            if (checkElement.id === 'filtroCedula') {
                parametro.style.display = '';
                parametroLabel.innerHTML = 'Ingrese la cédula';

            } else if (checkElement.id === 'filtroCodigo') {
                parametro.style.display = '';
                parametroLabel.innerHTML = 'Ingrese el código';

            } else if (checkElement.id === 'filtroTodos') {
                parametro.style.display = 'none';
            }
        }
    </script>
@endpushOnce
