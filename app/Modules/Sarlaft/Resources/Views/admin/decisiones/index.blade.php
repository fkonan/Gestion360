@extends('layouts.dashboard')

@section('title', 'Reporte de decisiones')

@section('content')

<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
    <div>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('home') }}">Inicio</a></li>
                <li class="breadcrumb-item"><a href="#">Sarlaft</a></li>
                <li class="breadcrumb-item active" aria-current="page">Decisiones</li>
            </ol>
        </nav>
        <h4 class="mb-0 fw-bold mt-1">Reporte de decisiones de servicio</h4>
    </div>
    <a href="{{ route('sarlaft.decisiones.exportar', request()->query()) }}" class="btn btn-outline-success">
        <i class="fas fa-file-csv"></i> Exportar CSV
    </a>
</div>

{{-- Filtros --}}
<div class="card shadow-sm mb-4">
    <div class="card-body">
        <form method="GET" action="{{ route('sarlaft.decisiones.index') }}" class="row g-3 align-items-end">
            <div class="col-md-3">
                <label for="busqueda" class="form-label">Buscar</label>
                <input type="text" name="busqueda" id="busqueda" class="form-control" placeholder="Documento o nombre" value="{{ $filtros['busqueda'] ?? '' }}">
            </div>
            <div class="col-md-2">
                <label for="decision" class="form-label">Decision</label>
                <select name="decision" id="decision" class="form-select">
                    <option value="">Todas</option>
                    <option value="permitido" @selected(($filtros['decision'] ?? '') === 'permitido')>Permitido</option>
                    <option value="bloqueado" @selected(($filtros['decision'] ?? '') === 'bloqueado')>Bloqueado</option>
                </select>
            </div>
            <div class="col-md-2">
                <label for="tipo" class="form-label">Tipo</label>
                <select name="tipo" id="tipo" class="form-select">
                    <option value="">Todos</option>
                    <option value="vinculante" @selected(($filtros['tipo'] ?? '') === 'vinculante')>Lista Vinculante</option>
                    <option value="restrictiva" @selected(($filtros['tipo'] ?? '') === 'restrictiva')>Lista Restrictiva</option>
                </select>
            </div>
            <div class="col-md-2">
                <label for="desde" class="form-label">Desde</label>
                <input type="date" name="desde" id="desde" class="form-control" value="{{ $filtros['desde'] ?? '' }}">
            </div>
            <div class="col-md-2">
                <label for="hasta" class="form-label">Hasta</label>
                <input type="date" name="hasta" id="hasta" class="form-control" value="{{ $filtros['hasta'] ?? '' }}">
            </div>
            <div class="col-md-1 d-flex gap-2">
                <button type="submit" class="btn btn-success w-100">
                    <i class="fas fa-filter"></i> Filtrar
                </button>
            </div>
            <div class="col-12 d-flex justify-content-end">
                <a href="{{ route('sarlaft.decisiones.index') }}" class="btn btn-sm btn-outline-secondary">
                    <i class="fas fa-times"></i> Limpiar filtros
                </a>
            </div>
        </form>
    </div>
</div>

{{-- Tabla de resultados --}}
<div class="card shadow-sm">
    <div class="card-body p-0">
        @if($decisiones->isEmpty())
        <div class="alert alert-light border m-3 mb-3">
            <i class="fas fa-info-circle text-muted me-1"></i>
            No hay decisiones registradas con los criterios actuales.
        </div>
        @else
        <div class="table-responsive">
            <table
                id="tablaDecisiones"
                class="table table-sm table-hover align-middle mb-0"
                data-toggle="table"
                data-detail-view="true"
                data-detail-formatter="detalleDecision"
                data-locale="es-ES">
                <thead class="table-primary">
                    <tr>
                        <th data-field="fecha">Fecha</th>
                        <th data-field="decision">Decision</th>
                        <th data-field="tipo">Tipo</th>
                        <th data-field="documento">Documento</th>
                        <th data-field="persona">Persona</th>
                        <th data-field="oficial">Oficial</th>
                        {{-- El motivo (largo) se muestra en el detalle expandible --}}
                        <th data-field="motivo">Motivo</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($decisiones as $alerta)
                    @php
                        $datosPersona = is_array($alerta->datos_persona) ? $alerta->datos_persona : [];
                        $nombrePersona = trim((string) ($datosPersona['nombre'] ?? trim(($datosPersona['nombres'] ?? '') . ' ' . ($datosPersona['apellidos'] ?? ''))));
                        $nombrePersona = $nombrePersona !== '' ? $nombrePersona : '-';

                        $oficial = $alerta->atendidaPor?->persona;
                        $nombreOficial = $oficial
                            ? trim(($oficial->PerNombres ?? '') . ' ' . ($oficial->PerApellidos ?? ''))
                            : '-';
                        $nombreOficial = $nombreOficial !== '' ? $nombreOficial : '-';

                        $esVinculante = strtolower(trim((string) $alerta->nivel_riesgo)) === 'vinculante';
                    @endphp
                    <tr>
                        <td class="text-nowrap">
                            {{ $alerta->decision_at ? \Illuminate\Support\Carbon::parse($alerta->decision_at)->format('d/m/Y H:i') : '-' }}
                        </td>
                        <td>
                            @if($alerta->decision_servicio === 'permitido')
                                <span class="badge bg-success">Permitido</span>
                            @else
                                <span class="badge bg-danger">Bloqueado</span>
                            @endif
                        </td>
                        <td>
                            <span class="badge bg-{{ $esVinculante ? 'danger' : 'secondary' }}">
                                {{ $esVinculante ? 'Lista Vinculante' : 'Lista Restrictiva' }}
                            </span>
                        </td>
                        <td class="text-nowrap">{{ $alerta->numero_documento ?? '-' }}</td>
                        <td>{{ $nombrePersona }}</td>
                        <td>{{ $nombreOficial }}</td>
                        <td>{{ $alerta->notas ?: '-' }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @endif
    </div>
    @if($decisiones->hasPages())
    <div class="card-footer bg-white">
        {{ $decisiones->links() }}
    </div>
    @endif
</div>

@endsection

@pushOnce('script')
<script>
    document.addEventListener('DOMContentLoaded', () => {
        if (typeof window.initTablaBootstrapTable !== 'function') {
            return;
        }
        // 'documento' y 'decision' quedan siempre visibles; 'motivo' va al detalle expandible.
        window.initTablaBootstrapTable(
            '#tablaDecisiones',
            { protegidas: ['documento', 'decision'], ocultas: ['motivo'] },
            'detalleDecision'
        );
    });
</script>
@endpushOnce
