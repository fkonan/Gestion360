@extends('layouts.dashboard')

@section('title', 'Solicitudes y novedades')

@section('breadcrumb')
<x-breadcrumb :items="[
        ['name' => 'Inicio', 'url' => route('home')],
        ['name' => 'Solicitudes y novedades'],
    ]" />
@endsection

@section('content')
<div class="container optionsMenu mt-4 p-0" style="max-width: 1500px;">
  <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 row-cols-xl-4 g-2">
    <div class="col">
      <x-card
        color="bg-teriary"
        titulo="Radicar solicitud"
        descripcion="Permiso, incapacidad y mas"
        icono="fa-file-signature"
        ruta="{{ route('gestionRRHH.solicitudes.radicar') }}" />
    </div>

    <div class="col">
      <x-card
        color="bg-teriary"
        titulo="Mis solicitudes"
        descripcion="Solicitudes en gestion"
        icono="fa-clipboard-check"
        ruta="{{ route('gestionRRHH.permisos.mis-solicitudes') }}" />
    </div>

    <div class="col">
      <x-card
        color="bg-teriary"
        titulo="Mis novedades"
        descripcion="Solo aprobadas"
        icono="fa-clipboard-list"
        ruta="{{ route('gestionRRHH.permisos.mis-novedades') }}" />
    </div>

    @if(!empty($puedeGestionarPermisosJefe))
    <div class="col">
      <x-card
        color="bg-teriary"
        titulo="Solicitudes equipo"
        descripcion="Revisar y gestionar"
        icono="fa-user-check"
        ruta="{{ route('gestionRRHH.permisos.jefe') }}" />
    </div>
    @endif

    @if(!empty($puedeGestionarPermisosRrhh))
    <!-- <div class="col">
      <x-card
        color="bg-teriary"
        titulo="Citar a descargos"
        descripcion="Proceso disciplinario"
        icono="fa-gavel"
        ruta="{{ route('gestionRRHH.descargos.citaciones.create') }}" />
    </div> -->

    <div class="col">
      <x-card
        color="bg-teriary"
        titulo="Gestion RRHH"
        descripcion="Solicitudes pendientes"
        icono="fa-user-check"
        ruta="{{ route('gestionRRHH.permisos.rrhh') }}" />
    </div>
    @endif

    @if(!empty($puedeVerNovedades))
    <div class="col">
      <x-card
        color="bg-teriary"
        titulo="Novedades"
        descripcion="Consulta global"
        icono="fa-clipboard-list"
        ruta="{{ route('gestionRRHH.permisos.novedades') }}" />
    </div>
    @endif
  </div>
</div>
@endsection

