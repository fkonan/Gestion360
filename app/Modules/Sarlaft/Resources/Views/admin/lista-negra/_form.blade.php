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
@if(isset($registro) && $registro->exists)
<div class="mb-3">
    <label for="estado" class="form-label">Estado</label>
    <select name="estado" id="estado" class="form-select @error('estado') is-invalid @enderror">
        <option value="activo" {{ old('estado', $registro->estado) === 'activo' ? 'selected' : '' }}>Activo</option>
        <option value="inactivo" {{ old('estado', $registro->estado) === 'inactivo' ? 'selected' : '' }}>Inactivo</option>
    </select>
    @error('estado') <div class="invalid-feedback">{{ $message }}</div> @enderror
</div>
@endif
