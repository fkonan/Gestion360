@extends('layouts.dashboard')

@section('content')
<div class="container-fluid">
    {{-- Breadcrumb --}}
    <div class="row mb-3">
        <div class="col-12">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('home') }}">Inicio</a></li>
                    <li class="breadcrumb-item active">Aprobación Compras</li>
                </ol>
            </nav>
        </div>
    </div>

    {{-- Alert --}}
    <x-alert />

    {{-- Header --}}
    <div class="row mb-3">
        <div class="col-12">
            <h3><i class="fas fa-shopping-cart"></i> Aprobación de Compras</h3>
            <p class="text-muted">Radicaciones pendientes de aprobación final para pago</p>
        </div>
    </div>

    {{-- Estadísticas --}}
    <div class="row mb-4">
        <div class="col-md-4 mb-3">
            <div class="card bg-primary text-white shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="card-title mb-0">Pendientes</h6>
                            <h3 class="mb-0">{{ $radicaciones->total() }}</h3>
                        </div>
                        <i class="fas fa-file-invoice-dollar fa-3x opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Tabla de Radicaciones --}}
    <div class="card shadow-sm">
        <div class="card-header">
            <h6 class="card-title mb-0">
                Radicaciones Pendientes ({{ $radicaciones->total() }} registros)
            </h6>
        </div>
        <div class="card-body p-0">
            @if($radicaciones->count() > 0)
            <div class="table-responsive">
                <table class="table table-hover table-striped mb-0">
                    <thead class="table-dark">
                        <tr>
                            <th>N° Radicado</th>
                            <th>Proveedor</th>
                            <th>N° Factura</th>
                            <th>Fecha Envío</th>
                            <th>Fecha Vencimiento</th>
                            <th class="text-end">Valor</th>
                            <th class="text-center">Distribuciones</th>
                            <th class="text-center">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($radicaciones as $radicacion)
                        <tr>
                            <td>
                                <strong>#{{ str_pad($radicacion->id, 6, '0', STR_PAD_LEFT) }}</strong>
                            </td>
                            <td>
                                {{ $radicacion->proveedor->razon_social ?? 'N/A' }}<br>
                                <small class="text-muted">{{ $radicacion->proveedor->documento ?? '' }}</small>
                            </td>
                            <td>
                                {{ $radicacion->num_factura }}
                                @if($radicacion->num_contrato)
                                    <br><small class="text-muted">Contrato: {{ $radicacion->num_contrato }}</small>
                                @endif
                            </td>
                            <td>
                                {{ \Carbon\Carbon::parse($radicacion->fecha_envio_compras)->format('d/m/Y') }}<br>
                                <small class="text-muted">
                                    {{ \Carbon\Carbon::parse($radicacion->fecha_envio_compras)->diffForHumans() }}
                                </small>
                            </td>
                            <td>
                                {{ \Carbon\Carbon::parse($radicacion->fecha_vencimiento)->format('d/m/Y') }}
                                @if(\Carbon\Carbon::parse($radicacion->fecha_vencimiento)->isPast())
                                    <br><span class="badge bg-danger">Vencida</span>
                                @elseif(\Carbon\Carbon::parse($radicacion->fecha_vencimiento)->diffInDays() <= 5)
                                    <br><span class="badge bg-warning text-dark">Por vencer</span>
                                @endif
                            </td>
                            <td class="text-end">
                                <strong>${{ number_format($radicacion->valor, 2) }}</strong>
                                @if($radicacion->numero_pagos > 1)
                                    <br><small class="text-muted">{{ $radicacion->numero_pagos }} pagos</small>
                                @endif
                            </td>
                            <td class="text-center">
                                <span class="badge bg-success">
                                    {{ $radicacion->distribucionesActivas->count() }} áreas
                                </span>
                            </td>
                            <td class="text-center">
                                <div class="btn-group btn-group-sm" role="group">
                                    <a href="{{ route('radfact.compras.show', $radicacion) }}"
                                       class="btn btn-outline-primary"
                                       title="Ver detalle y aprobar">
                                        <i class="fas fa-eye"></i> Ver
                                    </a>

                                    @if($radicacion->pdf)
                                        <a href="{{ Storage::url($radicacion->pdf) }}"
                                           target="_blank"
                                           class="btn btn-outline-danger"
                                           title="Ver PDF">
                                            <i class="fas fa-file-pdf"></i>
                                        </a>
                                    @endif
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @else
            <div class="text-center py-5">
                <i class="fas fa-check-circle fa-4x text-success mb-3"></i>
                <h5 class="text-muted">No hay radicaciones pendientes</h5>
                <p class="text-muted">Todas las radicaciones han sido procesadas</p>
            </div>
            @endif
        </div>

        @if($radicaciones->hasPages())
        <div class="card-footer">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    Mostrando {{ $radicaciones->firstItem() }} a {{ $radicaciones->lastItem() }}
                    de {{ $radicaciones->total() }} registros
                </div>
                <div>
                    {{ $radicaciones->links() }}
                </div>
            </div>
        </div>
        @endif
    </div>
</div>
@endsection

@push('styles')
<style>
.opacity-50 {
    opacity: 0.5;
}

.table tbody tr:hover {
    cursor: pointer;
}
</style>
@endpush

@push('scripts')
<script>
$(document).ready(function() {
    // Click en fila para ver detalle
    $('.table tbody tr').click(function(e) {
        if ($(e.target).closest('.btn-group').length === 0) {
            const url = $(this).find('a[title="Ver detalle y aprobar"]').attr('href');
            if (url) {
                window.location.href = url;
            }
        }
    });
});
</script>
@endpush
