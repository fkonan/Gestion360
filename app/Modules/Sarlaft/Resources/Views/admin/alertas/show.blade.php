@extends('layouts.dashboard')

@section('title', 'Alerta #' . $alerta->id)

@section('content')
@php
    $consulta = $alerta->consulta;
    $simulacionPasaje = $consulta?->simulacionPasaje;
    $simulacionRemesa = $consulta?->simulacionRemesa;
    $evidencias = is_array($alerta->evidencias) ? $alerta->evidencias : [];
    $contextoOperacion = is_array($alerta->contexto_operacion) ? $alerta->contexto_operacion : [];
    $origenAtencion = $contextoOperacion['origen_atencion'] ?? null;

    if ($origenAtencion === null && $alerta->escalada_automatica) {
        $origenAtencion = 'auto_sla';
    }
@endphp

<div class="d-flex justify-content-end gap-2 mb-3">
    <a href="{{ route('sarlaft.alertas.index') }}" class="btn btn-sm btn-outline-dark">
        <i class="fas fa-arrow-left"></i> Volver
    </a>
    @if($alerta->numero_documento)
    <a href="{{ route('sarlaft.reportes.operaciones.index', ['tipo_documento' => $alerta->tipo_documento, 'numero_documento' => $alerta->numero_documento]) }}" class="btn btn-sm btn-dark">
        <i class="fas fa-chart-line"></i> Historial Operativo
    </a>
    @endif
</div>

