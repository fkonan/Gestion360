@extends('layouts.dashboard')

@section('title','Pagos del dia')

@section('breadcrumb')
<x-breadcrumb :items="[
        ['name' => 'Inicio', 'url' => route('home')],
        ['name' => 'Pago convenio', 'url' => route('pagosConvenios.index')],
        ['name' => 'Pagos del dia']
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
    <span class="text-left text-light fs-5 fw-medium py-1">Pagos del dia</span>
    <span class="badge bg-light text-dark">
      {{ $pagos->count() }} registros en pagina
    </span>
  </div>

  <div class="d-flex flex-wrap gap-2 ms-4 mt-3">
    <a style="width: 160px;" class="btn btn-success fw-bold" href="{{ route('pagosConvenios.index') }}">Nuevo pago</a>
    <a style="width: 160px;" class="btn btn-outline-secondary fw-bold" href="{{ route('home') }}">Inicio</a>
  </div>

  <form method="GET" action="{{ route('pagosConvenios.historialHoy') }}" class="p-4 pb-2">
    <div class="row g-3 align-items-end">
      <div class="col-12 col-md-7">
        <label for="q" class="form-label">Buscar por identificacion, cliente, numero interno o comprobante</label>
        <input
          type="text"
          class="form-control"
          id="q"
          name="q"
          value="{{ $filters['q'] }}"
          placeholder="Ej: 1098, Maria Perez, 654321">
      </div>

      <div class="col-12 col-md-3">
        <label for="estado" class="form-label">Estado</label>
        <select class="form-select" id="estado" name="estado">
          <option value="todos" {{ $filters['estado'] === 'todos' ? 'selected' : '' }}>Todos</option>
          <option value="pagado" {{ $filters['estado'] === 'pagado' ? 'selected' : '' }}>Pagado</option>
          <option value="fallido" {{ $filters['estado'] === 'fallido' ? 'selected' : '' }}>Fallido/Anulado</option>
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
        @if($pagos->count() > 0)
        <div class="table-responsive">
          <table class="table table-hover table-sm align-middle mb-0">
            <thead class="table-light">
              <tr>
                <th>Hora</th>
                <th>Identificacion</th>
                <th>Cliente</th>
                <th>Telefono</th>
                <th class="text-end">Valor</th>
                <th>Estado</th>
                <th>Numero interno</th>
                <th>Comprobante</th>
                <th class="text-center">Accion</th>
              </tr>
            </thead>
            <tbody>
              @foreach($pagos as $pago)
              @php
                $estado = \App\Modules\PagosRecaudos\Services\Cajasan\PagoHistorialService::etiquetaEstado($pago->estado);
                $esPagado = $pago->estado === 'P' && !empty($pago->comprobante);
              @endphp
              <tr>
                <td>{{ \Carbon\Carbon::parse($pago->feccreacion)->format('H:i:s') }}</td>
                <td>{{ $pago->identificacion }}</td>
                <td>{{ $pago->cliente }}</td>
                <td>{{ $pago->telefono ?: 'No registrado' }}</td>
                <td class="text-end">${{ number_format((float) $pago->valor_total, 0, ',', '.') }}</td>
                <td>
                  <span class="badge {{ $pago->estado === 'P' ? 'bg-success' : 'bg-warning text-dark' }}">
                    {{ $estado }}
                  </span>
                </td>
                <td>{{ $pago->nro_interno ?: 'N/A' }}</td>
                <td>{{ $pago->comprobante ?: 'N/A' }}</td>
                <td class="text-center">
                  @if($esPagado)
                  <a
                    href="{{ route('pagosConvenios.recibo', ['IdDetallePago' => $pago->id]) }}"
                    class="btn btn-outline-danger btn-sm"
                    target="_blank">
                    <i class="bi bi-file-earmark-pdf-fill"></i> Recibo
                  </a>
                  @else
                  <span class="text-muted small">No disponible</span>
                  @endif
                </td>
              </tr>
              @endforeach
            </tbody>
          </table>
        </div>
        @else
        <div class="text-center py-5">
          <i class="bi bi-receipt text-muted display-4 d-block mb-3"></i>
          <h5 class="text-muted">No hay pagos registrados hoy para este criterio</h5>
          <p class="text-muted mb-0">Ajusta los filtros o regresa al flujo principal para registrar un nuevo pago.</p>
        </div>
        @endif
      </div>

      @if($pagos->hasPages())
      <div class="card-footer bg-white">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
          <div>
            Mostrando {{ $pagos->firstItem() }} a {{ $pagos->lastItem() }} en la pagina {{ $pagos->currentPage() }}
            @if($pagos->hasMorePages())
            <span class="text-muted">. Hay mas resultados disponibles.</span>
            @endif
          </div>
          <div>
            {{ $pagos->links() }}
          </div>
        </div>
      </div>
      @endif
    </div>
  </div>
</div>
@endsection
