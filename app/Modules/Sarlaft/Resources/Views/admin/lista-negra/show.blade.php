@extends('layouts.dashboard')

@section('title', 'Ver Registro Lista Restrictiva')

@section('breadcrumb')
<x-breadcrumb :items="[
        ['name' => 'Inicio', 'url' => route('home')],
        ['name' => 'Sarlaft', 'url' =>'#'],
        ['name' => 'Lista Restrictiva', 'url' => route('sarlaft.lista-negra.index')],
        ['name' => 'Ver Registro']
    ]" />
<br>
@endsection
@section('content')
   <div class="card shadow-sm">
      <x-sectionHeader titulo="Listas Restrictivas - Detalle" rutaVolver="{{ route('sarlaft.lista-negra.index') }}" btnVolver=false />
      <div class="card-header d-flex align-items-center gap-2">
         <a href="{{ route('sarlaft.lista-negra.index') }}" class="btn btn-sm btn-secondary">
            <i class="fas fa-arrow-left"></i> Regresar
         </a>
         <a href="{{ route('sarlaft.lista-negra.edit', $listaNegra) }}" class="btn btn-sm btn-primary">
            <i class="fas fa-edit"></i> Editar
         </a>
      </div>
      <div class="card-body">
         <div class="row mb-3">
            <div class="col-md-4">
               <strong>Tipo Entidad:</strong><br>
               {{ ucfirst($listaNegra->tipo_entidad) }}
            </div>
            <div class="col-md-4">
               <strong>Documento:</strong><br>
               {{ $listaNegra->tipo_documento }} {{ $listaNegra->numero_documento }}
            </div>
            <div class="col-md-4">
               <strong>Estado:</strong><br>
               <span class="badge bg-{{ $listaNegra->estado === 'activo' ? 'success' : 'secondary' }}">
                  {{ ucfirst($listaNegra->estado) }}
               </span>
            </div>
         </div>
         <hr>
         <p><strong>Nombres:</strong> {{ $listaNegra->nombres }}</p>
         <p><strong>Motivo:</strong></p>
         <p class="bg-light p-3 rounded">{{ $listaNegra->motivo }}</p>

         <p><strong>Evidencia de inclusion:</strong></p>
         @if(is_array($listaNegra->evidencia_inclusion))
         <a href="{{ route('sarlaft.lista-negra.evidencias.download', [$listaNegra, 'inclusion']) }}" class="btn btn-sm btn-outline-dark">
            <i class="fas fa-download"></i> Descargar evidencia de inclusion
         </a>
         @else
         <p class="text-muted">No hay evidencia registrada.</p>
         @endif

         @if($listaNegra->estado === 'inactivo')
         <hr>
         <p><strong>Motivo de retiro:</strong></p>
         <p class="bg-light p-3 rounded">{{ $listaNegra->motivo_retiro ?? 'N/A' }}</p>

         <p><strong>Evidencia de retiro:</strong></p>
         @if(is_array($listaNegra->evidencia_retiro))
         <a href="{{ route('sarlaft.lista-negra.evidencias.download', [$listaNegra, 'retiro']) }}" class="btn btn-sm btn-outline-dark">
            <i class="fas fa-download"></i> Descargar evidencia de retiro
         </a>
         @else
         <p class="text-muted">No hay evidencia de retiro registrada.</p>
         @endif
         @endif

         <hr>
         <div class="text-muted small">
            Creado por <strong>{{ trim(($listaNegra->creadoPor->persona?->PerNombres ?? '') . ' ' . ($listaNegra->creadoPor->persona?->PerApellidos ?? '')) ?: '-' }}</strong>
            el {{ $listaNegra->created_at->format('d/m/Y H:i') }}

            @if($listaNegra->retiradoPor)
                <br>
                Retirado por <strong>{{ trim(($listaNegra->retiradoPor->persona?->PerNombres ?? '') . ' ' . ($listaNegra->retiradoPor->persona?->PerApellidos ?? '')) ?: '-' }}</strong>
                el {{ $listaNegra->retirado_at?->format('d/m/Y H:i') ?? 'N/A' }}
            @endif
         </div>
      </div>
   </div>
@endsection
