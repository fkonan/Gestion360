@extends('layouts.dashboard')

@section('title','Reportes')
    
@section('breadcrumb')
<x-breadcrumb :items="[
        ['name' => 'Inicio', 'url' => route('home')],
        ['name' => 'Reportes'],
    ]" />
@endsection

@section('content')
<div class="container py-4">
    <div class="mb-4">
        <h2 class="fw-bold">Panel de Reportes</h2>
        <p class="text-muted mb-0">
            Accede a los distintos reportes del sistema según tu rol y permisos. Esta vista centraliza todos los tipos de reportes disponibles en la plataforma.
        </p>
    </div>

    <div class="row row-cols-1 row-cols-md-3 g-3">
        @foreach ($reportes as $reporte)
            @permite($reporte['permiso'])
            <div class="col">
                <a href="{{ route($reporte['ruta']) }}" class="text-decoration-none">
                    <div class="card h-100 shadow-sm border-0">
                        <div class="card-body">
                            <h5 class="card-title fw-semibold">{{ $reporte['titulo'] }}</h5>
                            <p class="card-text text-muted">{{ $reporte['descripcion'] }}</p>
                        </div>
                        <div class="card-footer bg-transparent border-0 text-end">
                            <span class="text-primary">Ver reportes <i class="bi bi-arrow-right"></i></span>
                        </div>
                    </div>
                </a>
            </div>
            @endpermite
        @endforeach
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
