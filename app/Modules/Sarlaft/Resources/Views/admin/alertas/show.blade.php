@extends('layouts.dashboard')

@section('title', 'Alerta #' . $alerta->id)

@section('actions')
<a href="{{ route('sarlaft.alertas.index') }}" class="btn btn-sm btn-outline-secondary">
    <i class="fas fa-arrow-left"></i> Volver
</a>
@endsection

@section('content')
<div class="row g-4">
    {{-- Info de la alerta --}}
    <div class="col-lg-8">
        <div class="card shadow-sm">
            <div class="card-header">
                <h6 class="mb-0">Informacion de la Alerta</h6>
            </div>
            <div class="card-body">
                <div class="row mb-3">
                    <div class="col-md-3">
                        <strong>Estado:</strong><br>
                        @include('sarlaft::admin.partials.badge-estado-alerta', ['estado' => $alerta->estado])
                    </div>
                    <div class="col-md-3">
                        <strong>Riesgo:</strong><br>
                        @include('sarlaft::admin.partials.badge-riesgo', ['nivel' => $alerta->nivel_riesgo])
                    </div>
                    <div class="col-md-3">
                        <strong>Tipo:</strong><br>
                        {{ $alerta->tipo }}
                    </div>
                    <div class="col-md-3">
                        <strong>Fecha:</strong><br>
                        {{ $alerta->created_at->format('d/m/Y H:i:s') }}
                    </div>
                </div>
                <div class="row mb-3">
                    <div class="col-md-4">
                        <strong>Decision de servicio:</strong><br>
                        {{ str_replace('_', ' ', ucfirst((string) ($alerta->decision_servicio ?? 'sin_decision'))) }}
                    </div>
                    <div class="col-md-4">
                        <strong>Decision activa:</strong><br>
                        {{ $alerta->decision_activa ? 'Si' : 'No' }}
                    </div>
                    <div class="col-md-4">
                        <strong>Consumo decision:</strong><br>
                        @if($alerta->decision_servicio === 'permitir_una_operacion')
                            @if($alerta->decision_consumida_at)
                                Consumida ({{ $alerta->decision_consumida_at->format('d/m/Y H:i') }})
                            @else
                                Pendiente de consumo
                            @endif
                        @else
                            N/A
                        @endif
                    </div>
                </div>

                <h6 class="mt-4">Datos de la Persona</h6>
                <div class="table-responsive">
                    <table class="table table-sm table-bordered">
                        @foreach($alerta->datos_persona ?? [] as $key => $value)
                        <tr>
                            <th class="w-25 table-light">{{ str_replace('_', ' ', ucfirst($key)) }}</th>
                            <td>{{ $value ?? '-' }}</td>
                        </tr>
                        @endforeach
                    </table>
                </div>

                <h6 class="mt-4">Listas Coincidentes</h6>
                <div class="table-responsive">
                    <table class="table table-sm table-bordered">
                        <thead class="table-light">
                            <tr>
                                <th>Lista</th>
                                <th>Tipo</th>
                                <th>Nombres</th>
                                <th>Alias</th>
                                <th>Identificacion</th>
                                <th>Coincidencia</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($alerta->listas_coincidentes ?? [] as $coincidencia)
                            <tr>
                                <td>{{ $coincidencia['lista'] ?? '-' }}</td>
                                <td><span class="badge bg-secondary">{{ $coincidencia['tipo_lista'] ?? '-' }}</span></td>
                                <td>{{ $coincidencia['nombres'] ?? '-' }}</td>
                                <td>
                                    @if(!empty($coincidencia['alias']))
                                        @if(is_array($coincidencia['alias']))
                                            {{ implode(', ', $coincidencia['alias']) }}
                                        @else
                                            {{ $coincidencia['alias'] }}
                                        @endif
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                                <td>{{ $coincidencia['identificacion'] ?? '-' }}</td>
                                <td>{{ str_replace('_', ' ', $coincidencia['tipo_coincidencia'] ?? '-') }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                @if($alerta->notas)
                <h6 class="mt-4">Notas</h6>
                <p class="bg-light p-3 rounded">{{ $alerta->notas }}</p>
                @endif

                @if($alerta->atendidaPor)
                <div class="mt-3 text-muted small">
                    Atendida por <strong>{{ trim(($alerta->atendidaPor->persona?->PerNombres ?? '') . ' ' . ($alerta->atendidaPor->persona?->PerApellidos ?? '')) ?: '-' }}</strong>
                    el {{ $alerta->fecha_atencion?->format('d/m/Y H:i') }}
                </div>
                @endif
            </div>
        </div>
    </div>

    {{-- Acciones --}}
    <div class="col-lg-4">
        <div class="card shadow-sm">
            <div class="card-header">
                <h6 class="mb-0">Atender Alerta</h6>
            </div>
            <div class="card-body">
                <form action="{{ route('sarlaft.alertas.atender', $alerta) }}" method="POST">
                    @csrf
                    @method('PATCH')
                    @php
                        $estadoActual = old('estado', $alerta->estado);
                        $decisionActual = old('decision_servicio', $alerta->decision_servicio ?? 'sin_decision');
                    @endphp
                    <div class="mb-3">
                        <label for="estado" class="form-label">Cambiar estado</label>
                        <select name="estado" id="estado" class="form-select" required>
                            <option value="">Seleccionar...</option>
                            <option value="pendiente" {{ $estadoActual === 'pendiente' ? 'selected' : '' }}>Pendiente</option>
                            <option value="en_revision" {{ $estadoActual === 'en_revision' ? 'selected' : '' }}>En revision</option>
                            <option value="atendida" {{ $estadoActual === 'atendida' ? 'selected' : '' }}>Atendida</option>
                            <option value="descartada" {{ $estadoActual === 'descartada' ? 'selected' : '' }}>Descartada</option>
                        </select>
                        @error('estado') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                    </div>
                    <div class="mb-3">
                        <label for="decision_servicio" class="form-label">Decision de servicio</label>
                        <select name="decision_servicio" id="decision_servicio" class="form-select" required>
                            <option value="sin_decision" {{ $decisionActual === 'sin_decision' ? 'selected' : '' }}>Sin decision</option>
                            <option value="bloquear" {{ $decisionActual === 'bloquear' ? 'selected' : '' }}>Bloquear</option>
                            <option value="permitir_una_operacion" {{ $decisionActual === 'permitir_una_operacion' ? 'selected' : '' }}>Permitir una operacion</option>
                            <option value="permitir_permanente" {{ $decisionActual === 'permitir_permanente' ? 'selected' : '' }}>Permitir permanente</option>
                        </select>
                        <small class="text-muted d-block mt-2">
                            Esta decision controla el servicio futuro para el documento consultado, independiente del estado operativo de la alerta.
                        </small>
                        @error('decision_servicio') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                    </div>
                    @if($alerta->decision_servicio === 'permitir_una_operacion')
                    <div class="alert alert-info py-2">
                        @if($alerta->decision_consumida_at)
                            Excepcion consumida en consulta #{{ $alerta->decision_consumida_consulta_id ?? 'N/A' }}.
                        @else
                            Excepcion pendiente: se consumira en la primera consulta bloqueante.
                        @endif
                    </div>
                    @endif
                    <div class="mb-3">
                        <label for="notas" class="form-label">Notas</label>
                        <textarea name="notas" id="notas" rows="4" class="form-control" placeholder="Observaciones...">{{ old('notas', $alerta->notas) }}</textarea>
                        @error('notas') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                    </div>
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="fas fa-check"></i> Guardar
                    </button>
                </form>
            </div>
        </div>

        {{-- Consulta asociada --}}
        @if($alerta->consulta)
        <div class="card shadow-sm mt-3">
            <div class="card-header">
                <h6 class="mb-0">Consulta Asociada #{{ $alerta->consulta->id }}</h6>
            </div>
            <div class="card-body small">
                <p><strong>Sistema:</strong> {{ $alerta->consulta->sistema_origen }}</p>
                <p><strong>Documento:</strong> {{ $alerta->consulta->tipo_documento }} {{ $alerta->consulta->numero_documento }}</p>
                <p><strong>Resultado:</strong> {{ $alerta->consulta->encontrado ? 'Encontrado' : 'No encontrado' }}</p>
                <p><strong>Presta servicio:</strong> {{ $alerta->consulta->presta_servicio ? 'Si' : 'No' }}</p>
                <p class="mb-0"><strong>IP:</strong> {{ $alerta->consulta->ip_origen }}</p>
            </div>
        </div>
        @endif
    </div>
</div>
@endsection
