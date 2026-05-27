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
        <div class="card h-100">
          <div class="card-body d-flex flex-column gap-3">
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
                <div class="fw-semibold">Reconocimiento automatico activo</div>
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
                data-recognize-url="{{ route('camera.recognize-live') }}"
                data-latest-events-url="{{ route('camera.ultimos-eventos') }}"
                data-today-events-url="{{ route('camera.eventos-hoy') }}">Iniciar</button>
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
            <div class="d-flex align-items-center justify-content-end gap-2">
              <button id="latestEventsBtn" class="btn btn-outline-secondary btn-sm py-1 px-2" type="button" title="Consultar ultimos 10 registros">
                <i class="fas fa-history me-1"></i>Registros
              </button>
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

<div class="modal fade" id="latestEventsModal" tabindex="-1" aria-labelledby="latestEventsModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="latestEventsModalLabel">Ultimos 10 registros</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
      </div>
      <div class="modal-body">
        <div class="border rounded p-2 mb-3">
          <label for="todayEventsDocInput" class="form-label form-label-sm mb-1">Buscar registros de hoy por identificacion</label>
          <div class="input-group input-group-sm">
            <input id="todayEventsDocInput" type="text" class="form-control" placeholder="Numero de identificacion" inputmode="numeric" pattern="[0-9]*" maxlength="20" autocomplete="off">
            <button id="todayEventsSearchBtn" class="btn btn-primary" type="button">Buscar</button>
            <button id="todayEventsResetBtn" class="btn btn-outline-secondary" type="button">Ultimos 10</button>
          </div>
        </div>
        <div id="latestEventsLoading" class="text-center text-muted py-2 d-none">
          <span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>Consultando...
        </div>
        <div id="latestEventsEmpty" class="text-muted small">Sin registros recientes.</div>
        <ul id="latestEventsList" class="list-group list-group-flush"></ul>
      </div>
    </div>
  </div>
</div>
@endsection

@push('script')
  @vite('resources/js/camara/recognize.js')
@endpush
