@extends('layouts.dashboard')

@section('title','Seguimiento incapacidades')

@section('breadcrumb')
<x-breadcrumb :items="[
        ['name' => 'Inicio', 'url' => route('home')],
        ['name' => 'Gestión Empleado', 'url' => route('gestion-incapacidades.index')],
        ['name' => 'Seguimiento Incapacidades'],
    ]" />
<br>
@endsection
    
@section('content')
<div class="container-fluid p-0 border rounded" style="min-height:150px; background-color: white">
    <div class="border rounded-top d-flex justify-content-between align-items-center px-4 bg-secondary">
        <span class="text-left text-light fs-5 fw-medium py-1">Seguimiento incapacidades</span>
        <a class="btn fw-bold my-2 bg-primary" onclick="window.history.back()">Volver</a>
    </div>

    <div class="row p-4 g-2">
        <table
            id="incapacidadesDataTable"
            class="table table-sm table-striped"
            data-page-size="25"
            data-toggle="table"
            data-locale="es-ES"
            data-search="true"
            data-pagination="true"
            data-sort-name="IncFecIni"
            data-sort-order="desc"
            data-detail-view="true"
            data-detail-formatter="detalleIncapacidad"
            data-check-on-init="true"
            data-url="{{ route('gestion-incapacidades.seguimiento.cargarDatos') }}">   
            <thead class="table-primary">
                <tr>
                    <th data-field="IncPerNom" >Nombre</th>
                    <th data-field="PerNumDoc">Documento</th>
                    <th data-field="causaDes">Causa Incapacidad</th>
                    <th data-field="epsNombre">EPS</th>
                    <th data-field="arlNombre">ARL</th>
                    <th data-field="IncFecIni" data-sortable="true">Fecha Inicio</th>
                    <th data-field="IncFecFin" data-sortable="true">Fecha Fin</th>
                    <th data-field="DiagnosticoDes">Diagnóstico</th>
                    <th data-field="diasIncapacidad" data-formatter="diasIncapacidadFormatter">Días Incapacidad</th>
                    <th data-field="IncapacidadEstado">Estado</th>
                    <th data-field="IncFecReg">Fecha Registro</th>
                    <th data-field="IncHorReg">Hora Registro</th>
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

        function accionesFormatter(value, row) {
            const urlAdjuntos = "{{ route('gestion-incapacidades.seguimiento.adjuntos', ['id' => ':id']) }}".replace(':id', value);
            const urlDatos = "{{ route('gestion-incapacidades.incapacidades.edit', ['id' => ':id']) }}".replace(':id', value);
            const urlSeguimientoDetalle = "{{ route('gestion-incapacidades.seguimiento.detalle', ['id' => ':id']) }}".replace(':id', value);

            return `
                <div class="d-flex flex-wrap gap-3 justify-content-start ps-3">
                    <a class="text-decoration-none"  title="Ver adjuntos"
                        onclick="cargarModal('${urlAdjuntos}', 'Documentos Incapacidad', '', 'modal-lg')">
                        <img src="https://autogestion.copetran.com.co/gestion/aFrame/library/bower_components/Ionicons/png/512/carpetas.png" 
                            alt="Ver Adjuntos" style="width: 28px; height: 28px;">
                    </a>
                    <a class="text-decoration-none"  title="Editar incapacidad"
                        onclick="cargarModal('${urlDatos}', 'Revisión datos incapacidad', '#formIncapacidad', 'modal-xl')">
                        <img src="https://autogestion.copetran.com.co/gestion/aFrame/library/bower_components/Ionicons/png/512/new_editar.png" 
                            alt="Editar" style="width: 28px; height: 28px;">
                    </a>
                    <a class="text-decoration-none"  title="Seguimiento"
                        href="${urlSeguimientoDetalle}">
                        <img src="https://autogestion.copetran.com.co/gestion/aFrame/library/bower_components/Ionicons/png/512/seguimiento.png" 
                            alt="Seguimiento" style="width: 28px; height: 28px;">
                    </a>
                </div>
            `;
        };

        document.addEventListener("DOMContentLoaded", () => {
            initTablaBootstrapTable(
                '#incapacidadesDataTable', 
                { protegidas: ['Nombre'] }, 
                'detalleIncapacidad', 
                { 'IdIncapacidad': accionesFormatter,
                    'diasIncapacidad': diasIncapacidadFormatter
                }
            );
        });


    </script>
@endpushOnce