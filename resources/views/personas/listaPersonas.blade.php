@extends('layouts.dashboard')

@section('title','Lista personas')

@section('content')
<!-- Spinner component -->
<x-spinner />

<div class="container-fluid p-0 border rounded" style="min-height:150px">

    <div class="border rounded-top d-flex justify-content-between align-items-center px-4 bg-secondary">
        <span class="text-left text-light fs-4 fw-bold">Personas registradas</span>
        <a class="btn fw-bold my-2 text-light bg-primary" onclick="window.history.back()" >Volver</a>
    </div>

    <a class="btn fw-bold ms-4 mt-4 text-light bg-primary" 
        style="position: absolute; top:150px;"
        onclick="cargarModal(`{{ route('personas.create') }}`, 'Crear Persona', '#formPersonaCrear')">
        Registrar persona
    </a>

    <div class="row p-4">
        <table
            id="personasDataTable"
            class="table table-sm table-striped d-none"
            data-page-size="25"
            data-toggle="table"
            data-search="true"
            data-pagination="true"
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
                            <a class="btn btn-secondary p-0 px-2" onclick="cargarModal(`{{ route('personas.edit', ['persona' => $persona->IdPersona]) }}`, 'Editar Persona', '#formEditPersona')">
                                <i class="nav-icon fas fa-edit"></i>
                            </a>
                        </td>
                    @endif
                </tr>
            @endforeach
            </tbody>
        </table>
    </div> 
</div>
@endsection

@pushOnce('script')
<script>
document.addEventListener("DOMContentLoaded", function () {
    let table = document.getElementById("personasDataTable");

    //Cargar spinner
    $(table).on('post-body.bs.table', function () {
        document.getElementById("loadingSpinner").classList.add("d-none"); 
        table.classList.remove("d-none"); 
    });
});
</script>
@endpushOnce