@extends('layouts.dashboard')

@section('title', 'Lista Restrictiva')

@section('breadcrumb')
   <x-breadcrumb :items="[
       ['name' => 'Inicio', 'url' => route('home')],
       ['name' => 'Sarlaft', 'url' => '#'],
       ['name' => 'Lista Restrictiva'],
   ]" />
   <br>
@endsection

@section('content')
   <div class="card shadow-sm">
      <x-sectionHeader titulo="Listas restrictivas" rutaVolver="{{ route('home') }}" btnVolver=false />
      <div class="card-header d-flex justify-content-between align-items-center">
         <a href="{{ route('sarlaft.lista-negra.create') }}" class="btn btn-sm btn-success">
            Nuevo registro
         </a>
      </div>
      <div class="card-body p-0">
         <div class="table-responsive">
            <table class="table table-hover mb-0">
               <thead class="table-light">
                  <tr>
                     <th>ID</th>
                     <th>Tipo</th>
                     <th>Documento</th>
                     <th>Nombres</th>
                     <th>Estado</th>
                     <th>Creado por</th>
                     <th>Fecha</th>
                     <th></th>
                  </tr>
               </thead>
               <tbody>
                  @forelse($registros as $registro)
                     <tr>
                        <td>{{ $registro->id }}</td>
                        <td>{{ ucfirst($registro->tipo_entidad) }}</td>
                        <td>{{ $registro->tipo_documento }} {{ $registro->numero_documento }}</td>
                        <td>{{ $registro->nombres }}</td>
                        <td>
                           <span class="badge bg-{{ $registro->estado === 'activo' ? 'success' : 'secondary' }}">
                              {{ ucfirst($registro->estado) }}
                           </span>
                        </td>
                        <td>
                           {{ trim(($registro->creadoPor->persona?->PerNombres ?? '') . ' ' . ($registro->creadoPor->persona?->PerApellidos ?? '')) ?: '-' }}
                        </td>
                        <td>{{ $registro->created_at->format('d/m/Y') }}</td>
                        <td>
                           <div class="btn-group btn-group-sm gap-2">
                              <a href="{{ route('sarlaft.lista-negra.show', $registro) }}" class="btn btn-dark"><i
                                    class="fas fa-eye"></i></a>
                              <a href="{{ route('sarlaft.lista-negra.edit', $registro) }}" class="btn btn-success"><i
                                    class="fas fa-edit"></i></a>
                           </div>
                        </td>
                     </tr>
                  @empty
                     <tr>
                        <td colspan="8" class="text-center text-muted py-4">No hay registros en la lista restrictiva.</td>
                     </tr>
                  @endforelse
               </tbody>
            </table>
         </div>
      </div>
      @if ($registros->hasPages())
         <div class="card-footer">
            {{ $registros->links() }}
         </div>
      @endif
   </div>
@endsection
@pushOnce('script')
@vite(['resources/js/cargarModal.js'])
@endpushOnce
