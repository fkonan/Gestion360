@extends('layouts.dashboard')

@section('title', 'Reconocimiento facial')

@section('breadcrumb')
<x-breadcrumb :items="[
        ['name' => 'Inicio', 'url' => route('home')],
        ['name' => 'Reconocimiento facial'],
    ]" />
@endsection

@section('content')
<div class="container optionsMenu mt-4 p-0" style="max-width: 1500px;">
  <div class="row row-cols-1 row-cols-md-2 row-cols-lg-4 g-2">
    @if(auth()->user()->can(\App\Constants\Permisos::BIOMETRIA_GESTION_CAMARA_ENROLL))
    <div class="col">
      <x-card
        color="bg-primary"
        titulo="Registrar rostro"
        descripcion="Registro"
        icono="fa-camera"
        ruta="{{ route('face.enroll') }}" />
    </div>
    @endif

    @if(auth()->user()->can(\App\Constants\Permisos::BIOMETRIA_GESTION_CAMARA_RECONOCER))
    <div class="col">
      <x-card
        color="bg-info"
        titulo="Ingreso/Salida Personal"
        descripcion="Registro de entrada y salida"
        icono="fa-user-check"
        ruta="{{ route('face.recognize') }}" />
    </div>

    <div class="col">
      <x-card
        color="bg-warning"
        titulo="Camara IP"
        descripcion="Vista de video RTSP"
        icono="fa-video"
        ruta="{{ route('camera.ip.preview') }}" />
    </div>

    <div class="col">
      <x-card
        color="bg-secondary"
        titulo="Validar persona"
        descripcion="Verificacion informativa"
        icono="fa-user-shield"
        ruta="{{ route('face.verify') }}" />
    </div>

    @endif
  </div>
</div>
@endsection
