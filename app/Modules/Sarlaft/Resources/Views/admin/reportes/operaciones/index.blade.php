@extends('layouts.dashboard')

@section('title', 'Reporte Operativo SARLAFT')

@section('content')
<div class="d-flex justify-content-end gap-2 mb-3">
    <a href="{{ route('sarlaft.dashboard') }}" class="btn btn-sm btn-outline-dark">
        <i class="fas fa-arrow-left"></i> Dashboard
    </a>
    <a href="{{ route('sarlaft.simulaciones.index') }}" class="btn btn-sm btn-success">
        <i class="fas fa-vial"></i> Nueva Simulacion
    </a>
</div>

<div class="card shadow-sm mb-4">
    <div class="card-header">
        <h6 class="mb-0">Filtros del Reporte</h6>
    </div>
    <div class="card-body">
        <form method="GET" action="{{ route('sarlaft.reportes.operaciones.index') }}">
            <div class="row g-3">
                <div class="col-md-2">
                    <label for="tipo_documento" class="form-label">Tipo documento</label>
                    <input type="text" id="tipo_documento" name="tipo_documento" class="form-control" value="{{ $filtros['tipo_documento'] ?? '' }}" placeholder="CC">
                </div>
                <div class="col-md-2">
                    <label for="numero_documento" class="form-label">Documento</label>
                    <input type="text" id="numero_documento" name="numero_documento" class="form-control" value="{{ $filtros['numero_documento'] ?? '' }}" placeholder="Numero documento">
                </div>
                <div class="col-md-3">
                    <label for="nombre" class="form-label">Nombre</label>
                    <input type="text" id="nombre" name="nombre" class="form-control" value="{{ $filtros['nombre'] ?? '' }}" placeholder="Nombre consultado">
                </div>
                <div class="col-md-2">
                    <label for="operacion" class="form-label">Operacion</label>
                    <select id="operacion" name="operacion" class="form-select">
                        <option value="">Todas</option>
                        <option value="pasaje" {{ ($filtros['operacion'] ?? null) === 'pasaje' ? 'selected' : '' }}>Pasajes</option>
                        <option value="remesa" {{ ($filtros['operacion'] ?? null) === 'remesa' ? 'selected' : '' }}>Mensajeria</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="resultado" class="form-label">Resultado</label>
                    <select id="resultado" name="resultado" class="form-select">
                        <option value="">Todos</option>
                        <option value="permitida" {{ ($filtros['resultado'] ?? null) === 'permitida' ? 'selected' : '' }}>Permitidas</option>
                        <option value="bloqueada" {{ ($filtros['resultado'] ?? null) === 'bloqueada' ? 'selected' : '' }}>Bloqueadas</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="fecha_desde" class="form-label">Fecha desde</label>
                    <input type="date" id="fecha_desde" name="fecha_desde" class="form-control" value="{{ $filtros['fecha_desde'] ?? '' }}">
                </div>
                <div class="col-md-3">
                    <label for="fecha_hasta" class="form-label">Fecha hasta</label>
                    <input type="date" id="fecha_hasta" name="fecha_hasta" class="form-control" value="{{ $filtros['fecha_hasta'] ?? '' }}">
                </div>
                <div class="col-md-6 d-flex align-items-end gap-2">
                    <button type="submit" class="btn btn-dark">
                        <i class="fas fa-search"></i> Filtrar
                    </button>
                    <a href="{{ route('sarlaft.reportes.operaciones.index') }}" class="btn btn-outline-secondary">
                        Limpiar
                    </a>
                </div>
            </div>
        </form>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-md-6 col-lg-2">
        <div class="card shadow-sm h-100 border-primary">
            <div class="card-body">
                <div class="text-muted small">Total operaciones</div>
                <div class="fs-3 fw-bold">{{ number_format($stats['total_operaciones']) }}</div>
            </div>
        </div>
    </div>
    <div class="col-md-6 col-lg-2">
        <div class="card shadow-sm h-100 border-success">
            <div class="card-body">
                <div class="text-muted small">Permitidas</div>
                <div class="fs-3 fw-bold text-success">{{ number_format($stats['total_permitidas']) }}</div>
            </div>
        </div>
    </div>
    <div class="col-md-6 col-lg-2">
        <div class="card shadow-sm h-100 border-danger">
            <div class="card-body">
                <div class="text-muted small">Bloqueadas</div>
                <div class="fs-3 fw-bold text-danger">{{ number_format($stats['total_bloqueadas']) }}</div>
            </div>
        </div>
    </div>
    <div class="col-md-6 col-lg-2">
        <div class="card shadow-sm h-100 border-info">
            <div class="card-body">
                <div class="text-muted small">Pasajes</div>
                <div class="fs-3 fw-bold">{{ number_format($stats['total_pasajes']) }}</div>
            </div>
        </div>
    </div>
    <div class="col-md-6 col-lg-2">
        <div class="card shadow-sm h-100 border-warning">
            <div class="card-body">
                <div class="text-muted small">Mensajeria</div>
                <div class="fs-3 fw-bold">{{ number_format($stats['total_remesas']) }}</div>
            </div>
        </div>
    </div>
    <div class="col-md-6 col-lg-2">
        <div class="card shadow-sm h-100 border-dark">
            <div class="card-body">
                <div class="text-muted small">Con alerta</div>
                <div class="fs-3 fw-bold">{{ number_format($stats['con_alerta']) }}</div>
            </div>
        </div>
    </div>
