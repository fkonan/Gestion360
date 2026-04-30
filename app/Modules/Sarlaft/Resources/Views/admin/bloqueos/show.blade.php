@extends('layouts.dashboard')

@section('title', 'Detalle del Bloqueo')

@section('breadcrumb')
   <x-breadcrumb :items="[
       ['name' => 'Inicio', 'url' => route('home')],
       ['name' => 'Sarlaft', 'url' => '#'],
       ['name' => 'Lista de Bloqueos', 'url' => route('sarlaft.bloqueos.index')],
       ['name' => 'Detalle del Bloqueo'],
   ]" />
   <br>
@endsection
@section('content')
   <div class="card shadow-sm">
      <x-sectionHeader titulo="Bloqueos" rutaVolver="{{ route('sarlaft.bloqueos.index') }}" btnVolver=false />
      <div class="card-header d-flex justify-content-between align-items-center">
         <a href="{{ route('sarlaft.bloqueos.index') }}" class="btn btn-sm btn-success">
            <i class="fas fa-arrow-left"></i> Regresar
         </a>
      </div>
      <div class="card-body">
         <div class="row g-4">
            <div class="col-lg-8">
               <div class="card shadow-sm">
                  <div class="card-header">
                     <h6 class="mb-0">Detalle del Bloqueo</h6>
                  </div>
                  <div class="card-body">
                     <div class="row mb-3">
                        <div class="col-md-4">
                           <strong>Estado:</strong><br>
                           <span class="badge bg-{{ $bloqueo->estado === 'bloqueado' ? 'danger' : 'success' }} fs-6">
                              {{ ucfirst($bloqueo->estado) }}
                           </span>
                        </div>
                        <div class="col-md-4">
                           <strong>Tipo Bloqueo:</strong><br>
                           {{ ucfirst($bloqueo->tipo_bloqueo) }}
                        </div>
                        <div class="col-md-4">
                           <strong>Creado por:</strong><br>
                           {{ trim(($bloqueo->creadoPor->persona?->PerNombres ?? '') . ' ' . ($bloqueo->creadoPor->persona?->PerApellidos ?? '')) ?: '-' }}
                        </div>
                     </div>
                     <hr>
                     <div class="row mb-3">
                        <div class="col-md-4">
                           <strong>Documento:</strong><br>
                           {{ $bloqueo->tipo_documento }} {{ $bloqueo->numero_documento }}
                        </div>
                        <div class="col-md-4">
                           <strong>Nombre:</strong><br>
                           {{ $bloqueo->nombre ?? '-' }}
                        </div>
                        <div class="col-md-4">
                           <strong>Fecha:</strong><br>
                           {{ $bloqueo->created_at->format('d/m/Y H:i:s') }}
                        </div>
                     </div>
                     <hr>
                     <h6>Motivo del Bloqueo</h6>
                     <p class="bg-light p-3 rounded">{{ $bloqueo->motivo_bloqueo }}</p>

                     @if ($bloqueo->justificacion_desbloqueo)
                        <h6 class="text-success">Justificacion de Desbloqueo</h6>
                        <p class="bg-light p-3 rounded">{{ $bloqueo->justificacion_desbloqueo }}</p>
                     @endif

                     @if ($bloqueo->archivo_soporte && is_array($bloqueo->archivo_soporte))
                        <h6>Documento Soporte</h6>
                        <div class="d-flex align-items-center gap-2">
                           <i class="fas fa-paperclip text-muted"></i>
                           <span>{{ $bloqueo->archivo_soporte['original_name'] ?? 'Archivo adjunto' }}</span>
                           <a href="{{ route('sarlaft.bloqueos.archivo-soporte.download', $bloqueo) }}"
                              class="btn btn-sm btn-outline-secondary ms-2">
                              <i class="fas fa-download"></i> Descargar
                           </a>
                        </div>
                     @endif
                  </div>
               </div>
            </div>

            {{-- Desbloquear --}}
            <div class="col-lg-4">
               @if ($bloqueo->estado === 'bloqueado')
                  <div class="card shadow-sm">
                     <div class="card-header">
                        <h6 class="mb-0"><i class="fas fa-unlock"></i> Desbloquear</h6>
                     </div>
                     <div class="card-body">
                        <form action="{{ route('sarlaft.bloqueos.update', $bloqueo) }}" method="POST"
                           enctype="multipart/form-data">
                           @csrf
                           @method('PATCH')
                           <div class="mb-3">
                              <label for="justificacion_desbloqueo" class="form-label">Justificacion <span
                                    class="text-danger">*</span></label>
                              <textarea name="justificacion_desbloqueo" id="justificacion_desbloqueo" rows="4"
                                 class="form-control @error('justificacion_desbloqueo') is-invalid @enderror" required>{{ old('justificacion_desbloqueo') }}</textarea>
                              @error('justificacion_desbloqueo')
                                 <div class="invalid-feedback">{{ $message }}</div>
                              @enderror
                           </div>
                           <div class="mb-3">
                              <label for="archivo_soporte" class="form-label">Documento Soporte <span
                                    class="text-muted">(opcional)</span></label>
                              <input type="file" name="archivo_soporte" id="archivo_soporte"
                                 class="form-control @error('archivo_soporte') is-invalid @enderror"
                                 accept=".pdf,.jpg,.jpeg,.png,.doc,.docx">
                              <small class="text-muted">PDF, imagen o Word — máx. 10 MB.</small>
                              @error('archivo_soporte')
                                 <div class="invalid-feedback">{{ $message }}</div>
                              @enderror
                           </div>
                           <button type="submit" class="btn btn-success w-100"
                              onclick="return confirm('Esta seguro de levantar este bloqueo?')">
                              <i class="fas fa-unlock"></i> Confirmar Desbloqueo
                           </button>
                        </form>
                     </div>
                  </div>
               @endif
            </div>
         </div>
      </div>
   </div>
@endsection
