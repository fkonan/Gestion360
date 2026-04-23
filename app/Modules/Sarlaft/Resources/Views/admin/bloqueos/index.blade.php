@extends('layouts.dashboard')

@section('title', 'Listado de Bloqueos')

@section('breadcrumb')
   <x-breadcrumb :items="[
       ['name' => 'Inicio', 'url' => route('home')],
       ['name' => 'Sarlaft', 'url' => '#'],
       ['name' => 'Listado de Bloqueos'],
   ]" />
   <br>
@endsection
@section('content')
   <div class="card shadow-sm">
      <x-sectionHeader titulo="Listado de Bloqueos" rutaVolver="{{ route('home') }}" btnVolver=false />
      <div class="card-header d-flex justify-content-between align-items-center">
         <a href="{{ route('sarlaft.bloqueos.create') }}" class="btn btn-sm btn-success">
            Nuevo Registro
         </a>
      </div>
      <div class="card-body p-0">
         <div class="table-responsive">
            <table class="table table-hover mb-0">
               <thead class="table-light">
                  <tr>
                     <th>ID</th>
                     <th>Documento</th>
                     <th>Nombre</th>
                     <th>Tipo</th>
                     <th>Estado</th>
                     <th>Creado por</th>
                     <th>Fecha</th>
                     <th></th>
                  </tr>
               </thead>
               <tbody>
                  @forelse($bloqueos as $bloqueo)
                     <tr>
                        <td>{{ $bloqueo->id }}</td>
                        <td>{{ $bloqueo->tipo_documento }} {{ $bloqueo->numero_documento }}</td>
                        <td>{{ $bloqueo->nombre ?? '-' }}</td>
                        <td>
                            {{ ucfirst($bloqueo->tipo_bloqueo) }}
                        </td>
                        <td>
                           <span class="badge bg-{{ $bloqueo->estado === 'bloqueado' ? 'danger' : 'success' }}">
                              {{ ucfirst($bloqueo->estado) }}
                           </span>
                        </td>
                        <td>
                           {{ trim(($bloqueo->creadoPor->persona?->PerNombres ?? '') . ' ' . ($bloqueo->creadoPor->persona?->PerApellidos ?? '')) ?: '-' }}
                        </td>
                        <td>{{ $bloqueo->created_at->format('d/m/Y H:i') }}</td>
                        <td>
                           <a href="{{ route('sarlaft.bloqueos.show', $bloqueo) }}" class="btn btn-sm btn-dark">
                              <i class="fas fa-eye"></i>
                           </a>
                        </td>
                     </tr>
                  @empty
                     <tr>
                        <td colspan="8" class="text-center text-muted py-4">No hay bloqueos registrados.</td>
                     </tr>
                  @endforelse
               </tbody>
            </table>
         </div>
      </div>
      @if ($bloqueos->hasPages())
         <div class="card-footer">
            {{ $bloqueos->links() }}
         </div>
      @endif
   </div>
@endsection
@pushOnce('script')
@vite(['resources/js/cargarModal.js'])
@endpushOnce
