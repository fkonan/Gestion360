@extends('layouts.dashboard')

@section('title','Gestión appmovil')
    
@section('breadcrumb')
<x-breadcrumb :items="[
        ['name' => 'Inicio', 'url' => route('home')],
        ['name' => 'Gestión appmovil', 'url' => route('gestion-appmovil.index')],
        ['name' => 'Notificaciones'],
    ]" />
<br>
@endsection

@section('content')
<div class="container-fluid p-0 border rounded sidebar-dark-primary tableContainer" style="min-height:150px">

    <x-sectionHeader 
        titulo="Notificaciones"
        rutaVolver="{{ route('gestion-appmovil.index') }}"
        crear="newpage"
        crearRoute="{{ route('notificaciones.create') }}"
        crearLabel="Registrar notificación"
    />

    <div class="table-responsive" style="padding:1.5em">
        <table
            id="notificacionesTable"
            class="table table-sm table-striped"
            data-page-size="25"
            data-toggle="table"
            data-locale="es-ES"
            data-search="true"
            data-pagination="true"
            data-detail-view="true"
            data-detail-formatter="detalleNotificacion"
            data-check-on-init="true"
            data-side-pagination="server"
            data-url="{{ route('notificaciones.cargarDatos') }}">
            <thead class="table-primary">
                <tr>
                    <th class="text-nowrap" data-field="titulo" data-sortable="true">Título</th>
                    <th class="text-nowrap" data-field="usuarioCrea" data-sortable="true">Usuario crea</th>
                    <th class="text-nowrap" data-field="estado" data-sortable="true">Estado</th>
                    <th class="text-nowrap" data-field="privacidad" data-sortable="true">Privacidad</th>
                    <th class="text-nowrap" data-field="proceso" data-sortable="true"> Proceso</th>
                    <th class="text-nowrap" data-field="programada" data-sortable="true">Fecha programada</th>
                    <th class="text-nowrap" data-field="createdAt" data-sortable="true">Fecha Creación</th> 
                    <th class="text-nowrap" data-field="destino" data-sortable="true" data-escape="false">Destinatarios</th>
                    <th class="text-nowrap" data-field="bodyPush">Resumen Push</th>
                    <th class="text-nowrap" data-field="bodyCompleto">Descripción</th>
                </tr>
            </thead>
        </table>
    </div>
</div>
@endsection

@pushOnce('script')
<script>
document.addEventListener("DOMContentLoaded", () => {
    initTablaBootstrapTable(
        '#notificacionesTable', 
        { protegidas: ['titulo'] }, 
        'detalleNotificacion', 
    );
});
</script>
@endpushOnce




