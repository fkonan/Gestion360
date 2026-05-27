@extends('layouts.dashboard')

@section('title','Gestion Huellero')

@section('breadcrumb')
<x-breadcrumb :items="[
        ['name' => 'Inicio', 'url' => route('home')],
        ['name' => 'Gestion Huellero'],
    ]" />
@endsection

@section('content')
<div class="container optionsMenu mt-4 p-0" style="max-width: 1500px;">
  <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 row-cols-xl-4 g-2">
    @if(auth()->user()->can(\App\Constants\Permisos::BIOMETRIA_GESTION_HUELLERO_ACCESO_PERSONAL))
    <div class="col">
      <x-card color="bg-primary"
        titulo="Ingreso Personal"
        descripcion="Registro de entrada y salida"
        icono="fa-user-clock"
        ruta="{{ route('fingerprint.eventos.empleados') }}" />
    </div>

    <div class="col">
      <x-card color="bg-secondary"
        titulo="Ingreso Personal Manual"
        descripcion="Registro de entrada y salida"
        icono="fa-user-edit"
        ruta="{{ route('fingerprint.eventos.empleados.manual') }}" />
    </div>
    @endif

    <div class="col">
      <x-card color="bg-dark"
        titulo="Horarios por Cargo"
        descripcion="Configurar horarios por cargo"
        icono="fa-business-time"
        ruta="{{ route('fingerprint.cargos-horarios.index') }}" />
    </div>

    @if(auth()->user()->can(\App\Constants\Permisos::BIOMETRIA_GESTION_HUELLERO_DESCANSO_CONDUCTORES))
    <div class="col">
      <x-card color="bg-success"
        titulo="Descanso Conductores"
        descripcion="Salida y regreso de conductores"
        icono="fa-bus"
        ruta="{{ route('fingerprint.eventos.conductores') }}" />
    </div>
    @endif

    @if(auth()->user()->can(\App\Constants\Permisos::BIOMETRIA_GESTION_HUELLERO_ENROLL))
    <div class="col">
      <x-card color="bg-info"
        titulo="Enroll"
        descripcion="Registro de huellas"
        icono="fa-fingerprint"
        ruta="{{ route('fingerprint.enroll') }}" />
    </div>
    @endif

    @if(auth()->user()->can(\App\Constants\Permisos::BIOMETRIA_GESTION_HUELLERO_VERIFICAR))
    <div class="col">
      <x-card color="bg-warning"
        titulo="Verificar"
        descripcion="Verificacion de huellas"
        icono="fa-check-circle"
        ruta="{{ route('fingerprint.verify') }}" />
    </div>
    @endif
  </div>
</div>
@endsection
