@extends('layouts.dashboard')

@section('title', 'Reconocimiento facial')

@section('breadcrumb')
<x-breadcrumb :items="[
        ['name' => 'Inicio', 'url' => route('home')],
        ['name' => 'Reconocimiento facial', 'url' => route('reconocimientoFacial.index')],
        ['name' => 'Reconocimiento en vivo'],
    ]" />
<br>
@endsection

@section('content')
<div class="container-fluid p-0 border rounded sidebar-dark-primary tableContainer" style="min-height:150px;">
  <x-sectionHeader titulo="Reconocimiento facial" rutaVolver="{{ route('reconocimientoFacial.index') }}" :crear="false" />

  <div class="p-4 mt-5">
    <div id="alertContainer"></div>

    <div class="row g-3 mt-3">
      <div class="col-12 col-lg-8">
        <div class="card h-100">
          <div class="card-body d-flex flex-column gap-3">
            <div class="d-flex flex-wrap align-items-center gap-2">
              <span class="fw-semibold">Camara</span>
              <span id="cameraStatus" class="badge bg-secondary">Camara detenida</span>
            </div>
            <div class="d-flex flex-wrap align-items-center gap-2">
              <span class="fw-semibold">Deteccion</span>
              <span id="faceStatus" class="badge bg-secondary">No hay cara</span>
            </div>
            <div class="d-flex flex-wrap align-items-center gap-2">
              <span class="fw-semibold">Servicio</span>
              <span id="serviceStatus" class="badge bg-secondary">Comprobando...</span>
            </div>
            <div class="d-flex flex-wrap align-items-center gap-2">
              <span class="fw-semibold">Tamano del envio</span>
              <span id="lastPayloadSize" class="badge bg-info">--</span>
            </div>

            <div class="ratio ratio-16x9 bg-dark rounded overflow-hidden">
              <video id="cameraVideo" class="w-100 h-100" autoplay muted playsinline></video>
            </div>
            <div class="border rounded p-2">
              <div class="small text-muted mb-2">Preview envio (debug)</div>
              <div id="sendPreview" class="d-flex flex-wrap gap-2" style="min-height: 150px;"></div>
            </div>

            <div class="d-flex flex-wrap gap-2">
              <button id="startBtn" class="btn btn-success" type="button"
                data-health-url="{{ route('camera.health') }}"
                data-recognize-url="{{ route('camera.recognize-live') }}">Iniciar</button>
              <button id="stopBtn" class="btn btn-outline-secondary btn-sm" type="button" disabled>Detener</button>
              <button id="retryBtn" class="btn btn-outline-warning btn-sm d-none" type="button">Reintentar camara</button>
            </div>

            <canvas id="captureCanvas" class="d-none"></canvas>
          </div>
        </div>
      </div>

      <div class="col-12 col-lg-4">
        <div class="card h-100">
          <div class="card-body d-flex flex-column gap-3">
            <div class="d-flex align-items-center justify-content-between">
              <div class="fw-semibold">Reconocidos</div>
              <span id="recognizedCount" class="badge rounded-pill bg-primary">0</span>
            </div>

            <div class="input-group input-group-sm">
              <span class="input-group-text">Buscar</span>
              <input id="searchInput" class="form-control" type="text" placeholder="Identificacion o nombre">
            </div>
            <div class="d-flex align-items-center justify-content-between">
              <small class="text-muted">Escribe para buscar.</small>
              <span id="searchStatus" class="badge bg-secondary d-none">Encontrado</span>
            </div>

            <div id="recognizeEmpty" class="text-muted small">Sin coincidencias todavia.</div>
            <ul id="recognizeList" class="list-group list-group-flush overflow-auto" style="max-height: 420px;"></ul>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
@endsection

@push('script')
  @vite('resources/js/camara/recognize.js')
@endpush
