@extends('layouts.dashboard')

@section('title','Reportes')

@section('breadcrumb')
<x-breadcrumb :items="[
        ['name' => 'Inicio', 'url' => route('home')],
        ['name' => 'Reportes'],
    ]" />
@endsection

@section('content')
<div class="container-fluid reportes-dashboard px-0">
  <div class="card border-0 shadow-sm overflow-hidden">
    <div class="reportes-dashboard__hero">
      <div class="reportes-dashboard__hero-content">
        <span class="badge rounded-pill text-bg-light text-primary-emphasis mb-3">Panel de reportes</span>
        <h1 class="reportes-dashboard__title">Visualiza la información clave con claridad</h1>
        <p class="reportes-dashboard__subtitle">
          Organiza y consulta los reportes agrupados por categorías como conductores, pasajes, carga y más.
          Cada tarjeta te lleva directamente a la información que necesitas para tomar decisiones oportunas.
        </p>

        <ul class="reportes-dashboard__tips list-unstyled mt-4">
          <li><i class="fas fa-chart-line"></i> Accede rápidamente a los indicadores más consultados.</li>
          <li><i class="fas fa-filter"></i> Aplica filtros específicos para encontrar datos concretos.</li>
          <li><i class="fas fa-file-export"></i> Exporta la información y compártela con tu equipo.</li>
        </ul>
      </div>

      <div class="reportes-dashboard__summary card shadow-sm border-0">
        <div class="card-body">
          <h2 class="h5 fw-semibold text-primary mb-3">¿Qué puedes hacer aquí?</h2>
          <div class="d-flex flex-column gap-3">
            <div class="d-flex gap-3 align-items-start">
              <span class="reportes-dashboard__summary-icon bg-primary-subtle text-primary"><i class="fas fa-layer-group"></i></span>
              <div>
                <p class="mb-1 fw-semibold text-dark-emphasis">Explora las categorías disponibles</p>
                <p class="mb-0 text-muted small">Selecciona un módulo para descubrir reportes operativos, administrativos o financieros.</p>
              </div>
            </div>
            <div class="d-flex gap-3 align-items-start">
              <span class="reportes-dashboard__summary-icon bg-info-subtle text-info"><i class="fas fa-lightbulb"></i></span>
              <div>
                <p class="mb-1 fw-semibold text-dark-emphasis">Mantén el control de tus procesos</p>
                <p class="mb-0 text-muted small">Monitorea tendencias y detecta oportunidades de mejora en tiempo real.</p>
              </div>
            </div>
            <div class="d-flex gap-3 align-items-start">
              <span class="reportes-dashboard__summary-icon bg-success-subtle text-success"><i class="fas fa-shield-alt"></i></span>
              <div>
                <p class="mb-1 fw-semibold text-dark-emphasis">Acceso seguro según tus permisos</p>
                <p class="mb-0 text-muted small">Las tarjetas se adaptan a tus autorizaciones para garantizar la confidencialidad.</p>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="card-body p-4 p-xl-5">
      <div class="reportes-dashboard__grid-intro">
        <h2 class="h5 fw-semibold text-dark-emphasis mb-2">Explora los reportes disponibles</h2>
        <p class="text-muted mb-0">Selecciona una tarjeta para ingresar al informe correspondiente. Puedes volver a este panel en cualquier momento con el botón 'Regresar'.</p>
      </div>

      <div class="row row-cols-1 row-cols-sm-2 row-cols-lg-3 row-cols-xxl-4 g-4 mt-1">
        @foreach ($reportes as $reporte)
        @if(!isset($reporte['permiso']) || $reporte['permiso'] === null)
        <div class="col">
          <div class="reportes-dashboard__card-wrapper h-100">
            <x-card color="bg-teriary"
              titulo="{{ $reporte['titulo'] }}"
              descripcion="{{ $reporte['descripcion'] }}"
              tooltip="{{ $reporte['tooltip'] }}"
              icono="{{ $reporte['icono'] }}"
              ruta="{{ route($reporte['ruta'][0], $reporte['ruta'][1]) }}" />
          </div>
        </div>
        @else
        @permite($reporte['permiso'])
        <div class="col">
          <div class="reportes-dashboard__card-wrapper h-100">
            <x-card color="bg-teriary"
              titulo="{{ $reporte['titulo'] }}"
              descripcion="{{ $reporte['descripcion'] }}"
              tooltip="{{ $reporte['tooltip'] }}"
              icono="{{ $reporte['icono'] }}"
              ruta="{{ route($reporte['ruta'][0], $reporte['ruta'][1]) }}" />
          </div>
        </div>
        @endpermite
        @endif
        @endforeach
      </div>
    </div>
  </div>
