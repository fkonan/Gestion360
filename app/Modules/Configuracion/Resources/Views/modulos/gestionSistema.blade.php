@extends('layouts.dashboard')

@section('title','Gestión Sistema')

@section('breadcrumb')
<x-breadcrumb :items="[
        ['name' => 'Inicio', 'url' => route('home')],
        ['name' => 'Gestión Sistema'],
    ]" />
@endsection

@section('content')
<div class="container optionsMenu mt-4 p-0" style="max-width: 1500px;">
  <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 row-cols-xl-4 g-2">
    <div class="col">
      <x-card color="bg-success"
        titulo="Modulos"
        descripcion="Administración"
        icono="fa-cube"
        ruta="{{ route('modulos.index') }}" />
    </div>
    <div class="col">
      <x-card color="bg-danger"
        titulo="Sub Modulos"
        descripcion="Administración"
        icono="fa-stream"
        ruta="{{ route('submodulos.index') }}" />
    </div>
    <div class="col">
      <x-card color="bg-warning"
        titulo="Roles"
        descripcion="Administración"
        icono="fa-user-tag"
        ruta="{{ route('roles.index') }}" />
    </div>
    <div class="col">
      <x-card color="bg-teriary"
        titulo="Permisos"
        descripcion="Administración"
        icono="fa-key"
        ruta="{{ route('gestion-permisos.index') }}" />
    </div>
  </div>
</div>
@endsection
