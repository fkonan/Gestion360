@extends('layouts.dashboard')

@section('title', 'Dashboard')

@section('content')
{{-- Stats Cards --}}
<div class="row">
  <h3>Dashboard Sarlaft</h3>
</div>
<div class="row g-3 mb-4">
    <div class="col-md-4 col-lg-3">
        <a href="{{ route('sarlaft.alertas.index') }}" class="text-decoration-none">
            <div class="card stat-card border-danger shadow-sm h-100">
                <div class="card-body">
                    <div class="text-muted small">Alertas Pendientes</div>
                    <div class="fs-3 fw-bold text-danger">{{ $stats['alertas_pendientes'] }}</div>
                </div>
            </div>
        </a>
    </div>
    <div class="col-md-4 col-lg-3">
        <a href="{{ route('sarlaft.alertas.index') }}" class="text-decoration-none">
            <div class="card stat-card border-warning shadow-sm h-100">
                <div class="card-body">
                    <div class="text-muted small">En Revision</div>
                    <div class="fs-3 fw-bold text-warning">{{ $stats['alertas_en_revision'] }}</div>
                </div>
            </div>
        </a>
    </div>
    <div class="col-md-4 col-lg-3">
        <a href="{{ route('sarlaft.lista-negra.index') }}" class="text-decoration-none">
            <div class="card stat-card border-dark shadow-sm h-100">
                <div class="card-body">
                    <div class="text-muted small">Lista Restrictiva</div>
                    <div class="fs-3 fw-bold text-dark">{{ $stats['lista_negra_total'] }}</div>
                </div>
            </div>
        </a>
    </div>
</div>


<div class="row g-4">
    {{-- Alertas Pendientes --}}
    <div class="col-12">
        <div class="card shadow-sm">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h6 class="mb-0"><i class="fas fa-exclamation-triangle text-danger me-1"></i> Alertas Pendientes
                    @if($stats['alertas_pendientes'] > 0)
                        <span class="badge bg-danger ms-1">{{ $stats['alertas_pendientes'] }}</span>
                    @endif
                </h6>
                <a href="{{ route('sarlaft.alertas.index') }}" class="btn btn-sm btn-dark">Ver todas</a>
            </div>
            <div class="card-body p-0">
                @if($ultimasAlertas->isEmpty())
                    <div class="text-center text-muted py-4">No hay alertas pendientes.</div>
                @else
                    <div class="table-responsive">
                        <table class="table table-hover table-sm mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>#</th>
                                    <th>Tipo</th>
                                    <th>Riesgo</th>
                                    <th>Persona</th>
                                    <th>Fecha</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($ultimasAlertas as $alerta)
                                <tr>
                                    <td>{{ $alerta->id }}</td>
                                    <td>{{ $alerta->tipo }}</td>
                                    <td>@include('sarlaft::admin.partials.badge-riesgo', ['nivel' => $alerta->nivel_riesgo])</td>
                                    <td class="text-truncate" style="max-width:200px;">{{ $alerta->datos_persona['nombre'] ?? $alerta->datos_persona['numero_documento'] ?? '-' }}</td>
                                    <td class="text-nowrap">{{ $alerta->created_at->format('d/m/Y') }}</td>
                                    <td>
                                        <a href="{{ route('sarlaft.alertas.show', $alerta) }}" class="btn btn-sm btn-dark">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
