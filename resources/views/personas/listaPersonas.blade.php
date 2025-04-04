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
        <span class="text-left text-light fs-5 fw-bold">Personas registradas</span>
        <a class="btn fw-bold my-2 text-light bg-primary" onclick="window.history.back()" >Volver</a>
    </div>

    <a class="btn fw-bold ms-4 mt-4 text-light bg-warning botonBoostrapTable" 
        onclick="cargarModal(`{{ route('personas.create') }}`, 'Crear Persona', '#formPersonaCrear')">
        Registrar persona
    </a>

    <div class="row p-4">
        <table
            id="personasDataTable"
            class="table table-sm table-striped"
            data-page-size="25"
            data-toggle="table"
            data-search="true"
            data-pagination="true"
            data-pagination-parts="['pageSize', 'pageList', 'pageNext', 'pagePrev']">   
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
                    <td>{{ $persona?->PerNumDoc }}</td>
                    <td>{{ $persona?->PerNombres }} {{ $persona?->PerApellidos }}</td>
                    <td>{{ $persona?->datos?->PerEmail ?? "-"}}</td>
                    <td>{{ $persona?->datos?->PerTelefono ?? "-"}}</td>
                    <td>{{ $persona?->municipioNac->departamento->DepNomMin }}</td>
                    <td>{{ $persona?->PerGenero }}</td>
                    <td>{{ $persona?->PerEstado }}</td>
                    <td class="text-center" style="width: 100px;">
                        <a class="btn btn-secondary p-0 px-2" onclick="cargarModal(`{{ route('personas.edit', ['persona' => $persona->IdPersona]) }}`, 'Editar Persona', '#formEditPersona')">
                            <i class="nav-icon fas fa-edit"></i>
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

