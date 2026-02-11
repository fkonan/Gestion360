@extends('layouts.dashboard')

@section('title', 'Nueva Radicación')

@section('breadcrumb')
<x-breadcrumb :items="[
        ['name' => 'Inicio', 'url' => route('home')],
        ['name' => 'Radicaciones', 'url' => route('radfact.radicaciones.index')],
        ['name' => 'Nueva radicación'],
    ]" />
<br>
@endsection

@section('content')
<div class="container-fluid p-0 border shadow rounded sidebar-dark-primary">
    <div class="border rounded-top d-flex justify-content-between align-items-center px-4 bg-primary-subtle">
        <span class="text-left text-light fs-5 fw-medium">Información de la factura</span>
    </div>

    <form id="formRadicacion" action="{{ route('radfact.radicaciones.store') }}" method="POST" enctype="multipart/form-data">
        @csrf

        <div class="row pt-4 mx-4">
            {{-- Proveedor --}}
            <div class="col-md-6 mb-3">
                <label for="proveedor_id" class="form-label">Proveedor</label>
                <select class="form-select select2 @error('proveedor_id') is-invalid @enderror"
                    id="proveedor_id" name="proveedor_id" required>
                    <option value="">Seleccione un proveedor...</option>
                    @foreach($proveedores as $proveedor)
                        <option value="{{ $proveedor->id }}" {{ old('proveedor_id') == $proveedor->id ? 'selected' : '' }}>
                            {{ $proveedor->nombre_completo }} - {{ $proveedor->documento }}
                        </option>
                    @endforeach
                </select>
                <span class="error text-danger fw-bold" id="error-proveedor_id">
                    @error('proveedor_id') {{ $message }} @enderror
                </span>
            </div>

            {{-- Número de Factura --}}
            <div class="col-md-3 mb-3">
                <label for="num_factura" class="form-label">Número de Factura</label>
                <input type="text" class="form-control @error('num_factura') is-invalid @enderror"
                    id="num_factura" name="num_factura" value="{{ old('num_factura') }}" required>
                <span class="error text-danger fw-bold" id="error-num_factura">
                    @error('num_factura') {{ $message }} @enderror
                </span>
            </div>

            {{-- Número de Contrato --}}
            <div class="col-md-3 mb-3">
                <label for="num_contrato" class="form-label">Número de Contrato</label>
                <input type="text" class="form-control @error('num_contrato') is-invalid @enderror"
                    id="num_contrato" name="num_contrato" value="{{ old('num_contrato') }}">
                <span class="error text-danger fw-bold" id="error-num_contrato">
                    @error('num_contrato') {{ $message }} @enderror
                </span>
            </div>
        </div>

        <div class="row mx-4">
            {{-- Fecha Radicación --}}
            <div class="col-md-3 mb-3">
                <label for="fecha_radicacion" class="form-label">Fecha de Radicación</label>
                <input type="date" class="form-control @error('fecha_radicacion') is-invalid @enderror"
                    id="fecha_radicacion" name="fecha_radicacion"
                    value="{{ old('fecha_radicacion', date('Y-m-d')) }}" required>
                <span class="error text-danger fw-bold" id="error-fecha_radicacion">
                    @error('fecha_radicacion') {{ $message }} @enderror
                </span>
            </div>

            {{-- Fecha Vencimiento --}}
            <div class="col-md-3 mb-3">
                <label for="fecha_vencimiento" class="form-label">Fecha de Vencimiento</label>
                <input type="date" class="form-control @error('fecha_vencimiento') is-invalid @enderror"
                    id="fecha_vencimiento" name="fecha_vencimiento"
                    value="{{ old('fecha_vencimiento') }}" required>
                <span class="error text-danger fw-bold" id="error-fecha_vencimiento">
                    @error('fecha_vencimiento') {{ $message }} @enderror
                </span>
            </div>

            {{-- Valor Total --}}
            <div class="col-md-3 mb-3">
                <label for="valor" class="form-label">Valor Total</label>
                <div class="input-group">
                    <span class="input-group-text">$</span>
                    <input type="number" class="form-control @error('valor') is-invalid @enderror"
                        id="valor" name="valor" value="{{ old('valor') }}"
                        step="0.01" min="0" required>
                </div>
                <span class="error text-danger fw-bold" id="error-valor">
                    @error('valor') {{ $message }} @enderror
                </span>
            </div>

            {{-- Número de Pagos --}}
            <div class="col-md-3 mb-3">
                <label for="numero_pagos" class="form-label">Número de Pagos</label>
                <input type="number" class="form-control @error('numero_pagos') is-invalid @enderror"
                    id="numero_pagos" name="numero_pagos" value="{{ old('numero_pagos', 1) }}"
                    min="1" required>
                <span class="error text-danger fw-bold" id="error-numero_pagos">
                    @error('numero_pagos') {{ $message }} @enderror
                </span>
            </div>
        </div>

        <div class="row mx-4">
            {{-- Descripción --}}
            <div class="col-md-6 mb-3">
                <label for="descripcion" class="form-label">Descripción</label>
                <textarea class="form-control @error('descripcion') is-invalid @enderror"
                    id="descripcion" name="descripcion" rows="3" required>{{ old('descripcion') }}</textarea>
                <span class="error text-danger fw-bold" id="error-descripcion">
                    @error('descripcion') {{ $message }} @enderror
                </span>
            </div>

            {{-- Observaciones --}}
            <div class="col-md-6 mb-3">
                <label for="observacion" class="form-label">Observaciones</label>
                <textarea class="form-control @error('observacion') is-invalid @enderror"
                    id="observacion" name="observacion" rows="3">{{ old('observacion') }}</textarea>
                <span class="error text-danger fw-bold" id="error-observacion">
                    @error('observacion') {{ $message }} @enderror
                </span>
            </div>
        </div>

        <div class="row mx-4 pb-4">
            {{-- PDF --}}
            <div class="col-md-6 mb-3">
                <label for="pdf" class="form-label">PDF de Factura</label>
                <input type="file" class="form-control @error('pdf') is-invalid @enderror"
                    id="pdf" name="pdf" accept=".pdf">
                <small class="form-text text-muted">Tamaño máximo: 10MB</small>
                <span class="error text-danger fw-bold" id="error-pdf">
                    @error('pdf') {{ $message }} @enderror
                </span>
            </div>

            {{-- Necesita Visto Bueno --}}
            <div class="col-md-6 mb-3">
                <label class="form-label d-block">&nbsp;</label>
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" id="necesita_visto_bueno"
                        name="necesita_visto_bueno" value="1" {{ old('necesita_visto_bueno') ? 'checked' : '' }}>
                    <label class="form-check-label" for="necesita_visto_bueno">
                        ¿Necesita Visto Bueno?
                    </label>
                </div>
            </div>
        </div>

        <div class="border rounded-top d-flex justify-content-between align-items-center px-4 bg-primary-subtle">
            <span class="text-left text-light fs-5 fw-medium">Distribución por áreas</span>
        </div>

        <div class="px-4 pt-4">
            <p class="text-muted">Distribuya el valor total entre las áreas correspondientes. La suma de porcentajes debe ser 100%.</p>

            <div id="distribuciones-container">
                {{-- Las distribuciones se agregan dinámicamente aquí --}}
            </div>

            <div class="row mt-3">
                <div class="col-12">
                    <button type="button" class="btn btn-outline-primary" id="btnAgregarDistribucion">
                        <i class="fas fa-plus"></i> Agregar Área
                    </button>
                </div>
            </div>

            <div class="row mt-3 pb-4">
                <div class="col-md-6">
                    <div class="alert alert-info mb-0">
                        <strong>Total Porcentaje:</strong> <span id="totalPorcentaje">0</span>%
                        <span id="alertPorcentaje" class="ms-2"></span>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="alert alert-secondary mb-0">
                        <strong>Total Valor:</strong> $<span id="totalValor">0.00</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="p-4">
            <button type="submit" class="btn btn-success" id="btnGuardar">
                <i class="fas fa-save"></i> Guardar
            </button>
            <a href="{{ route('radfact.radicaciones.index') }}" class="btn btn-dark">
                <i class="fas fa-times"></i> Cancelar
            </a>
        </div>
    </form>
