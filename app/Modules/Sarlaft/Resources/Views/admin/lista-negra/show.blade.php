@extends('layouts.dashboard')

@section('title', 'Lista Negra - ' . $listaNegra->nombres)

@section('actions')
<div class="btn-group btn-group-sm">
    <a href="{{ route('sarlaft.lista-negra.index') }}" class="btn btn-dark"><i class="fas fa-arrow-left"></i> Volver</a>
    <a href="{{ route('sarlaft.lista-negra.edit', $listaNegra) }}" class="btn btn-success"><i class="fas fa-edit"></i> Editar</a>
</div>
@endsection

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="card shadow-sm">
            <div class="card-body">
                <div class="row mb-3">
                    <div class="col-md-4">
                        <strong>Tipo Entidad:</strong><br>
                        <span class="badge bg-secondary">{{ ucfirst($listaNegra->tipo_entidad) }}</span>
                    </div>
                    <div class="col-md-4">
                        <strong>Documento:</strong><br>
                        {{ $listaNegra->tipo_documento }} {{ $listaNegra->numero_documento }}
                    </div>
                    <div class="col-md-4">
                        <strong>Estado:</strong><br>
                        <span class="badge bg-{{ $listaNegra->estado === 'activo' ? 'danger' : 'secondary' }}">
                            {{ ucfirst($listaNegra->estado) }}
                        </span>
                    </div>
                </div>
                <hr>
                <p><strong>Nombres:</strong> {{ $listaNegra->nombres }}</p>
                <p><strong>Motivo:</strong></p>
                <p class="bg-light p-3 rounded">{{ $listaNegra->motivo }}</p>
                <hr>
                <div class="text-muted small">
                    Creado por <strong>{{ trim(($listaNegra->creadoPor->persona?->PerNombres ?? '') . ' ' . ($listaNegra->creadoPor->persona?->PerApellidos ?? '')) ?: '-' }}</strong>
                    el {{ $listaNegra->created_at->format('d/m/Y H:i') }}
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
