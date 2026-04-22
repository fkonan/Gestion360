@extends('layouts.dashboard')

@section('title', 'Gestion appmovil')

@section('breadcrumb')
<x-breadcrumb :items="[
        ['name' => 'Inicio', 'url' => route('home')],
        ['name' => 'Gestion appmovil', 'url' => route('gestion-appmovil.index')],
        ['name' => 'Recursos digitales'],
    ]" />
<br>
@endsection

@push('css')
<style>
  .rd-overview .card,
  .rd-overview .accordion-item {
    border-radius: 1rem;
  }

  .rd-overview .accordion-button {
    background-color: var(--bs-body-bg);
    color: var(--bs-emphasis-color);
    box-shadow: none;
  }

  .rd-overview .accordion-button:not(.collapsed) {
    background-color: var(--bs-tertiary-bg);
    color: var(--bs-emphasis-color);
  }

  .rd-overview .accordion-button:focus {
    box-shadow: none;
  }

  .rd-overview .accordion-body {
    background-color: var(--bs-tertiary-bg);
  }

  .rd-overview .list-group-item {
    background-color: transparent;
    border-color: var(--bs-border-color);
  }

  [data-bs-theme="dark"] .rd-overview .card {
    background-color: #101a2d;
    border: 1px solid rgba(80, 105, 145, 0.25) !important;
  }

  [data-bs-theme="dark"] .rd-overview .accordion-item {
    background-color: #101a2d;
    border: 1px solid rgba(80, 105, 145, 0.25) !important;
  }

  [data-bs-theme="dark"] .rd-overview .accordion-button {
    background-color: #101a2d;
    color: #eef4ff;
  }

  [data-bs-theme="dark"] .rd-overview .accordion-button:not(.collapsed) {
    background-color: #12203a;
    color: #ffffff;
  }

  [data-bs-theme="dark"] .rd-overview .accordion-body {
    background-color: #0f192b;
  }

  [data-bs-theme="dark"] .rd-overview .list-group-item {
    background-color: #162235;
    border-color: rgba(80, 105, 145, 0.2);
    color: #eef4ff;
  }

  [data-bs-theme="dark"] .rd-overview .text-muted {
    color: #9fb3d2 !important;
  }

  [data-bs-theme="dark"] .rd-overview .badge.text-bg-light {
    background-color: #1d2b42 !important;
    color: #eef4ff !important;
    border-color: rgba(92, 122, 166, 0.4) !important;
  }
</style>
@endpush

@section('content')
<div class="container-fluid p-0 border rounded sidebar-dark-primary tableContainer" style="min-height: 150px;">
  <x-sectionHeader
    titulo="Gestion de recursos digitales appmovil"
    rutaVolver="{{ route('gestion-appmovil.index') }}"
    :crear="false" />

  <div class="px-4 py-3 rd-overview">
    <div class="card shadow-sm border-0 mb-4">
      <div class="card-body p-4">
        <div class="d-flex flex-column flex-xl-row justify-content-between gap-3">
          <div>
            <div class="small text-uppercase text-muted mb-2">Recursos digitales appmovil</div>
            <h4 class="mb-1">Administracion simple por grupos</h4>
            <p class="text-muted mb-0">
              Selecciona primero el grupo y luego entra al conjunto exacto que necesitas administrar. Asi evitas ver todo al mismo tiempo.
            </p>
          </div>

          <div class="d-flex flex-wrap gap-2 align-content-start">
            <span class="badge rounded-pill text-bg-light border px-3 py-2">{{ $summary['total_types'] }} grupos disponibles</span>
            <span class="badge rounded-pill text-bg-light border px-3 py-2">{{ $summary['types_with_resources'] }} con contenido</span>
            <span class="badge rounded-pill text-bg-light border px-3 py-2">{{ $summary['total_resources'] }} recursos</span>
            <span class="badge rounded-pill text-bg-light border px-3 py-2">{{ $summary['active_resources'] }} activos</span>
          </div>
        </div>
      </div>
    </div>

    <div class="accordion" id="recursosDigitalesAccordion">
      @foreach($groupedTypes as $group)
        <div class="accordion-item border-0 shadow-sm mb-3 rounded overflow-hidden">
          <h2 class="accordion-header" id="heading-{{ $loop->index }}">
            <button
              class="accordion-button {{ $loop->first ? '' : 'collapsed' }} bg-body"
              type="button"
              data-bs-toggle="collapse"
              data-bs-target="#collapse-{{ $loop->index }}"
              aria-expanded="{{ $loop->first ? 'true' : 'false' }}"
              aria-controls="collapse-{{ $loop->index }}">
              <div class="w-100 d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3 pe-3">
                <div>
                  <div class="d-flex align-items-center gap-2 mb-1">
                    <span class="badge text-bg-{{ $group['accent'] }}">
                      <i class="fas {{ $group['icon'] }} me-1"></i>{{ $group['title'] }}
                    </span>
                  </div>
                  <div class="fw-semibold">{{ $group['title'] }}</div>
                  <div class="small text-muted">{{ $group['description'] }}</div>
                </div>

                <div class="d-flex flex-wrap gap-2">
                  <span class="badge rounded-pill text-bg-light border">{{ $group['total_types'] }} conjuntos</span>
                  <span class="badge rounded-pill text-bg-light border">{{ $group['total_resources'] }} recursos</span>
                  <span class="badge rounded-pill text-bg-light border">{{ $group['active_resources'] }} activos</span>
                </div>
              </div>
            </button>
          </h2>

          <div
            id="collapse-{{ $loop->index }}"
            class="accordion-collapse collapse {{ $loop->first ? 'show' : '' }}"
            aria-labelledby="heading-{{ $loop->index }}"
            data-bs-parent="#recursosDigitalesAccordion">
            <div class="accordion-body p-0">
              <div class="list-group list-group-flush">
                @foreach($group['types'] as $type)
                  <div class="list-group-item px-4 py-3">
                    <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3">
                      <div>
                        <div class="fw-semibold">{{ $type->clean_description }}</div>
                        <div class="small text-muted">
                          {{ $type->total_recursos }} recursos registrados,
                          {{ $type->activos_count }} activos
                          @if(! $type->has_resources)
                            . Aun no hay contenido cargado.
                          @endif
                        </div>
                      </div>

                      <div class="d-flex gap-2 align-items-center">
                        <a class="btn btn-primary btn-sm" href="{{ route('recursos-digitales.show', ['tipo' => $type->IdTipoRecurso]) }}">
                          Gestionar
                        </a>
                      </div>
                    </div>
                  </div>
                @endforeach
              </div>
            </div>
          </div>
        </div>
      @endforeach
    </div>
  </div>
</div>
@endsection
