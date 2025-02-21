@extends('layouts.app')

@section('title','Crear departamento')

@section('content')
<div class="container mt-4">
    <h1 class="text-center">Crear Nuevo Departamento</h1>

    <form action="{{ route('departamentos.store') }}" method="POST">
        @csrf
        <div class="mb-3">
            <label for="IdDepartamento" class="form-label">ID Departamento</label>
            <input type="number" class="form-control" id="IdDepartamento" name="IdDepartamento" required>
        </div>

        <div class="mb-3">
            <label for="DepNom" class="form-label">Nombre Departamento</label>
            <input type="text" class="form-control" id="DepNom" name="DepNom" required>
        </div>

        <div class="mb-3">
            <label for="DepNomMin" class="form-label">Nombre en Minúsculas</label>
            <input type="text" class="form-control" id="DepNomMin" name="DepNomMin" required>
        </div>

        <button type="submit" class="btn btn-primary">Guardar</button>
    </form>
</div>
@endsection
