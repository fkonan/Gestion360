@extends('layouts.dashboard')

@section('title', 'Simulaciones SARLAFT')

@section('content')
@if(session('resultado_simulacion'))
@php $resultado = session('resultado_simulacion'); @endphp
<div class="alert alert-{{ ($resultado['presta_servicio'] ?? false) ? 'success' : 'warning' }} alert-dismissible fade show" role="alert">
    <h6 class="mb-2"><i class="fas fa-shield-alt"></i> Resultado de Simulacion ({{ strtoupper($resultado['escenario'] ?? '') }})</h6>
    <div><strong>Consulta ID:</strong> {{ $resultado['consulta_id'] ?? '-' }}</div>
    <div><strong>Documento:</strong> {{ $resultado['documento'] ?? '-' }}</div>
    <div><strong>Nombre:</strong> {{ $resultado['nombre'] ?? '-' }}</div>
    <div><strong>Riesgo:</strong> {{ strtoupper((string) ($resultado['nivel_riesgo'] ?? 'ninguno')) }}</div>
    <div><strong>Coincidencias:</strong> {{ $resultado['total_coincidencias'] ?? 0 }}</div>
    <div><strong>Decision:</strong> {{ ($resultado['presta_servicio'] ?? false) ? 'Permitir operacion' : 'No permitir operacion' }}</div>
    <div><strong>Fecha:</strong> {{ $resultado['fecha'] ?? '-' }}</div>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif

