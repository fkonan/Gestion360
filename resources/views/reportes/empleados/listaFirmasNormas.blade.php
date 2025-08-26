@extends('layouts.dashboard')

@section('title','Lista firmas')

@section('breadcrumb')
<x-breadcrumb :items="[
        ['name' => 'Inicio', 'url' => route('home')],
        ['name' => 'Reportes', 'url' => route('reportes.index')],
        ['name' => 'Empleados', 'url' => route('reportes.empleados')],
        ['name' => 'Firmas preingreso SARLAFT'],
    ]" />
<br>
@endsection
    
@section('content')
<div class="container-fluid tableContainer p-0 border rounded sidebar-dark-primary" style="min-height:150px">

    <x-sectionHeader 
        titulo="Resultado firma normas SARLAFT empleados"
        rutaVolver="{{ route('reportes.empleados') }}"
        excel="true"
        excelRoute="{{ route('firmaNormas.cargarData') }}{{ request()->getQueryString() ? '?' . request()->getQueryString() : '' }}"
        excelName="Firmas_Empleados_Politica_Preingreso"
    />
   
    <div style="padding:1.5em">
        <table
            id="reporteFirmasNormas"
            class="table table-sm table-striped"
            data-page-size="25"
            data-toggle="table"
            data-locale="es-ES"
            data-search="true"
            data-pagination="true"
            data-detail-view="true"
            data-detail-formatter="detalleReporteFirmaNormas"
            data-check-on-init="true"
            data-url="{{ route('firmaNormas.cargarData') }}{{ request()->getQueryString() ? '?' . request()->getQueryString() : '' }}">
            <thead class="table-primary">
                <tr class="bg-primary">
                    <th data-field="identificacion" data-sortable="true">Identificación</th>
                    <th data-field="nombre_completo" data-sortable="true">Nombre Completo</th>
                    <th data-field="correo" data-sortable="true">Correo</th>
                    <th data-field="firma_ip" data-sortable="true">Firma IP</th>
                    <th class="text-center" data-field="fecha_registro" data-sortable="true">Fecha firma</th>
                    <th class="text-center" data-field="hora_registro" data-sortable="true">Hora firma</th>
                    <th class="text-center" 
                        data-field="acciones" 
                        data-formatter="comprobantePDF" >
                        Acciones
                    </th>

                </tr>
            </thead>
        </table>
    </div>
</div>
@endsection 

@pushOnce('script')
    @vite(['resources/js/cargarModal.js'])
    <script>
        document.addEventListener("DOMContentLoaded", () => {
        initTablaBootstrapTable(
            '#reporteFirmasNormas', 
            { protegidas: ['NombreCompleto'] }, 
            'detalleReporteFirmaNormas',
            { 'acciones': comprobantePDF }
        );
    });

    function comprobantePDF(value, row) {
        const url = `{{ route('firmaNormas.comprobantePDF', ':id') }}`.replace(':id', row.Id);
        return `
            <a href="${url}" target="_blank" title="Descargar comprobante">
                <img src="{{ asset('img/descargarPDF.png') }}" 
                     alt="Descargar PDF" 
                     style="width:35px;height:35px;">
            </a>
        `;
    }
    </script>
@endpushOnce

