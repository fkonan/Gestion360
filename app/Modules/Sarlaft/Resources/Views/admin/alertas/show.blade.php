@extends('layouts.dashboard')

@section('title', 'Alerta #' . $alerta->id)

@section('content')
@php
    $intento = $alerta->intento;
    $evidencias = is_array($alerta->evidencias) ? $alerta->evidencias : [];
    $contextoOperacion = is_array($alerta->contexto_operacion) ? $alerta->contexto_operacion : [];
    $origenAtencion = $contextoOperacion['origen_atencion'] ?? null;

    if ($origenAtencion === null && $alerta->escalada_automatica) {
        $origenAtencion = 'auto_sla';
    }

    $tipoAtencionLabel = match ($origenAtencion) {
        'auto_lista_negra_interna' => 'Auto Lista Restrictiva',
        'auto_sla' => 'Auto SLA',
        default => 'Atencion manual',
    };

    $datosPersona = is_array($alerta->datos_persona) ? $alerta->datos_persona : [];
    $personaNombre = trim((string) ($datosPersona['nombre'] ?? trim(($datosPersona['nombres'] ?? '') . ' ' . ($datosPersona['apellidos'] ?? ''))));
    $personaNombre = $personaNombre !== '' ? $personaNombre : 'Sin nombre registrado';
    $personaTipoDoc = (string) ($datosPersona['tipo_documento'] ?? $alerta->tipo_documento ?? '');
    $personaNumDoc = (string) ($datosPersona['numero_documento'] ?? $alerta->numero_documento ?? '');
    $personaDocumento = trim($personaTipoDoc . ' ' . $personaNumDoc);
@endphp

<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
    <div class="d-flex align-items-center gap-3 flex-wrap">
        <h4 class="mb-0 fw-bold">Alerta <span class="text-primary">#AL-{{ $alerta->id }}</span></h4>
        @include('sarlaft::admin.partials.badge-estado-alerta', ['estado' => $alerta->estado])
        @include('sarlaft::admin.partials.badge-riesgo', ['nivel' => $alerta->nivel_riesgo])
        <span class="text-muted small">
            <i class="far fa-clock"></i> {{ $alerta->created_at->format('d/m/Y H:i') }}
        </span>
        @if($alerta->escalada_automatica)
        <span class="badge bg-warning text-dark"><i class="fas fa-bolt"></i> Escalada automatica</span>
        @endif
    </div>
    <a href="{{ route('sarlaft.alertas.index') }}" class="btn btn-sm btn-outline-dark">
        <i class="fas fa-arrow-left"></i> Volver
    </a>
</div>

