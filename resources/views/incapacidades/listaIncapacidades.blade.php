@extends('layouts.dashboard')

@section('title','Incapacidades')

@section('breadcrumb')
<x-breadcrumb :items="[
        ['name' => 'Inicio', 'url' => route('home')],
        ['name' => 'Gestión Empleado', 'url' => route('gestion-incapacidades.index')],
        ['name' => 'Incapacidades'],
    ]" />
<br>
@endsection
    
@section('content')
<div class="container-fluid p-0 border shadow rounded bg-white" style="min-height:150px;">

    <x-cardHeader 
        titulo="Incapacidades"
        rutaVolver="{{ route('gestion-incapacidades.index') }}"
    />

    <div style="padding:1.5em">
        <table
            id="incapacidadesDataTable"
            class="table table-sm table-striped"
            data-page-size="10"
            data-toggle="table"
            data-locale="es-ES"
            data-search="true"
            data-pagination="true"
            data-check-on-init="true"
            data-detail-view="true"
            data-detail-formatter="detalleIncapacidad"
            data-url="{{ route('gestion-incapacidades.incapacidades.cargarDatos') }}"        
            data-pagination-parts="['pageSize', 'pageList', 'pageNext', 'pagePrev']">   
            <thead class="table-primary">
                <tr class="bg-primary">
                    <th class="text-nowrap" data-field="IncPerNom">Nombre</th>
                    <th data-field="PerNumDoc">Documento</th>
                    <th class="text-nowrap" data-field="causaDes">Causa Incapacidad</th>
                    <th data-field="DiagnosticoCod">Codigo CIE</th>
                    <th class="text-nowrap" data-field="epsNombre">EPS</th>
                    <th class="text-nowrap" data-field="arlNombre">ARL</th>
                    <th class="text-nowrap" data-field="TipoIncapacidad">Tipo Incapacidad</th>
                    <th class="text-center" data-field="IncFecIni" data-sortable="true">Fecha Inicio</th>
                    <th class="text-center" data-field="IncFecFin" data-sortable="true">Fecha Fin</th>
                    <th class="text-nowrap" data-field="DiagnosticoDes">Diagnóstico</th>
                    <th class="text-center" data-field="diasIncapacidad" data-formatter="diasIncapacidadFormatter">Días de Incapacidad</th>
                    <th class="text-center" data-field="IncFecReg">Fecha Registro</th>
                    <th class="text-center" data-field="IncHorReg">Hora Registro</th>
                    <th data-field="IncapacidadEstado">Estado</th>
                    <th data-field="IdIncapacidad" data-formatter="accionesFormatter">Acciones</th>
                </tr>
            </thead>
        </table>
    </div>
</div>
@endsection 

@pushOnce('script')
    @vite(['resources/js/cargarModal.js'])
    <script>
        function diasIncapacidadFormatter(value, row) {
            const fechaInicio = new Date(row.IncFecIni);
            const fechaFin = new Date(row.IncFecFin);
            const dias = Math.ceil((fechaFin - fechaInicio) / (1000 * 60 * 60 * 24)) + 1;
            return dias;
        };
        
        function accionesFormatter(index, row) {
            let fechaInicio = new Date(row.IncFecIni);
            let fechaFin = new Date(row.IncFecFin);

            // Ruta para cargar los adjuntos de la incapacidad con el ID correspondiente
            const urlAdjuntos = "{{ route('gestion-incapacidades.seguimiento.adjuntos', ['id' => ':id']) }}".replace(':id', row.IdIncapacidad);
            const urlDatos = "{{ route('gestion-incapacidades.incapacidades.edit', ['id' => ':id']) }}".replace(':id', row.IdIncapacidad);
            const urlGestion = "{{ route('gestion-incapacidades.incapacidades.gestion', ['id' => ':id']) }}".replace(':id', row.IdIncapacidad);

            return `
                <div class="d-flex flex-wrap gap-3 justify-content-center">
                    <a class="text-decoration-none" 
                        title="Ver adjuntos"
                        onclick="cargarModal('${urlAdjuntos}', 'Documentos Incapacidad', '', 'modal-lg')">     
                        <img src="https://autogestion.copetran.com.co/gestion/aFrame/library/bower_components/Ionicons/png/512/carpetas.png" alt="Ver Adjuntos" style="width: 32px; height: 32px;">
                    </a>
                    <a class="text-decoration-none" 
                        title="Editar incapacidad"
                        onclick="cargarModal('${urlDatos}', 'Revisión datos incapacidad', '#formIncapacidad', 'modal-xl')">
                        <img src="https://autogestion.copetran.com.co/gestion/aFrame/library/bower_components/Ionicons/png/512/new_editar.png" alt="Revisión datos" style="width: 32px; height: 32px;">
                    </a>
                    ${row.RevisionDatos == 1 ? `
                    <a class="text-decoration-none" 
                        title="Gestionar el radicado"
                        onclick="cargarModal('${urlGestion}', 'Gestion Incapacidad', '#formGestionIncapacidad', 'modal-lg')">
                        <img src="https://autogestion.copetran.com.co/gestion/aFrame/library/bower_components/Ionicons/png/512/aprobarrechazar.png" alt="AprobarRechazar" style="width: 32px; height: 32px;">
                    </a>` : ''}
                </div>`;
        }

        function habilitarInputs() {
            document.querySelectorAll('#formIncapacidad input, #formIncapacidad select').forEach(element => {
                if (element.id === 'IdIncapacidad') { return; }
                element.disabled = false;
            });
        }

        function mostrarObservacion() {
            const selectedOption = document.querySelector('input[name="IncapacidadEstado"]:checked').value;
            
            if (selectedOption === 'RECHAZADO') {
                document.getElementById('observacionDiv').style.display = 'block';
                document.getElementById('observacionDiv').required = true;
            } else {
                document.getElementById('observacionDiv').style.display = 'none';
                document.getElementById('observacionDiv').required = false;
            }
        }

        document.addEventListener("DOMContentLoaded", () => {
            initTablaBootstrapTable(
                '#incapacidadesDataTable', 
                { protegidas: ['PerNumDoc'] }, 
                'detalleIncapacidad', 
                { 'IdIncapacidad': accionesFormatter,
                    'diasIncapacidad': diasIncapacidadFormatter
                }
            );
        });
    
    </script>
@endpushOnce

