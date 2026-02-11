@extends('layouts.dashboard')

@section('title','Gestión Huellero')

@section('breadcrumb')
<x-breadcrumb :items="[
        ['name' => 'Inicio', 'url' => route('home')],
        ['name' => 'Gestión Huellero'],
    ]" />
@endsection

@section('content')
<div class="container optionsMenu mt-4 p-0" style="max-width: 1500px;">
  <div class="row row-cols-1 row-cols-md-2 row-cols-lg-4 g-2">
    <div class="col">
      <x-card color="bg-primary"
        titulo="Ingreso Personal"
        descripcion="Registro de entrada y salida"
        icono="fa-user-clock"
        ruta="{{ route('fingerprint.eventos.empleados') }}" />
    </div>
    <div class="col">
      <x-card color="bg-success"
        titulo="Descanso Conductores"
        descripcion="Salida y regreso de conductores"
        icono="fa-bus"
        ruta="{{ route('fingerprint.eventos.conductores') }}" />
    </div>
    <div class="col">
      <x-card color="bg-info"
        titulo="Enroll"
        descripcion="Registro de huellas"
        icono="fa-fingerprint"
        ruta="{{ route('fingerprint.enroll') }}" />
    </div>
    <div class="col">
      <x-card color="bg-warning"
        titulo="Verificar"
        descripcion="Verificación de huellas"
        icono="fa-check-circle"
        ruta="{{ route('fingerprint.verify') }}" />
    </div>
  </div>
</div>
@endsection
