@extends('layouts.dashboard')

@section('title','Seguimiento incapacidades')

@section('breadcrumb')
<x-breadcrumb :items="[
        ['name' => 'Home', 'url' => route('home')],
        ['name' => 'Gestión Empleado', 'url' => route('gestion-incapacidades.index')],
        ['name' => 'Seguimiento Incapacidades'],
    ]" />
<br>
@endsection
    
@section('content')
<div class="container-fluid p-0 border rounded" style="min-height:150px; background-color: white">
    <div class="border rounded-top d-flex justify-content-between align-items-center px-4 bg-secondary">
        <span class="text-left text-light fs-5 fw-bold">Seguimiento incapacidades</span>
        <a class="btn fw-bold my-2 text-light bg-primary" onclick="window.history.back()" >Volver</a>
    </div>

    <div id="no-more-tables" class="row p-4">
        <table
            id="incapacidadesDataTable"
            class="table table-sm table-striped"
            data-page-size="10"
            data-toggle="table"
            data-locale="es-ES"
            data-search="true"
            data-pagination="true"
            data-detail-view="true"
            data-detail-formatter="detalleIncapacidad"
            data-url="{{ route('gestion-incapacidades.seguimiento.cargarDatos') }}"        
            data-pagination-parts="['pageSize', 'pageList', 'pageNext', 'pagePrev']">   
            <thead class="table-primary">
                <tr>
                    <th data-field="IncPerNom">Nombre</th>
                    <th data-field="PerNumDoc">Documento</th>
                    <th data-field="causa.ParDes">Causa Incapacidad</th>
                    <th data-field="eps.EPSNombre">EPS</th>
                    <th data-field="arl.ARLNombre">ARL</th>
                    <th data-field="IncFecIni">Fecha Inicio</th>
                    <th data-field="IncFecFin">Fecha Fin</th>
                </tr>
            </thead>
        </table>
    </div>
</div>
@endsection 

@pushOnce('script')
    @vite(['resources/js/cargarModal.js'])
    <script>
        var rutas = {
            adjuntos: "{{ route('gestion-incapacidades.seguimiento.adjuntos', ['id' => ':id']) }}",
            datos: "{{ route('gestion-incapacidades.incapacidades.edit', ['id' => ':id']) }}",
            seguimientoDetalle: "{{ route('gestion-incapacidades.seguimiento.detalle', ['id' => ':id']) }}"
        };

        function detalleIncapacidad(index, row) {
            let fechaInicio = new Date(row.IncFecIni);
            let fechaFin = new Date(row.IncFecFin);

            let diasIncapacidad = Math.ceil((fechaFin - fechaInicio) / (1000 * 60 * 60 * 24));
            let urlDatos = rutas.datos.replace(':id', row.IdIncapacidad);
            let urlAdjuntos = rutas.adjuntos.replace(':id', row.IdIncapacidad);
            let urlSeguimientoDetalle = rutas.seguimientoDetalle.replace(':id', row.IdIncapacidad);

            return `
            <div class="p-3 border rounded bg-light">
                <div class="row">
                    <div class="col-md-12">
                        <p><strong>Diagnóstico:</strong> ${row.diagnostico.DescCie}</p>
                        <p><strong>Días de Incapacidad:</strong> ${diasIncapacidad}</p>
                        <p><strong>Estado:</strong> ${row.IncapacidadEstado}</p>
                        <p><strong>Fecha Registro:</strong> ${row.IncFecReg}</p>
                        <p><strong>Hora Registro:</strong> ${row.IncHorReg}</p>
                        <div>
                            <strong>Acciones:</strong> 
                            <a class="ms-3 text-decoration-none" 
                                title="Haga click para ver los adjuntos"
                                onclick="cargarModal('${urlAdjuntos}', 'Documentos Incapacidad', '', 'modal-lg')">     
                                <img src="https://autogestion.copetran.com.co/gestion/aFrame/library/bower_components/Ionicons/png/512/carpetas.png" alt="Ver Adjuntos" style="width: 32px; height: 32px;">
                            </a>
                            <a class="ms-3 text-decoration-none" 
                                title="Haga click para editar la incapacidad"
                                onclick="cargarModal('${urlDatos}', 'Revisión datos incapacidad', '#formIncapacidad', 'modal-xl')">
                                <img src="https://autogestion.copetran.com.co/gestion/aFrame/library/bower_components/Ionicons/png/512/new_editar.png" alt="Revisión datos" style="width: 32px; height: 32px;">
                            </a>
                             <a class="ms-3 text-decoration-none" 
                                title="Haga click para ver el seguimiento a la incapacidad"
                                href="${urlSeguimientoDetalle}">
                                <img src="https://autogestion.copetran.com.co/gestion/aFrame/library/bower_components/Ionicons/png/512/seguimiento.png" alt="Seguimiento" style="width: 34px; height: 34px;">
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            `;
        }

        function habilitarInputs() {
            document.querySelectorAll('#formIncapacidad input, #formIncapacidad select').forEach(element => {
                if (element.id === 'IdIncapacidad') { return; }
                element.disabled = false;
            });
        }
    </script>
@endpushOnce