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
         <hr>
         <div class="text-muted small">
            Creado por <strong>{{ trim(($listaNegra->creadoPor->persona?->PerNombres ?? '') . ' ' . ($listaNegra->creadoPor->persona?->PerApellidos ?? '')) ?: '-' }}</strong>
            el {{ $listaNegra->created_at->format('d/m/Y H:i') }}
         </div>
      </div>
   </div>
@endsection
