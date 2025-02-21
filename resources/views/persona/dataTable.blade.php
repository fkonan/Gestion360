@extends('layouts.app')

@section('title','Lista personas')

@section('content')
<div class="container mt-4 text-center">
    <h1 class="text-center">Lista de personas</h1>
    <div class="row">
        <table
            data-show-pagination-switch="true"
            data-pagination="true">
            <thead>
                <tr>
                    <th>Id Persona</th>
                    <th>Tipo documento</th>
                    <th>Numero de documento</th>
                    <th>Apellidos</th>
                    <th>Nombres</th>
                    <th>Departamento</th>
                    <th>Generos</th>    
                    <th>Estado</th>
                </tr>
            </thead>
            @foreach($personas as $persona)
            <tbody>
                <tr>
                    <th>{{ $persona?->IdPersona }}</th>
                    <th>{{ $persona?->tipoDocumento->nomenclatura }}</th>
                    <th>{{ $persona?->PerNumDoc }}</th>
                    <th>{{ $persona?->PerApellidos }}</th>
                    <th>{{ $persona?->PerNombres }}</th>
                    <th>{{ $persona?->municipioNac->departamento->DepNomMin }}</th>
                    <th>{{ $persona?->PerGenero }}</th>
                    <th>{{ $persona?->PerEstado }}</th>
                </tr>
            </tbody>
            @endforeach
        </table>
    </div>
</div>
@endsection