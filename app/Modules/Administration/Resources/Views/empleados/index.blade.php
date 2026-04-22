@extends('layouts.dashboard')

@section('title', 'Empleados')

@section('breadcrumb')
<x-breadcrumb :items="[
        ['name' => 'Inicio', 'url' => route('home')],
        ['name' => 'Empleados'],
    ]" />
@endsection

@section('content')
<div class="container optionsMenu mt-4 p-0" style="max-width: 1500px;">
  <div class="row row-cols-1 row-cols-md-2 row-cols-lg-4 g-2">
    <div class="col">
      <x-card
        color="bg-teriary"
        titulo="Novedades"
        descripcion="Consulta empleados"
        icono="fa-clipboard-list"
        ruta="{{ route('empleados.novedades') }}" />
    </div>
  </div>
</div>
@endsection