</div>
@endsection


@pushOnce('css')
<style>
  .reportes-dashboard {
    color: inherit;
  }

  .reportes-dashboard .card {
    border-radius: 24px;
  }

  .reportes-dashboard__hero {
    display: flex;
    flex-wrap: wrap;
    gap: 2.5rem;
    padding: 3rem clamp(1.5rem, 3vw, 3rem);
    background: linear-gradient(140deg, rgba(13, 110, 253, 0.12) 0%, rgba(13, 202, 240, 0.18) 100%);
    border-bottom: 1px solid rgba(15, 23, 42, 0.08);
  }

  .reportes-dashboard__hero-content {
    flex: 1 1 320px;
    max-width: 640px;
  }

  .reportes-dashboard__title {
    font-size: clamp(1.6rem, 2.6vw, 2.4rem);
    font-weight: 700;
    color: #0b3d91;
    margin-bottom: 0.75rem;
    line-height: 1.2;
  }

  .reportes-dashboard__subtitle {
    font-size: 1rem;
    color: #334155;
    margin-bottom: 0;
  }

  .reportes-dashboard__tips li {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 0.6rem 0;
    font-size: 0.95rem;
    color: #1e293b;
    border-bottom: 1px dashed rgba(15, 23, 42, 0.15);
  }

  .reportes-dashboard__tips li:last-child {
    border-bottom: none;
  }

  .reportes-dashboard__tips i {
    color: #0d6efd;
  }

  .reportes-dashboard__summary {
    flex: 1 1 260px;
    max-width: 420px;
    border-radius: 20px;
    background: rgba(255, 255, 255, 0.85);
    backdrop-filter: blur(6px);
  }

  .reportes-dashboard__summary-icon {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 42px;
    height: 42px;
    border-radius: 12px;
    font-size: 1.1rem;
  }

  .reportes-dashboard__grid-intro {
    max-width: 720px;
    margin-bottom: 1.5rem;
  }

  .reportes-dashboard__card-wrapper .cardComponent {
    height: 100%;
    border-radius: 18px;
  }

  @media (max-width: 767.98px) {
    .reportes-dashboard__hero {
      padding: 2.5rem 1.25rem;
    }

    .reportes-dashboard__summary {
      max-width: 100%;
    }
  }

  [data-bs-theme="dark"] .reportes-dashboard__hero {
    background: linear-gradient(140deg, rgba(56, 189, 248, 0.16) 0%, rgba(99, 102, 241, 0.2) 100%);
    border-bottom-color: rgba(148, 163, 184, 0.18);
  }

  [data-bs-theme="dark"] .reportes-dashboard__title {
    color: #e2e8f0;
  }

  [data-bs-theme="dark"] .reportes-dashboard__subtitle {
    color: #cbd5f5;
  }

  [data-bs-theme="dark"] .reportes-dashboard__tips li {
    color: #e2e8f0;
    border-bottom-color: rgba(148, 163, 184, 0.25);
  }

  [data-bs-theme="dark"] .reportes-dashboard__summary {
    background: rgba(15, 23, 42, 0.78);
    border: 1px solid rgba(148, 163, 184, 0.25);
  }

  [data-bs-theme="dark"] .reportes-dashboard__grid-intro p {
    color: #cbd5f5;
  }
</style>
@endpushOnce

@pushOnce('script')
@vite(['resources/js/cargarModal.js'])
<script>
  //para el reporte descanso conductores
  function habilitarOpciones() {
    const opciones = document.getElementById('opciones');
    const opcionEvento = document.getElementById('opcionEvento');
    const opcionEventoLabel = document.getElementById('opcionEventoLabel');
    const evento = document.getElementById('evento');

    opciones.style.display = 'block';
    opcionEvento.style.display = 'block';

    if (evento.value == '50') {
      opcionEventoLabel.innerHTML = 'Fecha de salida a descanso';
    } else {
      opcionEventoLabel.innerHTML = 'Fecha de reingreso de descanso';
    }
  }

  //para el reporte ingreso y salidas conductores
  function habilitarInputFiltro(checkElement) {
    const parametro = document.getElementById('parametro');
    const parametroLabel = parametro ? parametro.querySelector('label') : null;
