@extends('layouts.dashboard')

@section('content')
<div class="container-fluid">
    {{-- Breadcrumb --}}
    <div class="row mb-3">
        <div class="col-12">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('home') }}">Inicio</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('radfact.aprobaciones.index') }}">Aprobaciones</a></li>
                    <li class="breadcrumb-item active">Detalle</li>
                </ol>
            </nav>
        </div>
    </div>

    {{-- Alert --}}
    <x-alert />

    {{-- Información de la Radicación --}}
    <div class="row">
        <div class="col-lg-8">
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-file-invoice"></i> Información de la Radicación
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <strong>Proveedor:</strong><br>
                            {{ $aprobacion->distribucion->radicacion->proveedor->razon_social }}<br>
                            <small class="text-muted">{{ $aprobacion->distribucion->radicacion->proveedor->documento }}</small>
                        </div>
                        <div class="col-md-3 mb-3">
                            <strong>N° Factura:</strong><br>
                            {{ $aprobacion->distribucion->radicacion->num_factura }}
                        </div>
                        <div class="col-md-3 mb-3">
                            <strong>N° Contrato:</strong><br>
                            {{ $aprobacion->distribucion->radicacion->num_contrato ?? 'N/A' }}
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-3 mb-3">
                            <strong>Fecha Radicación:</strong><br>
                            {{ \Carbon\Carbon::parse($aprobacion->distribucion->radicacion->fecha_radicacion)->format('d/m/Y') }}
                        </div>
                        <div class="col-md-3 mb-3">
                            <strong>Fecha Vencimiento:</strong><br>
                            {{ \Carbon\Carbon::parse($aprobacion->distribucion->radicacion->fecha_vencimiento)->format('d/m/Y') }}
                        </div>
                        <div class="col-md-3 mb-3">
                            <strong>Valor Total:</strong><br>
                            <span class="h5 text-success">${{ number_format($aprobacion->distribucion->radicacion->valor, 2) }}</span>
                        </div>
                        <div class="col-md-3 mb-3">
                            <strong>N° Pagos:</strong><br>
                            {{ $aprobacion->distribucion->radicacion->numero_pagos }}
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-12 mb-3">
                            <strong>Descripción:</strong><br>
                            {{ $aprobacion->distribucion->radicacion->descripcion }}
                        </div>
                    </div>

                    @if($aprobacion->distribucion->radicacion->observacion)
                    <div class="row">
                        <div class="col-12 mb-3">
                            <strong>Observaciones:</strong><br>
                            <div class="alert alert-info mb-0">
                                {{ $aprobacion->distribucion->radicacion->observacion }}
                            </div>
                        </div>
                    </div>
                    @endif

                    @if($aprobacion->distribucion->radicacion->pdf)
                    <div class="row">
                        <div class="col-12">
                            <a href="{{ Storage::url($aprobacion->distribucion->radicacion->pdf) }}" target="_blank" class="btn btn-outline-danger">
                                <i class="fas fa-file-pdf"></i> Ver PDF de Factura
                            </a>
                        </div>
                    </div>
                    @endif
                </div>
            </div>

            {{-- Distribución a Aprobar --}}
            <div class="card shadow-sm mb-4 border-warning">
                <div class="card-header bg-warning text-dark">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-check-circle"></i> Distribución para Aprobar
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <strong>Área Asignada:</strong><br>
                            <span class="h5">{{ $aprobacion->distribucion->area->area }}</span>
                        </div>
                        <div class="col-md-3 mb-3">
                            <strong>Porcentaje:</strong><br>
                            <span class="h5 text-primary">{{ number_format($aprobacion->distribucion->porcentaje, 2) }}%</span>
                        </div>
                        <div class="col-md-3 mb-3">
                            <strong>Valor Asignado:</strong><br>
                            <span class="h5 text-success">${{ number_format($aprobacion->distribucion->valor_calculado, 2) }}</span>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <strong>Estado Actual:</strong><br>
                            @if($aprobacion->estado === 'PENDIENTE')
                                <span class="badge bg-warning text-dark fs-6">
                                    <i class="fas fa-clock"></i> Pendiente
                                </span>
                            @elseif($aprobacion->estado === 'APROBADO')
                                <span class="badge bg-success fs-6">
                                    <i class="fas fa-check"></i> Aprobado
                                </span>
                            @else
                                <span class="badge bg-danger fs-6">
                                    <i class="fas fa-times"></i> Rechazado
                                </span>
                            @endif
                        </div>
                        <div class="col-md-6 mb-3">
                            <strong>Solicitado por:</strong><br>
                            {{ $aprobacion->distribucion->radicacion->usuario->Nombre ?? 'N/A' }}
                        </div>
                    </div>

                    @if($aprobacion->estado === 'PENDIENTE')
                    <hr>
                    <div class="row mt-3">
                        <div class="col-12">
                            <button type="button" class="btn btn-success btn-lg" data-bs-toggle="modal" data-bs-target="#modalAprobar">
                                <i class="fas fa-check"></i> Aprobar Distribución
                            </button>
                            <button type="button" class="btn btn-danger btn-lg" data-bs-toggle="modal" data-bs-target="#modalRechazar">
                                <i class="fas fa-times"></i> Rechazar Distribución
                            </button>
                        </div>
                    </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- Sidebar - Todas las Distribuciones --}}
        <div class="col-lg-4">
            <div class="card shadow-sm">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-chart-pie"></i> Todas las Distribuciones
                    </h5>
                </div>
                <div class="card-body">
                    @foreach($aprobacion->distribucion->radicacion->distribucionesActivas as $dist)
                    <div class="card mb-2 {{ $dist->id === $aprobacion->distribucion_id ? 'border-warning' : '' }}">
                        <div class="card-body p-2">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <strong>{{ $dist->area->area }}</strong><br>
                                    <small class="text-muted">{{ number_format($dist->porcentaje, 2) }}% - ${{ number_format($dist->valor_calculado, 2) }}</small>
                                </div>
                                <div>
                                    @if($dist->aprobacion->estado === 'PENDIENTE')
                                        <span class="badge bg-warning text-dark">Pendiente</span>
                                    @elseif($dist->aprobacion->estado === 'APROBADO')
                                        <span class="badge bg-success">Aprobado</span>
                                    @else
                                        <span class="badge bg-danger">Rechazado</span>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                    @endforeach

                    <hr>
                    <div class="text-center">
                        <strong>Total:</strong> 100% - ${{ number_format($aprobacion->distribucion->radicacion->valor, 2) }}
                    </div>
                </div>
            </div>

            <div class="card shadow-sm mt-3">
                <div class="card-header">
                    <h6 class="card-title mb-0">
                        <i class="fas fa-info-circle"></i> Estado de la Radicación
                    </h6>
                </div>
                <div class="card-body">
                    <p class="mb-2">
                        <strong>Estado:</strong>
                        @switch($aprobacion->distribucion->radicacion->estado)
                            @case('RADICADO')
                                <span class="badge bg-info">Radicado</span>
                                @break
                            @case('EN_APROBACION')
                                <span class="badge bg-warning text-dark">En Aprobación</span>
                                @break
                            @case('RECHAZADO')
                                <span class="badge bg-danger">Rechazado</span>
                                @break
                            @case('PENDIENTE_SUBGERENCIA')
                                <span class="badge bg-primary">Pendiente Subgerencia</span>
                                @break
                            @case('COMPLETADO')
                                <span class="badge bg-success">Completado</span>
                                @break
                        @endswitch
                    </p>
                    @if($aprobacion->distribucion->radicacion->necesita_visto_bueno)
                    <p class="mb-0">
                        <span class="badge bg-secondary"><i class="fas fa-clipboard-check"></i> Requiere Visto Bueno</span>
                    </p>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <a href="{{ route('radfact.aprobaciones.index') }}" class="btn btn-outline-dark">
                <i class="fas fa-arrow-left"></i> Volver a Aprobaciones
            </a>
        </div>
    </div>
