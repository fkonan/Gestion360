@extends('layouts.dashboard')

@section('title','Reversos')

@section('breadcrumb')
<x-breadcrumb :items="[
        ['name' => 'Inicio', 'url' => route('home')],
        ['name' => 'Pago convenio', 'url' => route('pagosConvenios.index')],
        ['name' => 'Reversos']
    ]" />
<br>
@endsection

@section('headerInfo')
<div class="header-info-pagos-recaudos d-flex flex-wrap justify-content-end align-items-center gap-2 text-end">
  <span class="fw-bold small">CAJA ACTIVA</span>
  <span>
    <small>Sucursal:</small> <span class="fw-bold small">{{ $cajaActiva->nomsucursal }}</span>
  </span>
  <span>
    <small>Fecha apertura:</small> <span class="fw-bold small">{{ \Carbon\Carbon::parse($cajaActiva->fecini)->format('d/m/Y H:i:s') }}</span>
  </span>
</div>
@endsection

@section('content')
<div class="container-fluid p-0 border rounded sidebar-dark-primary tableContainer" style="min-height:150px;">
  <div class="border rounded-top d-flex justify-content-between align-items-center px-4 bg-primary-subtle">
    <span class="text-left text-light fs-5 fw-medium py-1">Reversos registrados</span>
    <span class="badge bg-light text-dark">
      {{ $reversos->count() }} registros en pagina
    </span>
  </div>

  <div class="d-flex flex-wrap gap-2 ms-4 mt-3">
    <a style="width: 160px;" class="btn btn-success fw-bold" href="{{ route('pagosConvenios.index') }}">Nuevo pago</a>
    <a style="width: 160px;" class="btn btn-outline-primary fw-bold" href="{{ route('pagosConvenios.historialHoy') }}">Pagos del dia</a>
    <a style="width: 160px;" class="btn btn-outline-secondary fw-bold" href="{{ route('home') }}">Inicio</a>
  </div>

  <form method="GET" action="{{ route('pagosConvenios.historialReversos') }}" class="p-4 pb-2">
    <div class="row g-3 align-items-end">
      <div class="col-12 col-md-7">
        <label for="q" class="form-label">Buscar por detalle, cliente, identificacion o codigo de autorizacion</label>
        <input
          type="text"
          class="form-control"
          id="q"
          name="q"
          value="{{ $filters['q'] }}"
          placeholder="Ej: 12345, 1007408720, Maria Perez">
      </div>

      <div class="col-12 col-md-3">
        <label for="resultado" class="form-label">Resultado del reverso</label>
        <select class="form-select" id="resultado" name="resultado">
          <option value="todos" {{ $filters['resultado'] === 'todos' ? 'selected' : '' }}>Todos</option>
          <option value="exitoso" {{ $filters['resultado'] === 'exitoso' ? 'selected' : '' }}>Exitoso</option>
          <option value="fallido" {{ $filters['resultado'] === 'fallido' ? 'selected' : '' }}>Fallido</option>
        </select>
      </div>

      <div class="col-12 col-md-2 d-flex gap-2">
        <button type="submit" class="btn btn-primary w-100">Filtrar</button>
      </div>
    </div>
  </form>

  <div class="px-4 pb-4">
    <div class="card shadow-sm border-0">
      <div class="card-body p-0">
        @if($reversos->count() > 0)
        <div id="no-more-tables" class="table-responsive">
          <table
            id="tablaReversos"
            class="table table-sm table-striped"
            data-toggle="table"
            data-locale="es-ES"
            data-search="false"
            data-pagination="false"
            data-detail-view="true"
            data-detail-formatter="detalleReversos"
            data-check-on-init="true">
            <thead class="table-light">
              <tr>
                <th data-field="fecha_hora" data-sortable="true" class="text-nowrap">Fecha y hora</th>
                <th data-field="detalle_id" data-sortable="true" class="text-nowrap">Detalle</th>
                <th data-field="cliente">Cliente</th>
                <th data-field="identificacion" class="text-nowrap">Identificacion</th>
                <th data-field="valor" data-align="right" class="text-end text-nowrap">Valor</th>
                <th data-field="resultado_html" class="text-nowrap">Resultado</th>
                <th data-field="situacion">Situacion del pago</th>
                <th data-field="autorizacion" class="text-nowrap">Autorizacion</th>
                <th data-field="error">Error</th>
              </tr>
            </thead>
            <tbody>
              @foreach($reversos as $reverso)
              @php
                $resultado = \App\Modules\PagosRecaudos\Services\Cajasan\ReversoHistorialService::etiquetaResultado($reverso->response_code);
                $situacion = \App\Modules\PagosRecaudos\Services\Cajasan\ReversoHistorialService::situacionPago($reverso->response_code, $reverso->estado_pago_local);
                $exitoso = filter_var($reverso->response_code, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) === true;
              @endphp
              <tr>
                <td>{{ $reverso->transmission_datetime ? \Carbon\Carbon::parse($reverso->transmission_datetime)->format('d/m/Y H:i:s') : 'N/A' }}</td>
                <td class="fw-semibold">{{ $reverso->detalle_id }}</td>
                <td>{{ $reverso->cliente ?: 'No disponible' }}</td>
                <td>{{ $reverso->identificacion_cliente ?: $reverso->identification ?: 'No disponible' }}</td>
                <td class="text-end">
                  @if($reverso->valor_total !== null)
                    ${{ number_format((float) $reverso->valor_total, 0, ',', '.') }}
                  @elseif($reverso->amount_tran !== null)
                    ${{ number_format((float) $reverso->amount_tran, 0, ',', '.') }}
                  @else
                    N/A
                  @endif
                </td>
                <td>
                  <span class="badge {{ $exitoso ? 'bg-success' : 'bg-danger' }}">
                    {{ $resultado }}
                  </span>
                </td>
                <td>
                  <span class="small {{ $exitoso ? 'text-success fw-semibold' : 'text-danger fw-semibold' }}">
                    {{ $situacion }}
                  </span>
                </td>
                <td>{{ $reverso->authorization_rsp_code ?: 'N/A' }}</td>
                <td>{{ $reverso->error_message ?: ($reverso->error_id ?: 'Sin detalle') }}</td>
              </tr>
              @endforeach
            </tbody>
          </table>
        </div>
        @else
        <div class="text-center py-5">
          <i class="bi bi-arrow-repeat text-muted display-4 d-block mb-3"></i>
          <h5 class="text-muted">No hay reversos registrados para este criterio</h5>
          <p class="text-muted mb-0">Ajusta los filtros o regresa al flujo principal.</p>
        </div>
        @endif
      </div>

      @if($reversos->hasPages())
      <div class="card-footer bg-white">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
          <div>
            Mostrando {{ $reversos->firstItem() }} a {{ $reversos->lastItem() }} en la pagina {{ $reversos->currentPage() }}
            @if($reversos->hasMorePages())
            <span class="text-muted">. Hay mas resultados disponibles.</span>
            @endif
          </div>
          <div>
            {{ $reversos->links() }}
          </div>
        </div>
      </div>
      @endif
    </div>
  </div>
</div>
@endsection

@pushOnce('script')
<script>
  document.addEventListener('DOMContentLoaded', () => {
    initTablaBootstrapTable(
      '#tablaReversos',
      {
        protegidas: ['fecha_hora', 'detalle_id', 'resultado_html', 'situacion'],
        forzarDetalle: true,
      },
      'detalleReversos'
    );
  });
</script>
@endpushOnce
