@extends('layouts.dashboard')

@section('title', 'Bloqueo #' . $bloqueo->id)

@section('content')
<div class="d-flex justify-content-end mb-3">
    <a href="{{ route('sarlaft.bloqueos.index') }}" class="btn btn-sm btn-dark">
        <i class="fas fa-arrow-left"></i> Volver
    </a>
</div>

<div class="row g-4">
    <div class="col-lg-8">
        <div class="card shadow-sm">
            <div class="card-header">
                <h6 class="mb-0">Detalle del Bloqueo</h6>
            </div>
            <div class="card-body">
                <div class="row mb-3">
                    <div class="col-md-4">
                        <strong>Estado:</strong><br>
                        <span class="badge bg-{{ $bloqueo->estado === 'bloqueado' ? 'danger' : 'success' }} fs-6">
                            {{ ucfirst($bloqueo->estado) }}
                        </span>
                    </div>
                    <div class="col-md-4">
                        <strong>Tipo Bloqueo:</strong><br>
                        {{ ucfirst($bloqueo->tipo_bloqueo) }}
                    </div>
                    <div class="col-md-4">
                        <strong>Creado por:</strong><br>
                        {{ trim(($bloqueo->creadoPor->persona?->PerNombres ?? '') . ' ' . ($bloqueo->creadoPor->persona?->PerApellidos ?? '')) ?: '-' }}
                    </div>
                </div>
                <hr>
                <div class="row mb-3">
                    <div class="col-md-4">
                        <strong>Documento:</strong><br>
                        {{ $bloqueo->tipo_documento }} {{ $bloqueo->numero_documento }}
                    </div>
                    <div class="col-md-4">
                        <strong>Nombre:</strong><br>
                        {{ $bloqueo->nombre ?? '-' }}
                    </div>
                    <div class="col-md-4">
                        <strong>Fecha:</strong><br>
                        {{ $bloqueo->created_at->format('d/m/Y H:i:s') }}
                    </div>
                </div>
                <hr>
                <h6>Motivo del Bloqueo</h6>
                <p class="bg-light p-3 rounded">{{ $bloqueo->motivo_bloqueo }}</p>

                @if($bloqueo->justificacion_desbloqueo)
                <h6 class="text-success">Justificacion de Desbloqueo</h6>
                <p class="bg-light p-3 rounded">{{ $bloqueo->justificacion_desbloqueo }}</p>
                @endif

                @if($bloqueo->documentos_soporte)
                <h6>Documentos Soporte</h6>
                <ul>
                    @foreach($bloqueo->documentos_soporte as $doc)
                    <li>{{ $doc }}</li>
                    @endforeach
                </ul>
                @endif
            </div>
        </div>
    </div>

    {{-- Desbloquear --}}
    <div class="col-lg-4">
        @if($bloqueo->estado === 'bloqueado')
        <div class="card shadow-sm border-success">
            <div class="card-header bg-success text-white">
                <h6 class="mb-0"><i class="fas fa-unlock"></i> Desbloquear</h6>
            </div>
            <div class="card-body">
                <form action="{{ route('sarlaft.bloqueos.update', $bloqueo) }}" method="POST">
                    @csrf
                    @method('PATCH')
                    <div class="mb-3">
                        <label for="justificacion_desbloqueo" class="form-label">Justificacion <span class="text-danger">*</span></label>
                        <textarea name="justificacion_desbloqueo" id="justificacion_desbloqueo" rows="4" class="form-control @error('justificacion_desbloqueo') is-invalid @enderror" required>{{ old('justificacion_desbloqueo') }}</textarea>
                        @error('justificacion_desbloqueo') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Documentos Soporte (opcional)</label>
                        <small class="text-muted d-block mb-2">Puedes registrar referencias, radicados o enlaces.</small>
                        @php
                            $documentosSoporte = old('documentos_soporte');
                            if (! is_array($documentosSoporte)) {
                                $documentosSoporte = is_array($bloqueo->documentos_soporte) && $bloqueo->documentos_soporte !== []
                                    ? $bloqueo->documentos_soporte
                                    : [''];
                            }
                        @endphp
                        @foreach($documentosSoporte as $indice => $documento)
                            <input type="text"
                                name="documentos_soporte[]"
                                class="form-control @error('documentos_soporte') is-invalid @enderror @error('documentos_soporte.'.$indice) is-invalid @enderror mb-2"
                                value="{{ $documento }}"
                                placeholder="Ej: Ticket #12345 o URL de soporte">
                        @endforeach
                        @error('documentos_soporte') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                        @error('documentos_soporte.*') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                    </div>
                    <button type="submit" class="btn btn-success w-100" onclick="return confirm('Esta seguro de levantar este bloqueo?')">
                        <i class="fas fa-unlock"></i> Confirmar Desbloqueo
                    </button>
                </form>
            </div>
        </div>
        @endif
    </div>
</div>
@endsection
