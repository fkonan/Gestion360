@extends('layouts.app')

@section('title','Lista municipios')

@section('content')
<div class="container mt-4">
    <h1 class="text-center">Lista de Departamentos</h1>
    <div class="row">
        <table class="table table-striped">
        <tr>
            <th>Id Departamento</th>
            <th>Nombre Departamento</th>
            <th>Nombre Departamento minuscula</th>
            <th></th>
            <th></th>
        </tr>
        @foreach($departamentos as $departamento)
        <tr>
            <th>{{ $departamento?->IdDepartamento }}</th>
            <th>{{ $departamento?->DepNom }}</th>
            <th>{{ $departamento?->DepNomMin }}</th>
            <th>
                <form method='POST' action="{{ route('departamentos.edit', ['id' => $departamento->IdDepartamento]) }}">
                    @csrf
                    <input type="submit" class="bg-primary text-light" value="Actualizar" />
                </form>
            </th>
            <th>
                <form method='POST' action="{{ route('departamentos.destroy', ['id' => $departamento->IdDepartamento]) }}">
                    @csrf
                    @method('DELETE')
                    <input type="submit" class="bg-danger text-light" value="Borrar" />
                </form>
            </th>
        </tr>
        @endforeach
        </table>
    </div>
</div>
@endsection
