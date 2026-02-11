@extends('layouts.dashboard')

@section('title', 'Radicaciones de Facturas')

@section('breadcrumb')
<x-breadcrumb :items="[
    ['name' => 'Inicio', 'url' => route('home')],
    ['name' => 'Radicaciones de Facturas'],
]" />
<br>
@endsection

@section('content')
<div class="container-fluid p-0 border rounded sidebar-dark-primary tableContainer" style="min-height:150px;">

    <x-sectionHeader
        titulo="Radicaciones de Facturas"
        crear="newpage"
        rutaVolver="{{ route('home') }}"
        crearRoute="{{ route('radfact.radicaciones.create') }}"
        crearLabel="Nueva Radicación" />

    <div id="no-more-tables" class="table-responsive" style="padding:1.5em">
        <table
            id="radicacionesDataTable"
            class="table table-sm table-striped"
            data-toggle="table"
            data-page-size="25"
            data-search="true"
            data-locale="es-ES"
            data-pagination="true"
            data-responsive="true"
            data-detail-view="true"
            data-detail-formatter="detalleRadicacion"
            data-check-on-init="true"
            data-sort-name='fecha_radicacion'
            data-sort-order='desc'
            data-side-pagination="server"
            data-url="{{ route('radfact.radicaciones.cargarDatos') }}">
            <thead class="table-primary">
                <tr>
                    <th data-field="num_factura">N° Factura</th>
                    <th data-field="proveedor">Proveedor</th>
                    <th data-field="descripcion" data-order="4">Descripción</th>
                    <th data-field="valor" data-order="5">Valor</th>
                    <th class="text-center" data-field="fecha_radicacion" data-sortable="true">Fecha Radicación</th>
                    <th class="text-center" data-field="fecha_vencimiento" data-sortable="true" data-order="6">Fecha Vencimiento</th>
                    <th class="text-center" data-field="estado" data-formatter="estadoFormatter">Estado</th>
                    <th class="text-center" data-field="acciones" data-formatter="accionesFormatter">Acciones</th>
                </tr>
            </thead>
        </table>
    </div>
</div>
@endsection

@pushOnce('script')
@vite(['resources/js/cargarModal.js'])

<script>
    function estadoFormatter(value, row) {
        let badgeClass = 'bg-secondary';
        let estadoTexto = value;

        switch(value) {
            case 'RADICADO':
                badgeClass = 'bg-info';
                estadoTexto = 'Radicado';
                break;
            case 'EN_APROBACION':
                badgeClass = 'bg-warning text-dark';
                estadoTexto = 'En Aprobación';
                break;
            case 'RECHAZADO':
                badgeClass = 'bg-danger';
                estadoTexto = 'Rechazado';
                break;
            case 'PENDIENTE_SUBGERENCIA':
                badgeClass = 'bg-primary';
                estadoTexto = 'Pte. Subgerencia';
                break;
            case 'COMPLETADO':
                badgeClass = 'bg-success';
                estadoTexto = 'Completado';
                break;
        }

        return `<span class="badge ${badgeClass}">${estadoTexto}</span>`;
    }

    function accionesFormatter(index, row) {
        let rutaVer = "{{ route('radfact.radicaciones.show', ['radicacion' => ':id']) }}";
        let rutaVerFinal = rutaVer.replace(':id', row.id);

        return `
            <div class="col-md-12 d-flex justify-content-center">
                <a href="${rutaVerFinal}"
                   class="sig-action-btn"
                   data-title="Ver"
                   aria-label="Ver"
                   style="cursor: pointer;"
                   title="Ver radicación">
                    <img src="{{ asset('img/verDetalle.png') }}" alt="Ver Radicación" style="width: 32px; height: 32px;">
                </a>
            </div>
        `;
    }

    document.addEventListener("DOMContentLoaded", () => {
        initTablaBootstrapTable(
            '#radicacionesDataTable',
            {
                protegidas: ['num_factura', 'proveedor']
            },
            'detalleRadicacion',
            {
                'acciones': accionesFormatter,
                'estado': estadoFormatter,
            }
        );
    });
</script>
@endpushOnce
