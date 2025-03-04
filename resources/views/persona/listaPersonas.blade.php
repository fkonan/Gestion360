@extends('layouts.dashboard')

@section('title','Lista personas')

@section('content')
<div class="container-fluid p-0 bg-white border rounded">

    <div class="border rounded-top" style="background-color: #2C3643">
        <h5 class="text-left text-light p-2 ps-4">Personas</h5>
    </div>

    <div class="row p-4">
        <table
            class="table table-striped"
            data-toggle="table"
            data-pagination="true"
            data-page-size="10"
            data-page-list="[]"
            data-pagination-parts="['pageSize', 'pageList', 'pageNext', 'pagePrev']">   
            <thead class="text-center">
                <tr>
                    <th data-sortable="true">Id Persona</th>
                    <th>Tipo documento</th>
                    <th>Numero de documento</th>
                    <th>Apellidos</th>
                    <th>Nombres</th>
                    <th>Departamento</th>
                    <th>Generos</th>    
                    <th>Estado</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
            @foreach($personas as $persona)
                <tr>
                    <td>{{ $persona?->IdPersona }}</td>
                    <td>{{ $persona?->tipoDocumento->nomenclatura }}</td>
                    <td>{{ $persona?->PerNumDoc }}</td>
                    <td>{{ $persona?->PerApellidos }}</td>
                    <td>{{ $persona?->PerNombres }}</td>
                    <td>{{ $persona?->municipioNac->departamento->DepNomMin }}</td>
                    <td>{{ $persona?->PerGenero }}</td>
                    <td>{{ $persona?->PerEstado }}</td>
                    <td class="text-center"><a href="{{ route('persona.edit', ['id' => $persona->IdPersona]) }}" class="btn btn-primary">Actualizar</a></td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection