@extends('layouts.dashboard')

@section('title','Lista personas')

@section('breadcrumb')
<x-breadcrumb :items="[
        ['name' => 'Home', 'url' => route('home')],
        ['name' => 'Personas'],
    ]" />
<br>
@endsection

@section('content')
<div class="container-fluid p-0 border rounded bg-white" style="min-height:150px;">

    <div class="border rounded-top d-flex justify-content-between align-items-center px-4 bg-secondary">
        <span class="text-left text-light fs-5 fw-medium py-1">Lista de personas</span>
    </div>

    <a class="btn fw-bold ms-4 mt-4 text-light bg-warning botonBoostrapTable" 
        href="{{ route('personas.create') }}">
        Registrar persona
    </a>

    <div class="row p-4">
        <table
            id="personasDataTable"
            class="table table-sm table-striped"
            data-page-size="10"
            data-toggle="table"
            data-search="true"
            data-pagination="true"
            data-mobile-responsive="true"
            data-check-on-init="true"
            data-pagination-parts="['pageSize', 'pageList', 'pageNext', 'pagePrev']"
            onsubmit="">   
            <thead class="table-primary">
                <tr>
                    <th>Identificación</th>
                    <th>Nombre Completo</th>
                    <th>Correo</th>
                    <th>Telefono</th>
                    <th>Departamento</th>
                    <th>Genero</th>    
                    <th>Estado</th>
                    <th>Opciones</th>
                </tr>
            </thead>
            <tbody>
            @foreach($personas as $persona)
                <tr>
                    <td data-sortable="true">{{ $persona?->PerNumDoc }}</td>
                    <td data-sortable="true">{{ $persona?->PerNombres }} {{ $persona?->PerApellidos }}</td>
                    <td data-sortable="true">{{ $persona?->datos?->PerEmail ?? "-"}}</td>
                    <td data-sortable="true">{{ $persona?->datos?->PerTelefono ?? "-"}}</td>
                    <td data-sortable="true">{{ $persona?->municipioNac->departamento->DepNomMin }}</td>
                    <td data-sortable="true">{{ $persona?->PerGenero }}</td>
                    <td data-sortable="true" class="text-center" data-label="Estado">
                        <span class="badge {{ $persona?->PerEstado == 'ACTIVO' ? 'bg-success' : 'bg-danger' }}">
                        {{ $persona?->PerEstado }}
                        </span>
                    </td>
                    <td class="text-center" style="width: 100px;">
                        <a class="p-0 px-2" onclick="cargarModal(`{{ route('personas.edit', ['id' => $persona->IdPersona]) }}`, 'Editar Persona', '#formEditPersona')">
                            <img src="https://autogestion.copetran.com.co/gestion/aFrame/library/bower_components/Ionicons/png/512/new_editar.png" alt="Editar" style="width: 30px; height: 30px;">
                        </a>
                    </td>   
                </tr>
            @endforeach
            </tbody>
        </table>
    </div> 
</div>
@endsection

@pushOnce('script')
    @vite(['resources/js/cargarModal.js'])
@endpushOnce

