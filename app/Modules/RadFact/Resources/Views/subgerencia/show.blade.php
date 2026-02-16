@extends('layouts.dashboard')

@section('content')
<div class="container-fluid">
    {{-- Breadcrumb --}}
    <div class="row mb-3">
        <div class="col-12">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('home') }}">Inicio</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('radfact.subgerencia.index') }}">Aprobación Subgerencia</a></li>
                    <li class="breadcrumb-item active">Detalle</li>
                </ol>
            </nav>
        </div>
    </div>

    {{-- Alert --}}
    <x-alert />

    {{-- Header --}}
    <div class="row mb-3">
        <div class="col-md-8">
            <h3><i class="fas fa-file-invoice"></i> Radicación #{{ str_pad($radicacion->id, 6, '0', STR_PAD_LEFT) }}</h3>
            <p class="text-muted">Aprobación de Subgerencia</p>
        </div>
        <div class="col-md-4 text-end">
            <a href="{{ route('radfact.subgerencia.index') }}" class="btn btn-outline-dark">
                <i class="fas fa-arrow-left"></i> Volver
            </a>
        </div>
    </div>

    <div class="row">
        {{-- Información de la Radicación --}}
        <div class="col-lg-8">
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-info-circle"></i> Información de la Radicación
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <strong>Proveedor:</strong><br>
                            {{ $radicacion->proveedor->razon_social }}<br>
                            <small class="text-muted">{{ $radicacion->proveedor->documento }}</small>
                        </div>
                        <div class="col-md-3 mb-3">
                            <strong>N° Factura:</strong><br>
                            {{ $radicacion->num_factura }}
                        </div>
                        <div class="col-md-3 mb-3">
                            <strong>N° Contrato:</strong><br>
                            {{ $radicacion->num_contrato ?? 'N/A' }}
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-3 mb-3">
                            <strong>Fecha Radicación:</strong><br>
                            {{ \Carbon\Carbon::parse($radicacion->fecha_radicacion)->format('d/m/Y') }}
                        </div>
                        <div class="col-md-3 mb-3">
                            <strong>Fecha Vencimiento:</strong><br>
                            {{ \Carbon\Carbon::parse($radicacion->fecha_vencimiento)->format('d/m/Y') }}
                            @if(\Carbon\Carbon::parse($radicacion->fecha_vencimiento)->isPast())
                                <br><span class="badge bg-danger">Vencida</span>
                            @endif
                        </div>
                        <div class="col-md-3 mb-3">
                            <strong>Valor Total:</strong><br>
                            <span class="h5 text-success">${{ number_format($radicacion->valor, 2) }}</span>
                        </div>
                        <div class="col-md-3 mb-3">
                            <strong>N° Pagos:</strong><br>
                            {{ $radicacion->numero_pagos }}
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-12 mb-3">
                            <strong>Descripción:</strong><br>
                            {{ $radicacion->descripcion }}
                        </div>
                    </div>

                    @if($radicacion->observacion)
                    <div class="row">
                        <div class="col-12 mb-3">
                            <strong>Observaciones:</strong><br>
                            <div class="alert alert-info mb-0">
                                {{ $radicacion->observacion }}
                            </div>
                        </div>
                    </div>
                    @endif

                    @if($radicacion->pdf)
                    <div class="row">
                        <div class="col-12">
                            <a href="{{ Storage::disk('public')->url($radicacion->pdf) }}" target="_blank" class="btn btn-outline-danger">
                                <i class="fas fa-file-pdf"></i> Ver PDF de Factura
                            </a>
                        </div>
                    </div>
                    @endif
                </div>
            </div>

            {{-- Distribuciones Aprobadas --}}
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-success text-white">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-check-circle"></i> Distribuciones Aprobadas por Áreas
                    </h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-sm mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Área</th>
                                    <th class="text-end">Valor</th>
                                    <th class="text-center">Estado</th>
                                    <th>Aprobado por</th>
                                    <th>Fecha Aprobación</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($radicacion->distribucionesActivas as $dist)
                                <tr>
                                    <td>{{ $dist->area->area }}</td>
                                    <td class="text-end">${{ number_format($dist->valor, 2) }}</td>
                                    <td class="text-center">
                                        @if($dist->aprobacion && $dist->aprobacion->estado === 'APROBADO')
                                            <span class="badge bg-success">Aprobado</span>
                                        @else
                                            <span class="badge bg-secondary">Sin aprobar</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($dist->aprobacion)
                                            {{ $dist->aprobacion->usuario->Usuario ?? 'N/A' }}
                                        @else
                                            -
                                        @endif
                                    </td>
                                    <td>
                                        @if($dist->aprobacion)
                                            {{ \Carbon\Carbon::parse($dist->aprobacion->fecha_respuesta)->format('d/m/Y H:i') }}
                                        @else
                                            -
                                        @endif
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                            <tfoot class="table-light">
                                <tr>
                                    <th>TOTAL</th>
                                    <th class="text-end">${{ number_format($radicacion->distribucionesActivas->sum('valor'), 2) }}</th>
                                    <th colspan="3"></th>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        {{-- Panel de Aprobación --}}
        <div class="col-lg-4">
            <div class="card shadow-sm border-warning">
                <div class="card-header bg-warning text-dark">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-user-shield"></i> Aprobación Subgerencia
                    </h5>
                </div>
                <div class="card-body">
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i>
                        <strong>Instrucciones:</strong><br>
                        Revisa la información de la radicación y las aprobaciones de áreas.
                        Si está correcta, aprueba para enviar a Compras.
                    </div>

                    {{-- Formulario de Aprobación --}}
                    <form method="POST" action="{{ route('radfact.subgerencia.aprobar', $radicacion) }}" id="formAprobar">
                        @csrf

                        <div class="mb-3">
                            <label for="observacion" class="form-label">Observación (Opcional)</label>
                            <textarea class="form-control" id="observacion" name="observacion" rows="3"
                                      placeholder="Agregar observación..."></textarea>
                        </div>

                        <button type="submit" class="btn btn-success w-100">
                            <i class="fas fa-check"></i> Aprobar y Enviar a Compras
                        </button>
                    </form>

                    <hr>

                    {{-- Formulario de Rechazo --}}
                    <form method="POST" action="{{ route('radfact.subgerencia.rechazar', $radicacion) }}" id="formRechazar">
                        @csrf

                        <div class="mb-3">
                            <label for="observacion_rechazo" class="form-label">Motivo del Rechazo *</label>
                            <textarea class="form-control" id="observacion_rechazo" name="observacion" rows="3"
                                      placeholder="Explica el motivo del rechazo..." required></textarea>
                        </div>

                        <button type="submit" class="btn btn-danger w-100">
                            <i class="fas fa-times"></i> Rechazar Radicación
                        </button>
                    </form>
                </div>
            </div>

            {{-- Información Adicional --}}
            <div class="card shadow-sm mt-4">
                <div class="card-header">
                    <h6 class="card-title mb-0">
                        <i class="fas fa-history"></i> Información Adicional
                    </h6>
                </div>
                <div class="card-body">
                    <p><strong>Radicado por:</strong><br>{{ $radicacion->usuario->Usuario ?? 'N/A' }}</p>
                    <p><strong>Fecha de radicación:</strong><br>{{ $radicacion->created_at->format('d/m/Y H:i') }}</p>
                    <p><strong>Fecha envío a subgerencia:</strong><br>{{ \Carbon\Carbon::parse($radicacion->fecha_envio_subgerencia)->format('d/m/Y H:i') }}</p>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    // Confirmación al aprobar
    $('#formAprobar').submit(function(e) {
        if (!confirm('¿Está seguro de APROBAR esta radicación? Se enviará a Compras para su proceso final.')) {
            e.preventDefault();
            return false;
        }
        return true;
    });

    // Confirmación al rechazar
    $('#formRechazar').submit(function(e) {
        const motivo = $('#observacion_rechazo').val().trim();

        if (!motivo) {
            alert('Debe ingresar el motivo del rechazo');
            e.preventDefault();
            return false;
        }

        if (!confirm('¿Está seguro de RECHAZAR esta radicación? Se notificará al usuario.')) {
            e.preventDefault();
            return false;
        }

        return true;
    });
});
</script>
@endpush
