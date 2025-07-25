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
                    <th data-field="titulo">Título</th>
                    <th data-field="bodyPush">Nombre</th>
                    <th data-field="bodyCompleto">Descripción</th>
                    <th data-field="destino">Destinatarios</th>
                    <th data-field="estado">Estado</th>
                    <th data-field="privacidad">Privacidad</th>
                    <th data-field="proceso">Proceso</th>
                    <th data-field="programada">Programada</th>
                    <th data-field="createdAt">Fecha Creación</th> 
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