<div class="row g-4">
    {{-- Columna principal --}}
    <div class="col-lg-8">
        {{-- Sujeto identificado --}}
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-white">
                <h6 class="mb-0"><i class="fas fa-user-shield text-primary me-1"></i> Sujeto identificado</h6>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <div class="text-muted small text-uppercase">Nombre / Razon social</div>
                        <div class="fs-5 fw-semibold">{{ $personaNombre }}</div>
                    </div>
                    <div class="col-md-3">
                        <div class="text-muted small text-uppercase">Tipo documento</div>
                        <div class="fw-semibold">{{ $personaTipoDoc !== '' ? $personaTipoDoc : '-' }}</div>
                    </div>
                    <div class="col-md-3">
                        <div class="text-muted small text-uppercase">Numero documento</div>
                        <div class="fw-semibold">{{ $personaNumDoc !== '' ? $personaNumDoc : '-' }}</div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Operacion que genero la alerta --}}
        @if($intento)
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <h6 class="mb-0"><i class="fas fa-exchange-alt text-primary me-1"></i> Operacion que genero la alerta</h6>
                <span class="badge bg-light text-dark border">Intento #{{ $intento->id }}</span>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-4">
                        <div class="text-muted small text-uppercase">Sistema origen</div>
                        <div class="fw-semibold">{{ $intento->sistema_origen ?? ($intento->sistema?->codigo ?? $intento->sistema?->nombre ?? '-') }}</div>
                    </div>
                    <div class="col-md-4">
                        <div class="text-muted small text-uppercase">Tipo de operacion</div>
                        <div class="fw-semibold">{{ \Illuminate\Support\Str::headline(\Illuminate\Support\Str::lower((string) $intento->tipo_operacion)) ?: '-' }}</div>
                    </div>
                    <div class="col-md-4">
                        <div class="text-muted small text-uppercase">Referencia</div>
                        <div class="fw-semibold">{{ $intento->referencia ?? $intento->referencia_externa ?? '-' }}</div>
                    </div>
                    <div class="col-md-4">
                        <div class="text-muted small text-uppercase">Monto</div>
                        <div class="fw-semibold">{{ $intento->monto !== null ? '$' . number_format((float) $intento->monto, 2, ',', '.') : '-' }}</div>
                    </div>
                    <div class="col-md-4">
                        <div class="text-muted small text-uppercase">Fecha operacion</div>
                        <div class="fw-semibold">{{ $intento->created_at?->format('d/m/Y H:i') ?? '-' }}</div>
                    </div>
                    @if(!empty($intento->descripcion))
                    <div class="col-12">
                        <div class="text-muted small text-uppercase">Descripcion</div>
                        <div>{{ $intento->descripcion }}</div>
                    </div>
                    @endif
                </div>
            </div>
        </div>
        @endif

        {{-- Contexto de la operacion (enriquecido desde el sistema origen) --}}
        @php
            $ctx = $intento && is_array($intento->contexto) ? $intento->contexto : [];
            // Etiquetas legibles para las claves conocidas; el orden define la prioridad de despliegue.
            $ctxLabels = [
                'empresa' => 'Empresa',
                'empresaId' => 'Documento',
                'agencia' => 'Agencia',
                'usuario' => 'Usuario que atendio',
                'rolTercero' => 'Rol del tercero',
                'evento' => 'Evento',
                'metodo' => 'Metodo',
            ];
            // Claves internas/redundantes que no aportan al oficial.
            $ctxOcultar = ['sistema', 'identificacion', 'contexto', 'resultadoConsulta', 'agenciaId', 'tipoMovimiento'];
            $ctxConocidas = collect($ctxLabels)
                ->filter(fn ($label, $key) => filled($ctx[$key] ?? null))
                ->map(fn ($label, $key) => ['label' => $label, 'valor' => $ctx[$key]]);
            $ctxOtras = collect($ctx)
                ->reject(fn ($v, $k) => array_key_exists($k, $ctxLabels) || in_array($k, $ctxOcultar, true) || blank($v))
                ->map(fn ($v, $k) => ['label' => \Illuminate\Support\Str::headline($k), 'valor' => $v]);
            $ctxItems = $ctxConocidas->concat($ctxOtras);
        @endphp
        @if($ctxItems->isNotEmpty())
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-white">
                <h6 class="mb-0"><i class="fas fa-circle-info text-primary me-1"></i> Contexto de la operacion</h6>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    @foreach($ctxItems as $item)
                    <div class="col-md-4">
                        <div class="text-muted small text-uppercase">{{ $item['label'] }}</div>
                        <div class="fw-semibold">{{ $item['valor'] }}</div>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>
        @endif

        {{-- Coincidencia en listas --}}
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-white">
                <h6 class="mb-0"><i class="fas fa-list-check text-primary me-1"></i> Coincidencia en listas</h6>
            </div>
            <div class="card-body p-0">
                @if(empty($alerta->listas_coincidentes))
                <div class="alert alert-light border m-3 mb-3">No hay coincidencias de lista registradas.</div>
                @else
                <div class="table-responsive">
                    <table class="table table-sm table-hover align-middle mb-0">
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
                                <td class="fw-semibold">{{ $coincidencia['lista'] ?? $coincidencia['nombre'] ?? '-' }}</td>
                                <td><span class="badge bg-secondary">{{ $coincidencia['tipo_lista'] ?? $coincidencia['tipo'] ?? '-' }}</span></td>
                                <td>{{ $coincidencia['nombres'] ?? '-' }}</td>
                                <td>
                                    @if(!empty($coincidencia['alias']))
                                        {{ is_array($coincidencia['alias']) ? implode(', ', $coincidencia['alias']) : $coincidencia['alias'] }}
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                                <td>{{ $coincidencia['identificacion'] ?? ($personaNumDoc !== '' ? $personaNumDoc : '-') }}</td>
                                <td>{{ str_replace('_', ' ', $coincidencia['tipo_coincidencia'] ?? $coincidencia['tipo'] ?? '-') }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @endif
            </div>
        </div>

        {{-- Evidencias --}}
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-white">
                <h6 class="mb-0"><i class="fas fa-paperclip text-primary me-1"></i> Evidencias adjuntas</h6>
            </div>
            <div class="card-body">
                @if($evidencias === [])
                <div class="alert alert-light border mb-0">No hay evidencias adjuntas para esta alerta.</div>
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
                            <div class="fw-semibold"><i class="far fa-file me-1 text-secondary"></i>{{ $evidencia['original_name'] ?? 'Documento de soporte' }}</div>
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
            </div>
        </div>
    </div>

    {{-- Columna lateral --}}
    <div class="col-lg-4">
        {{-- Decision de servicio --}}
        @php
            $esVinculanteAlerta = in_array(strtolower(trim((string) $alerta->nivel_riesgo)), ['vinculante', 'alto'], true);
            $decision = $alerta->decision_servicio; // 'permitido', 'bloqueado' o null
            $decisionColor = $decision === 'permitido' ? 'success' : 'danger';
        @endphp
        <div class="card shadow-sm mb-4 border-{{ $decisionColor }}">
            <div class="card-header bg-white">
                <h6 class="mb-0"><i class="fas fa-gavel text-{{ $decisionColor }} me-1"></i> Decision de servicio</h6>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <span class="text-muted small text-uppercase d-block">Estado del servicio</span>
                    @if($decision === 'permitido')
                        <span class="badge bg-success"><i class="fas fa-check"></i> Servicio permitido</span>
                    @elseif($decision === 'bloqueado')
                        <span class="badge bg-danger"><i class="fas fa-ban"></i> Servicio bloqueado (decision)</span>
                    @else
                        <span class="badge bg-danger"><i class="fas fa-ban"></i> Servicio bloqueado (por defecto)</span>
                    @endif
                </div>

                @if($decision === null)
                {{-- Sin decision aun: mostrar ambos botones --}}
                <p class="small text-muted">
                    Por defecto la operacion esta <strong>bloqueada</strong>. Si tras la revision decide
                    permitir el servicio, indique el motivo. Esto retirara a la persona
                    (documento {{ $alerta->numero_documento }}) de la
                    {{ $esVinculanteAlerta ? 'lista vinculante (estado removido)' : 'lista restrictiva (retiro)' }},
                    dejando de bloquearse en los sistemas externos.
                </p>

                <div class="d-grid gap-2">
                    <button class="btn btn-outline-success" type="button" data-bs-toggle="collapse" data-bs-target="#formPermitirServicio" aria-expanded="false" aria-controls="formPermitirServicio">
                        <i class="fas fa-unlock"></i> Permitir servicio
                    </button>
                    <button class="btn btn-outline-danger" type="button" data-bs-toggle="collapse" data-bs-target="#formMantenerBloqueo" aria-expanded="false" aria-controls="formMantenerBloqueo">
                        <i class="fas fa-ban"></i> Mantener bloqueo
                    </button>
                </div>

                <div class="collapse mt-3" id="formPermitirServicio">
                    <form action="{{ route('sarlaft.alertas.permitir-servicio', $alerta) }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        <div class="mb-3">
                            <label for="motivo_servicio" class="form-label">Motivo <span class="text-danger">*</span></label>
                            <textarea name="motivo" id="motivo_servicio" rows="3" class="form-control @error('motivo') is-invalid @enderror" placeholder="Justificacion de la decision..." required>{{ old('motivo') }}</textarea>
                            @error('motivo') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                        </div>
                        <div class="mb-3">
                            <label for="evidencias_servicio" class="form-label">Soportes (opcional)</label>
                            <input type="file" name="evidencias[]" id="evidencias_servicio" class="form-control @error('evidencias.*') is-invalid @enderror" accept=".pdf,.jpg,.jpeg,.png,.doc,.docx,.xls,.xlsx" multiple>
                            @error('evidencias.*') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                        </div>
                        <button type="submit" class="btn btn-success w-100" onclick="return confirm('Confirma permitir el servicio? Se retiraran los registros de lista del documento {{ $alerta->numero_documento }}.')">
                            <i class="fas fa-check"></i> Confirmar y permitir
                        </button>
                    </form>
                </div>

                <div class="collapse mt-3" id="formMantenerBloqueo">
                    <form action="{{ route('sarlaft.alertas.mantener-bloqueo', $alerta) }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        <div class="mb-3">
                            <label for="motivo_bloqueo" class="form-label">Motivo <span class="text-danger">*</span></label>
                            <textarea name="motivo" id="motivo_bloqueo" rows="3" class="form-control @error('motivo') is-invalid @enderror" placeholder="Justificacion de la decision de bloqueo..." required>{{ old('motivo') }}</textarea>
                            @error('motivo') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                        </div>
                        <div class="mb-3">
                            <label for="evidencias_bloqueo" class="form-label">Soportes (opcional)</label>
                            <input type="file" name="evidencias[]" id="evidencias_bloqueo" class="form-control @error('evidencias.*') is-invalid @enderror" accept=".pdf,.jpg,.jpeg,.png,.doc,.docx,.xls,.xlsx" multiple>
                            @error('evidencias.*') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                        </div>
                        <button type="submit" class="btn btn-danger w-100" onclick="return confirm('Confirma mantener el bloqueo? La persona ({{ $alerta->numero_documento }}) seguira sin poder operar.')">
                            <i class="fas fa-ban"></i> Confirmar bloqueo
                        </button>
                    </form>
                </div>

                @else
                {{-- Ya hay decision registrada: mostrar estado y motivo --}}
                @if($alerta->notas)
                <div class="mb-2">
                    <span class="text-muted small text-uppercase d-block">Motivo</span>
                    <p class="small mb-0">{{ $alerta->notas }}</p>
                </div>
                @endif
                @if($alerta->decision_at)
                <div>
                    <span class="text-muted small text-uppercase d-block">Fecha de decision</span>
                    <span class="small">{{ \Illuminate\Support\Carbon::parse($alerta->decision_at)->format('d/m/Y H:i') }}</span>
                </div>
                @endif
                @endif
            </div>
        </div>

        {{-- Atender alerta --}}
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-white">
                <h6 class="mb-0"><i class="fas fa-clipboard-check text-success me-1"></i> Atender alerta</h6>
            </div>
            <div class="card-body">
                <form action="{{ route('sarlaft.alertas.atender', $alerta) }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    @method('PATCH')
                    @php
                        $estadoActual = old('estado', $alerta->estado);
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
                        <label for="notas" class="form-label">Notas</label>
                        <textarea name="notas" id="notas" rows="4" class="form-control" placeholder="Observaciones...">{{ old('notas', $alerta->notas) }}</textarea>
                        @error('notas') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                    </div>
                    <div class="mb-3" id="bloque-evidencias">
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
                            Adjunta soportes de gestion: PDF, imagen o documentos Office. Maximo 5 archivos por actualizacion.
                        </small>
                        @error('evidencias') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                        @error('evidencias.*') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                    </div>

                    @if(($relacionadasAbiertas ?? 1) >= 2)
                    <div class="alert alert-info py-2 px-3 mb-3">
                        <div class="form-check mb-1">
                            <input class="form-check-input" type="checkbox" name="aplicar_relacionadas" value="1" id="aplicar_relacionadas">
                            <label class="form-check-label fw-semibold" for="aplicar_relacionadas">
                                Aplicar a las {{ $relacionadasAbiertas }} alertas abiertas de este documento
                            </label>
                        </div>
                        <small class="text-muted d-block">
                            Mismo documento ({{ $alerta->numero_documento }}). Aplica el estado y las notas a todas las
                            pendientes o en revision. No adjunta evidencias en bloque.
                        </small>
                    </div>
                    @endif

                    <button type="submit" class="btn btn-success w-100">
                        <i class="fas fa-check"></i> Guardar
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@push('script')
<script>
    (function () {
        const checkbox = document.getElementById('aplicar_relacionadas');
        const bloqueEvidencias = document.getElementById('bloque-evidencias');

        if (!checkbox || !bloqueEvidencias) {
            return;
        }

        const sincronizar = () => {
            // El cierre masivo no procesa evidencias: se ocultan para no confundir.
            bloqueEvidencias.classList.toggle('d-none', checkbox.checked);
        };

        checkbox.addEventListener('change', sincronizar);
        sincronizar();
    })();
</script>
@endpush
