@extends('layouts.dashboard')

@section('title','Crear Rol')
    
@section('breadcrumb')
<x-breadcrumb :items="[
        ['name' => 'Inicio', 'url' => route('home')],
        ['name' => 'Gestión Sistema' , 'url' => route('gestion-sistema.index')],
        ['name' => 'Administrar Roles', 'url' => route('roles.index')],
        ['name' => 'Crear Rol'],
    ]" />
<br>
@endsection

@section('content')
<div class="container-fluid p-0 border rounded shadow bg-white">

    <div class="border rounded-top d-flex justify-content-between align-items-center px-4 bg-secondary">
        <span class="text-left text-light fs-5 fw-medium py-1">Crear Rol</span>
    </div>

    <div class="row p-4 m-0">
        <form id="formCrearRol" action="{{ route('roles.store') }}" method="POST">
            @csrf

            <label for="name" class="form-label">Nombre</label>
            <input type="name" class="form-control" id="name" name="name"  required>
            
            <br>

            @foreach ($modulos as $modulo)
                <div class="border border-primary rounded m-0 p-0 pb-3 mb-4 z-3">
                <h5 class="p-2 bg-primary text-light">{{ ucfirst($modulo->ModNom) }}</h5>

                @php
                    $nombreModulo = normalizarNombre($modulo->ModNom);
                    $permisoModulo = \Spatie\Permission\Models\Permission::where('name', "$nombreModulo.acceder")->first();
                @endphp

                @if($permisoModulo)
                    <div class="m-3 form-check form-switch">
                        <input class="form-check-input" type="checkbox" role="switch"
                            name="permissions[]" value="{{ $permisoModulo->name }}">
                        <label class="form-check-label">Acceso al módulo</label>
                    </div>
                @endif

                @foreach ($modulo->submodulos as $submodulo)
                    <div class="mx-4 rounded rolCreate">
                        <p class="text-secondary fs-6 fw-medium m-0 p-0">{{ ucfirst($submodulo->SubModNom) }}</p>
                        <div class="row mb-2">
                            @foreach ($submodulo->permisos as $permiso)
                                <div class="col-md-2">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" role="switch"
                                            name="permissions[]" value="{{ $permiso->name }}">
                                        <label class="form-check-label">{{ Str::title(str_replace('_', ' ', Str::afterLast($permiso->name, '.'))) }}
                                        </label>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endforeach
                </div>
            @endforeach

            <button type="submit" class="btn btn-success my-3">Guardar</button>
            <a type="button" class="btn btn-dark" href="{{ route('roles.index') }}">Cancelar</a>   
            
        </form>
    </div>
</div>
@endsection