@extends('layouts.dashboard')

@section('title','Gestión appmovil')

@section('breadcrumb')
<x-breadcrumb :items="[
        ['name' => 'Inicio', 'url' => route('home')],
        ['name' => 'Gestión appmovil'],
    ]" />
<br>
@endsection

@section('content')
<div class="container-fluid optionsMenu mt-4 mx-1 mb-0 p-0">
  <div class="row row-cols-1 row-cols-md-2 row-cols-lg-4 g-2">
    <div class="col">
      <x-card color="bg-success"
        titulo="Notificaciones"
        descripcion="Appmovil"
        icono="fa-bell-slash"
        ruta="{{ route('notificaciones.index') }}" />
    </div>
    <div class="col">
      <x-card color="bg-teriary"
        titulo="Recursos digitales"
        descripcion="Tipos y recursos"
        icono="fa-images"
        ruta="{{ route('recursos-digitales.index') }}" />
    </div>
    <div class="col">
      <x-card color="bg-info"
        titulo="Personas"
        descripcion="Datos appmovil"
        icono="fa-user"
        ruta="{{ route('personas-appmovil.index') }}" />
    </div>
  </div>
</div>
@endsection
