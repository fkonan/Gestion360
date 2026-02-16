@extends('layouts.dashboard')

@section('content')
<div class="container-fluid">
    {{-- Breadcrumb --}}
    <div class="row mb-3">
        <div class="col-12">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('home') }}">Inicio</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('radfact.compras.index') }}">Aprobación Compras</a></li>
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
            <p class="text-muted">Aprobación Final de Compras</p>
        </div>
        <div class="col-md-4 text-end">
            <a href="{{ route('radfact.compras.index') }}" class="btn btn-outline-dark">
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
                            @elseif(\Carbon\Carbon::parse($radicacion->fecha_vencimiento)->diffInDays() <= 5)
                                <br><span class="badge bg-warning text-dark">Por vencer</span>
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
                        <i class="fas fa-check-circle"></i> Distribuciones Aprobadas
                    </h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-sm mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Área</th>
                                    <th class="text-end">Valor</th>
                                    <th>Aprobado por</th>
                                    <th>Fecha Aprobación</th>
                                    <th>Observación</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($radicacion->distribucionesActivas as $dist)
                                <tr>
                                    <td>{{ $dist->area->area }}</td>
                                    <td class="text-end">${{ number_format($dist->valor, 2) }}</td>
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
                                    <td>
                                        @if($dist->aprobacion && $dist->aprobacion->observacion)
                                            <small>{{ $dist->aprobacion->observacion }}</small>
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

            {{-- Aprobación Subgerencia --}}
            @if($radicacion->usuarioSubgerencia)
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-info text-white">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-user-shield"></i> Aprobación de Subgerencia
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4">
                            <strong>Aprobado por:</strong><br>
                            {{ $radicacion->usuarioSubgerencia->Usuario ?? 'N/A' }}
                        </div>
                        <div class="col-md-4">
                            <strong>Fecha:</strong><br>
                            {{ \Carbon\Carbon::parse($radicacion->fecha_respuesta_subgerencia)->format('d/m/Y H:i') }}
                        </div>
                        <div class="col-md-4">
                            <strong>Estado:</strong><br>
                            <span class="badge bg-success">{{ $radicacion->estado_subgerencia }}</span>
                        </div>
                    </div>
                    @if($radicacion->observacion_subgerencia)
                    <div class="row mt-3">
                        <div class="col-12">
                            <strong>Observación:</strong><br>
                            <div class="alert alert-info mb-0">
                                {{ $radicacion->observacion_subgerencia }}
                            </div>
                        </div>
                    </div>
                    @endif
                </div>
            </div>
            @endif
        </div>

        {{-- Panel de Aprobación --}}
        <div class="col-lg-4">
            <div class="card shadow-sm border-success">
                <div class="card-header bg-success text-white">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-shopping-cart"></i> Aprobación Final - Compras
                    </h5>
                </div>
                <div class="card-body">
                    <div class="alert alert-success">
                        <i class="fas fa-info-circle"></i>
                        <strong>Última Aprobación:</strong><br>
                        Revisa que toda la información esté correcta. Al aprobar, la factura quedará lista para pago.
                    </div>

                    {{-- Formulario de Aprobación --}}
                    <form method="POST" action="{{ route('radfact.compras.aprobar', $radicacion) }}" id="formAprobar">
                        @csrf

                        <div class="mb-3">
                            <label for="observacion" class="form-label">Observación (Opcional)</label>
                            <textarea class="form-control" id="observacion" name="observacion" rows="3"
                                      placeholder="Agregar observación..."></textarea>
                        </div>

                        <button type="submit" class="btn btn-success w-100">
                            <i class="fas fa-check"></i> Aprobar - Lista para Pago
                        </button>
                    </form>

                    <hr>

                    {{-- Formulario de Rechazo --}}
                    <form method="POST" action="{{ route('radfact.compras.rechazar', $radicacion) }}" id="formRechazar">
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
                        <i class="fas fa-history"></i> Historial del Proceso
                    </h6>
                </div>
                <div class="card-body">
                    <p><strong>Radicado por:</strong><br>{{ $radicacion->usuario->Usuario ?? 'N/A' }}</p>
                    <p><strong>Fecha de radicación:</strong><br>{{ $radicacion->created_at->format('d/m/Y H:i') }}</p>
                    <p><strong>Aprobado por subgerencia:</strong><br>{{ $radicacion->usuarioSubgerencia->Usuario ?? 'N/A' }}</p>
                    <p><strong>Fecha aprobación subgerencia:</strong><br>
                        @if($radicacion->fecha_respuesta_subgerencia)
                            {{ \Carbon\Carbon::parse($radicacion->fecha_respuesta_subgerencia)->format('d/m/Y H:i') }}
                        @else
                            N/A
                        @endif
                    </p>
                    <p><strong>Fecha envío a compras:</strong><br>{{ \Carbon\Carbon::parse($radicacion->fecha_envio_compras)->format('d/m/Y H:i') }}</p>
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
        if (!confirm('¿Está seguro de APROBAR esta radicación? Quedará lista para PAGO.')) {
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

        if (!confirm('¿Está seguro de RECHAZAR esta radicación? Se notificará al usuario y deberá iniciar el proceso nuevamente.')) {
            e.preventDefault();
            return false;
        }

        return true;
    });
});
</script>
@endpush
