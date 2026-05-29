@extends('layouts.dashboard')

@section('title', 'Listado de Alertas')

@section('breadcrumb')
   <x-breadcrumb :items="[
       ['name' => 'Inicio', 'url' => route('home')],
       ['name' => 'Sarlaft', 'url' => '#'],
       ['name' => 'Alertas'],
   ]" />
   <br>
@endsection

@push('css')
    @vite(['resources/css/sarlaft/alertas-index.css'])
@endpush

@section('content')
@php
    $estadoOptions = [
        'pendiente' => 'Pendiente',
        'en_revision' => 'En revision',
        'atendida' => 'Atendida',
        'descartada' => 'Descartada',
    ];
    $riesgoOptions = [
        'critico' => 'Critico',
        'alto' => 'Alto',
        'medio' => 'Medio',
        'bajo' => 'Bajo',
    ];
    $hasFilters = collect($filters)->filter(static fn ($value): bool => filled($value))->isNotEmpty();
@endphp

<div class="sarlaft-alerts-page">
    <div class="sarlaft-alerts-hero">
        <div class="sarlaft-alerts-intro">
            <span class="sarlaft-alerts-kicker">Centro de monitoreo SARLAFT</span>
            <h1 class="sarlaft-alerts-title">Lista de Alertas Combinada</h1>
            <p class="sarlaft-alerts-description">
                Monitoreo estructural de seguridad y flujo operativo para las alertas generadas por consultas SARLAFT.
            </p>
        </div>

        <div class="sarlaft-alerts-actions">
            <a href="{{ route('sarlaft.dashboard') }}" class="btn btn-outline-dark">
                <i class="fas fa-arrow-left"></i>
                <span>Dashboard</span>
            </a>
            <a href="{{ route('sarlaft.reportes.operaciones.index') }}" class="btn btn-dark">
                <i class="fas fa-chart-bar"></i>
                <span>Reporte Operativo</span>
            </a>
        </div>
    </div>

    <div class="row g-3 mb-3">
        <div class="col-md-6 col-xl-4">
            <article class="sarlaft-kpi-card sarlaft-kpi-card-critical">
                <div class="sarlaft-kpi-icon">
                    <i class="fas fa-exclamation-triangle"></i>
                </div>
                <div class="sarlaft-kpi-label">Critico</div>
                <div class="sarlaft-kpi-value">{{ number_format($stats['criticas_pendientes']) }}</div>
                <p class="sarlaft-kpi-copy mb-0">Alertas de alto impacto abiertas.</p>
            </article>
        </div>

        <div class="col-md-6 col-xl-4">
            <article class="sarlaft-kpi-card sarlaft-kpi-card-success">
                <div class="sarlaft-kpi-icon">
                    <i class="fas fa-check-circle"></i>
                </div>
                <div class="sarlaft-kpi-label">Completado</div>
                <div class="sarlaft-kpi-value">{{ number_format($stats['resueltas_hoy']) }}</div>
                <p class="sarlaft-kpi-copy mb-0">Alertas resueltas en la jornada actual.</p>
            </article>
        </div>

        <div class="col-md-6 col-xl-4">
            <article class="sarlaft-kpi-card sarlaft-kpi-card-warning">
                <div class="sarlaft-kpi-icon">
                    <i class="fas fa-bolt"></i>
                </div>
                <div class="sarlaft-kpi-label">Seguimiento</div>
                <div class="sarlaft-kpi-value">{{ number_format($stats['escaladas_automaticas']) }}</div>
                <p class="sarlaft-kpi-copy mb-0">Alertas escaladas de forma automatica.</p>
            </article>
        </div>
    </div>

    <div class="card shadow-sm sarlaft-alerts-card">
        <div class="card-body p-0">
            <div class="sarlaft-filter-shell">
                <form action="{{ route('sarlaft.alertas.index') }}" method="GET">
                    <div class="row g-3 align-items-end">
                        <div class="col-xl-5">
                            <label for="search" class="form-label">Busqueda</label>
                            <div class="sarlaft-search-control">
                                <i class="fas fa-search"></i>
                                <input
                                    type="text"
                                    id="search"
                                    name="search"
                                    class="form-control"
                                    value="{{ $filters['search'] ?? '' }}"
                                    placeholder="Filtrar por ID, documento, persona u operacion"
                                >
                            </div>
                        </div>

                        <div class="col-md-4 col-xl-2">
                            <label for="estado" class="form-label">Estado</label>
                            <select name="estado" id="estado" class="form-select">
                                <option value="">Todos</option>
                                @foreach($estadoOptions as $value => $label)
                                <option value="{{ $value }}" @selected(($filters['estado'] ?? null) === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-4 col-xl-2">
                            <label for="riesgo" class="form-label">Riesgo</label>
                            <select name="riesgo" id="riesgo" class="form-select">
                                <option value="">Todos</option>
                                @foreach($riesgoOptions as $value => $label)
                                <option value="{{ $value }}" @selected(($filters['riesgo'] ?? null) === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-4 col-xl-3">
                            <div class="sarlaft-filter-actions">
                                <button type="submit" class="btn btn-success">
                                    <i class="fas fa-filter"></i>
                                    <span>Aplicar filtros</span>
                                </button>

                                @if($hasFilters)
                                <a href="{{ route('sarlaft.alertas.index') }}" class="btn btn-outline-dark">
                                    <i class="fas fa-eraser"></i>
                                    <span>Limpiar</span>
                                </a>
                                @endif
                            </div>
                        </div>
                    </div>
                </form>
            </div>

            <div class="sarlaft-results-bar">
                <div>
                    <strong>{{ number_format($alertas->total()) }}</strong> alertas encontradas
                </div>
                @if($hasFilters)
                <div class="sarlaft-results-filters">
                    @if(filled($filters['estado'] ?? null))
                    <span class="sarlaft-results-pill">{{ $estadoOptions[$filters['estado']] ?? 'Estado' }}</span>
                    @endif
                    @if(filled($filters['riesgo'] ?? null))
                    <span class="sarlaft-results-pill">{{ $riesgoOptions[$filters['riesgo']] ?? 'Riesgo' }}</span>
                    @endif
                    @if(filled($filters['search'] ?? null))
                    <span class="sarlaft-results-pill">Busqueda activa</span>
                    @endif
                </div>
                @endif
            </div>

            @if($alertas->isEmpty())
            <div class="sarlaft-empty-state">
                <div class="sarlaft-empty-icon">
                    <i class="fas fa-inbox"></i>
                </div>
                <h3>No hay alertas para mostrar</h3>
                <p class="mb-0">No encontramos registros con los criterios actuales.</p>
            </div>
            @else
            <div class="table-responsive">
                <table class="table table-sm table-striped align-middle mb-0 sarlaft-alerts-table">
                    <thead class="table-primary">
                        <tr>
                            <th>ID</th>
                            <th>Fecha</th>
                            <th>Operacion</th>
                            <th>Persona</th>
                            <th>Resultado</th>
                            <th>Riesgo</th>
                            <th>Detalles</th>
                            <th>Estado</th>
                            <th class="text-center">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($alertas as $alerta)
                        @php
                            $contexto = is_array($alerta->contexto_operacion) ? $alerta->contexto_operacion : [];
                            $datosPersona = is_array($alerta->datos_persona) ? $alerta->datos_persona : [];
                            $personaNombre = trim((string) ($datosPersona['nombre'] ?? ''));

                            if ($personaNombre === '') {
                                $personaNombre = trim(implode(' ', array_filter([
                                    $datosPersona['nombres'] ?? null,
                                    $datosPersona['apellidos'] ?? null,
                                ])));
                            }

                            $personaNombre = $personaNombre !== '' ? $personaNombre : '-';
                            $reportTipoDocumento = (string) ($datosPersona['tipo_documento'] ?? $alerta->tipo_documento ?? '');
                            $reportNumeroDocumento = (string) ($datosPersona['numero_documento'] ?? $alerta->numero_documento ?? '');
                            $documento = trim(implode(' ', array_filter([$reportTipoDocumento, $reportNumeroDocumento])));

                            $origenAtencion = $contexto['origen_atencion'] ?? null;

                            if ($origenAtencion === null && $alerta->escalada_automatica) {
                                $origenAtencion = 'auto_sla';
                            }

                            $tipoOperacion = \Illuminate\Support\Str::headline((string) ($contexto['tipo_operacion'] ?? $alerta->intento?->tipo_operacion ?? $alerta->tipo));

                            $sistemaOrigen = null;

                            if (is_string($alerta->intento?->sistema_origen) && trim((string) $alerta->intento?->sistema_origen) !== '') {
                                $sistemaOrigen = \Illuminate\Support\Str::headline((string) $alerta->intento->sistema_origen);
                            } elseif (is_string($alerta->intento?->sistema?->nombre) && trim((string) $alerta->intento?->sistema?->nombre) !== '') {
                                $sistemaOrigen = (string) $alerta->intento->sistema->nombre;
                            }

                            $operationClass = match (true) {
                                str_contains(strtolower((string) ($alerta->intento?->sistema_origen ?? '')), 'remesa') => 'is-remesa',
                                str_contains(strtolower((string) ($alerta->intento?->sistema_origen ?? '')), 'pasaje') => 'is-pasaje',
                                default => 'is-default',
                            };

                            $resultadoLabel = $alerta->nivel_riesgo === 'alto' || $alerta->nivel_riesgo === 'critico'
                                ? 'Bloqueante'
                                : 'Revisar';

                            $resultadoClass = $alerta->nivel_riesgo === 'alto' || $alerta->nivel_riesgo === 'critico'
                                ? 'is-danger'
                                : 'is-muted';

                            $resultadoIcon = $alerta->nivel_riesgo === 'alto' || $alerta->nivel_riesgo === 'critico'
                                ? 'fa-ban'
                                : 'fa-search';

                            $riskLabel = $riesgoOptions[$alerta->nivel_riesgo] ?? ucfirst((string) $alerta->nivel_riesgo);
                            $riskClass = match ($alerta->nivel_riesgo) {
                                'critico' => 'is-critical',
                                'alto' => 'is-high',
                                'medio' => 'is-medium',
                                default => 'is-low',
                            };

                            $stateLabel = $estadoOptions[$alerta->estado] ?? ucfirst((string) $alerta->estado);
                            $stateClass = match ($alerta->estado) {
                                'pendiente' => 'is-pending',
                                'en_revision' => 'is-review',
                                'atendida' => 'is-attended',
                                default => 'is-dismissed',
                            };

                            $originLabel = match ($origenAtencion) {
                                'auto_lista_negra_interna' => 'Auto Lista Restrictiva',
                                'auto_sla' => 'Auto SLA',
                                default => 'Manual',
                            };
                        @endphp
                        <tr>
                            <td>
                                <span class="sarlaft-row-id">#AL-{{ $alerta->id }}</span>
                            </td>
                            <td>
                                <div class="sarlaft-date-block">
                                    <span>{{ $alerta->created_at->format('d/m/Y') }}</span>
                                    <small>{{ $alerta->created_at->format('H:i') }}</small>
                                </div>
                            </td>
                            <td>
                                <span class="sarlaft-operation-pill {{ $operationClass }}">{{ $tipoOperacion }}</span>
                                <div class="sarlaft-cell-meta">{{ $sistemaOrigen ?? 'Operacion SARLAFT' }}</div>
                            </td>
                            <td>
                                <div class="sarlaft-person-name">{{ $personaNombre }}</div>
                                <div class="sarlaft-cell-meta">{{ $documento !== '' ? $documento : 'Sin documento asociado' }}</div>
                            </td>
                            <td>
                                <div class="sarlaft-result {{ $resultadoClass }}">
                                    <i class="fas {{ $resultadoIcon }}"></i>
                                    <span>{{ $resultadoLabel }}</span>
                                </div>
                                <div class="sarlaft-cell-meta">
                                    {{ $originLabel }}
                                    @if($alerta->escalada_automatica)
                                    <span class="sarlaft-meta-separator"></span>
                                    <span>Escalada automatica</span>
                                    @endif
                                </div>
                            </td>
                            <td>
                                <span class="sarlaft-pill sarlaft-pill-risk {{ $riskClass }}">{{ $riskLabel }}</span>
                            </td>
                            <td>
                                <div class="sarlaft-link-group">
                                    <a href="{{ route('sarlaft.alertas.show', $alerta) }}">Ver</a>
                                    @if($reportTipoDocumento !== '' && $reportNumeroDocumento !== '')
                                    <a href="{{ route('sarlaft.reportes.operaciones.index', ['tipo_documento' => $reportTipoDocumento, 'numero_documento' => $reportNumeroDocumento]) }}">Historial</a>
                                    @endif
                                </div>
                            </td>
                            <td>
                                <span class="sarlaft-pill sarlaft-pill-state {{ $stateClass }}">{{ $stateLabel }}</span>
                                @if($alerta->fecha_atencion)
                                <div class="sarlaft-cell-meta">Atendida {{ $alerta->fecha_atencion->format('d/m H:i') }}</div>
                                @endif
                            </td>
                            <td class="text-center">
                                <a href="{{ route('sarlaft.alertas.show', $alerta) }}" class="btn btn-sm btn-dark sarlaft-action-btn">
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

        @if($alertas->isNotEmpty())
        <div class="card-footer sarlaft-pagination-bar">
            <div class="sarlaft-pagination-meta">
                Mostrando {{ $alertas->firstItem() }} a {{ $alertas->lastItem() }} de {{ $alertas->total() }} alertas
            </div>
            @if($alertas->hasPages())
            {{ $alertas->links() }}
            @endif
        </div>
        @endif
    </div>
</div>
@endsection
