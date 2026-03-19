@extends('layouts.dashboard')

@section('title', 'Gestion appmovil')

@section('breadcrumb')
<x-breadcrumb :items="[
        ['name' => 'Inicio', 'url' => route('home')],
        ['name' => 'Gestion appmovil', 'url' => route('gestion-appmovil.index')],
        ['name' => 'Recursos digitales', 'url' => route('recursos-digitales.index')],
        ['name' => $currentType->clean_description],
    ]" />
<br>
@endsection

@section('content')
<div class="container-fluid p-0 border rounded sidebar-dark-primary tableContainer" style="min-height: 150px;">
  <x-sectionHeader
    titulo="{{ $typeConfig['section_title'] }}"
    rutaVolver="{{ route('recursos-digitales.index') }}"
    :crear="false" />

  <div class="px-4 pt-3 pb-4">
    <div class="card shadow-sm border-0 mb-4">
      <div class="card-body p-4">
        <div class="d-flex flex-column flex-xl-row justify-content-between gap-3">
          <div>
            <div class="d-flex flex-wrap align-items-center gap-2 mb-2">
              <span class="badge text-bg-{{ $currentType->family_config['accent'] }}">
                <i class="fas {{ $currentType->family_config['icon'] }} me-1"></i>{{ $currentType->family_config['title'] }}
              </span>
            </div>
            <h4 class="mb-1">{{ $currentType->clean_description }}</h4>
            <p class="text-muted mb-0">{{ $typeConfig['list_intro'] }}</p>
          </div>

          <div class="d-flex align-items-start align-items-xl-center gap-2">
            <a class="btn btn-outline-secondary" href="{{ route('recursos-digitales.index') }}">
              Ver grupos
            </a>
            <a class="btn btn-warning fw-bold" href="{{ route('recursos-digitales.create', ['tipo' => $currentType->IdTipoRecurso]) }}">
              {{ $typeConfig['create_label'] }}
            </a>
          </div>
        </div>
      </div>
    </div>

    @if($recursos->isEmpty())
      <div class="alert alert-warning mb-0 d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3">
        <div>
          <strong>No hay {{ $typeConfig['plural_label'] }}</strong> registrados para este tipo.
        </div>
        <a class="btn btn-warning btn-sm fw-bold align-self-start align-self-lg-auto" href="{{ route('recursos-digitales.create', ['tipo' => $currentType->IdTipoRecurso]) }}">
          {{ $typeConfig['create_label'] }}
        </a>
      </div>
    @elseif($typeConfig['detail_layout'] === 'gallery')
      <div class="row g-4">
        @foreach($recursos as $recurso)
          <div class="col-12 col-md-6 col-xl-4">
            <div class="card h-100 shadow-sm border-0 overflow-hidden">
              <div class="position-relative">
                <img
                  src="{{ $recurso->preview_url }}"
                  data-placeholder="{{ $placeholderPreview }}"
                  onerror="this.onerror=null;this.src=this.dataset.placeholder;"
                  class="w-100 object-fit-cover {{ $recurso->es_activo ? '' : 'opacity-50' }}"
                  style="height: 240px;"
                  alt="{{ $recurso->display_name }}">

                <span class="position-absolute top-0 start-0 m-3 badge text-bg-light border">
                  Orden {{ $recurso->Orden }}
                </span>

                <span class="position-absolute top-0 end-0 m-3 badge {{ $recurso->es_activo ? 'text-bg-success' : 'text-bg-light border' }}">
                  {{ $recurso->Estado }}
                </span>
              </div>

              <div class="card-body d-flex flex-column gap-3">
                <div class="small text-muted">
                  {{ $recurso->display_name }}
                </div>

                <div class="d-flex justify-content-between align-items-center rounded border px-3 py-2 bg-light-subtle">
                  <div>
                    <div class="small text-muted text-uppercase">Estado del recurso</div>
                    <div class="fw-semibold {{ $recurso->es_activo ? 'text-success' : 'text-secondary' }}">
                      {{ $recurso->es_activo ? 'Activo' : 'Inactivo' }}
                    </div>
                  </div>

                  <div class="form-check form-switch m-0">
                    <input
                      class="form-check-input"
                      type="checkbox"
                      role="switch"
                      {{ $recurso->es_activo ? 'checked' : '' }}
                      onchange="actualizarEstadoRecurso(this, '{{ route('recursos-digitales.cambiarEstado', ['id' => $recurso->IdRecurso]) }}')">
                  </div>
                </div>

                <div class="row g-2 mt-auto">
                  <div class="col-12 col-sm-6">
                    <a class="btn btn-outline-secondary btn-sm w-100" href="{{ $recurso->preview_url }}" target="_blank" rel="noopener noreferrer">
                      Ver imagen
                    </a>
                  </div>
                  <div class="col-12 col-sm-6">
                    <a class="btn btn-primary btn-sm w-100" href="{{ route('recursos-digitales.edit', ['id' => $recurso->IdRecurso]) }}">
                      Editar
                    </a>
                  </div>
                </div>
              </div>
            </div>
          </div>
        @endforeach
      </div>
    @else
      <div class="card shadow-sm border-0">
        <div class="list-group list-group-flush">
          @foreach($recursos as $recurso)
            <div class="list-group-item px-4 py-3">
              <div class="d-flex flex-column flex-xl-row justify-content-between align-items-xl-center gap-3">
                <div class="d-flex align-items-start gap-3">
                  <div class="rounded-circle bg-light d-inline-flex align-items-center justify-content-center flex-shrink-0" style="width: 46px; height: 46px;">
                    @if(($recurso->preview_icon_type ?? 'font') === 'image' && filled($recurso->preview_icon_asset))
                      <img src="{{ $recurso->preview_icon_asset }}" alt="PDF" style="width: 24px; height: 24px; object-fit: contain;">
                    @else
                      <i class="fas {{ $recurso->preview_icon }} text-primary"></i>
                    @endif
                  </div>
                  <div>
                    <div class="fw-semibold">{{ $recurso->display_name }}</div>
                    <div class="small text-muted text-break">{{ $recurso->short_url }}</div>
                    <div class="d-flex flex-wrap gap-2 mt-2">
                      <span class="badge rounded-pill text-bg-light border">Orden {{ $recurso->Orden }}</span>
                      <span class="badge rounded-pill text-bg-light border">{{ $recurso->preview_label }}</span>
                      <span class="badge rounded-pill text-bg-light border">{{ $recurso->source_label }}</span>
                    </div>
                  </div>
                </div>

                <div class="d-flex flex-wrap align-items-center gap-2">
                  <div class="form-check form-switch m-0">
                    <input
                      class="form-check-input"
                      type="checkbox"
                      role="switch"
                      {{ $recurso->es_activo ? 'checked' : '' }}
                      onchange="actualizarEstadoRecurso(this, '{{ route('recursos-digitales.cambiarEstado', ['id' => $recurso->IdRecurso]) }}')">
                  </div>
                  <span class="badge {{ $recurso->es_activo ? 'text-bg-success' : 'text-bg-light border' }}">
                    {{ $recurso->es_activo ? 'Activo' : 'Inactivo' }}
                  </span>
                  <a class="btn btn-outline-secondary btn-sm" href="{{ $recurso->preview_url }}" target="_blank" rel="noopener noreferrer">
                    Abrir
                  </a>
                  <a class="btn btn-primary btn-sm" href="{{ route('recursos-digitales.edit', ['id' => $recurso->IdRecurso]) }}">
                    Editar
                  </a>
                </div>
              </div>
            </div>
          @endforeach
        </div>
      </div>
    @endif
  </div>
</div>
@endsection

@pushOnce('script')
<script>
  function actualizarEstadoRecurso(element, url) {
    const checkedBeforeRequest = element.checked;
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

    $.ajax({
      url: url,
      type: 'POST',
      headers: csrfToken ? {
        'X-CSRF-TOKEN': csrfToken
      } : {},
      data: {
        _token: csrfToken
      },
      success: function(response) {
        if (response.type !== 'success') {
          element.checked = !checkedBeforeRequest;
        }

        mostrarToast(response.message, response.type || 'primary');
      },
      error: function(xhr) {
        element.checked = !checkedBeforeRequest;
        mostrarToast(xhr.responseJSON?.message || 'Error al cambiar el estado del recurso', 'danger');
      }
    });
  }
</script>
@endpushOnce
