@extends('layouts.dashboard')

@section('title', 'Validar persona')

@section('breadcrumb')
<x-breadcrumb :items="[
        ['name' => 'Inicio', 'url' => route('home')],
        ['name' => 'Reconocimiento facial', 'url' => route('reconocimientoFacial.index')],
        ['name' => 'Validar persona'],
    ]" />
<br>
@endsection

@section('content')
<div class="container-fluid p-0 border rounded sidebar-dark-primary tableContainer" style="min-height:150px;">
  <x-sectionHeader titulo="Validar persona" rutaVolver="{{ route('reconocimientoFacial.index') }}" :crear="false" />

  <div class="p-4" style="margin-top: -30px;">
    <style>
      .verify-video-wrap { max-width: 860px; }
      .verify-camera-mirror { transform: scaleX(-1); transform-origin: center; }
      .verify-auto-note {
        border: 1px solid rgba(var(--bs-primary-rgb), .22);
        background: rgba(var(--bs-primary-rgb), .08);
        border-radius: 12px;
        padding: .6rem .8rem;
        font-size: .95rem;
      }
      .verify-result-card {
        border: 1px solid rgba(var(--bs-primary-rgb), .18);
        border-radius: 14px;
        background: rgba(var(--bs-primary-rgb), .06);
      }
      .verify-result-name {
        font-size: 1.1rem;
        font-weight: 800;
        line-height: 1.2;
      }
      .verify-result-doc {
        font-size: .95rem;
      }
      [data-bs-theme="dark"] .verify-result-card {
        background: rgba(85, 133, 214, .16);
        border-color: rgba(162, 194, 245, .4);
      }
    </style>

    <div id="alertContainer"></div>
    <div id="verifyRoutes" class="d-none"
      data-health-url="{{ route('camera.health') }}"
      data-verify-url="{{ route('camera.verify-live') }}"></div>

    <div class="row g-3 mt-3">
      <div class="col-12 col-lg-6">
        <div class="card h-100">
          <div class="card-body d-flex flex-column gap-3">
            <div class="verify-auto-note text-muted">
              <i class="fas fa-info-circle me-1"></i>
              Validacion automatica activa. Solo informativo, no registra eventos.
            </div>

            <div class="verify-video-wrap w-100 mx-auto">
              <div class="ratio ratio-16x9 bg-dark rounded overflow-hidden">
                <video id="cameraVideo" class="w-100 h-100 verify-camera-mirror" autoplay muted playsinline></video>
              </div>
            </div>

            <canvas id="captureCanvas" class="d-none"></canvas>
          </div>
        </div>
      </div>

      <div class="col-12 col-lg-6">
        <div class="card h-100">
          <div class="card-body d-flex flex-column gap-3">
            <div class="d-flex align-items-center justify-content-end">
              <span id="serviceStatusTop" class="badge rounded-pill bg-secondary">Comprobando...</span>
            </div>

            <div id="verifyEmpty" class="text-muted small">Aun no hay coincidencias.</div>
            <div id="verifyResults" class="d-flex flex-column gap-2 overflow-auto" style="max-height: 680px;"></div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
@endsection

@push('script')
  @vite('resources/js/camara/verify.js')
@endpush
