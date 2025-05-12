@extends('layouts.dashboard')

@section('title','Gestión Sistema')
    
@section('breadcrumb')
<x-breadcrumb :items="[
        ['name' => 'Home', 'url' => route('home')],
        ['name' => 'Gestión Sistema'],
    ]" />
@endsection

@section('content')
<div class="container-fluid pt-4" style="min-height:150px">
    <div class="row row-cols-1 row-cols-md-2 row-cols-lg-4">
        <div class="col">
            <x-card color="bg-success" 
                    titulo="Terminales" 
                    descripcion="Administrar" 
                    icono="fa-map-marked-alt" 
                    ruta="#"
            />
        </div>
        <div class="col">
            <x-card color="bg-primary" 
                    titulo="Modulos" 
                    descripcion="Administrar" 
                    icono="fa-cube" 
                    ruta="{{ route('modulos.index') }}"
            />
        </div>
        <div class="col">
            <x-card color="bg-danger" 
                    titulo="Sub Modulos" 
                    descripcion="Administrar" 
                    icono="fa-stream" 
                    ruta="{{ route('submodulos.index') }}"
            />
        </div>
        <div class="col">
            <x-card color="bg-warning" 
                    titulo="Roles" 
                    descripcion="Administrar" 
                    icono="fa-user-tag" 
                    ruta="{{ route('roles.index') }}"
            />
        </div>
    </div>
</div>
@endsection

