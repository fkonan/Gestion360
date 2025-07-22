@extends('layouts.dashboard')

@section('title','Reportes')
    
@section('breadcrumb')
<x-breadcrumb :items="[
        ['name' => 'Inicio', 'url' => route('home')],
        ['name' => 'Reportes'],
    ]" />
@endsection

@section('content')
<br>
<div class="container-fluid p-0 border rounded sidebar-dark-primary tableContainer" style="min-height:150px;">

    <x-sectionHeader 
        titulo="Panel de Reportes"
        rutaVolver="{{ route('home') }}"
    />

    <div style="padding:1.5em" class="mt-md-5">
        <div class="row row-cols-1 row-cols-md-3 g-3 mt-2">
            @foreach ($reportes as $reporte)
                @permite($reporte['permiso'])
                    <div class="col" style="max-width:400px">
                        <x-card color="bg-teriary" 
                            titulo="{{ $reporte['titulo'] }}" 
                            descripcion="{{ $reporte['descripcion'] }}" 
                            tooltip="{{ $reporte['tooltip'] }}"
                            icono="{{ $reporte['icono'] }}" 
                            ruta="{{ route($reporte['ruta']) }}"
                        />
                    </div>
                @endpermite
            @endforeach
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