</div>

<div class="card shadow-sm">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h6 class="mb-0">Operaciones Consultadas</h6>
        <span class="text-muted small">{{ $operaciones->total() }} registro(s)</span>
    </div>
    <div class="card-body p-0">
        @if($operaciones->isEmpty())
        <div class="text-center text-muted py-5">
            No se encontraron operaciones con los filtros seleccionados.
        </div>
        @else
        <div class="table-responsive">
            <table class="table table-hover mb-0 align-middle">
                <thead class="table-light">
                    <tr>
                        <th>Fecha</th>
                        <th>Operacion</th>
                        <th>Persona</th>
                        <th>Resultado</th>
                        <th>Detalle</th>
                        <th>Contexto</th>
                        <th>Alerta</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($operaciones as $consulta)
                    @php
                        $esPasaje = $consulta->sistema_origen === 'simulacion_pasaje';
                        $detalle = $esPasaje ? $consulta->simulacionPasaje : $consulta->simulacionRemesa;
                        $contextoOperacion = is_array($consulta->contexto_operacion) ? $consulta->contexto_operacion : [];
                        $alerta = $consulta->alertas->first();
                    @endphp
                    <tr>
                        <td class="small text-nowrap">{{ $consulta->created_at?->format('d/m/Y H:i') ?? '-' }}</td>
                        <td>
                            @if($esPasaje)
                                <span class="badge bg-info text-dark">Pasajes</span>
                            @else
                                <span class="badge bg-warning text-dark">Mensajeria</span>
                            @endif
                        </td>
                        <td>
                            <div class="fw-semibold">{{ $consulta->nombre_consultado ?: '-' }}</div>
                            <div class="text-muted small">{{ $consulta->tipo_documento }} {{ $consulta->numero_documento }}</div>
                        </td>
                        <td>
                            @if($consulta->presta_servicio)
                                <span class="badge bg-success">Permitida</span>
                            @else
                                <span class="badge bg-danger">Bloqueada</span>
                            @endif
                            <div class="mt-2">
                                @include('sarlaft::admin.partials.badge-riesgo', ['nivel' => $consulta->nivel_riesgo])
                            </div>
                        </td>
                        <td class="small">
                            @if($esPasaje && $detalle)
                                <div><strong>Ruta:</strong> {{ $detalle->ciudad_origen_nombre }} -> {{ $detalle->ciudad_destino_nombre }}</div>
                                <div><strong>Fecha viaje:</strong> {{ $detalle->fecha_viaje?->format('d/m/Y') ?? '-' }}</div>
                                <div><strong>Contacto:</strong> {{ $detalle->telefono }} / {{ $detalle->correo }}</div>
                                <div><strong>Direccion:</strong> {{ $detalle->direccion }}</div>
                            @elseif($detalle)
                                <div><strong>Ruta:</strong> {{ $detalle->ciudad_origen_nombre }} -> {{ $detalle->ciudad_destino_nombre }}</div>
                                <div><strong>Fecha envio:</strong> {{ $detalle->fecha_envio?->format('d/m/Y') ?? '-' }}</div>
                                <div><strong>Destinatario:</strong> {{ $detalle->nombre_destinatario }}</div>
                                <div><strong>Monto:</strong> ${{ number_format((float) $detalle->monto, 2, ',', '.') }}</div>
                                <div><strong>Concepto:</strong> {{ $detalle->concepto }}</div>
                            @else
                                <span class="text-muted">Sin detalle operativo adicional.</span>
                            @endif
                        </td>
                        <td class="small">
                            <div><strong>Encontrado:</strong> {{ $consulta->encontrado ? 'Si' : 'No' }}</div>
                            <div><strong>Decision aplicada:</strong> {{ str_replace('_', ' ', (string) ($contextoOperacion['decision_aplicada'] ?? 'sin_decision')) }}</div>
                            <div><strong>Resultado base:</strong> {{ ($contextoOperacion['presta_servicio_base'] ?? $consulta->presta_servicio) ? 'Permitido' : 'Bloqueado' }}</div>
                            <div><strong>Resultado final:</strong> {{ ($contextoOperacion['presta_servicio_final'] ?? $consulta->presta_servicio) ? 'Permitido' : 'Bloqueado' }}</div>
                        </td>
                        <td>
                            @if($alerta)
                                <a href="{{ route('sarlaft.alertas.show', $alerta) }}" class="btn btn-sm btn-dark">
                                    <i class="fas fa-eye"></i> Ver #{{ $alerta->id }}
                                </a>
                                @if($consulta->alertas_count > 1)
                                <div class="text-muted small mt-2">{{ $consulta->alertas_count }} alertas asociadas</div>
                                @endif
                            @else
                                <span class="text-muted small">Sin alerta asociada</span>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @endif
    </div>
    @if($operaciones->hasPages())
    <div class="card-footer">
        {{ $operaciones->links() }}
    </div>
    @endif
</div>
@endsection
