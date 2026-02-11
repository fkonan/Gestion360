@extends('layouts.dashboard')

@section('title', 'Proveedores')

@section('breadcrumb')
<x-breadcrumb :items="[
    ['name' => 'Inicio', 'url' => route('home')],
    ['name' => 'Radicación de Facturas', 'url' => route('radfact.radicaciones.index')],
    ['name' => 'Proveedores'],
]" />
<br>
@endsection

@section('content')
<div class="container-fluid p-0 border rounded sidebar-dark-primary tableContainer" style="min-height:150px;">

    <x-sectionHeader
        titulo="Proveedores"
        crear="newpage"
        rutaVolver="{{ route('radfact.radicaciones.index') }}"
        crearRoute="{{ route('radfact.proveedores.create') }}"
        crearLabel="Nuevo Proveedor" />

    <div id="no-more-tables" class="table-responsive" style="padding:1.5em">
        <table
            id="proveedoresDataTable"
            class="table table-sm table-striped"
            data-toggle="table"
            data-page-size="25"
            data-search="true"
            data-locale="es-ES"
            data-pagination="true"
            data-responsive="true"
            data-detail-view="true"
            data-detail-formatter="detalleProveedor"
            data-check-on-init="true"
            data-sort-name='created_at'
            data-sort-order='desc'
            data-side-pagination="server"
            data-url="{{ route('radfact.proveedores.cargarDatos') }}">
            <thead class="table-primary">
                <tr>
                    <th data-field="tipo_documento">Tipo Doc</th>
                    <th data-field="documento">Documento</th>
                    <th data-field="nombre_completo" data-formatter="nombreFormatter">Nombre / Razón Social</th>
                    <th data-field="correo">Correo</th>
                    <th data-field="telefono">Teléfono</th>
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
    function nombreFormatter(value, row) {
        // Mostrar razón social si existe, sino nombres + apellidos
        if (row.razon_social) {
            return row.razon_social;
        }
        const nombres = row.nombres || '';
        const apellidos = row.apellidos || '';
        return `${nombres} ${apellidos}`.trim() || '-';
    }

    function accionesFormatter(index, row) {
        let rutaEditar = "{{ route('radfact.proveedores.edit', ['proveedor' => ':id']) }}";
        let rutaVer = "{{ route('radfact.proveedores.show', ['proveedor' => ':id']) }}";
        let rutaEditarFinal = rutaEditar.replace(':id', row.id);
        let rutaVerFinal = rutaVer.replace(':id', row.id);

        return `
            <div class="col-md-12 d-flex justify-content-center gap-2">
                <a href="${rutaEditarFinal}"
                   class="sig-action-btn"
                   data-title="Editar"
                   aria-label="Editar"
                   style="cursor: pointer;"
                   title="Editar proveedor">
                    <img src="{{ asset('img/edit.png') }}" alt="Editar Proveedor" style="width: 32px; height: 32px;">
                </a>
            </div>
        `;
    }

    document.addEventListener("DOMContentLoaded", () => {
        initTablaBootstrapTable(
            '#proveedoresDataTable',
            {
                protegidas: ['documento', 'nombre_completo']
            },
            'detalleProveedor',
            {
                'acciones': accionesFormatter,
                'nombre_completo': nombreFormatter,
            }
        );
    });
</script>
@endpushOnce