<div class="card shadow-sm mb-4">
    <div class="card-header">
        <h6 class="mb-0"><i class="fas fa-vial"></i> Simulador Operaciones SARLAFT</h6>
    </div>
    <div class="card-body">
        <ul class="nav nav-tabs" id="simulacionTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="pasaje-tab" data-bs-toggle="tab" data-bs-target="#pasaje-tab-pane" type="button" role="tab" aria-controls="pasaje-tab-pane" aria-selected="true">Compra de Tiquete</button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="remesa-tab" data-bs-toggle="tab" data-bs-target="#remesa-tab-pane" type="button" role="tab" aria-controls="remesa-tab-pane" aria-selected="false">Elaboracion de Remesa</button>
            </li>
        </ul>

        <div class="tab-content pt-4" id="simulacionTabContent">
            <div class="tab-pane fade show active" id="pasaje-tab-pane" role="tabpanel" aria-labelledby="pasaje-tab" tabindex="0">
                <form action="{{ route('sarlaft.simulaciones.pasajes.store') }}" method="POST">
                    @csrf
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label" for="pasaje_ciudad_origen_id">Ciudad origen <span class="text-danger">*</span></label>
                            <select class="form-select @error('ciudad_origen_id') is-invalid @enderror" id="pasaje_ciudad_origen_id" name="ciudad_origen_id" required>
                                <option value="">Seleccione...</option>
                                @foreach($municipios as $municipio)
                                <option value="{{ $municipio->IdMunicipio }}" @selected(old('ciudad_origen_id') == $municipio->IdMunicipio)>{{ $municipio->MunNomMin }} - {{ $municipio->departamento?->DepNomMin }}</option>
                                @endforeach
                            </select>
                            @error('ciudad_origen_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="col-md-6">
                            <label class="form-label" for="pasaje_ciudad_destino_id">Ciudad destino <span class="text-danger">*</span></label>
                            <select class="form-select @error('ciudad_destino_id') is-invalid @enderror" id="pasaje_ciudad_destino_id" name="ciudad_destino_id" required>
                                <option value="">Seleccione...</option>
                                @foreach($municipios as $municipio)
                                <option value="{{ $municipio->IdMunicipio }}" @selected(old('ciudad_destino_id') == $municipio->IdMunicipio)>{{ $municipio->MunNomMin }} - {{ $municipio->departamento?->DepNomMin }}</option>
                                @endforeach
                            </select>
                            @error('ciudad_destino_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="col-md-4">
                            <label class="form-label" for="pasaje_fecha_viaje">Fecha de viaje <span class="text-danger">*</span></label>
                            <input type="date" class="form-control @error('fecha_viaje') is-invalid @enderror" id="pasaje_fecha_viaje" name="fecha_viaje" value="{{ old('fecha_viaje') }}" required>
                            @error('fecha_viaje') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="col-md-4">
                            <label class="form-label" for="pasaje_tipo_documento">Tipo documento <span class="text-danger">*</span></label>
                            <select class="form-select @error('tipo_documento') is-invalid @enderror" id="pasaje_tipo_documento" name="tipo_documento" required>
                                <option value="">Seleccione...</option>
                                @foreach($tiposDocumento as $tipo)
                                <option value="{{ $tipo->nomenclatura }}" @selected(old('tipo_documento') == $tipo->nomenclatura)>{{ $tipo->nomenclatura }} - {{ $tipo->nombre }}</option>
                                @endforeach
                            </select>
                            @error('tipo_documento') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="col-md-4">
                            <label class="form-label" for="pasaje_documento">Documento <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('documento') is-invalid @enderror" id="pasaje_documento" name="documento" value="{{ old('documento') }}" required>
                            @error('documento') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="col-md-6">
                            <label class="form-label" for="pasaje_nombres">Nombres <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('nombres') is-invalid @enderror" id="pasaje_nombres" name="nombres" value="{{ old('nombres') }}" required>
                            @error('nombres') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="col-md-6">
                            <label class="form-label" for="pasaje_apellidos">Apellidos <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('apellidos') is-invalid @enderror" id="pasaje_apellidos" name="apellidos" value="{{ old('apellidos') }}" required>
                            @error('apellidos') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="col-md-6">
                            <label class="form-label" for="pasaje_direccion">Direccion <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('direccion') is-invalid @enderror" id="pasaje_direccion" name="direccion" value="{{ old('direccion') }}" required>
                            @error('direccion') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="col-md-3">
                            <label class="form-label" for="pasaje_telefono">Telefono <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('telefono') is-invalid @enderror" id="pasaje_telefono" name="telefono" value="{{ old('telefono') }}" required>
                            @error('telefono') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="col-md-3">
                            <label class="form-label" for="pasaje_correo">Correo <span class="text-danger">*</span></label>
                            <input type="email" class="form-control @error('correo') is-invalid @enderror" id="pasaje_correo" name="correo" value="{{ old('correo') }}" required>
                            @error('correo') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                    </div>

                    <div class="mt-4 d-flex justify-content-end">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-check-circle"></i> Validar y Guardar Tiquete
                        </button>
                    </div>
                </form>
            </div>

            <div class="tab-pane fade" id="remesa-tab-pane" role="tabpanel" aria-labelledby="remesa-tab" tabindex="0">
                <form action="{{ route('sarlaft.simulaciones.remesas.store') }}" method="POST">
                    @csrf
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label" for="remesa_ciudad_origen_id">Ciudad origen <span class="text-danger">*</span></label>
                            <select class="form-select @error('ciudad_origen_id') is-invalid @enderror" id="remesa_ciudad_origen_id" name="ciudad_origen_id" required>
                                <option value="">Seleccione...</option>
                                @foreach($municipios as $municipio)
                                <option value="{{ $municipio->IdMunicipio }}">{{ $municipio->MunNomMin }} - {{ $municipio->departamento?->DepNomMin }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label" for="remesa_ciudad_destino_id">Ciudad destino <span class="text-danger">*</span></label>
                            <select class="form-select @error('ciudad_destino_id') is-invalid @enderror" id="remesa_ciudad_destino_id" name="ciudad_destino_id" required>
                                <option value="">Seleccione...</option>
                                @foreach($municipios as $municipio)
                                <option value="{{ $municipio->IdMunicipio }}">{{ $municipio->MunNomMin }} - {{ $municipio->departamento?->DepNomMin }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label" for="remesa_fecha_envio">Fecha envio <span class="text-danger">*</span></label>
                            <input type="date" class="form-control @error('fecha_envio') is-invalid @enderror" id="remesa_fecha_envio" name="fecha_envio" value="{{ old('fecha_envio') }}" required>
                            @error('fecha_envio') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="col-md-4">
                            <label class="form-label" for="remesa_tipo_documento">Tipo documento <span class="text-danger">*</span></label>
                            <select class="form-select @error('tipo_documento') is-invalid @enderror" id="remesa_tipo_documento" name="tipo_documento" required>
                                <option value="">Seleccione...</option>
                                @foreach($tiposDocumento as $tipo)
                                <option value="{{ $tipo->nomenclatura }}">{{ $tipo->nomenclatura }} - {{ $tipo->nombre }}</option>
                                @endforeach
                            </select>
                            @error('tipo_documento') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="col-md-4">
                            <label class="form-label" for="remesa_documento_remitente">Documento remitente <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('documento_remitente') is-invalid @enderror" id="remesa_documento_remitente" name="documento_remitente" value="{{ old('documento_remitente') }}" required>
                            @error('documento_remitente') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="col-md-6">
                            <label class="form-label" for="remesa_nombres_remitente">Nombres remitente <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('nombres_remitente') is-invalid @enderror" id="remesa_nombres_remitente" name="nombres_remitente" value="{{ old('nombres_remitente') }}" required>
                            @error('nombres_remitente') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="col-md-6">
                            <label class="form-label" for="remesa_apellidos_remitente">Apellidos remitente <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('apellidos_remitente') is-invalid @enderror" id="remesa_apellidos_remitente" name="apellidos_remitente" value="{{ old('apellidos_remitente') }}" required>
                            @error('apellidos_remitente') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="col-md-4">
                            <label class="form-label" for="remesa_telefono_remitente">Telefono remitente <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('telefono_remitente') is-invalid @enderror" id="remesa_telefono_remitente" name="telefono_remitente" value="{{ old('telefono_remitente') }}" required>
                            @error('telefono_remitente') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="col-md-4">
                            <label class="form-label" for="remesa_nombre_destinatario">Nombre destinatario <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('nombre_destinatario') is-invalid @enderror" id="remesa_nombre_destinatario" name="nombre_destinatario" value="{{ old('nombre_destinatario') }}" required>
                            @error('nombre_destinatario') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="col-md-4">
                            <label class="form-label" for="remesa_documento_destinatario">Documento destinatario <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('documento_destinatario') is-invalid @enderror" id="remesa_documento_destinatario" name="documento_destinatario" value="{{ old('documento_destinatario') }}" required>
                            @error('documento_destinatario') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="col-md-4">
                            <label class="form-label" for="remesa_monto">Monto <span class="text-danger">*</span></label>
                            <input type="number" step="0.01" min="0" class="form-control @error('monto') is-invalid @enderror" id="remesa_monto" name="monto" value="{{ old('monto') }}" required>
                            @error('monto') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="col-md-8">
                            <label class="form-label" for="remesa_concepto">Concepto <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('concepto') is-invalid @enderror" id="remesa_concepto" name="concepto" value="{{ old('concepto') }}" required>
                            @error('concepto') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                    </div>

                    <div class="mt-4 d-flex justify-content-end">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-check-circle"></i> Validar y Guardar Remesa
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
