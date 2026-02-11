@extends('layouts.dashboard')

@section('title', 'Nueva Área')

@section('breadcrumb')
<x-breadcrumb :items="[
        ['name' => 'Inicio', 'url' => route('home')],
        ['name' => 'Áreas', 'url' => route('radfact.areas.index')],
        ['name' => 'Registrar área'],
    ]" />
<br>
@endsection

@section('content')
<div class="container-fluid p-0 border shadow rounded sidebar-dark-primary">
    <div class="border rounded-top d-flex justify-content-between align-items-center px-4 bg-primary-subtle">
        <span class="text-left text-light fs-5 fw-medium">Datos del área</span>
    </div>

    <form id="formArea" action="{{ route('radfact.areas.store') }}" method="POST">
        @csrf

        <div class="row pt-4 mx-4">
            {{-- Nombre del Área --}}
            <div class="col-md-6 mb-3">
                <label for="area" class="form-label">Nombre del Área</label>
                <input type="text" class="form-control @error('area') is-invalid @enderror"
                    id="area" name="area" value="{{ old('area') }}"
                    placeholder="Ej: Gerencia General, Subgerencia Administrativa, etc." required>
                <span class="error text-danger fw-bold" id="error-area">
                    @error('area') {{ $message }} @enderror
                </span>
            </div>

            {{-- Responsable --}}
            <div class="col-md-6 mb-3">
                <label for="responsable" class="form-label">Responsable</label>
                <input type="text" class="form-control @error('responsable') is-invalid @enderror"
                    id="responsable" name="responsable" value="{{ old('responsable') }}"
                    placeholder="Nombre completo del responsable" required>
                <span class="error text-danger fw-bold" id="error-responsable">
                    @error('responsable') {{ $message }} @enderror
                </span>
            </div>
        </div>

        <div class="row mx-4">
            {{-- Correo --}}
            <div class="col-md-4 mb-3">
                <label for="correo" class="form-label">Correo Electrónico</label>
                <input type="email" class="form-control @error('correo') is-invalid @enderror"
                    id="correo" name="correo" value="{{ old('correo') }}"
                    placeholder="correo@empresa.com">
                <span class="error text-danger fw-bold" id="error-correo">
                    @error('correo') {{ $message }} @enderror
                </span>
            </div>

            {{-- Subgerencia --}}
            <div class="col-md-4 mb-3">
                <label for="subgerencia" class="form-label">¿Es Subgerencia?</label>
                <select class="form-select @error('subgerencia') is-invalid @enderror"
                    id="subgerencia" name="subgerencia">
                    <option value="0" {{ old('subgerencia', '0') === '0' ? 'selected' : '' }}>No</option>
                    <option value="1" {{ old('subgerencia') === '1' ? 'selected' : '' }}>Sí</option>
                </select>
                <span class="error text-danger fw-bold" id="error-subgerencia">
                    @error('subgerencia') {{ $message }} @enderror
                </span>
            </div>

            {{-- Compras --}}
            <div class="col-md-4 mb-3">
                <label for="compras" class="form-label">¿Es Compras?</label>
                <select class="form-select @error('compras') is-invalid @enderror"
                    id="compras" name="compras">
                    <option value="0" {{ old('compras', '0') === '0' ? 'selected' : '' }}>No</option>
                    <option value="1" {{ old('compras') === '1' ? 'selected' : '' }}>Sí</option>
                </select>
                <span class="error text-danger fw-bold" id="error-compras">
                    @error('compras') {{ $message }} @enderror
                </span>
            </div>
        </div>

        <div class="alert alert-info mx-4" role="alert">
            <i class="fas fa-info-circle"></i>
            <strong>Nota:</strong> Las áreas marcadas como "Subgerencia" o "Compras" tendrán permisos especiales en el flujo de aprobación.
        </div>

        <div class="p-4">
            <button type="submit" class="btn btn-success">
                <i class="fas fa-save"></i> Guardar
            </button>
            <a href="{{ route('radfact.areas.index') }}" class="btn btn-dark">
                <i class="fas fa-times"></i> Cancelar
            </a>
        </div>
    </form>
</div>
@endsection

@pushOnce('script')
@vite(['resources/js/cargarModal.js'])

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const formArea = document.getElementById('formArea');

        // Validación del formulario
        formArea.addEventListener('submit', function(e) {
            const area = document.getElementById('area').value.trim();
            const responsable = document.getElementById('responsable').value.trim();

            if (!area || !responsable) {
                alert('Por favor complete todos los campos obligatorios');
                e.preventDefault();
                return false;
            }

            return true;
        });

        // Validar formulario con la función global
        if (typeof validarFormulario === 'function') {
            validarFormulario("#formArea");
        }
    });
</script>
@endpushOnce
