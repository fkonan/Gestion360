@extends('layouts.app')

@section('title','Lista municipios')

@section('content')
<div class="container mt-4">
    <h1 class="text-center">Lista de Departamentos</h1>
    <div class="row">
        <table
            data-toggle="table"
            data-pagination="true"
            data-page-size="10"
            data-search="true">
        <thead>
        <tr>
            <th>Id Departamento</th>
            <th>Nombre Departamento</th>
            <th>Nombre Departamento minuscula</th>
            <th></th>
        </tr>
        </thead>
        <tbody>
        @foreach($departamentos as $departamento)
        <tr>
            <td>{{ $departamento?->IdDepartamento }}</td>
            <td>{{ $departamento?->DepNom }}</td>
            <td>{{ $departamento?->DepNomMin }}</td>
            <td>
                <a href="{{ route('departamentos.edit', ['id' => $departamento->IdDepartamento]) }}" class="btn btn-primary">Actualizar</a>
            </td>
        </tr>
        @endforeach
        </tbody>
        </table>
    </div>
</div>
@endsection
