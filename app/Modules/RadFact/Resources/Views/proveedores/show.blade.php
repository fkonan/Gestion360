@extends('layouts.dashboard')

@section('content')
<div class="container-fluid">
    {{-- Breadcrumb --}}
    <div class="row mb-3">
        <div class="col-12">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('home') }}">Inicio</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('radfact.proveedores.index') }}">Proveedores</a></li>
                    <li class="breadcrumb-item active">Detalle</li>
                </ol>
            </nav>
        </div>
    </div>

    {{-- Header con Acciones --}}
    <div class="row mb-3">
        <div class="col-md-8">
            <h3><i class="fas fa-truck"></i> Detalle del Proveedor</h3>
            <p class="text-muted">{{ $proveedor->razon_social }}</p>
        </div>
        <div class="col-md-4 text-end">
            <a href="{{ route('radfact.proveedores.edit', $proveedor) }}" class="btn btn-warning">
                <i class="fas fa-edit"></i> Editar
            </a>
            <a href="{{ route('radfact.proveedores.index') }}" class="btn btn-outline-dark">
                <i class="fas fa-arrow-left"></i> Volver
            </a>
        </div>
    </div>

    <div class="row">
        {{-- Información del Proveedor --}}
        <div class="col-md-6">
            <div class="card shadow-sm">
                <div class="card-header">
                    <h6 class="card-title mb-0">
                        <i class="fas fa-info-circle"></i> Información General
                    </h6>
                </div>
                <div class="card-body">
                    <table class="table table-sm">
                        <tbody>
                            <tr>
                                <th width="40%">Estado:</th>
                                <td>
                                    @if($proveedor->activo)
                                        <span class="badge bg-success">Activo</span>
                                    @else
                                        <span class="badge bg-danger">Inactivo</span>
                                    @endif
                                </td>
                            </tr>
                            <tr>
                                <th>Tipo Documento:</th>
                                <td><span class="badge bg-secondary">{{ $proveedor->tipo_documento }}</span></td>
                            </tr>
                            <tr>
                                <th>Documento:</th>
                                <td>
                                    <strong>{{ $proveedor->documento }}</strong>
                                    @if($proveedor->digito_verificacion)
                                        <small class="text-muted">-{{ $proveedor->digito_verificacion }}</small>
                                    @endif
                                </td>
                            </tr>
                            <tr>
                                <th>Razón Social:</th>
                                <td>{{ $proveedor->razon_social }}</td>
                            </tr>
                            <tr>
                                <th>Nombre Comercial:</th>
                                <td>{{ $proveedor->nombre_comercial ?? '-' }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- Información de Contacto --}}
            <div class="card shadow-sm mt-4">
                <div class="card-header">
                    <h6 class="card-title mb-0">
                        <i class="fas fa-address-book"></i> Información de Contacto
                    </h6>
                </div>
                <div class="card-body">
                    <table class="table table-sm">
                        <tbody>
                            <tr>
                                <th width="40%">Correo:</th>
                                <td>
                                    @if($proveedor->correo)
                                        <a href="mailto:{{ $proveedor->correo }}">{{ $proveedor->correo }}</a>
                                    @else
                                        <span class="text-muted">No registrado</span>
                                    @endif
                                </td>
                            </tr>
                            <tr>
                                <th>Teléfono:</th>
                                <td>{{ $proveedor->telefono ?? '-' }}</td>
                            </tr>
                            <tr>
                                <th>Celular:</th>
                                <td>{{ $proveedor->celular ?? '-' }}</td>
                            </tr>
                            <tr>
                                <th>Dirección:</th>
                                <td>{{ $proveedor->direccion ?? '-' }}</td>
                            </tr>
                            <tr>
                                <th>Ciudad:</th>
                                <td>{{ $proveedor->ciudad ?? '-' }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- Radicaciones Recientes --}}
        <div class="col-md-6">
            <div class="card shadow-sm">
                <div class="card-header">
                    <h6 class="card-title mb-0">
                        <i class="fas fa-file-invoice"></i> Radicaciones Recientes
                        @if($proveedor->radicaciones)
                            <span class="badge bg-primary">{{ $proveedor->radicaciones->count() }}</span>
                        @endif
                    </h6>
                </div>
                <div class="card-body p-0">
                    @if($proveedor->radicaciones && $proveedor->radicaciones->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-sm table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Radicado</th>
                                    <th>Factura</th>
                                    <th>Fecha</th>
                                    <th>Valor</th>
                                    <th>Estado</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($proveedor->radicaciones as $rad)
                                <tr onclick="window.location='{{ route('radfact.radicaciones.show', $rad) }}'"
                                    style="cursor: pointer;">
                                    <td><strong>#{{ str_pad($rad->id, 6, '0', STR_PAD_LEFT) }}</strong></td>
                                    <td>{{ $rad->num_factura }}</td>
                                    <td>
                                        <small>{{ \Carbon\Carbon::parse($rad->fecha_radicacion)->format('d/m/Y') }}</small>
                                    </td>
                                    <td>
                                        <small>${{ number_format($rad->valor, 0) }}</small>
                                    </td>
                                    <td>
                                        @switch($rad->estado)
                                            @case('RADICADO')
                                                <span class="badge bg-info">Radicado</span>
                                                @break
                                            @case('EN_APROBACION')
                                                <span class="badge bg-warning text-dark">En Aprobación</span>
                                                @break
                                            @case('RECHAZADO')
                                                <span class="badge bg-danger">Rechazado</span>
                                                @break
                                            @case('COMPLETADO')
                                                <span class="badge bg-success">Completado</span>
                                                @break
                                            @default
                                                <span class="badge bg-secondary">{{ $rad->estado }}</span>
                                        @endswitch
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    @else
                    <div class="text-center py-5">
                        <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                        <p class="text-muted">No hay radicaciones registradas para este proveedor</p>
                    </div>
                    @endif
                </div>
            </div>

            {{-- Información de Registro --}}
            <div class="card shadow-sm mt-4">
                <div class="card-header">
                    <h6 class="card-title mb-0">
                        <i class="fas fa-history"></i> Historial
                    </h6>
                </div>
                <div class="card-body">
                    <table class="table table-sm">
                        <tbody>
                            <tr>
                                <th width="40%">Creado por:</th>
                                <td>{{ $proveedor->usuario->Usuario ?? 'N/A' }}</td>
                            </tr>
                            <tr>
                                <th>Fecha creación:</th>
                                <td>{{ $proveedor->created_at->format('d/m/Y H:i') }}</td>
                            </tr>
                            <tr>
                                <th>Última actualización:</th>
                                <td>{{ $proveedor->updated_at->format('d/m/Y H:i') }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
