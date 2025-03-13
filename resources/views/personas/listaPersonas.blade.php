@extends('layouts.dashboard')

@section('title','Lista personas')

@section('content')
<div class="container-fluid p-0 border rounded">

    <div class="border rounded-top d-flex justify-content-between align-items-center px-4" style="background-color: #2C3643">
        <span class="text-left text-light fs-4 fw-medium">Personas registradas</span>
        <a class="btn fw-bold my-2 text-light" style="background-color: #007BFF" onclick="window.history.back()" >Volver</a>
    </div>

    <a class="btn fw-bold ms-4 mt-4 text-light" 
        style="position: absolute; top:150px; background-color: #007BFF"
        onclick="cargarModal(`{{ route('persona.create') }}`, 'crearPersonaModal', '#formPersonaCrear')">
        Registrar persona
    </a>

    <div class="row p-4">
        <table
            class="table table-striped"
            data-toggle="table"
            data-search="true"
            data-pagination="true"
            data-page-size="10"
            data-page-list="[]"
            data-pagination-parts="['pageSize', 'pageList', 'pageNext', 'pagePrev']">   
            <thead>
                <tr>
                    <th>Identificación</th>
                    <th>Nombre Completo</th>
                    <th>Departamento</th>
                    <th>Genero</th>    
                    <th>Estado</th>
                    @if(auth()->user()->can('editar-admin-personas'))
                        <th>Opciones</th>
                    @endif
                </tr>
            </thead>
            <tbody>
            @foreach($personas as $persona)
                <tr>
                    <td>{{ $persona?->PerNumDoc }}</td>
                    <td>{{ $persona?->PerNombres }} {{ $persona?->PerApellidos }}</td>
                    <td>{{ $persona?->municipioNac->departamento->DepNomMin }}</td>
                    <td>{{ $persona?->PerGenero }}</td>
                    <td>{{ $persona?->PerEstado }}</td>
                    @if(auth()->user()->can('editar-admin-personas'))
                        <td class="text-center" style="width: 100px;">
                            <a class="btn btn-primary p-0 px-2" onclick="cargarModal(`{{ route('persona.edit', ['id' => $persona->IdPersona]) }}`, 'editPersonaModal', '#formEditPersona')">
                                <i class="nav-icon fas fa-edit"></i>
                            </a>
                        </td>
                    @endif
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>

    <!-- modales -->
    <x-modal id="editPersonaModal" titulo="Editar Persona" size="xl" />
    <x-modal id="crearPersonaModal" titulo="Crear Persona" size="xl" />
   
</div>
@endsection