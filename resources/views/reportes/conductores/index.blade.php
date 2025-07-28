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
<br>
<div class="container-fluid p-0 border rounded sidebar-dark-primary tableContainer" style="min-height:150px">

    <x-sectionHeader 
        titulo="Reportes de Conductores"
        rutaVolver="{{ route('reportes.index') }}"
    />

    <div id="no-more-tables" class="table-responsive" style="padding:1.5em">
        <table 
            id="reportesConductores" 
            class="table table-sm table-striped table-hover align-middle mb-0" 
            data-toggle="table"
            data-locale="es-ES"
            data-search="true">

            <thead class="table-primary m-0 p-0 border-bottom">
                <tr>
                    <th>Reporte</th>
                    <th>Descripción</th>
                    <th>Acción</th> 
            </thead>
            <tbody id="lista-reportes">
                <x-reporteItem
                    titulo="Salidas a descanso"
                    descripcion="Reporte de conductores que han registrado ingresos y salidas para periodos de descanso"
                    icono="fas fa-fingerprint"
                    onclick="cargarModal(`{{ route('conductor.ingresoSalidas') }}`, 'Reporte salidas a descanso','#ingSalConForm','modal-lg')"
                />

               <!--  <x-reporteItem 
                    titulo="Actualización Estado Conductores en FICS"
                    descripcion="Reporte"
                    icono="fas fa-id-badge"
                    onclick="cargarModal(`{{ route('conductor.estado') }}`, 'Activacion o Suspensión Conductores','#estadoConductorForm','modal-md')"
                /> -->

                <x-reporteItem
                    titulo="Firma política de equipaje"
                    descripcion="Reporte de conductores que han firmado la política de equipaje para el transporte de pasajeros"
                    icono="fas fa-file-signature"
                    onclick="cargarModal(`{{ route('conductor.firmaEquipaje') }}`, 'Reporte firma política de equipaje','#firmaEquipajeForm','modal-md')"
                />

            <!--     <x-reporteItem
                    titulo="Descanso Conductores"
                    descripcion="Reportes"
                    icono="fas fa-bed"
                    onclick="cargarModal(`{{ route('conductor.descanso') }}`, 'Aqui puede registrar eventos no reportados de descanso de conductores','#descansoConductorForm','modal-md')"
                /> -->

                <x-reporteItem
                    titulo="Documentos conductores pasajes por vencer"
                    descripcion="Reporta conductores de pasajes activos con documentos próximos a vencer en un rango de fecha"
                    icono="fas fa-hourglass-half"
                    :onclick="'cargarModal(`' . route('reportes.formulario', ['id' => 5]) . '`, `Documentos conductores pasajes por vencer`, `#formReporte`, `modal-lg`)'"
                />

                <x-reporteItem
                    titulo="Documentos conductores carga por vencer"
                    descripcion="Reporta conductores de carga activos con documentos próximos a vencer en un rango de fecha"
                    icono="fas fa-hourglass-half"
                    :onclick="'cargarModal(`' . route('reportes.formulario', ['id' => 6]) . '`, `Documentos conductores carga por vencer`, `#formReporte`, `modal-lg`)'"
                />
            </tbody>
        </table>



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
