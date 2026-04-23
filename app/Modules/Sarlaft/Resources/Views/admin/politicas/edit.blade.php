@extends('layouts.dashboard')

@section('title', 'Politicas SARLAFT')

@section('content')
<div class="card shadow-sm">
    <x-sectionHeader titulo="Politicas SARLAFT" rutaVolver="{{ route('sarlaft.dashboard') }}" btnVolver=false />
    <div class="card-header d-flex justify-content-between align-items-center">
        <a href="{{ route('sarlaft.dashboard') }}" class="btn btn-sm btn-success">
            <i class="fas fa-arrow-left"></i> Regresar
        </a>
    </div>
    <form action="{{ route('sarlaft.politicas.update') }}" method="POST">
        @csrf
        @method('PUT')
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-6">
                    <label for="sla_dias_alerta" class="form-label">SLA de alertas pendientes (dias)</label>
                    <input
                        type="number"
                        min="1"
                        max="30"
                        class="form-control @error('sla_dias_alerta') is-invalid @enderror"
                        id="sla_dias_alerta"
                        name="sla_dias_alerta"
                        value="{{ old('sla_dias_alerta', $politica['sla_dias_alerta']) }}"
                        required
                    >
                    @error('sla_dias_alerta')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                <div class="col-md-6">
                    <label for="auto_user_id" class="form-label">Usuario tecnico automatico (opcional)</label>
                    <input
                        type="number"
                        min="1"
                        class="form-control @error('auto_user_id') is-invalid @enderror"
                        id="auto_user_id"
                        name="auto_user_id"
                        value="{{ old('auto_user_id', $politica['auto_user_id']) }}"
                        placeholder="Ej: 1"
                    >
                    @error('auto_user_id')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>

            <hr>

            <h6 class="mb-3">Supresion de alertas repetidas</h6>
            <div class="row g-3">
                <div class="col-md-6">
                    <input type="hidden" name="suppress_alert_on_bloquear" value="0">
                    <div class="form-check form-switch">
                        <input
                            class="form-check-input"
                            type="checkbox"
                            role="switch"
                            id="suppress_alert_on_bloquear"
                            name="suppress_alert_on_bloquear"
                            value="1"
                            {{ old('suppress_alert_on_bloquear', $politica['suppress_alert_on_bloquear']) ? 'checked' : '' }}
                        >
                        <label class="form-check-label" for="suppress_alert_on_bloquear">
                            No crear alerta nueva si ya existe decision activa <strong>bloquear</strong>
                        </label>
                    </div>
                </div>
                <div class="col-md-6">
                    <input type="hidden" name="suppress_alert_on_permitir_permanente" value="0">
                    <div class="form-check form-switch">
                        <input
                            class="form-check-input"
                            type="checkbox"
                            role="switch"
                            id="suppress_alert_on_permitir_permanente"
                            name="suppress_alert_on_permitir_permanente"
                            value="1"
                            {{ old('suppress_alert_on_permitir_permanente', $politica['suppress_alert_on_permitir_permanente']) ? 'checked' : '' }}
                        >
                        <label class="form-check-label" for="suppress_alert_on_permitir_permanente">
                            No crear alerta nueva si ya existe decision activa <strong>permitir permanente</strong>
                        </label>
                    </div>
                </div>
            </div>

            <hr>

            <h6 class="mb-3">Escalamiento automatico por vencimiento</h6>
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label d-block">Riesgos a escalar automaticamente</label>
                    @php
                        $riesgosActuales = old('auto_escalar_riesgos', $politica['auto_escalar_riesgos']);
                    @endphp
                    @foreach(['bajo', 'medio', 'alto', 'critico'] as $riesgo)
                        <div class="form-check">
                            <input
                                class="form-check-input"
                                type="checkbox"
                                id="riesgo_{{ $riesgo }}"
                                name="auto_escalar_riesgos[]"
                                value="{{ $riesgo }}"
                                {{ in_array($riesgo, (array) $riesgosActuales, true) ? 'checked' : '' }}
                            >
                            <label class="form-check-label" for="riesgo_{{ $riesgo }}">{{ ucfirst($riesgo) }}</label>
                        </div>
                    @endforeach
                    @error('auto_escalar_riesgos')
                        <div class="text-danger small">{{ $message }}</div>
                    @enderror
                </div>
                <div class="col-md-3">
                    <label for="auto_estado" class="form-label">Estado automatico</label>
                    <select id="auto_estado" name="auto_estado" class="form-select @error('auto_estado') is-invalid @enderror" required>
                        @foreach(['pendiente', 'en_revision', 'atendida', 'descartada'] as $estado)
                            <option value="{{ $estado }}" {{ old('auto_estado', $politica['auto_estado']) === $estado ? 'selected' : '' }}>
                                {{ str_replace('_', ' ', ucfirst($estado)) }}
                            </option>
                        @endforeach
                    </select>
                    @error('auto_estado')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                <div class="col-md-3">
                    <label for="auto_decision" class="form-label">Decision automatica</label>
                    <select id="auto_decision" name="auto_decision" class="form-select @error('auto_decision') is-invalid @enderror" required>
                        @foreach(['sin_decision', 'bloquear', 'permitir_una_operacion', 'permitir_permanente'] as $decision)
                            <option value="{{ $decision }}" {{ old('auto_decision', $politica['auto_decision']) === $decision ? 'selected' : '' }}>
                                {{ str_replace('_', ' ', ucfirst($decision)) }}
                            </option>
                        @endforeach
                    </select>
                    @error('auto_decision')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>

            <hr>

            <h6 class="mb-3">Autoatencion por Lista Negra Interna</h6>
            <div class="row g-3">
                <div class="col-md-6">
                    <input type="hidden" name="auto_atender_lista_negra_interna" value="0">
                    <div class="form-check form-switch">
                        <input
                            class="form-check-input"
                            type="checkbox"
                            role="switch"
                            id="auto_atender_lista_negra_interna"
                            name="auto_atender_lista_negra_interna"
                            value="1"
                            {{ old('auto_atender_lista_negra_interna', $politica['auto_atender_lista_negra_interna']) ? 'checked' : '' }}
                        >
                        <label class="form-check-label" for="auto_atender_lista_negra_interna">
                            Autoatender alertas generadas por coincidencia en lista negra interna
                        </label>
                    </div>
                </div>
                <div class="col-md-6">
                    <input type="hidden" name="auto_crear_alerta_atendida" value="0">
                    <div class="form-check form-switch">
                        <input
                            class="form-check-input"
                            type="checkbox"
                            role="switch"
                            id="auto_crear_alerta_atendida"
                            name="auto_crear_alerta_atendida"
                            value="1"
                            {{ old('auto_crear_alerta_atendida', $politica['auto_crear_alerta_atendida']) ? 'checked' : '' }}
                        >
                        <label class="form-check-label" for="auto_crear_alerta_atendida">
                            Crear alerta y dejarla atendida automaticamente para mantener trazabilidad
                        </label>
                    </div>
                </div>
            </div>
        </div>
        <div class="card-footer d-flex justify-content-end gap-2">
            <a href="{{ route('sarlaft.dashboard') }}" class="btn btn-dark">
                <i class="fas fa-arrow-left"></i> Volver
            </a>
            <button type="submit" class="btn btn-success">
                <i class="fas fa-save"></i> Guardar politicas
            </button>
        </div>
    </form>
</div>
@endsection