</div>

{{-- Modal Aprobar --}}
<div class="modal fade" id="modalAprobar" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ route('radfact.aprobaciones.aprobar', $aprobacion) }}" method="POST">
                @csrf
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title">
                        <i class="fas fa-check"></i> Aprobar Distribución
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-success">
                        <i class="fas fa-info-circle"></i>
                        Está a punto de aprobar la distribución del <strong>{{ number_format($aprobacion->distribucion->porcentaje, 2) }}%</strong>
                        ($ {{ number_format($aprobacion->distribucion->valor_calculado, 2) }}) para el área
                        <strong>{{ $aprobacion->distribucion->area->area }}</strong>.
                    </div>

                    <div class="mb-3">
                        <label for="observacion_aprobar" class="form-label">Observaciones (opcional)</label>
                        <textarea class="form-control" id="observacion_aprobar" name="observacion" rows="3"
                                  placeholder="Ingrese observaciones adicionales si lo desea..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-check"></i> Confirmar Aprobación
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Modal Rechazar --}}
<div class="modal fade" id="modalRechazar" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ route('radfact.aprobaciones.rechazar', $aprobacion) }}" method="POST" id="formRechazar">
                @csrf
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title">
                        <i class="fas fa-times"></i> Rechazar Distribución
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-triangle"></i>
                        Está a punto de rechazar la distribución. Esta acción permitirá al usuario ajustar las distribuciones.
                    </div>

                    <div class="mb-3">
                        <label for="observacion_rechazar" class="form-label">Motivo del Rechazo *</label>
                        <textarea class="form-control" id="observacion_rechazar" name="observacion" rows="4" required
                                  placeholder="Por favor indique el motivo del rechazo..."></textarea>
                        <small class="text-muted">Este campo es obligatorio para rechazar</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-danger">
                        <i class="fas fa-times"></i> Confirmar Rechazo
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
$(document).ready(function() {
    // Validar formulario de rechazo
    $('#formRechazar').submit(function(e) {
        const observacion = $('#observacion_rechazar').val().trim();
        if (observacion === '') {
            e.preventDefault();
            alert('Debe ingresar un motivo para rechazar la distribución');
            return false;
        }
        return true;
    });
});
</script>
@endpush
