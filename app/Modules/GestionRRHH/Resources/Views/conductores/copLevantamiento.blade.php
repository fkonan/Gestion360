@extends('layouts.dashboard')

@section('title', 'Levantamiento COP')

@section('breadcrumb')
<x-breadcrumb :items="[
        ['name' => 'Inicio', 'url' => route('home')],
        ['name' => 'Gestion Empleado', 'url' => route('gestion-incapacidades.index')],
        ['name' => 'Levantamiento COP'],
    ]" />
@endsection

@push('css')
<style>
  .cop-shell {
    background: linear-gradient(135deg, rgba(14, 86, 138, 0.08), rgba(247, 249, 252, 0.85));
  }

  .cop-list-card {
    border: 0;
    border-radius: 1rem;
    box-shadow: 0 12px 30px rgba(16, 24, 40, 0.08);
  }

  .cop-block-item {
    border: 1px solid rgba(15, 23, 42, 0.08);
    border-left: 6px solid #9ca3af;
    border-radius: 1rem;
    background: #fff;
    transition: transform .15s ease, box-shadow .15s ease, border-color .15s ease;
  }

  .cop-block-item:hover {
    transform: translateY(-1px);
    box-shadow: 0 10px 24px rgba(15, 23, 42, 0.08);
  }

  .cop-block-item.is-manageable {
    border-left-color: #198754;
    background: linear-gradient(180deg, rgba(25, 135, 84, 0.04), rgba(255, 255, 255, 1));
  }

  .cop-block-item.is-readonly {
    border-left-color: #f59e0b;
    background: linear-gradient(180deg, rgba(245, 158, 11, 0.08), rgba(255, 255, 255, 1));
  }

  .cop-block-origin {
    min-width: 140px;
  }

  .cop-block-status {
    font-size: 0.75rem;
    letter-spacing: .04em;
    text-transform: uppercase;
  }

  .cop-block-action {
    min-width: 180px;
  }

  .cop-context-box {
    border: 1px solid rgba(15, 23, 42, 0.08);
    border-radius: .85rem;
    background: #f8fafc;
  }
</style>
@endpush

@section('content')
@php
  $bloqueosCollection = collect($bloqueos)
    ->sortByDesc(fn ($item) => (int) ($item['tipo']->permite_levantamiento_cop ?? 0))
    ->values();
@endphp
<div class="container-fluid p-0 border rounded sidebar-dark-primary tableContainer cop-shell" style="min-height: 150px;">
  <x-sectionHeader titulo="Levantamiento COP de bloqueos" rutaVolver="{{ route('gestion-incapacidades.index') }}" />

  <div class="p-4">
    <div class="alert alert-light border mb-4">
      Consulte la cédula del conductor para cargar los bloqueos activos detectados en FICS y Logtrans. El levantamiento queda habilitado si el bloqueo está presente en alguno de los dos sistemas.
    </div>

    <form action="{{ route('conductor.levantamiento-cop.index') }}" method="GET" class="row g-3 align-items-end mb-4">
      <div class="col-12 col-lg-4">
        <label for="identificacion" class="form-label">Numero de identificacion</label>
        <input
          type="text"
          class="form-control @if(!empty($searchErrors['identificacion'])) is-invalid @endif"
          id="identificacion"
          name="identificacion"
          value="{{ $identificacion }}"
          placeholder="Ingrese la cédula del conductor">
        @if(!empty($searchErrors['identificacion']))
          <div class="invalid-feedback d-block">{{ $searchErrors['identificacion'][0] }}</div>
        @endif
      </div>
      <div class="col-12 col-lg-auto">
        <button type="submit" class="btn btn-primary">
          <i class="fas fa-search me-1"></i>Consultar bloqueos
        </button>
      </div>
    </form>

    @if($identificacion !== '' && empty($searchErrors) && empty($persona))
      <div class="alert alert-warning">
        No se encontro un conductor activo con la identificacion consultada.
      </div>
    @endif

    @if(!empty($persona))
      <div class="row g-3 mb-4">
        <div class="col-12 col-xl-8">
          <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
              <div class="text-muted small mb-2">Conductor consultado</div>
              <h4 class="mb-1">{{ $persona['nombre'] }}</h4>
              <div class="text-muted">Cédula: {{ $persona['identificacion'] }}</div>
            </div>
          </div>
        </div>
        <div class="col-12 col-xl-4">
          <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
              <div class="text-muted small mb-2">Bloqueos activos detectados</div>
              <div class="display-6 fw-semibold">{{ $bloqueosCollection->count() }}</div>
            </div>
          </div>
        </div>
      </div>

      @if($bloqueosCollection->isEmpty())
        <div class="alert alert-info">
          El conductor no presenta bloqueos activos parametrizados para esta vista.
        </div>
      @else
        <div class="card cop-list-card">
          <div class="card-body p-4">
            <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-2 mb-4">
              <div>
                <div class="text-muted small text-uppercase fw-semibold">Listado principal</div>
                <h5 class="mb-1">Bloqueos detectados para {{ $persona['nombre'] }}</h5>
                <div class="text-muted">Los bloqueos no gestionables se muestran diferenciados y sin accion de levantamiento.</div>
              </div>
            </div>

            <div class="d-flex flex-column gap-3">
              @foreach($bloqueosCollection as $bloqueo)
                @php
                  $tipo = $bloqueo['tipo'];
                  $estado = $bloqueo['estado'];
                  $contexto = $bloqueo['contexto'] ?? [];
                  $campos = $bloqueo['campos'] ?? collect();
                  $bag = $errors->getBag('bloqueo_' . $tipo->id);
                  $modalId = 'copBloqueoModal' . $tipo->id;
                  $esDescanso = $tipo->resolverHandlerKey() === 'descanso';
                  $origenes = [];

                  if ($estado['bloqueado_fics']) {
                    $origenes[] = 'FICS';
                  }

                  if ($estado['bloqueado_logtrans']) {
                    $origenes[] = 'Logtrans';
                  }

                  $origenTexto = empty($origenes) ? 'Sin origen activo' : implode(' + ', $origenes);
                  $permiteCop = (bool) $tipo->permite_levantamiento_cop;
                @endphp

                <div class="cop-block-item {{ $permiteCop ? 'is-manageable' : 'is-readonly' }} p-3 p-lg-4">
                  <div class="d-flex flex-column flex-xl-row justify-content-between align-items-xl-center gap-3">
                    <div class="flex-grow-1">
                      <div class="d-flex flex-wrap align-items-center gap-2 mb-2">
                        <h5 class="mb-0">{{ $tipo->nombre }}</h5>
                        <span class="badge {{ $permiteCop ? 'text-bg-success' : 'text-bg-warning' }}">
                          {{ $permiteCop ? 'Gestionable por COP' : 'Bloqueado sin levantamiento COP' }}
                        </span>
                      </div>

                      <div class="row g-3">
                        <div class="col-12 col-lg-4 cop-block-origin">
                          <div class="text-muted small text-uppercase fw-semibold">Codigo</div>
                          <div class="fw-semibold">{{ $tipo->codigo ?: 'Sin codigo' }}</div>
                        </div>
                        <div class="col-12 col-lg-4 cop-block-origin">
                          <div class="text-muted small text-uppercase fw-semibold">Origen activo</div>
                          <div class="fw-semibold">{{ $origenTexto }}</div>
                        </div>
                        <div class="col-12 col-lg-4">
                          <div class="text-muted small text-uppercase fw-semibold">Estado operativo</div>
                          <div class="cop-block-status {{ $permiteCop ? 'text-success' : 'text-warning' }}">
                            {{ $permiteCop ? 'Listo para gestionar' : 'Visible solo para seguimiento' }}
                          </div>
                        </div>
                      </div>

                      <div class="d-flex flex-wrap gap-2 mt-3">
                        <span class="badge {{ $estado['bloqueado_fics'] ? 'text-bg-danger' : 'text-bg-secondary' }}">
                          FICS {{ $estado['bloqueado_fics'] ? 'activo' : 'sin bloqueo' }}
                        </span>
                        <span class="badge {{ $estado['bloqueado_logtrans'] ? 'text-bg-danger' : 'text-bg-secondary' }}">
                          Logtrans {{ $estado['bloqueado_logtrans'] ? 'activo' : 'sin bloqueo' }}
                        </span>
                      </div>

                      @if(!empty($contexto['ultimo_evento']['evento']))
                        <div class="mt-3 small text-muted">
                          Ultimo evento registrado: <span class="fw-semibold text-dark">{{ $contexto['ultimo_evento']['evento'] }}</span>
                          @if(!empty($contexto['ultimo_evento']['fecha']))
                            <span class="ms-2">({{ $contexto['ultimo_evento']['fecha'] }})</span>
                          @endif
                        </div>
                      @endif
                    </div>

                    <div class="cop-block-action">
                      @if($permiteCop)
                        <button
                          type="button"
                          class="btn btn-outline-primary w-100"
                          data-bs-toggle="modal"
                          data-bs-target="#{{ $modalId }}">
                          <i class="fas fa-sliders-h me-1"></i>Gestionar bloqueo
                        </button>
                      @else
                        <button type="button" class="btn btn-outline-secondary w-100" disabled>
                          <i class="fas fa-eye me-1"></i>Solo informativo
                        </button>
                      @endif
                    </div>
                  </div>
                </div>

                @if($permiteCop)
                  <div class="modal fade" id="{{ $modalId }}" tabindex="-1" aria-hidden="true">
                    <div class="modal-dialog modal-lg modal-dialog-scrollable">
                      <div class="modal-content">
                        <div class="modal-header bg-dark">
                          <div>
                            <div class="small text-white-50 text-uppercase fw-semibold">Gestion COP</div>
                            <h5 class="modal-title text-white lh-sm mb-0">{{ $tipo->nombre }}</h5>
                          </div>
                          <button type="button" class="btn p-0 me-2" data-bs-dismiss="modal" aria-label="Close" style="background: none; border: none;">
                            <img src="{{ asset('img/cerrar-modal.png') }}" alt="Cerrar" style="width: 32px; height: 32px;">
                          </button>
                        </div>

                        <form action="{{ route('conductor.levantamiento-cop.ejecutar', $tipo) }}" method="POST">
                          @csrf
                          <input type="hidden" name="identificacion" value="{{ $identificacion }}">

                          <div class="modal-body">
                            <div class="container-fluid p-2">
                            <div class="d-flex flex-wrap gap-2 mb-3">
                              <span class="badge text-bg-light">Codigo: {{ $tipo->codigo ?: 'Sin codigo' }}</span>
                              <span class="badge {{ $estado['bloqueado_fics'] ? 'text-bg-danger' : 'text-bg-secondary' }}">
                                FICS {{ $estado['bloqueado_fics'] ? 'activo' : 'sin bloqueo' }}
                              </span>
                              <span class="badge {{ $estado['bloqueado_logtrans'] ? 'text-bg-danger' : 'text-bg-secondary' }}">
                                Logtrans {{ $estado['bloqueado_logtrans'] ? 'activo' : 'sin bloqueo' }}
                              </span>
                            </div>

                            @if($esDescanso)
                              <div class="row mb-4 mx-1">
                                <label class="form-label">Numero de identificacion Conductor</label>
                                <input type="text" class="form-control" value="{{ $identificacion }}" disabled>
                              </div>

                              <div class="row mb-4 mx-1">
                                <div class="col-12">
                                  <div class="card border border-info shadow-sm">
                                    <div class="card-body p-3">
                                      <div class="d-flex align-items-start">
                                        <div class="flex-shrink-0">
                                          <div class="rounded-circle p-2" style="width: 40px; height: 40px;">
                                            <i class="fas fa-user-clock"></i>
                                          </div>
                                        </div>
                                        <div class="flex-grow-1 ms-3">
                                          <h6 class="fw-bold mb-2">Ultimo evento registrado</h6>
                                          <div class="fw-semibold">{{ $contexto['ultimo_evento']['nombre'] ?? $persona['nombre'] }}</div>
                                          @if(!empty($contexto['ultimo_evento']['evento']))
                                            <div>{{ $contexto['ultimo_evento']['evento'] }}</div>
                                            <div class="small text-muted">Fecha: {{ $contexto['ultimo_evento']['fecha'] ?? 'Sin fecha registrada' }}</div>
                                          @else
                                            <div class="text-muted">No se encontro un ultimo evento vigente para este conductor.</div>
                                          @endif
                                        </div>
                                      </div>
                                    </div>
                                  </div>
                                </div>
                              </div>

                              @php
                                $campoEvento = $campos->firstWhere('nombre_campo', 'evento');
                                $campoFecha = $campos->firstWhere('nombre_campo', 'fecha');
                                $campoObservacion = $campos->firstWhere('nombre_campo', 'observacion');
                              @endphp

                              @if($campoEvento)
                                @php
                                  $errorKey = $campoEvento->nombre_campo;
                                  $fieldName = 'campos[' . $tipo->id . '][' . $campoEvento->nombre_campo . ']';
                                  $fieldValue = old('campos.' . $tipo->id . '.' . $campoEvento->nombre_campo, $campoEvento->valor_default);
                                  $hasError = $bag->has($errorKey);
                                @endphp
                                <div class="row mb-4">
                                  <div class="col-12 col-lg-8">
                                    <label for="{{ $tipo->id }}_{{ $campoEvento->nombre_campo }}" class="form-label ms-3 mx-1">
                                      {{ $campoEvento->label }}
                                      @if($campoEvento->requerido)
                                        <span class="text-danger">*</span>
                                      @endif
                                    </label>
                                    <select
                                      class="form-select @if($hasError) is-invalid @endif"
                                      id="{{ $tipo->id }}_{{ $campoEvento->nombre_campo }}"
                                      name="{{ $fieldName }}">
                                      <option value="">Seleccione una opcion</option>
                                      @foreach($campoEvento->options ?? [] as $option)
                                        <option value="{{ $option['value'] }}" @selected((string) $fieldValue === (string) $option['value'])>
                                          {{ $option['label'] }}
                                        </option>
                                      @endforeach
                                    </select>
                                    @if($hasError)
                                      <div class="invalid-feedback d-block">{{ $bag->first($errorKey) }}</div>
                                    @endif
                                  </div>
                                </div>
                              @endif

                              @if($campoFecha)
                                @php
                                  $errorKey = $campoFecha->nombre_campo;
                                  $fieldName = 'campos[' . $tipo->id . '][' . $campoFecha->nombre_campo . ']';
                                  $fieldValue = old('campos.' . $tipo->id . '.' . $campoFecha->nombre_campo, $campoFecha->valor_default);
                                  $hasError = $bag->has($errorKey);
                                @endphp
                                <div class="row mb-4 mx-1">
                                  <label for="{{ $tipo->id }}_{{ $campoFecha->nombre_campo }}" class="form-label">
                                    {{ $campoFecha->label }}
                                    @if($campoFecha->requerido)
                                      <span class="text-danger">*</span>
                                    @endif
                                  </label>
                                  <input
                                    type="datetime-local"
                                    class="form-control @if($hasError) is-invalid @endif"
                                    id="{{ $tipo->id }}_{{ $campoFecha->nombre_campo }}"
                                    name="{{ $fieldName }}"
                                    value="{{ $fieldValue }}">
                                  @if($campoFecha->help_text)
                                    <div class="form-text">{{ $campoFecha->help_text }}</div>
                                  @endif
                                  @if($hasError)
                                    <div class="invalid-feedback d-block">{{ $bag->first($errorKey) }}</div>
                                  @endif
                                </div>
                              @endif

                              @if($campoObservacion)
                                @php
                                  $errorKey = $campoObservacion->nombre_campo;
                                  $fieldName = 'campos[' . $tipo->id . '][' . $campoObservacion->nombre_campo . ']';
                                  $fieldValue = old('campos.' . $tipo->id . '.' . $campoObservacion->nombre_campo, $campoObservacion->valor_default);
                                  $hasError = $bag->has($errorKey);
                                @endphp
                                <div class="row mb-4 mx-1">
                                  <label for="{{ $tipo->id }}_{{ $campoObservacion->nombre_campo }}" class="form-label">
                                    {{ $campoObservacion->label }}
                                    @if($campoObservacion->requerido)
                                      <span class="text-danger">*</span>
                                    @endif
                                  </label>
                                  <textarea
                                    class="form-control @if($hasError) is-invalid @endif"
                                    id="{{ $tipo->id }}_{{ $campoObservacion->nombre_campo }}"
                                    name="{{ $fieldName }}"
                                    rows="3"
                                    placeholder="{{ $campoObservacion->placeholder }}">{{ $fieldValue }}</textarea>
                                  @if($campoObservacion->help_text)
                                    <div class="form-text">{{ $campoObservacion->help_text }}</div>
                                  @endif
                                  @if($hasError)
                                    <div class="invalid-feedback d-block">{{ $bag->first($errorKey) }}</div>
                                  @endif
                                </div>
                              @endif
                            @elseif($campos->isEmpty())
                              <div class="alert alert-light border mb-0">
                                Este bloqueo no requiere datos adicionales. Puede ejecutar el levantamiento directamente.
                              </div>
                            @else
                              <div class="row g-3">
                                @foreach($campos as $campo)
                                  @php
                                    $errorKey = $campo->nombre_campo;
                                    $fieldName = 'campos[' . $tipo->id . '][' . $campo->nombre_campo . ']';
                                    $fieldValue = old('campos.' . $tipo->id . '.' . $campo->nombre_campo, $campo->valor_default);
                                    $hasError = $bag->has($errorKey);
                                  @endphp

                                  @if($campo->visible)
                                    <div class="col-12 {{ $campo->tipo_input === 'textarea' ? '' : 'col-lg-6' }}">
                                      <label for="{{ $tipo->id }}_{{ $campo->nombre_campo }}" class="form-label">
                                        {{ $campo->label }}
                                        @if($campo->requerido)
                                          <span class="text-danger">*</span>
                                        @endif
                                      </label>

                                      @if($campo->tipo_input === 'textarea')
                                        <textarea
                                          class="form-control @if($hasError) is-invalid @endif"
                                          id="{{ $tipo->id }}_{{ $campo->nombre_campo }}"
                                          name="{{ $fieldName }}"
                                          rows="3"
                                          placeholder="{{ $campo->placeholder }}">{{ $fieldValue }}</textarea>
                                      @elseif($campo->tipo_input === 'select')
                                        <select
                                          class="form-select @if($hasError) is-invalid @endif"
                                          id="{{ $tipo->id }}_{{ $campo->nombre_campo }}"
                                          name="{{ $fieldName }}">
                                          <option value="">Seleccione una opcion</option>
                                          @foreach($campo->options ?? [] as $option)
                                            <option value="{{ $option['value'] }}" @selected((string) $fieldValue === (string) $option['value'])>
                                              {{ $option['label'] }}
                                            </option>
                                          @endforeach
                                        </select>
                                      @elseif($campo->tipo_input === 'datetime')
                                        <input
                                          type="datetime-local"
                                          class="form-control @if($hasError) is-invalid @endif"
                                          id="{{ $tipo->id }}_{{ $campo->nombre_campo }}"
                                          name="{{ $fieldName }}"
                                          value="{{ $fieldValue }}">
                                      @else
                                        <input
                                          type="{{ $campo->tipo_input }}"
                                          class="form-control @if($hasError) is-invalid @endif"
                                          id="{{ $tipo->id }}_{{ $campo->nombre_campo }}"
                                          name="{{ $fieldName }}"
                                          value="{{ $fieldValue }}"
                                          placeholder="{{ $campo->placeholder }}">
                                      @endif

                                      @if($campo->help_text)
                                        <div class="form-text">{{ $campo->help_text }}</div>
                                      @endif

                                      @if($hasError)
                                        <div class="invalid-feedback d-block">{{ $bag->first($errorKey) }}</div>
                                      @endif
                                    </div>
                                  @endif
                                @endforeach
                              </div>
                            @endif
                            </div>
                          </div>

                          <div class="modal-footer">
                            <button type="button" class="btn btn-dark" data-bs-dismiss="modal">Cancelar</button>
                            <button type="submit" class="btn btn-success">
                              <i class="fas fa-unlock me-1"></i>{{ $esDescanso ? 'Aceptar' : 'Ejecutar levantamiento' }}
                            </button>
                          </div>
                        </form>
                      </div>
                    </div>
                  </div>
                @endif
              @endforeach
            </div>
          </div>
        </div>
      @endif
    @endif
  </div>
</div>
@endsection

@push('script')
<script>
  document.addEventListener('DOMContentLoaded', function () {
    const modalIds = @json(
      $bloqueosCollection
        ->filter(fn ($item) => (bool) ($item['tipo']->permite_levantamiento_cop ?? false))
        ->filter(fn ($item) => $errors->getBag('bloqueo_' . $item['tipo']->id)->any())
        ->map(fn ($item) => 'copBloqueoModal' . $item['tipo']->id)
        ->values()
    );

    if (!Array.isArray(modalIds) || modalIds.length === 0 || !window.bootstrap?.Modal) {
      return;
    }

    const modalEl = document.getElementById(modalIds[0]);
    if (!modalEl) {
      return;
    }

    window.bootstrap.Modal.getOrCreateInstance(modalEl).show();
  });
</script>
@endpush
