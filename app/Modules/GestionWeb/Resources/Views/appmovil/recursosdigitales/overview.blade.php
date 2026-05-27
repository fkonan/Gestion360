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

@section('content')
<div class="container-fluid p-0 border rounded sidebar-dark-primary tableContainer" style="min-height: 150px;">
  <x-sectionHeader
    titulo="Gestion de recursos digitales appmovil"
    rutaVolver="{{ route('gestion-appmovil.index') }}"
    :crear="false" />

  <div class="px-4 py-3">
    <div class="alert alert-info border-start border-4 border-primary shadow-sm mb-4">
      <strong>Vista general por grupos.</strong>
      Desde aqui puedes entrar al tipo exacto que necesitas administrar y mantener separados banners, documentos y enlaces.
    </div>

    <div class="row g-3 mb-4">
      <div class="col-12 col-md-3">
        <div class="card shadow-sm border-0 h-100">
          <div class="card-body">
            <div class="text-muted small text-uppercase">Tipos disponibles</div>
            <div class="fs-3 fw-bold">{{ $summary['total_types'] }}</div>
          </div>
        </div>
      </div>
      <div class="col-12 col-md-3">
        <div class="card shadow-sm border-0 h-100">
          <div class="card-body">
            <div class="text-muted small text-uppercase">Tipos con recursos</div>
            <div class="fs-3 fw-bold">{{ $summary['types_with_resources'] }}</div>
          </div>
        </div>
      </div>
      <div class="col-12 col-md-3">
        <div class="card shadow-sm border-0 h-100">
          <div class="card-body">
            <div class="text-muted small text-uppercase">Recursos totales</div>
            <div class="fs-3 fw-bold text-primary">{{ $summary['total_resources'] }}</div>
          </div>
        </div>
      </div>
      <div class="col-12 col-md-3">
        <div class="card shadow-sm border-0 h-100">
          <div class="card-body">
            <div class="text-muted small text-uppercase">Recursos activos</div>
            <div class="fs-3 fw-bold text-success">{{ $summary['active_resources'] }}</div>
          </div>
        </div>
      </div>
    </div>

    @foreach($groupedTypes as $group)
      <div class="card shadow-sm border-0 mb-4">
        <div class="card-body p-4">
          <div class="d-flex flex-column flex-xl-row justify-content-between gap-3 mb-4">
            <div>
              <div class="d-flex flex-wrap align-items-center gap-2 mb-2">
                <span class="badge text-bg-{{ $group['accent'] }}">
                  <i class="fas {{ $group['icon'] }} me-1"></i>{{ $group['title'] }}
                </span>
                <span class="badge text-bg-light border">{{ $group['total_types'] }} tipos</span>
                <span class="badge text-bg-light border">{{ $group['total_resources'] }} recursos</span>
              </div>
              <h5 class="mb-1">{{ $group['title'] }}</h5>
              <p class="text-muted mb-0">{{ $group['description'] }}</p>
            </div>

            <div class="row row-cols-2 g-2 text-center align-self-start">
              <div class="col">
                <div class="border rounded px-3 py-2 bg-light h-100">
                  <div class="small text-muted text-uppercase">Con recursos</div>
                  <div class="fw-bold">{{ $group['types_with_resources'] }}</div>
                </div>
              </div>
              <div class="col">
                <div class="border rounded px-3 py-2 bg-light h-100">
                  <div class="small text-muted text-uppercase">Activos</div>
                  <div class="fw-bold text-success">{{ $group['active_resources'] }}</div>
                </div>
              </div>
            </div>
          </div>

          <div class="row g-3">
            @foreach($group['types'] as $type)
              <div class="col-12 col-md-6 col-xl-4">
                <div class="border rounded-3 p-3 h-100 {{ $type->has_resources ? '' : 'bg-light-subtle' }}">
                  <div class="d-flex justify-content-between align-items-start gap-2 mb-3">
                    <span class="badge text-bg-dark">{{ $type->type_config['selector_badge'] }}</span>
                    <span class="badge {{ $type->has_resources ? 'text-bg-success' : 'text-bg-secondary' }}">
                      {{ $type->total_recursos }} recursos
                    </span>
                  </div>

                  <h6 class="mb-1">{{ $type->clean_description }}</h6>
                  <p class="small text-muted mb-3">{{ $type->type_config['family_description'] }}</p>

                  <div class="row g-2 small mb-3">
                    <div class="col-4">
                      <div class="border rounded px-2 py-2 text-center h-100">
                        <div class="text-muted text-uppercase" style="font-size: 11px;">Total</div>
                        <div class="fw-bold">{{ $type->total_recursos }}</div>
                      </div>
                    </div>
                    <div class="col-4">
                      <div class="border rounded px-2 py-2 text-center h-100">
                        <div class="text-muted text-uppercase" style="font-size: 11px;">Activos</div>
                        <div class="fw-bold text-success">{{ $type->activos_count }}</div>
                      </div>
                    </div>
                    <div class="col-4">
                      <div class="border rounded px-2 py-2 text-center h-100">
                        <div class="text-muted text-uppercase" style="font-size: 11px;">Inactivos</div>
                        <div class="fw-bold text-secondary">{{ $type->inactivos_count }}</div>
                      </div>
                    </div>
                  </div>

                  <div class="d-grid gap-2">
                    <a class="btn btn-primary btn-sm" href="{{ route('recursos-digitales.show', ['tipo' => $type->IdTipoRecurso]) }}">
                      Gestionar tipo
                    </a>
                    <a class="btn btn-outline-secondary btn-sm" href="{{ route('recursos-digitales.create', ['tipo' => $type->IdTipoRecurso]) }}">
                      {{ $type->type_config['create_label'] }}
                    </a>
                  </div>
                </div>
              </div>
            @endforeach
          </div>
        </div>
      </div>
    @endforeach
  </div>
</div>
@endsection