<div class="row g-4">
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

                <div class="row mb-3">
                    <div class="col-md-4">
                        <strong>Escalada automatica:</strong><br>
                        {{ $alerta->escalada_automatica ? 'Si' : 'No' }}
                    </div>
                    <div class="col-md-4">
                        <strong>Fecha escalamiento:</strong><br>
                        {{ $alerta->escalada_automatica_at?->format('d/m/Y H:i') ?? 'N/A' }}
                    </div>
                    <div class="col-md-4">
                        <strong>Tipo atencion:</strong><br>
                        @if($origenAtencion === 'auto_lista_negra_interna')
                            Auto Lista Negra Interna
                        @elseif($origenAtencion === 'auto_sla')
                            Auto SLA
                        @else
                            Manual
                        @endif
                    </div>
                </div>

                @if(($contextoOperacion['alerta_suprimida'] ?? false) === true)
                <div class="alert alert-dark py-2">
                    Esta alerta fue marcada con contexto de supresion:
                    {{ str_replace('_', ' ', (string) ($contextoOperacion['motivo_suprimir_alerta'] ?? 'sin motivo')) }}.
                </div>
                @endif

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

                <h6 class="mt-4">Evidencias Adjuntas</h6>
                @if($evidencias === [])
                <div class="alert alert-light border mb-0">
                    No hay evidencias adjuntas para esta alerta.
                </div>
                @else
                <div class="list-group">
                    @foreach($evidencias as $evidencia)
                    @php
                        $sizeBytes = isset($evidencia['size_bytes']) ? (int) $evidencia['size_bytes'] : 0;
                        $sizeLabel = $sizeBytes >= 1048576
                            ? number_format($sizeBytes / 1048576, 2).' MB'
                            : number_format($sizeBytes / 1024, 1).' KB';
                    @endphp
                    <div class="list-group-item d-flex justify-content-between align-items-center flex-wrap gap-2">
                        <div>
                            <div class="fw-semibold">{{ $evidencia['original_name'] ?? 'Documento de soporte' }}</div>
                            <div class="text-muted small">
                                Cargado {{ isset($evidencia['uploaded_at']) ? \Illuminate\Support\Carbon::parse((string) $evidencia['uploaded_at'])->format('d/m/Y H:i') : 'N/A' }}
                                @if($sizeBytes > 0)
                                - {{ $sizeLabel }}
                                @endif
                            </div>
                        </div>
                        @if(!empty($evidencia['id']))
                        <a href="{{ route('sarlaft.alertas.evidencias.download', [$alerta, $evidencia['id']]) }}" class="btn btn-sm btn-outline-dark">
                            <i class="fas fa-download"></i> Descargar
                        </a>
                        @endif
                    </div>
                    @endforeach
                </div>
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

    <div class="col-lg-4">
        <div class="card shadow-sm">
            <div class="card-header">
                <h6 class="mb-0">Atender Alerta</h6>
            </div>
            <div class="card-body">
                <form action="{{ route('sarlaft.alertas.atender', $alerta) }}" method="POST" enctype="multipart/form-data">
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
                    <div class="mb-3">
                        <label for="evidencias" class="form-label">Adjuntar evidencias</label>
                        <input
                            type="file"
                            name="evidencias[]"
                            id="evidencias"
                            class="form-control @error('evidencias') is-invalid @enderror @error('evidencias.*') is-invalid @enderror"
                            accept=".pdf,.jpg,.jpeg,.png,.doc,.docx,.xls,.xlsx"
                            multiple
                        >
                        <small class="text-muted d-block mt-2">
                            Adjunta soportes de la decision: PDF, imagen o documentos Office. Maximo 5 archivos por actualizacion.
                        </small>
                        @error('evidencias') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                        @error('evidencias.*') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                    </div>
                    <button type="submit" class="btn btn-success w-100">
                        <i class="fas fa-check"></i> Guardar
                    </button>
                </form>
            </div>
        </div>

        @if($consulta)
        <div class="card shadow-sm mt-3">
            <div class="card-header">
                <h6 class="mb-0">Consulta Asociada #{{ $consulta->id }}</h6>
            </div>
            <div class="card-body small">
                <p><strong>Sistema:</strong> {{ $consulta->sistema_origen }}</p>
                <p><strong>Documento:</strong> {{ $consulta->tipo_documento }} {{ $consulta->numero_documento }}</p>
                <p><strong>Resultado:</strong> {{ $consulta->encontrado ? 'Encontrado' : 'No encontrado' }}</p>
                <p><strong>Presta servicio:</strong> {{ $consulta->presta_servicio ? 'Si' : 'No' }}</p>
                <p class="mb-0"><strong>IP:</strong> {{ $consulta->ip_origen }}</p>
            </div>
        </div>

        @if($simulacionPasaje || $simulacionRemesa)
        <div class="card shadow-sm mt-3">
            <div class="card-header">
                <h6 class="mb-0">Detalle Operativo Asociado</h6>
            </div>
            <div class="card-body small">
                @if($simulacionPasaje)
                <p><strong>Operacion:</strong> Compra de tiquete</p>
                <p><strong>Ruta:</strong> {{ $simulacionPasaje->ciudad_origen_nombre }} -> {{ $simulacionPasaje->ciudad_destino_nombre }}</p>
                <p><strong>Fecha viaje:</strong> {{ $simulacionPasaje->fecha_viaje?->format('d/m/Y') ?? '-' }}</p>
                <p><strong>Persona:</strong> {{ trim($simulacionPasaje->nombres.' '.$simulacionPasaje->apellidos) }}</p>
                <p><strong>Direccion:</strong> {{ $simulacionPasaje->direccion }}</p>
                <p><strong>Telefono:</strong> {{ $simulacionPasaje->telefono }}</p>
                <p class="mb-0"><strong>Correo:</strong> {{ $simulacionPasaje->correo }}</p>
                @endif

                @if($simulacionRemesa)
                <p><strong>Operacion:</strong> Mensajeria / remesa</p>
                <p><strong>Ruta:</strong> {{ $simulacionRemesa->ciudad_origen_nombre }} -> {{ $simulacionRemesa->ciudad_destino_nombre }}</p>
                <p><strong>Fecha envio:</strong> {{ $simulacionRemesa->fecha_envio?->format('d/m/Y') ?? '-' }}</p>
                <p><strong>Remitente:</strong> {{ trim($simulacionRemesa->nombres_remitente.' '.$simulacionRemesa->apellidos_remitente) }}</p>
                <p><strong>Telefono remitente:</strong> {{ $simulacionRemesa->telefono_remitente }}</p>
                <p><strong>Destinatario:</strong> {{ $simulacionRemesa->nombre_destinatario }} ({{ $simulacionRemesa->documento_destinatario }})</p>
                <p><strong>Monto:</strong> ${{ number_format((float) $simulacionRemesa->monto, 2, ',', '.') }}</p>
                <p class="mb-0"><strong>Concepto:</strong> {{ $simulacionRemesa->concepto }}</p>
                @endif
            </div>
        </div>
        @endif
        @endif
    </div>
</div>
@endsection
