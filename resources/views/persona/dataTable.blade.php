@extends('layouts.app')

@section('title','Lista personas')

@section('content')
<div class="container mt-4 text-center">
    <h1 class="text-center">Lista de personas</h1>
    <div class="row">
        <table
            data-toggle="table"
            data-pagination="true"
            data-page-size="10"
            data-search="true"
            data-search-highlight="true">   
            <thead>
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
                    <td><a href="{{ route('persona.edit', ['id' => $persona->IdPersona]) }}" class="btn btn-primary">Actualizar</a></td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection