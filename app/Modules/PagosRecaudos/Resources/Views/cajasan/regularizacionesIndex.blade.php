@extends('layouts.dashboard')

@section('title','Regularizaciones Cajasan')

@section('breadcrumb')
<x-breadcrumb :items="[
        ['name' => 'Inicio', 'url' => route('home')],
        ['name' => 'Pago convenio', 'url' => route('pagosConvenios.index')],
        ['name' => 'Regularizaciones']
    ]" />
<br>
@endsection

@section('headerInfo')
<div class="header-info-pagos-recaudos d-flex flex-wrap justify-content-end align-items-center gap-2 text-end">
  <span class="fw-bold small">PANEL</span>
  <span>
    <small>Alcance:</small> <span class="fw-bold small">SUPER ADMIN</span>
  </span>
  <span>
    <small>Fecha:</small> <span class="fw-bold small">{{ now('America/Bogota')->format('d/m/Y H:i') }}</span>
  </span>
</div>
@endsection

@section('content')
<div class="container-fluid p-0 border rounded sidebar-dark-primary tableContainer" style="min-height:150px;">
  <x-sectionHeader
    titulo="Casos pendientes de regularización"
    rutaVolver="{{ route('pagosConvenios.index') }}"
    :clasePosition="false">
    <a
      style="min-width: 170px;"
      class="btn btn-success fw-bold js-regularizacion-link"
      data-loader-message="Cargando modulo de pagos..."
      href="{{ route('pagosConvenios.index') }}">Volver a pagos</a>
    <a
      style="min-width: 150px;"
      class="btn btn-outline-secondary fw-bold js-regularizacion-link"
      data-loader-message="Redirigiendo al inicio..."
      href="{{ route('home') }}">Inicio</a>
    <span class="badge bg-light text-dark">{{ $casos->count() }} registros en pagina</span>
  </x-sectionHeader>

  <form method="GET" action="{{ route('pagosConvenios.regularizaciones.index') }}" class="px-4 pt-4 pb-2 js-regularizacion-filter-form">
    <div class="row g-3 align-items-end">
      <div class="col-12 col-md-10">
        <label for="q" class="form-label">Buscar por detalle, identificacion, cliente, numero interno o agencia</label>
        <input
          type="text"
          class="form-control"
          id="q"
          name="q"
          value="{{ $filters['q'] }}"
          placeholder="Ej: 35643581, 91013901, Maria Perez">
      </div>

      <div class="col-12 col-md-2 d-flex gap-2">
        <button
          type="submit"
          class="btn btn-primary w-100"
          data-loading-text="Filtrando..."
          data-loader-message="Filtrando casos de regularizacion...">
          Filtrar
        </button>
      </div>
    </div>
  </form>

  <div id="no-more-tables" class="table-responsive" style="padding:1.5em">
    @if($casos->count() > 0)
    <table
      id="tablaRegularizaciones"
      class="table table-sm table-striped"
      data-toggle="table"
      data-locale="es-ES"
      data-search="false"
      data-pagination="false"
      data-detail-view="true"
      data-detail-formatter="detalleRegularizaciones"
      data-check-on-init="true">
      <thead class="table-primary">
        <tr>
          <th data-field="detalle_id" class="text-nowrap">Detalle</th>
          <th data-field="fecha_hora" class="text-nowrap">Fecha</th>
          <th data-field="identificacion" class="text-nowrap">Identificacion</th>
          <th data-field="cliente">Cliente</th>
          <th data-field="valor" class="text-end text-nowrap">Valor</th>
          <th data-field="agencia">Agencia</th>
          <th data-field="usrcreacion" class="text-nowrap">UsrCreacion</th>
          <th data-field="motivo_pendiente" class="text-nowrap">Motivo</th>
          <th data-field="nro_interno" class="text-nowrap">Nro interno</th>
          <th data-field="detalle_contable" class="text-nowrap">Detalle contable</th>
          <th data-field="acciones" class="text-center text-nowrap">Accion</th>
        </tr>
      </thead>
      <tbody>
        @foreach($casos as $caso)
        <tr>
          <td>{{ $caso->id }}</td>
          <td>{{ \Carbon\Carbon::parse($caso->feccreacion)->format('d/m/Y H:i:s') }}</td>
          <td>{{ $caso->identificacion }}</td>
          <td>{{ $caso->cliente }}</td>
          <td class="text-end">${{ number_format((float) $caso->valortotal, 0, ',', '.') }}</td>
          <td>{{ $caso->agencia ?: 'N/A' }}</td>
          <td>{{ $caso->usrcreacion }}</td>
          <td>
            @if($caso->motivo_pendiente === 'ESTADO_C_Y_SIN_NRO_INTERNO')
            <span class="badge bg-danger">Estado C y sin nro</span>
            @elseif($caso->motivo_pendiente === 'ESTADO_C')
            <span class="badge bg-warning text-dark">Estado C</span>
            @else
            <span class="badge bg-secondary">Sin nro interno</span>
            @endif
          </td>
          <td>{{ $caso->nro_interno ?: 'N/A' }}</td>
          <td>
            <span class="badge {{ (int) $caso->tiene_detalle_contable === 1 ? 'bg-warning text-dark' : 'bg-secondary' }}">
              {{ (int) $caso->tiene_detalle_contable === 1 ? 'SI' : 'NO' }}
            </span>
          </td>
          <td class="text-center">
            <a
              href="{{ route('pagosConvenios.regularizaciones.show', $caso->id) }}"
              class="btn btn-outline-primary btn-sm js-regularizacion-link"
              data-loader-message="Cargando caso {{ $caso->id }}...">
              {{ (int) $caso->es_regularizable === 1 ? 'Gestionar' : 'Revisar' }}
            </a>
          </td>
        </tr>
        @endforeach
      </tbody>
    </table>
    @else
    <div class="text-center py-5">
      <i class="bi bi-shield-check text-muted display-4 d-block mb-3"></i>
      <h5 class="text-muted">No hay casos con estado C y sin numero interno</h5>
      <p class="text-muted mb-0">Solo se listan detalles que cumplan ambas condiciones al mismo tiempo.</p>
    </div>
    @endif
  </div>

  @if($casos->hasPages())
  <div class="px-4 pb-4 d-flex justify-content-end">
    {{ $casos->links() }}
  </div>
  @endif
