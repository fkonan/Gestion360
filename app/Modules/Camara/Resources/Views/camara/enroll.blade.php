@extends('layouts.dashboard')

@section('title', 'Enrolar rostro')

@section('breadcrumb')
<x-breadcrumb :items="[
        ['name' => 'Inicio', 'url' => route('home')],
        ['name' => 'Reconocimiento facial', 'url' => route('reconocimientoFacial.index')],
        ['name' => 'Enrolar rostro'],
    ]" />
<br>
@endsection

@section('content')
<div class="container-fluid p-0 border rounded sidebar-dark-primary tableContainer" style="min-height:150px;">
  <x-sectionHeader titulo="Enrolar rostro" rutaVolver="{{ route('reconocimientoFacial.index') }}" :crear="false" />

  <div class="p-4 mt-5">
    <div id="alertContainer"></div>

    <div class="row g-3 mt-3">
      <div class="col-12 col-lg-7">
        <div class="card h-100">
          <div class="card-body d-flex flex-column gap-3">
            <div class="d-flex flex-wrap align-items-center gap-2">
              <span class="fw-semibold">Camara</span>
              <span id="cameraStatus" class="badge bg-secondary">Camara detenida</span>
            </div>
            <div class="d-flex flex-wrap align-items-center gap-2">
              <span class="fw-semibold">Deteccion</span>
              <span id="faceStatus" class="badge bg-secondary">Deteccion desactivada</span>
            </div>
            <div class="ratio ratio-16x9 bg-dark rounded overflow-hidden">
              <div class="ratio-content position-absolute top-0 start-0 w-100 h-100">
                <video id="cameraVideo" class="camera-video" autoplay muted playsinline></video>

                <canvas id="overlayCanvas" class="overlay-canvas" aria-hidden="true"></canvas>

                <div id="faceOverlay" class="face-overlay" aria-hidden="true">
                  <div class="face-hint">Alinea tu rostro aqui</div>
                  <div id="autoStatus" class="auto-status small d-none">
                    <div id="autoStatusText">Ajustando...</div>
                    <div class="progress auto-progress">
                      <div id="autoProgressBar" class="progress-bar bg-success" role="progressbar" style="width: 0%"></div>
                    </div>
                  </div>
                </div>

                <span id="alignBadge" class="align-badge badge bg-secondary">Ajusta...</span>
              </div>
            </div>

            <div class="d-flex flex-wrap gap-2">
              <button id="startBtn" class="btn btn-success" type="button">Iniciar camara</button>
              <button id="stopBtn" class="btn btn-outline-danger" type="button" disabled>Detener</button>
            </div>

            <canvas id="captureCanvas" class="d-none"></canvas>
            <canvas id="detectionCanvas" class="d-none"></canvas>
          </div>
        </div>
      </div>

      <div class="col-12 col-lg-5">
        <div class="card h-100">
          <div class="card-body d-flex flex-column gap-3">
            <div class="border rounded p-3">
              <div class="fw-semibold mb-2">Identificación</div>
              <!-- <label class="form-label" for="personSelect">Identificacion</label> -->
              <select id="personSelect" class="form-select form-select-sm" data-url="{{ route('fingerprint.personas') }}" data-placeholder="Selecciona una identificacion...">
                <option value=""></option>
              </select>
            </div>

            <div class="border rounded p-3">
              <div class="fw-semibold mb-2">Estado del enrolamiento</div>
              <div class="d-flex align-items-center gap-2 mb-2">
                <span id="enrollStateBadge" class="badge bg-secondary">.</span>
                <div id="enrollStatusTitle" class="h6 mb-0">Buscando rostro...</div>
              </div>
              <div id="enrollStatusMessage" class="text-muted small mb-2">Coloca tu cara dentro del ovalo.</div>
              <div class="progress" style="height:8px;">
                <div id="enrollProgressBar" class="progress-bar" role="progressbar" style="width: 0%"></div>
              </div>
            </div>
            <input type="hidden" id="enrollEndpoint" value="{{ route('camera.enroll') }}">

            <div class="border rounded p-3 d-none">
              <div class="fw-semibold mb-2">Ajustes</div>
              <div class="form-check form-switch mb-2">
                <input class="form-check-input" type="checkbox" id="faceDetectionToggle" checked>
                <label class="form-check-label" for="faceDetectionToggle">Usar deteccion facial</label>
              </div>
              <div class="form-check form-switch mb-2 d-none">
                <input class="form-check-input" type="checkbox" id="autoCaptureToggle" checked>
                <label class="form-check-label" for="autoCaptureToggle">Captura automatica</label>
              </div>
              <div class="form-check form-switch mb-2">
                <input class="form-check-input" type="checkbox" id="debugToggle">
                <label class="form-check-label" for="debugToggle">Debug</label>
              </div>
              <div id="stabilityCounter" class="text-muted small">Estabilidad: 0/8</div>
              <div class="row g-2 d-none">
                <div class="col-12">
                  <label class="form-label" for="minFaceSizeInput">Tamano minimo cara (%)</label>
                  <input id="minFaceSizeInput" type="number" class="form-control" value="20" min="5" max="80" step="1">
                  <div class="form-text">Cara valida si score &gt; 0.6.</div>
                </div>
              </div>

              <div class="accordion mt-3" id="advancedSettings">
                <div class="accordion-item">
                  <h2 class="accordion-header" id="advancedHeading">
                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse"
                      data-bs-target="#advancedCollapse" aria-expanded="false" aria-controls="advancedCollapse">
                      Avanzado
                    </button>
                  </h2>
                  <div id="advancedCollapse" class="accordion-collapse collapse" aria-labelledby="advancedHeading"
                    data-bs-parent="#advancedSettings">
                    <div class="accordion-body">
                      <div class="row g-2">
                        <div class="col-6">
                          <label class="form-label" for="widthInput">Ancho</label>
                          <input id="widthInput" type="number" class="form-control" value="640" min="80" step="10">
                        </div>
                        <div class="col-6">
                          <label class="form-label" for="heightInput">Alto</label>
                          <input id="heightInput" type="number" class="form-control" value="480" min="60" step="10">
                        </div>
                        <div class="col-12">
                          <label class="form-label" for="qualityInput">Calidad JPEG (0-1)</label>
                          <input id="qualityInput" type="number" class="form-control" value="0.9" min="0.1" max="1" step="0.05">
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>

          </div>
        </div>
      </div>
    </div>
  </div>
</div>
@endsection

@push('script')
  @vite('resources/js/camara/enroll.js')
@endpush
