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
    <style>
      #eventModeBanner { border-radius: 12px; }
      #eventModeBanner .mode-title { letter-spacing: .08em; font-weight: 800; }
      #eventModeDot { width: 10px; height: 10px; border-radius: 999px; display: inline-block; }
      .mode-pulse { animation: modePulse 1.4s infinite; }
      @keyframes modePulse {
        0% { transform: scale(1); opacity: .9; }
        50% { transform: scale(1.15); opacity: 1; }
        100% { transform: scale(1); opacity: .9; }
      }
      .camara-recognize-list .list-group-item {
        border: 0;
        border-bottom: 1px solid #cbd5e1 !important;
        box-shadow: inset 0 -1px 0 rgba(0, 0, 0, 0.05);
      }
      .camara-recognize-list .list-group-item:last-child {
        border-bottom: 0;
        box-shadow: none;
      }
      [data-bs-theme="dark"] .camara-recognize-list .list-group-item {
        background-color: var(--darkmode-bg-lightdark) !important;
        color: var(--darkmode-text) !important;
        border-bottom: 1px solid #3b4d69 !important;
        box-shadow: inset 0 -1px 0 rgba(255, 255, 255, 0.05);
      }
    </style>
    <div id="alertContainer"></div>

    <div class="row g-3 mt-3">
      <div class="col-12 col-lg-8">
        <div id="leftPanel" class="card h-100 border-2">
          <div class="card-body d-flex flex-column gap-3">
            <div id="eventModeBanner" class="px-3 py-3 text-white d-flex align-items-center justify-content-between gap-3 bg-success">
              <div class="d-flex align-items-center gap-3">
                <i id="eventModeIcon" class="fas fa-sign-in-alt fa-2x"></i>
                <div>
                  <div class="text-uppercase small opacity-75">Evento actual</div>
                  <div id="eventModeText" class="mode-title fs-5">MODO: INGRESO</div>
                </div>
              </div>
              <span id="eventModeDot" class="mode-pulse bg-white"></span>
            </div>

            <div class="border rounded px-3 py-2">
              <div class="fw-semibold mb-2">Estado</div>
              <div class="d-flex flex-wrap align-items-center gap-2">
                <div class="d-flex align-items-center gap-2">
                  <span class="text-muted small d-flex align-items-center gap-2">
                    <i class="fas fa-video"></i> Camara
                  </span>
                  <span id="cameraStatus" class="badge rounded-pill bg-secondary">Camara detenida</span>
                </div>
                <div class="vr d-none d-md-block"></div>
                <div class="d-flex align-items-center gap-2">
                  <span class="text-muted small d-flex align-items-center gap-2">
                    <i class="fas fa-server"></i> Servicio
                  </span>
                  <span id="serviceStatus" class="badge rounded-pill bg-secondary">Comprobando...</span>
                </div>
                <div class="vr d-none d-md-block"></div>
                <div class="d-flex align-items-center gap-2">
                  <span class="text-muted small d-flex align-items-center gap-2">
                    <i class="fas fa-cloud-upload-alt"></i> Envio
                  </span>
                  <span id="lastPayloadSize" class="badge rounded-pill bg-info">--</span>
                </div>
              </div>
              <div id="serviceMessage" class="small text-muted mt-1"></div>
            </div>
            <div class="border rounded p-3">
              <div class="d-flex flex-wrap align-items-center justify-content-between gap-2">
                <div class="fw-semibold">Evento actual</div>
                <div class="btn-group btn-group-sm" role="group" aria-label="Evento reconocimiento">
                  <input type="radio" class="btn-check" name="evento" id="eventIngreso" value="2" autocomplete="off">
                  <label id="eventIngresoLabel" class="btn btn-outline-success" for="eventIngreso">
                    <i class="fas fa-sign-in-alt me-1"></i>Ingreso
                  </label>
                  <input type="radio" class="btn-check" name="evento" id="eventSalida" value="1" autocomplete="off">
                  <label id="eventSalidaLabel" class="btn btn-outline-danger" for="eventSalida">
                    <i class="fas fa-sign-out-alt me-1"></i>Salida
                  </label>
                </div>
              </div>
            </div>

            <div class="ratio ratio-16x9 bg-dark rounded overflow-hidden">
              <video id="cameraVideo" class="w-100 h-100" autoplay muted playsinline></video>
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
            <ul id="recognizeList" class="list-group list-group-flush overflow-auto camara-recognize-list" style="max-height: 420px;"></ul>
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
