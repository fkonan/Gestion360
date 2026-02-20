@extends('layouts.dashboard')

@section('title', 'Dashboard')

@section('content')
{{-- Stats Cards --}}
<div class="row g-3 mb-4">
    <div class="col-md-4 col-lg-2">
        <div class="card stat-card border-primary shadow-sm">
            <div class="card-body">
                <div class="text-muted small">Consultas Hoy</div>
                <div class="fs-3 fw-bold">{{ number_format($stats['consultas_hoy']) }}</div>
            </div>
        </div>
    </div>
    <div class="col-md-4 col-lg-2">
        <div class="card stat-card border-primary shadow-sm">
            <div class="card-body">
                <div class="text-muted small">Consultas Mes</div>
                <div class="fs-3 fw-bold">{{ number_format($stats['consultas_mes']) }}</div>
            </div>
        </div>
    </div>
    <div class="col-md-4 col-lg-2">
        <div class="card stat-card border-danger shadow-sm">
            <div class="card-body">
                <div class="text-muted small">Alertas Pendientes</div>
                <div class="fs-3 fw-bold text-danger">{{ $stats['alertas_pendientes'] }}</div>
            </div>
        </div>
    </div>
    <div class="col-md-4 col-lg-2">
        <div class="card stat-card border-warning shadow-sm">
            <div class="card-body">
                <div class="text-muted small">En Revision</div>
                <div class="fs-3 fw-bold text-warning">{{ $stats['alertas_en_revision'] }}</div>
            </div>
        </div>
    </div>
    <div class="col-md-4 col-lg-2">
        <div class="card stat-card border-danger shadow-sm">
            <div class="card-body">
                <div class="text-muted small">Bloqueos Activos</div>
                <div class="fs-3 fw-bold">{{ $stats['bloqueos_activos'] }}</div>
            </div>
        </div>
    </div>
    <div class="col-md-4 col-lg-2">
        <div class="card stat-card border-success shadow-sm">
            <div class="card-body">
                <div class="text-muted small">Lista Negra</div>
                <div class="fs-3 fw-bold">{{ $stats['lista_negra_total'] }}</div>
            </div>
        </div>
    </div>
</div>

<div class="d-flex justify-content-end mb-3">
    <a href="{{ route('sarlaft.simulaciones.index') }}" class="btn btn-outline-primary">
        <i class="fas fa-vial"></i> Simulaciones Operativas
    </a>
</div>

<div class="row g-4">
    {{-- Ultimas Alertas --}}
    <div class="col-lg-8">
        <div class="card shadow-sm">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h6 class="mb-0"><i class="bi bi-exclamation-triangle text-danger"></i> Alertas Pendientes</h6>
                <a href="{{ route('sarlaft.alertas.index') }}" class="btn btn-sm btn-outline-primary">Ver todas</a>
            </div>
            <div class="card-body p-0">
                @if($ultimasAlertas->isEmpty())
                    <div class="text-center text-muted py-4">No hay alertas pendientes.</div>
                @else
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>ID</th>
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
                                    <td>
                                        @include('sarlaft::admin.partials.badge-riesgo', ['nivel' => $alerta->nivel_riesgo])
                                    </td>
                                    <td>{{ $alerta->datos_persona['nombre'] ?? $alerta->datos_persona['numero_documento'] ?? '-' }}</td>
                                    <td>{{ $alerta->created_at->format('d/m/Y H:i') }}</td>
                                    <td>
                                        <a href="{{ route('sarlaft.alertas.show', $alerta) }}" class="btn btn-sm btn-outline-secondary">
                                            <i class="bi bi-eye"></i>
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

    {{-- Ultimas Sincronizaciones --}}
    <div class="col-lg-4">
        <div class="card shadow-sm">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h6 class="mb-0"><i class="bi bi-arrow-repeat text-primary"></i> Sincronizaciones</h6>
                <a href="{{ route('sarlaft.sincronizacion.index') }}" class="btn btn-sm btn-outline-primary">Ver todas</a>
            </div>
            <div class="card-body p-0">
                @if($ultimasSincronizaciones->isEmpty())
                    <div class="text-center text-muted py-4">Sin sincronizaciones registradas.</div>
                @else
                    <ul class="list-group list-group-flush">
                        @foreach($ultimasSincronizaciones as $log)
                        <li class="list-group-item">
                            <div class="d-flex justify-content-between">
                                <strong class="small">{{ $log->lista->nombre ?? 'N/A' }}</strong>
                                @include('sarlaft::admin.partials.badge-estado-sync', ['estado' => $log->estado])
                            </div>
                            <small class="text-muted">
                                {{ $log->created_at?->format('d/m/Y H:i') }} -
                                {{ $log->registros_nuevos }} nuevos, {{ $log->registros_actualizados }} actualizados
                            </small>
                        </li>
                        @endforeach
                    </ul>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
