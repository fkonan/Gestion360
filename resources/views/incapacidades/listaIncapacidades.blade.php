@extends('layouts.dashboard')

@section('title','Incapacidades')
    
@section('content')
<div class="container-fluid p-0 border rounded" style="min-height:150px">
    <div class="border rounded-top d-flex justify-content-between align-items-center px-4 bg-secondary">
        <span class="text-left text-light fs-5 fw-bold">Incapacidades</span>
        <a class="btn fw-bold my-2 text-light bg-primary" onclick="window.history.back()" >Volver</a>
    </div>

    <div class="row p-4">
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
            data-url="{{ route('gestion-incapacidades.incapacidades.cargarDatos') }}"        
            data-pagination-parts="['pageSize', 'pageList', 'pageNext', 'pagePrev']">   
            <thead class="table-primary">
                <tr class="bg-primary">
                    <th data-field="IncPerNom">Nombre</th>
                    <th data-field="PerNumDoc">Documento</th>
                    <th data-field="causa.ParDes">Causa Incapacidad</th>
                    <th data-field="diagnostico.DescCie">Diagnostico</th>
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
<script>
    function detalleIncapacidad(index, row) {
        return `
          <div class="p-3 border rounded bg-light">
            <div class="row">
                <div class="col-md-12">
                    <p><strong>Observación:</strong> ${row.Observacion}</p>
                    <p><strong>Estado:</strong> ${row.IncapacidadEstado}</p>
                    <p><strong>Fecha Registro:</strong> ${row.IncFecReg}</p>
                    <p><strong>Hora Registro:</strong> ${row.IncHorReg}</p>
                </div>
            </div>
        </div>
        `;
    }
</script>
@endpushOnce