</div>

{{-- Template para distribución --}}
<template id="distribucion-template">
    <div class="card mb-2 distribucion-item">
        <div class="card-body">
            <div class="row align-items-center">
                <div class="col-md-5">
                    <label class="form-label">Área</label>
                    <select class="form-select select2-dist" name="distribuciones[INDEX][area_id]" required>
                        <option value="">Seleccione área...</option>
                        @foreach($areas as $area)
                            <option value="{{ $area->id }}">{{ $area->area }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Porcentaje (%)</label>
                    <input type="number" class="form-control porcentaje-input"
                        name="distribuciones[INDEX][porcentaje]"
                        min="0" max="100" step="0.01" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Valor Calculado</label>
                    <input type="text" class="form-control valor-calculado" readonly>
                </div>
                <div class="col-md-1">
                    <label class="form-label d-none d-md-block">&nbsp;</label>
                    <button type="button" class="btn btn-danger btn-sm btn-eliminar-dist w-100">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>
@endsection

@pushOnce('script')
@vite(['resources/js/cargarModal.js'])

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
$(document).ready(function() {
    let distribucionIndex = 0;

    // Inicializar Select2 principal
    if (typeof $.fn.select2 !== 'undefined') {
        $('#proveedor_id').select2({
            dropdownParent: $("#formRadicacion"),
            width: '100%',
            placeholder: 'Seleccione un proveedor...',
            allowClear: true
        });
    }

    // Función para calcular totales
    function calcularTotales() {
        let totalPorcentaje = 0;
        let valorTotal = parseFloat($('#valor').val()) || 0;
        let totalValor = 0;

        $('.distribucion-item').each(function() {
            const porcentaje = parseFloat($(this).find('.porcentaje-input').val()) || 0;
            const valorCalculado = (porcentaje / 100) * valorTotal;

            $(this).find('.valor-calculado').val('$' + valorCalculado.toFixed(2));
            totalPorcentaje += porcentaje;
            totalValor += valorCalculado;
        });

        $('#totalPorcentaje').text(totalPorcentaje.toFixed(2));
        $('#totalValor').text(totalValor.toFixed(2));

        // Validación de porcentaje
        const $alertPorcentaje = $('#alertPorcentaje');
        if (Math.abs(totalPorcentaje - 100) < 0.01 && totalPorcentaje > 0) {
            $alertPorcentaje.html('<span class="badge bg-success"><i class="fas fa-check"></i> Correcto</span>');
            $('#btnGuardar').prop('disabled', false);
        } else if (totalPorcentaje === 0) {
            $alertPorcentaje.html('<span class="badge bg-secondary"><i class="fas fa-info-circle"></i> Agregue distribuciones</span>');
            $('#btnGuardar').prop('disabled', true);
        } else {
            $alertPorcentaje.html('<span class="badge bg-warning text-dark"><i class="fas fa-exclamation-triangle"></i> Debe sumar 100%</span>');
            $('#btnGuardar').prop('disabled', true);
        }
    }

    // Agregar distribución
    $('#btnAgregarDistribucion').on('click', function(e) {
        e.preventDefault();

        const template = document.getElementById('distribucion-template');
        if (!template) {
            console.error('Template de distribución no encontrado');
            return;
        }

        const templateContent = template.innerHTML;
        const newDistribucion = templateContent.replace(/INDEX/g, distribucionIndex);

        $('#distribuciones-container').append(newDistribucion);

        // Inicializar Select2 para el nuevo elemento
        if (typeof $.fn.select2 !== 'undefined') {
            const selectElement = $(`select[name="distribuciones[${distribucionIndex}][area_id]"]`);
            selectElement.select2({
                dropdownParent: $('body'),
                width: '100%',
                placeholder: 'Seleccione área...'
            });
        }

        distribucionIndex++;
        calcularTotales();
    });

    // Eliminar distribución
    $(document).on('click', '.btn-eliminar-dist', function(e) {
        e.preventDefault();
        $(this).closest('.distribucion-item').remove();
        calcularTotales();
    });

    // Recalcular cuando cambie el porcentaje
    $(document).on('input', '.porcentaje-input', function() {
        calcularTotales();
    });

    // Recalcular cuando cambie el valor total
    $('#valor').on('input', function() {
        calcularTotales();
    });

    // Agregar primera distribución automáticamente
    setTimeout(function() {
        $('#btnAgregarDistribucion').trigger('click');
    }, 100);

    // Validación antes de enviar
    $('#formRadicacion').on('submit', function(e) {
        const totalPorcentaje = parseFloat($('#totalPorcentaje').text());
        const numDistribuciones = $('.distribucion-item').length;

        if (Math.abs(totalPorcentaje - 100) >= 0.01) {
            e.preventDefault();
            alert('El total de porcentajes debe ser 100%. Actualmente es: ' + totalPorcentaje + '%');
            return false;
        }

        if (numDistribuciones === 0) {
            e.preventDefault();
            alert('Debe agregar al menos una distribución');
            return false;
        }

        return true;
    });

    // Validar formulario con la función global
    if (typeof validarFormulario === 'function') {
        validarFormulario("#formRadicacion");
    }

    // Inicializar totales
    calcularTotales();
});
</script>
@endpushOnce
