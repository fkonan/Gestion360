@extends('layouts.dashboard')

@section('title','Incapacidades')

@section('breadcrumb')
<nav aria-label="breadcrumb">
  <ol class="breadcrumb">
    <li class="breadcrumb-item"><a class="text-decoration-none" href="#">Home</a></li>
    <li class="breadcrumb-item active" aria-current="page">Gestion Empleado</li>
  </ol>
</nav>
@endsection
    
@section('content')
<div class="container-fluid pt-4" style="min-height:150px">
    <div class="row row-cols-1 row-cols-md-2 row-cols-lg-4 g-3">
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