@extends('layouts.app')

@section('title','Editar departamento')

@section('content')
<div class="container mt-4">
    <h1 class="text-center">Editar Departamento</h1>

    <form method="POST" action="{{ route('departamentos.update', ['id' => $departamento->IdDepartamento]) }}">
        @csrf
        @method('PUT')
        <div class="mb-3">
            <label for="IdDepartamento" class="form-label">ID Departamento</label>
            <input type="number" class="form-control" id="IdDepartamento" name="IdDepartamento" value="{{ $departamento->IdDepartamento }}" disabled>
        </div>

        <div class="mb-3">
            <label for="DepNom" class="form-label">Nombre Departamento</label>
            <input type="text" class="form-control" id="DepNom" name="DepNom" value="{{ $departamento->DepNom }}" required>
        </div>

        <div class="mb-3">
            <label for="DepNomMin" class="form-label">Nombre en Minúsculas</label>
            <input type="text" class="form-control" id="DepNomMin" name="DepNomMin" value="{{ $departamento->DepNomMin }}" required>
        </div>

        <button type="submit" class="btn btn-primary">Actualizar </button>
    </form>
</div>
@endsection
