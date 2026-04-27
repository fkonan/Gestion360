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
