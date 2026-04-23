<div class="row mb-3">
    <div class="col-md-4">
        <label for="tipo_entidad" class="form-label">Tipo Entidad <span class="text-danger">*</span></label>
        <select name="tipo_entidad" id="tipo_entidad" class="form-select @error('tipo_entidad') is-invalid @enderror" required>
            <option value="">Seleccionar...</option>
            <option value="persona" {{ old('tipo_entidad', $registro->tipo_entidad ?? '') === 'persona' ? 'selected' : '' }}>Persona</option>
            <option value="organizacion" {{ old('tipo_entidad', $registro->tipo_entidad ?? '') === 'organizacion' ? 'selected' : '' }}>Organizacion</option>
        </select>
        @error('tipo_entidad') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
    <div class="col-md-4">
        <label for="tipo_documento" class="form-label">Tipo Documento <span class="text-danger">*</span></label>
        <select name="tipo_documento" id="tipo_documento" class="form-select @error('tipo_documento') is-invalid @enderror" required>
            <option value="">Seleccionar...</option>
            <option value="CC" {{ old('tipo_documento', $registro->tipo_documento ?? '') === 'CC' ? 'selected' : '' }}>CC - Cedula</option>
            <option value="NIT" {{ old('tipo_documento', $registro->tipo_documento ?? '') === 'NIT' ? 'selected' : '' }}>NIT</option>
            <option value="CE" {{ old('tipo_documento', $registro->tipo_documento ?? '') === 'CE' ? 'selected' : '' }}>CE - Cedula Extranjeria</option>
            <option value="PA" {{ old('tipo_documento', $registro->tipo_documento ?? '') === 'PA' ? 'selected' : '' }}>PA - Pasaporte</option>
        </select>
        @error('tipo_documento') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
    <div class="col-md-4">
        <label for="numero_documento" class="form-label">Numero Documento <span class="text-danger">*</span></label>
        <input type="text" name="numero_documento" id="numero_documento" class="form-control @error('numero_documento') is-invalid @enderror" value="{{ old('numero_documento', $registro->numero_documento ?? '') }}" required>
        @error('numero_documento') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
</div>
<div class="mb-3">
    <label for="nombres" class="form-label">Nombres <span class="text-danger">*</span></label>
    <input type="text" name="nombres" id="nombres" class="form-control @error('nombres') is-invalid @enderror" value="{{ old('nombres', $registro->nombres ?? '') }}" required>
    @error('nombres') <div class="invalid-feedback">{{ $message }}</div> @enderror
</div>
<div class="mb-3">
    <label for="motivo" class="form-label">Motivo <span class="text-danger">*</span></label>
    <textarea name="motivo" id="motivo" rows="4" class="form-control @error('motivo') is-invalid @enderror" required>{{ old('motivo', $registro->motivo ?? '') }}</textarea>
    @error('motivo') <div class="invalid-feedback">{{ $message }}</div> @enderror
</div>
<div class="mb-3">
    <label for="archivo_evidencia_inclusion" class="form-label">Evidencia de inclusion <span class="text-danger">*</span></label>
    <input
        type="file"
        name="archivo_evidencia_inclusion"
        id="archivo_evidencia_inclusion"
        class="form-control @error('archivo_evidencia_inclusion') is-invalid @enderror"
        accept=".pdf,.jpg,.jpeg,.png,.doc,.docx,.xls,.xlsx"
        {{ isset($registro) && $registro->exists ? '' : 'required' }}
    >
    @error('archivo_evidencia_inclusion') <div class="invalid-feedback">{{ $message }}</div> @enderror

    @if(isset($registro) && is_array($registro->evidencia_inclusion))
    <div class="form-text">
        Evidencia actual: {{ $registro->evidencia_inclusion['original_name'] ?? 'archivo' }}.
        <a href="{{ route('sarlaft.lista-negra.evidencias.download', [$registro, 'inclusion']) }}" class="ms-1">Descargar</a>
    </div>
    @endif
</div>
@if(isset($registro) && $registro->exists)
<div class="mb-3">
    <label for="estado" class="form-label">Estado</label>
    <select name="estado" id="estado" class="form-select @error('estado') is-invalid @enderror">
        <option value="activo" {{ old('estado', $registro->estado) === 'activo' ? 'selected' : '' }}>Activo</option>
        <option value="inactivo" {{ old('estado', $registro->estado) === 'inactivo' ? 'selected' : '' }}>Inactivo</option>
    </select>
    @error('estado') <div class="invalid-feedback">{{ $message }}</div> @enderror
</div>

<div class="mb-3">
    <label for="motivo_retiro" class="form-label">Motivo de retiro (obligatorio al inactivar)</label>
    <textarea name="motivo_retiro" id="motivo_retiro" rows="3" class="form-control @error('motivo_retiro') is-invalid @enderror">{{ old('motivo_retiro', $registro->motivo_retiro ?? '') }}</textarea>
    @error('motivo_retiro') <div class="invalid-feedback">{{ $message }}</div> @enderror
</div>

<div class="mb-3">
    <label for="archivo_evidencia_retiro" class="form-label">Evidencia de retiro (obligatoria al inactivar)</label>
    <input
        type="file"
        name="archivo_evidencia_retiro"
        id="archivo_evidencia_retiro"
        class="form-control @error('archivo_evidencia_retiro') is-invalid @enderror"
        accept=".pdf,.jpg,.jpeg,.png,.doc,.docx,.xls,.xlsx"
    >
    @error('archivo_evidencia_retiro') <div class="invalid-feedback">{{ $message }}</div> @enderror

    @if(isset($registro) && is_array($registro->evidencia_retiro))
    <div class="form-text">
        Evidencia de retiro actual: {{ $registro->evidencia_retiro['original_name'] ?? 'archivo' }}.
        <a href="{{ route('sarlaft.lista-negra.evidencias.download', [$registro, 'retiro']) }}" class="ms-1">Descargar</a>
    </div>
    @endif
</div>
@endif
