@extends('layouts.dashboard')

@section('title','Lista municipios')

@section('content')
<div class="container mt-4">
    <h1 class="text-center">Lista de municipios</h1>
    <div class="row">
        <table>
        <tr>
            <th>Id Municipio</th>
            <th>Municipio nombre</th>
            <th>Departamento</th>
        </tr>
        @foreach($municipios as $municipio)
        <tr>
            <td>{{ $municipio?->IdMunicipio }}</td>
            <td>{{ $municipio?->MunNom }}</td>
            <td>{{ $municipio->departamento->DepNomMin }}</td>
        </tr>
        @endforeach
        </table>
    </div>
</div>
@endsection
