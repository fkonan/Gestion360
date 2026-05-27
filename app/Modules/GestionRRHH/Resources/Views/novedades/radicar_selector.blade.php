@extends('layouts.dashboard')

@section('title', 'Radicar solicitud')

@section('breadcrumb')
<x-breadcrumb :items="[
        ['name' => 'Inicio', 'url' => route('home')],
        ['name' => 'Solicitudes y novedades', 'url' => route('gestionRRHH.solicitudes.index')],
        ['name' => 'Radicar solicitud'],
    ]" />
@endsection

@section('content')
<div class="container optionsMenu mt-4 p-0" style="max-width: 1500px;">

  <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 row-cols-xl-4 g-2 mt-1">
    @forelse(($tiposRadicacion ?? []) as $tipo)
      <div class="col">
        <x-card
          color="bg-teriary"
          :titulo="$tipo['label'] ?? 'Solicitud'"
          descripcion="Nueva solicitud"
          :icono="$tipo['icono'] ?? 'fa-file-signature'"
          ruta="{{ route($tipo['ruta']) }}" />
      </div>
    @empty
      <div class="col-12">
        <div class="alert alert-info mb-0">
          No hay tipos de novedad activos para radicación en este momento.
        </div>
      </div>
    @endforelse
  </div>
</div>
@endsection
