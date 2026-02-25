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

  <div class="p-4" style="margin-top: -30px;">
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
        background: #f8fbff;
        border: 1px solid #bccadb;
        border-radius: 16px !important;
        padding: .9rem 1rem !important;
        margin-bottom: .8rem;
        box-shadow: inset 0 0 0 1px #eef3f9, 0 4px 12px rgba(24, 54, 98, .08);
      }
      .camara-recognize-list .recognize-card-head {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: .9rem;
        margin-bottom: .85rem;
      }
      .camara-recognize-list .recognize-card-ident {
        min-width: 0;
        flex: 1 1 auto;
      }
      .camara-recognize-list .recognize-card-name {
        font-size: 1.4rem;
        font-weight: 900;
        line-height: 1.08;
        color: var(--bs-body-color);
        letter-spacing: .01em;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
      }
      .camara-recognize-list .recognize-event-badge {
        font-size: 1.08rem;
        font-weight: 800;
        padding: .42rem .95rem;
        border-radius: 999px;
        margin-top: .1rem;
        box-shadow: 0 2px 6px rgba(0, 0, 0, .12);
      }
      .camara-recognize-list .recognize-card-doc {
        margin-top: .45rem;
        font-size: 1.12rem;
        font-weight: 600;
        color: var(--bs-secondary-color);
      }
      .camara-recognize-list .recognize-meta-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: .8rem;
      }
      .camara-recognize-list .recognize-meta-pill {
        display: flex;
        align-items: center;
        justify-content: space-between;
        min-height: 68px;
        border-radius: 12px;
        background: rgba(255, 255, 255, .65);
        border: 1px solid #c9d2de;
        box-shadow: inset 0 0 0 1px rgba(255, 255, 255, .55);
        padding: .62rem .78rem;
        gap: .65rem;
      }
      .camara-recognize-list .recognize-meta-label {
        display: inline-flex;
        align-items: center;
        gap: .35rem;
        color: #4a5565;
        font-size: 1.3rem;
        font-weight: 800;
        letter-spacing: .01em;
        white-space: nowrap;
      }
      .camara-recognize-list .recognize-meta-pill-date .recognize-meta-label {
        font-size: 1.3rem;
        font-weight: 800;
      }
      .camara-recognize-list .recognize-meta-value {
        color: var(--bs-body-color);
        font-size: 1.45rem;
        font-weight: 900;
        line-height: 1;
        letter-spacing: .01em;
        white-space: nowrap;
      }
      .camara-recognize-list .recognize-meta-pill-date .recognize-meta-value {
        font-size: 1.2rem;
        font-weight: 800;
      }
      .recognize-list-panel {
        background: var(--bs-body-bg);
        border-color: var(--bs-border-color);
        border-radius: 16px;
        overflow: hidden;
      }
      .recognize-list-panel .card-body {
        background: #ffffff;
        border-radius: inherit;
      }
      .camara-recognize-list {
        margin: 0;
        padding: .2rem .2rem .05rem;
        background: transparent;
      }
      .camara-recognize-list .list-group-item:last-child {
        margin-bottom: 0;
      }
      .camara-video-wrap {
        max-width: 840px;
      }
      .camera-mirror {
        transform: scaleX(-1);
        transform-origin: center;
      }
      [data-bs-theme="dark"] .camara-recognize-list .list-group-item {
        background: rgba(255, 255, 255, .02);
        border-color: rgba(141, 177, 232, .38);
        box-shadow: 0 8px 18px rgba(0, 0, 0, .22);
      }
      [data-bs-theme="dark"] .camara-recognize-list .recognize-card-name {
        color: #ecf4ff;
      }
      [data-bs-theme="dark"] .camara-recognize-list .recognize-card-doc {
        color: #c8dcff;
      }
      [data-bs-theme="dark"] .camara-recognize-list .recognize-meta-pill {
        background: rgba(55, 107, 180, .22);
        border-color: rgba(141, 177, 232, .55);
        box-shadow: inset 0 0 0 1px rgba(151, 191, 255, .16);
      }
      [data-bs-theme="dark"] .camara-recognize-list .recognize-meta-label {
        color: #a9caf8;
      }
      [data-bs-theme="dark"] .camara-recognize-list .recognize-meta-pill-date .recognize-meta-label {
        color: #b7d4fa;
      }
      [data-bs-theme="dark"] .camara-recognize-list .recognize-meta-value {
        color: #f1f7ff;
      }
      [data-bs-theme="dark"] .recognize-list-panel {
        background: var(--darkmode-bg-lightdark);
        border-color: var(--darkmode-border, #32435f);
      }
      [data-bs-theme="dark"] .recognize-list-panel .card-body {
        background: var(--darkmode-bg-lightdark);
      }
      @media (max-width: 576px) {
        .camara-recognize-list .recognize-card-name {
          font-size: 1.15rem;
        }
        .camara-recognize-list .recognize-card-doc {
          font-size: 1rem;
        }
        .camara-recognize-list .recognize-event-badge {
          font-size: .92rem;
          padding: .3rem .72rem;
        }
        .camara-recognize-list .recognize-meta-grid {
          grid-template-columns: 1fr;
          gap: .6rem;
        }
        .camara-recognize-list .recognize-meta-pill {
          min-height: 60px;
          padding: .5rem .65rem;
        }
        .camara-recognize-list .recognize-meta-label {
          font-size: .95rem;
        }
        .camara-recognize-list .recognize-meta-value {
          font-size: 1.18rem;
        }
        .camara-recognize-list .recognize-meta-pill-date .recognize-meta-value {
          font-size: 1.02rem;
        }
        .camara-recognize-list .recognize-card-head {
          gap: .55rem;
        }
        .camara-recognize-list .recognize-card-ident {
          width: 100%;
        }
        .camara-recognize-list .recognize-event-badge {
          align-self: flex-start;
        }
      }
    </style>
    <div id="alertContainer"></div>

    <div class="row g-3 mt-3">
      <div class="col-12 col-lg-6">
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

            <div class="border rounded px-3 py-2 d-none">
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
                <div class="fw-semibold">¿Que desea realizar?</div>
                <div class="btn-group btn-group-sm" role="group" aria-label="Evento reconocimiento">
                  <input type="radio" class="btn-check" name="evento" id="eventIngreso" value="2" autocomplete="off">
                  <label id="eventIngresoLabel" class="btn btn-outline-success" for="eventIngreso">
                    <i class="fas fa-sign-in-alt me-1"></i>Ingreso (1)
                  </label>
                  <input type="radio" class="btn-check" name="evento" id="eventSalida" value="1" autocomplete="off">
                  <label id="eventSalidaLabel" class="btn btn-outline-danger" for="eventSalida">
                    <i class="fas fa-sign-out-alt me-1"></i>Salida (2)
                  </label>
                </div>
              </div>
            </div>

            <div class="camara-video-wrap w-100 mx-auto">
              <div class="ratio ratio-16x9 bg-dark rounded overflow-hidden">
                <video id="cameraVideo" class="w-100 h-100 camera-mirror" autoplay muted playsinline></video>
              </div>
            </div>
            <div class="d-flex flex-wrap gap-2 d-none">
              <button id="startBtn" class="btn btn-success" type="button"
                data-health-url="{{ route('camera.health') }}"
                data-keepalive-url="{{ route('camera.session-keepalive') }}"
                data-recognize-url="{{ route('camera.recognize-live') }}">Iniciar</button>
              <button id="stopBtn" class="btn btn-outline-secondary btn-sm" type="button" disabled>Detener</button>
              <button id="retryBtn" class="btn btn-outline-warning btn-sm d-none" type="button">Reintentar camara</button>
            </div>

            <canvas id="captureCanvas" class="d-none"></canvas>
          </div>
        </div>
      </div>

      <div class="col-12 col-lg-6">
        <div class="card h-100 recognize-list-panel">
          <div class="card-body d-flex flex-column gap-3">
            <div class="d-flex align-items-center justify-content-end">
              <span id="serviceStatusTop" class="badge rounded-pill bg-secondary">Comprobando...</span>
            </div>

            <div id="recognizeEmpty" class="text-muted small">Sin coincidencias todavia.</div>
            <ul id="recognizeList" class="list-group list-group-flush overflow-auto camara-recognize-list" style="max-height: 680px;"></ul>
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
