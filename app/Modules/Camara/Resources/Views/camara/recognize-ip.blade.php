@extends('layouts.dashboard')

@section('title', 'Reconocimiento facial IP')

@section('breadcrumb')
<x-breadcrumb :items="[
        ['name' => 'Inicio', 'url' => route('home')],
        ['name' => 'Reconocimiento facial', 'url' => route('reconocimientoFacial.index')],
        ['name' => 'Reconocimiento IP en vivo'],
    ]" />
<br>
@endsection

@section('content')
<div class="container-fluid p-0 border rounded sidebar-dark-primary tableContainer" style="min-height:150px;">
  <x-sectionHeader titulo="Reconocimiento facial IP" rutaVolver="{{ route('reconocimientoFacial.index') }}" :crear="false" />

  <div class="p-4" style="margin-top: -30px;">
    <div id="alertContainer"></div>

    @if(empty($cameras))
      <div class="alert alert-warning mt-3 mb-0">
        No hay camaras IP habilitadas en <code>CAMERAS_JSON</code>.
      </div>
    @else
      <div class="row g-3 mt-3">
        <div class="col-12 col-lg-6">
          <div class="card h-100">
            <div class="card-body d-flex flex-column gap-3">
              <div class="border rounded p-3">
                <div class="row g-2 align-items-end">
                  <div class="col-12 col-md-8">
                    <label for="ipCameraSelect" class="form-label mb-1">Camara IP</label>
                    <select id="ipCameraSelect" class="form-select form-select-sm">
                      @foreach($cameras as $camera)
                        <option value="{{ $camera['id'] }}"
                          @selected($camera['id'] === $defaultCameraId)>
                          {{ $camera['name'] }} ({{ $camera['id'] }})
                        </option>
                      @endforeach
                    </select>
                  </div>
                  <div class="col-6 col-md-2">
                    <button id="startBtn" class="btn btn-success btn-sm w-100" type="button"
                      data-source-mode="ip"
                      data-face-console-log="1"
                      data-default-camera="{{ $defaultCameraId }}"
                      data-stream-url="{{ route('camera.ip.mjpeg') }}"
                      data-snapshot-url="{{ route('camera.ip.snapshot') }}"
                      data-server-sapi="{{ $serverSapi ?? '' }}"
                      data-force-mjpeg-on-cli="{{ !empty($ipForceMjpegOnCli) ? '1' : '0' }}"
                      data-capture-crop-only="{{ !empty($ipRecognizeCropOnly) ? '1' : '0' }}"
                      data-health-url="{{ route('camera.health') }}"
                      data-keepalive-url="{{ route('camera.session-keepalive') }}"
                      data-recognize-url="{{ route('camera.recognize-live') }}"
                      data-latest-events-url="{{ route('camera.ultimos-eventos') }}"
                      data-today-events-url="{{ route('camera.eventos-hoy') }}">Iniciar</button>
                  </div>
                  <div class="col-6 col-md-2">
                    <button id="stopBtn" class="btn btn-outline-secondary btn-sm w-100" type="button" disabled>Detener</button>
                  </div>
                </div>
              </div>

              <div class="d-flex flex-wrap align-items-center gap-2">
                <span class="text-muted small">Camara</span>
                <span id="cameraStatus" class="badge rounded-pill bg-secondary">Camara detenida</span>
                <span class="text-muted small ms-2">Servicio</span>
                <span id="serviceStatus" class="badge rounded-pill bg-secondary">Comprobando...</span>
                <span class="text-muted small ms-2">Envio</span>
                <span id="lastPayloadSize" class="badge rounded-pill bg-info">--</span>
                <span id="faceStatus" class="badge rounded-pill bg-danger">No hay cara</span>
              </div>
              <div id="serviceMessage" class="small text-muted"></div>

              <div class="w-100 mx-auto">
                <div class="ratio ratio-16x9 bg-dark rounded overflow-hidden">
                  <img id="cameraVideo" class="w-100 h-100 object-fit-contain" alt="Stream camara IP">
                </div>
              </div>

              <button id="retryBtn" class="btn btn-outline-warning btn-sm d-none" type="button">Reintentar stream</button>
              <canvas id="captureCanvas" class="d-none"></canvas>
            </div>
          </div>
        </div>

        <div class="col-12 col-lg-6">
          <div class="card h-100">
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
    @endif
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
