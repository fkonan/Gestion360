@extends('layouts.dashboard')

@section('title','Incapacidades')

@section('breadcrumb')
<x-breadcrumb :items="[
        ['name' => 'Inicio', 'url' => route('home')],
        ['name' => 'Gestión Empleado'],
    ]" />
@endsection
    
@section('content')
<div class="container-fluid optionsMenu mt-4 mx-1 p-0">
    <div class="row row-cols-1 row-cols-md-2 row-cols-lg-4 g-2">
        <div class="col">
            <x-card color="bg-success" 
                    titulo="Incapacidades" 
                    descripcion="Gestión" 
                    icono="fa-procedures" 
                    ruta="{{ route('gestion-incapacidades.incapacidades') }}"
            />
        </div>
        <div class="col">
            <x-card color="bg-warning" 
                    titulo="Incapacidades" 
                    descripcion="Seguimiento" 
                    icono="fa-phone" 
                    ruta="{{ route('gestion-incapacidades.seguimiento') }}"
            />
        </div>
    </div>
</div>
@endsection