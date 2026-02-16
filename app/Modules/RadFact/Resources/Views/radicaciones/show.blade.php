@extends('layouts.dashboard')

@section('title', 'Ver Radicación')

@section('breadcrumb')
<x-breadcrumb :items="[
        ['name' => 'Inicio', 'url' => route('home')],
        ['name' => 'Radicaciones', 'url' => route('radfact.radicaciones.index')],
        ['name' => 'Ver radicación'],
    ]" />
<br>
@endsection

@section('content')
<div class="container-fluid">
    {{-- Alert --}}
    <x-alert />

    {{-- Información de la Factura --}}
    <div class="p-0 border shadow rounded sidebar-dark-primary mb-3">
        <div class="border rounded-top d-flex justify-content-between align-items-center px-4 bg-primary-subtle">
            <span class="text-left text-light fs-5 fw-medium">Información de la factura</span>
            <div>
                @php
                    $badgeClass = match($radicacion->estado) {
                        'RADICADO' => 'bg-info',
                        'EN_APROBACION' => 'bg-warning text-dark',
                        'RECHAZADO' => 'bg-danger',
                        'PENDIENTE_SUBGERENCIA' => 'bg-primary',
                        'PENDIENTE_COMPRAS' => 'bg-primary',
                        'APROBADO' => 'bg-success',
                        'COMPLETADO' => 'bg-success',
                        'PAGADO' => 'bg-success',
                        default => 'bg-secondary',
                    };
                    $estadoTexto = match($radicacion->estado) {
                        'RADICADO' => 'Radicado',
                        'EN_APROBACION' => 'En Aprobación',
                        'RECHAZADO' => 'Rechazado',
                        'PENDIENTE_SUBGERENCIA' => 'Pte. Subgerencia',
                        'PENDIENTE_COMPRAS' => 'Pte. Compras',
                        'APROBADO' => 'Aprobado',
                        'COMPLETADO' => 'Completado',
                        'PAGADO' => 'Pagado',
                        default => $radicacion->estado,
                    };
                @endphp
                <span class="badge {{ $badgeClass }} me-2">{{ $estadoTexto }}</span>
            </div>
        </div>

        <div class="row pt-4 mx-4">
            <div class="col-md-3 mb-3">
                <label class="form-label fw-bold">Número de Factura</label>
                <input type="text" class="form-control" value="{{ $radicacion->num_factura }}" disabled>
            </div>

            <div class="col-md-3 mb-3">
                <label class="form-label fw-bold">Número de Contrato</label>
                <input type="text" class="form-control" value="{{ $radicacion->num_contrato ?? 'N/A' }}" disabled>
            </div>

            <div class="col-md-3 mb-3">
                <label class="form-label fw-bold">Valor Total</label>
                <input type="text" class="form-control" value="${{ number_format($radicacion->valor, 2) }}" disabled>
            </div>

            <div class="col-md-3 mb-3">
                <label class="form-label fw-bold">Número de Pagos</label>
                <input type="text" class="form-control" value="{{ $radicacion->numero_pagos }}" disabled>
            </div>
        </div>

        <div class="row mx-4">
            <div class="col-md-3 mb-3">
                <label class="form-label fw-bold">Fecha de Radicación</label>
                <input type="text" class="form-control" value="{{ $radicacion->fecha_radicacion->format('d/m/Y') }}" disabled>
            </div>

            <div class="col-md-3 mb-3">
                <label class="form-label fw-bold">Fecha de Vencimiento</label>
                <input type="text" class="form-control" value="{{ $radicacion->fecha_vencimiento->format('d/m/Y') }}" disabled>
            </div>

            <div class="col-md-3 mb-3">
                <label class="form-label fw-bold">Visto Bueno</label>
                <input type="text" class="form-control" value="{{ $radicacion->necesita_visto_bueno ? 'Sí' : 'No' }}" disabled>
            </div>

            <div class="col-md-3 mb-3">
                <label class="form-label fw-bold">PDF</label>
                @if($radicacion->pdf)
                    <a href="{{ Storage::disk('public')->url($radicacion->pdf) }}" target="_blank" class="btn btn-sm btn-outline-primary w-100">
                        <i class="fas fa-file-pdf"></i> Ver PDF
                    </a>
                @else
                    <a href="{{ route('radfact.radicaciones.edit_adjunto', $radicacion) }}" class="btn btn-sm btn-warning w-100">
                        <i class="fas fa-upload"></i> Cargar Adjunto
                    </a>
                @endif
            </div>
        </div>

        <div class="row mx-4 pb-4">
            <div class="col-md-6 mb-3">
                <label class="form-label fw-bold">Descripción</label>
                <textarea class="form-control" rows="3" disabled>{{ $radicacion->descripcion }}</textarea>
            </div>

            <div class="col-md-6 mb-3">
                <label class="form-label fw-bold">Observaciones</label>
                <textarea class="form-control" rows="3" disabled>{{ $radicacion->observacion ?? 'Sin observaciones' }}</textarea>
            </div>
        </div>
    </div>

    {{-- Información del Proveedor --}}
    <div class="p-0 border shadow rounded sidebar-dark-primary mb-3">
        <div class="border rounded-top d-flex justify-content-between align-items-center px-4 bg-primary-subtle">
            <span class="text-left text-light fs-5 fw-medium">Datos del proveedor</span>
        </div>

        <div class="row pt-4 mx-4 pb-4">
            <div class="col-md-3 mb-3">
                <label class="form-label fw-bold">Tipo de Documento</label>
                <input type="text" class="form-control" value="{{ $radicacion->proveedor->tipo_documento }}" disabled>
            </div>

            <div class="col-md-3 mb-3">
                <label class="form-label fw-bold">Documento/NIT</label>
                <input type="text" class="form-control" value="{{ $radicacion->proveedor->documento }}" disabled>
            </div>

            <div class="col-md-6 mb-3">
                <label class="form-label fw-bold">Nombre / Razón Social</label>
                <input type="text" class="form-control" value="{{ $radicacion->proveedor->nombre_completo }}" disabled>
            </div>
        </div>
    </div>

    {{-- Distribución por Áreas --}}
    <div class="p-0 border shadow rounded sidebar-dark-primary mb-3">
        <div class="border rounded-top d-flex justify-content-between align-items-center px-4 bg-primary-subtle">
            <span class="text-left text-light fs-5 fw-medium">Distribución por áreas</span>
            <span class="badge bg-light text-dark">
                {{ $radicacion->distribucionesActivas->count() }} área(s)
            </span>
        </div>

        <div class="px-4 pt-4 pb-4">
            @if($radicacion->distribucionesActivas->count() > 0)
                <div class="table-responsive">
                    <table class="table table-sm table-hover">
                        <thead class="table-dark">
                            <tr>
                                <th>Área</th>
                                <th class="text-center">Porcentaje</th>
                                <th class="text-end">Valor Asignado</th>
                                <th class="text-center">Estado Aprobación</th>
                                <th class="text-center">Fecha Respuesta</th>
                                <th>Observación</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($radicacion->distribucionesActivas as $distribucion)
                                @php
                                    $aprobacion = $distribucion->aprobacion;
                                    $estadoBadge = match($aprobacion?->estado ?? 'PENDIENTE') {
                                        'PENDIENTE' => 'bg-secondary',
                                        'APROBADO' => 'bg-success',
                                        'RECHAZADO' => 'bg-danger',
                                        default => 'bg-secondary',
                                    };
                                @endphp
                                <tr>
                                    <td>
                                        <strong>{{ $distribucion->area->area }}</strong>
                                        @if($distribucion->area->responsable)
                                            <br><small class="text-muted">{{ $distribucion->area->responsable }}</small>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        <span class="badge bg-info">{{ number_format($distribucion->porcentaje, 2) }}%</span>
                                    </td>
                                    <td class="text-end">
                                        <strong>${{ number_format($distribucion->valor_calculado, 2) }}</strong>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge {{ $estadoBadge }}">
                                            {{ $aprobacion?->estado ?? 'PENDIENTE' }}
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        @if($aprobacion && $aprobacion->fecha_respuesta)
                                            {{ $aprobacion->fecha_respuesta->format('d/m/Y H:i') }}
                                            <br><small class="text-muted">{{ $aprobacion->usuario?->Usuario ?? 'N/A' }}</small>
                                        @else
                                            <span class="text-muted">Pendiente</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($aprobacion && $aprobacion->observacion)
                                            <small>{{ $aprobacion->observacion }}</small>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot class="table-light">
                            <tr>
                                <th>TOTAL</th>
                                <th class="text-center">
                                    <span class="badge bg-primary">
                                        {{ number_format($radicacion->distribucionesActivas->sum('porcentaje'), 2) }}%
                                    </span>
                                </th>
                                <th class="text-end">
                                    <strong>${{ number_format($radicacion->distribucionesActivas->sum('valor_calculado'), 2) }}</strong>
                                </th>
                                <th colspan="3"></th>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            @else
                <div class="alert alert-warning">
                    <i class="fas fa-exclamation-triangle"></i> No hay distribuciones activas para esta radicación.
                </div>
            @endif
        </div>
    </div>

    {{-- Historial de Distribuciones (si hay inactivas) --}}
    @if($radicacion->distribucionesHistorial->count() > 0)
        <div class="p-0 border shadow rounded sidebar-dark-primary mb-3">
            <div class="border rounded-top d-flex justify-content-between align-items-center px-4 bg-secondary-subtle">
                <span class="text-left text-dark fs-6 fw-medium">Historial de distribuciones anteriores</span>
                <span class="badge bg-dark">
                    {{ $radicacion->distribucionesHistorial->count() }} versión(es) anterior(es)
                </span>
            </div>

            <div class="px-4 pt-4 pb-4">
                <div class="table-responsive">
                    <table class="table table-sm">
                        <thead class="table-secondary">
                            <tr>
                                <th>Área</th>
                                <th class="text-center">Porcentaje</th>
                                <th class="text-end">Valor</th>
                                <th class="text-center">Estado</th>
                                <th>Observación</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($radicacion->distribucionesHistorial as $distribucion)
                                <tr class="text-muted">
                                    <td>{{ $distribucion->area->area }}</td>
                                    <td class="text-center">{{ number_format($distribucion->porcentaje, 2) }}%</td>
                                    <td class="text-end">${{ number_format($distribucion->valor_calculado, 2) }}</td>
                                    <td class="text-center">
                                        <span class="badge bg-secondary">{{ $distribucion->aprobacion?->estado ?? 'N/A' }}</span>
                                    </td>
                                    <td>
                                        <small>{{ $distribucion->aprobacion?->observacion ?? '-' }}</small>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    @endif

    {{-- Información de Registro --}}
    <div class="p-0 border shadow rounded sidebar-dark-primary mb-3">
        <div class="border rounded-top d-flex justify-content-between align-items-center px-4 bg-primary-subtle">
            <span class="text-left text-light fs-5 fw-medium">Información de registro</span>
        </div>

        <div class="row pt-4 mx-4 pb-4">
            <div class="col-md-4 mb-3">
                <label class="form-label fw-bold">Radicado por</label>
                <input type="text" class="form-control" value="{{ $radicacion->usuario->Usuario ?? 'N/A' }}" disabled>
            </div>
            <div class="col-md-4 mb-3">
                <label class="form-label fw-bold">Fecha de creación</label>
                <input type="text" class="form-control" value="{{ $radicacion->created_at->format('d/m/Y H:i') }}" disabled>
            </div>
            <div class="col-md-4 mb-3">
                <label class="form-label fw-bold">Última actualización</label>
                <input type="text" class="form-control" value="{{ $radicacion->updated_at->format('d/m/Y H:i') }}" disabled>
            </div>
        </div>
    </div>

    {{-- Botones de Acción --}}
    <div class="mb-4">
        <a href="{{ route('radfact.radicaciones.index') }}" class="btn btn-dark">
            <i class="fas fa-arrow-left"></i> Volver al Listado
        </a>

        @if($radicacion->algunaDistribucionRechazada() && $radicacion->estado === 'RECHAZADO')
            <a href="{{ route('radfact.radicaciones.edit_distribuciones', $radicacion) }}" class="btn btn-warning">
                <i class="fas fa-edit"></i> Ajustar Distribuciones
            </a>
        @endif

        @if($radicacion->pdf)
            <a href="{{ Storage::disk('public')->url($radicacion->pdf) }}" target="_blank" class="btn btn-outline-primary">
                <i class="fas fa-file-pdf"></i> Ver PDF Completo
            </a>
        @else
            <a href="{{ route('radfact.radicaciones.edit_adjunto', $radicacion) }}" class="btn btn-warning">
                <i class="fas fa-upload"></i> Cargar PDF
            </a>
        @endif
    </div>
</div>
@endsection
