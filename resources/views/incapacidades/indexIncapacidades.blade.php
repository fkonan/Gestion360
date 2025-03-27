@extends('layouts.dashboard')

@section('title','Incapacidades')
    
@section('content')
<div class="container-fluid pt-4" style="min-height:150px">
    <div class="d-flex flex-wrap gap-4">
        <x-card color="bg-success" titulo="Incapacidades" descripcion="Gestión" icono="fa-procedures" />
        <x-card color="bg-warning" titulo="Incapacidades" descripcion="Seguimiento" icono="fa-phone" />

    </div>
</div>
@endsection