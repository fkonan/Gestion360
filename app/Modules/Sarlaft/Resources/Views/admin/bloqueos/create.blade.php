@extends('layouts.dashboard')
@section('title', 'Nuevo Bloqueo')

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="card shadow-sm">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h6 class="mb-0"><i class="fas fa-user-lock"></i> Crear Bloqueo</h6>
                <a href="{{ route('sarlaft.bloqueos.index') }}" class="btn btn-sm btn-outline-secondary">
                    <i class="fas fa-arrow-left"></i> Volver
                </a>
            </div>
            <div class="card-body">
                <form action="{{ route('sarlaft.bloqueos.store') }}" method="POST">
                    @csrf
                    <div class="row mb-3">
                        <div class="col-md-4">
                            <label for="tipo_documento" class="form-label">Tipo Documento <span class="text-danger">*</span></label>
                            <select name="tipo_documento" id="tipo_documento" class="form-select @error('tipo_documento') is-invalid @enderror" required>
                                <option value="">Seleccionar...</option>
                                <option value="CC" {{ old('tipo_documento') === 'CC' ? 'selected' : '' }}>CC - Cedula</option>
                                <option value="NIT" {{ old('tipo_documento') === 'NIT' ? 'selected' : '' }}>NIT</option>
                                <option value="CE" {{ old('tipo_documento') === 'CE' ? 'selected' : '' }}>CE - Cedula Extranjeria</option>
                                <option value="PA" {{ old('tipo_documento') === 'PA' ? 'selected' : '' }}>PA - Pasaporte</option>
                            </select>
                            @error('tipo_documento') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-8">
                            <label for="numero_documento" class="form-label">Numero Documento <span class="text-danger">*</span></label>
                            <input type="text" name="numero_documento" id="numero_documento" class="form-control @error('numero_documento') is-invalid @enderror" value="{{ old('numero_documento') }}" required>
                            @error('numero_documento') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="nombre" class="form-label">Nombre</label>
                        <input type="text" name="nombre" id="nombre" class="form-control @error('nombre') is-invalid @enderror" value="{{ old('nombre') }}">
                        @error('nombre') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="mb-3">
                        <label for="motivo_bloqueo" class="form-label">Motivo del Bloqueo <span class="text-danger">*</span></label>
                        <textarea name="motivo_bloqueo" id="motivo_bloqueo" rows="4" class="form-control @error('motivo_bloqueo') is-invalid @enderror" required>{{ old('motivo_bloqueo') }}</textarea>
                        @error('motivo_bloqueo') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Tipo de bloqueo</label>
                            <input type="text" class="form-control" value="Manual" readonly>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Estado inicial</label>
                            <input type="text" class="form-control" value="Bloqueado" readonly>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-danger">
                        <i class="fas fa-lock"></i> Crear Bloqueo
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