</div>
@endsection

@pushOnce('script')
<script>
  document.addEventListener('DOMContentLoaded', function () {
    if (document.getElementById('tablaRegularizaciones')) {
      initTablaBootstrapTable(
        '#tablaRegularizaciones',
        {
          protegidas: ['detalle_id', 'fecha_hora', 'identificacion', 'acciones'],
          forzarDetalle: true,
        },
        'detalleRegularizaciones'
      );
    }

    const loader = document.getElementById('fullscreen-loader');
    const loaderLabel = loader ? loader.querySelector('span') : null;

    const showLoader = (message) => {
      if (loaderLabel && message) {
        loaderLabel.textContent = message;
      }
      if (loader) {
        loader.style.display = 'flex';
      }
    };

    document.querySelectorAll('.js-regularizacion-link').forEach((link) => {
      link.addEventListener('click', function () {
        showLoader(this.dataset.loaderMessage || 'Cargando...');
      });
    });

    document.querySelectorAll('.js-regularizacion-filter-form').forEach((form) => {
      form.addEventListener('submit', function () {
        const submitButton = form.querySelector('button[type="submit"]');
        if (submitButton) {
          submitButton.dataset.originalText = submitButton.dataset.originalText || submitButton.innerHTML;
          submitButton.disabled = true;
          submitButton.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>' + (submitButton.dataset.loadingText || 'Procesando...');
        }

        showLoader(submitButton?.dataset.loaderMessage || 'Procesando solicitud...');
      });
    });
  });
</script>
@endpushOnce
