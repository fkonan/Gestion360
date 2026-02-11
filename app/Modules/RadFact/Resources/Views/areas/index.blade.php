@extends('layouts.dashboard')

@section('title', 'Áreas de Aprobación')

@section('breadcrumb')
<x-breadcrumb :items="[
    ['name' => 'Inicio', 'url' => route('home')],
    ['name' => 'Radicación de Facturas', 'url' => route('radfact.radicaciones.index')],
    ['name' => 'Áreas'],
]" />
<br>
@endsection

@section('content')
<div class="container-fluid p-0 border rounded sidebar-dark-primary tableContainer" style="min-height:150px;">

    <x-sectionHeader
        titulo="Áreas de aprobación"
        crear="newpage"
        rutaVolver="{{ route('radfact.radicaciones.index') }}"
        crearRoute="{{ route('radfact.areas.create') }}"
        crearLabel="Nueva Área" />

    <div id="no-more-tables" class="table-responsive" style="padding:1.5em">
        <table
            id="areasDataTable"
            class="table table-sm table-striped"
            data-toggle="table"
            data-page-size="25"
            data-search="true"
            data-locale="es-ES"
            data-pagination="true"
            data-responsive="true"
            data-detail-view="true"
            data-detail-formatter="detalleArea"
            data-check-on-init="true"
            data-sort-name='created_at'
            data-sort-order='desc'
            data-side-pagination="server"
            data-url="{{ route('radfact.areas.cargarDatos') }}">
            <thead class="table-primary">
                <tr>
                    <th data-field="area">Área</th>
                    <th data-field="responsable">Responsable</th>
                    <th data-field="correo" data-order="3">Correo</th>
                    <th class="text-center" data-field="created_at" data-sortable="true">Fecha Registro</th>
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
    function accionesFormatter(index, row) {
        let rutaEditar = "{{ route('radfact.areas.edit', ['area' => ':id']) }}";
        let rutaEditarFinal = rutaEditar.replace(':id', row.id);

        return `
            <div class="col-md-12 d-flex justify-content-center">
                <a href="${rutaEditarFinal}"
                   class="sig-action-btn"
                   data-title="Editar"
                   aria-label="Editar"
                   style="cursor: pointer;"
                   title="Editar área">
                    <img src="{{ asset('img/edit.png') }}" alt="Editar Área" style="width: 32px; height: 32px;">
                </a>
            </div>
        `;
    }

    document.addEventListener("DOMContentLoaded", () => {
        initTablaBootstrapTable(
            '#areasDataTable',
            {
                protegidas: ['area', 'responsable']
            },
            'detalleArea',
            {
                'acciones': accionesFormatter,
            }
        );
    });
</script>
@endpushOnce
