@extends('layouts.app')

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
            <th>{{ $municipio?->IdMunicipio }}</th>
            <th>{{ $municipio?->MunNom }}</th>
            <th>{{ $municipio->departamento->DepNomMin }}</th>
        </tr>
        @endforeach
        </table>
    </div>
</div>
@endsection
