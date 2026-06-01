@extends('layouts.dashboard')

@section('title', 'Sincronizacion de Listas')

@section('breadcrumb')
   <x-breadcrumb :items="[
       ['name' => 'Inicio', 'url' => route('home')],
       ['name' => 'Sarlaft', 'url' => '#'],
       ['name' => 'Sincronizacion de Listas'],
   ]" />
   <br>
@endsection
@section('content')
   <div class="container-fluid p-0 border shadow rounded sidebar-dark-primary" style="min-height:150px;">
      <x-sectionHeader titulo="Listas vinculantes registradas" rutaVolver="{{ route('home') }}" btnVolver=false />
      <div class="col mb-2">
         <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2 m-3">
            @hasanyrole('SUPER-ADMIN|ADMIN')
            <div class="d-flex align-items-center gap-2 flex-wrap">
               <form action="{{ route('sarlaft.sincronizacion.listas.sincronizar-ahora') }}" method="POST"
                  onsubmit="this.querySelector('[data-sync-text]').classList.add('d-none'); this.querySelector('[data-sync-loader]').classList.remove('d-none'); this.querySelector('button[type=submit]').setAttribute('disabled','disabled');">
                  @csrf
                  <button type="submit" class="btn btn-sm btn-success" @disabled(!$puedeSincronizarListasAhora)
                     title="{{ $puedeSincronizarListasAhora ? 'Sincronizar listas vinculantes ahora' : 'Disponible en ' . $proximaSincronizacionListas }}">
                     <span data-sync-text>
                        <i class="fas fa-sync-alt"></i> Sincronizar listas vinculantes
                     </span>
                     <span data-sync-loader class="d-none">
                        <i class="fas fa-spinner fa-spin"></i> Encolando...
                     </span>
                  </button>
               </form>

               <form action="{{ route('sarlaft.sincronizacion.intentos.sincronizar-ahora') }}" method="POST"
                  onsubmit="this.querySelector('[data-sync-text]').classList.add('d-none'); this.querySelector('[data-sync-loader]').classList.remove('d-none'); this.querySelector('button[type=submit]').setAttribute('disabled','disabled');">
                  @csrf
                  <button type="submit" class="btn btn-sm btn-primary" @disabled(!$puedeSincronizarIntentosAhora)
                     title="{{ $puedeSincronizarIntentosAhora ? 'Sincronizar intentos de operacion ahora' : 'Disponible en ' . $proximaSincronizacionIntentos }}">
                     <span data-sync-text>
                        <i class="fas fa-cloud-download-alt"></i> Sincronizar intentos de operacion
                     </span>
                     <span data-sync-loader class="d-none">
                        <i class="fas fa-spinner fa-spin"></i> Procesando...
                     </span>
                  </button>
               </form>

               {{-- <form action="{{ route('sarlaft.sincronizacion.listas.sincronizar-config') }}" method="POST">
                  @csrf
                  <button type="submit" class="btn btn-sm btn-dark">
                     <i class="bi bi-arrow-repeat"></i> Cargar desde config/listas.php
                  </button>
               </form> --}}
            </div>
            @endhasanyrole
         </div>

         <div class="card-body">
            @if (!$puedeSincronizarListasAhora)
               <div class="alert alert-warning mb-2 rounded-0 border-0">
                  <i class="fas fa-clock"></i> Sincronizacion de listas en cooldown. Disponible en {{ $proximaSincronizacionListas }}.
               </div>
            @endif
            @if (!$puedeSincronizarIntentosAhora)
               <div class="alert alert-info mb-2 rounded-0 border-0">
                  <i class="fas fa-clock"></i> Sincronizacion de intentos en cooldown. Disponible en {{ $proximaSincronizacionIntentos }}.
               </div>
            @endif
            <div class="table-responsive">
               <table class="table table-hover mb-0">
                  <thead class="table-light">
                     <tr>
                        <th>Nombre</th>
                        <th>Tipo</th>
                        <th>Frecuencia</th>
                        <th>URL</th>
                        <th>Estado</th>
                        <th>Ult. Sync</th>
                     </tr>
                  </thead>
                  <tbody>
                     @forelse($listas as $lista)
                        <tr>
                           <td>{{ $lista->nombre }}</td>
                           <td><span class="">{{ ucfirst($lista->tipo) }}</span></td>
                           <td>{{ $lista->frecuencia_sync ?? '-' }}</td>
                           <td class="small">
                              @if ($lista->url_fuente)
                                 <a href="{{ $lista->url_fuente }}" target="_blank" rel="noopener">
                                    {{ Str::limit($lista->url_fuente, 45) }}
                                 </a>
                              @else
                                 <span class="text-muted">-</span>
                              @endif
                           </td>
                           <td>
                              <span class="badge bg-{{ $lista->activa ? 'success' : 'secondary' }}">
                                 {{ $lista->activa ? 'Activa' : 'Inactiva' }}
                              </span>
                           </td>
                           <td>{{ $lista->ultima_sincronizacion?->format('d/m/Y H:i') ?? '-' }}</td>
                        </tr>
                     @empty
                        <tr>
                           <td colspan="6" class="text-center text-muted py-4">No hay listas vinculantes registradas.
                           </td>
                        </tr>
                     @endforelse
                  </tbody>
               </table>
            </div>
         </div>
      </div>

      <x-sectionHeader titulo="Historial de sincronizaciones" rutaVolver="{{ route('home') }}" btnVolver=false />
      <div class="col">
         <div class="table-responsive">
            <table class="table table-hover mb-0">
               <thead class="table-light">
                  <tr>
                     <th>ID</th>
                     <th>Lista</th>
                     <th>Estado</th>
                     <th>Procesados</th>
                     <th>Nuevos</th>
                     <th>Actualizados</th>
                     <th>Eliminados</th>
                     <th>Duracion</th>
                     <th>Fecha</th>
                     <th>Error</th>
                  </tr>
               </thead>
               <tbody>
                  @forelse($logs as $log)
                     <tr>
                        <td>{{ $log->id }}</td>
                        <td>{{ $log->lista->nombre ?? 'N/A' }}</td>
                        <td>@include('sarlaft::admin.partials.badge-estado-sync', ['estado' => $log->estado])</td>
                        <td>{{ number_format($log->registros_procesados) }}</td>
                        <td class="text-success">+{{ number_format($log->registros_nuevos) }}</td>
                        <td class="text-primary">{{ number_format($log->registros_actualizados) }}</td>
                        <td class="text-danger">
                           {{ $log->registros_eliminados ? '-' . number_format($log->registros_eliminados) : '0' }}</td>
                        <td>{{ $log->duracion_segundos ? $log->duracion_segundos . 's' : '-' }}</td>
                        <td>{{ $log->created_at?->format('d/m/Y H:i') }}</td>
                        <td>
                           @if ($log->error_mensaje)
                              <span class="text-danger small" title="{{ $log->error_mensaje }}">
                                 {{ Str::limit($log->error_mensaje, 50) }}
                              </span>
                           @else
                              <span class="text-muted">-</span>
                           @endif
                        </td>
                     </tr>
                  @empty
                     <tr>
                        <td colspan="10" class="text-center text-muted py-4">No hay logs de sincronizacion.</td>
                     </tr>
                  @endforelse
               </tbody>
            </table>
         </div>
      </div>
      @if ($logs->hasPages())
         <div class="card-footer">
            {{ $logs->links() }}
         </div>
      @endif
   </div>
@endsection
@pushOnce('script')
@vite(['resources/js/cargarModal.js'])
@endpushOnce
