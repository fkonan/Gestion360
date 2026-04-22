@extends('layouts.dashboard')

@section('title','Gestión empleados')

@section('breadcrumb')
<x-breadcrumb :items="[
        ['name' => 'Inicio', 'url' => route('home')],
        ['name' => 'Gestión Empleado'],
    ]" />
@endsection

@section('content')
<div class="container optionsMenu mt-4 p-0" style="max-width: 1500px;">
  <div class="row row-cols-1 row-cols-md-2 row-cols-lg-4 g-2">
    <div class="col">
      <x-card
        color="bg-success"
        titulo="Incapacidades"
        descripcion="Gestión"
        icono="fa-procedures"
        ruta="{{ route('gestion-empleado.incapacidades') }}" />
    </div>
    <div class="col">
      <x-card
        color="bg-warning"
        titulo="Incapacidades"
        descripcion="Seguimiento"
        icono="fa-phone"
        ruta="{{ route('gestion-empleado.seguimiento') }}" />
    </div>

    <div class="col">
      <x-card
        color="bg-secondary"
        titulo="Levantamiento COP"
        descripcion="Bloqueos"
        icono="fas fa-unlock-alt"
        ruta="{{ route('conductor.levantamiento-cop.index') }}" />
    </div>

    <div class="col"
      onclick="cargarModal(`{{ route('politicas.index') }}`, 'Politicas firmadas por el empleado','#formPoliticas','modal-lg')">
      <x-card
        color="bg-success"
        titulo="Firmas empleados"
        descripcion="Politicas"
        icono="fa-signature"
        ruta="#" />
    </div>

  </div>
</div>
@endsection

@pushOnce('script')
@vite(['resources/js/cargarModal.js'])
@endPushOnce
