@extends('layouts.dashboard')

@section('title', 'Sincronizacion de Listas')

@section('content')
<div class="row g-4 mb-4">
    <div class="col-lg-4">
        <div class="card shadow-sm">
            <div class="card-header">
                <h6 class="mb-0"><i class="bi bi-plus-lg"></i> Nueva Lista Vinculante</h6>
            </div>
            <div class="card-body">
                <form action="{{ route('sarlaft.sincronizacion.listas.store') }}" method="POST">
                    @csrf

                    <div class="mb-3">
                        <label for="nombre" class="form-label">Nombre <span class="text-danger">*</span></label>
                        <input type="text" name="nombre" id="nombre" class="form-control @error('nombre') is-invalid @enderror" value="{{ old('nombre') }}" required>
                        @error('nombre') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="mb-3">
                        <label for="tipo" class="form-label">Tipo <span class="text-danger">*</span></label>
                        <select name="tipo" id="tipo" class="form-select @error('tipo') is-invalid @enderror" required>
                            <option value="vinculante" @selected(old('tipo') === 'vinculante')>Vinculante</option>
                            <option value="interna" @selected(old('tipo') === 'interna')>Interna</option>
                        </select>
                        @error('tipo') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="mb-3">
                        <label for="url_fuente" class="form-label">URL Fuente</label>
                        <input type="url" name="url_fuente" id="url_fuente" class="form-control @error('url_fuente') is-invalid @enderror" value="{{ old('url_fuente') }}">
                        @error('url_fuente') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="mb-3">
                        <label for="frecuencia_sync" class="form-label">Frecuencia Sync</label>
                        <input type="text" name="frecuencia_sync" id="frecuencia_sync" class="form-control @error('frecuencia_sync') is-invalid @enderror" value="{{ old('frecuencia_sync', 'diaria') }}">
                        @error('frecuencia_sync') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" value="1" id="activa" name="activa" @checked(old('activa', true))>
                        <label class="form-check-label" for="activa">Lista activa</label>
                    </div>

                    <button type="submit" class="btn btn-success w-100">
                        <i class="bi bi-save"></i> Guardar Lista
                    </button>
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-8">
        <div class="card shadow-sm">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h6 class="mb-0"><i class="bi bi-list-check"></i> Listas Registradas</h6>
                <div class="d-flex align-items-center gap-2">
                    @if($isDev)
                    <form
                        action="{{ route('sarlaft.sincronizacion.sincronizar-ahora') }}"
                        method="POST"
                        onsubmit="this.querySelector('[data-sync-text]').classList.add('d-none'); this.querySelector('[data-sync-loader]').classList.remove('d-none'); this.querySelector('button[type=submit]').setAttribute('disabled','disabled');"
                    >
                        @csrf
                        <button
                            type="submit"
                            class="btn btn-sm btn-success"
                            @disabled(! $puedeSincronizarAhora)
                            title="{{ $puedeSincronizarAhora ? 'Sincronizar ahora' : 'Ya ejecutada hoy' }}"
                        >
                            <span data-sync-text>
                                <i class="fas fa-sync-alt"></i> Sincronizar ahora (dev)
                            </span>
                            <span data-sync-loader class="d-none">
                                <i class="fas fa-spinner fa-spin"></i> Encolando...
                            </span>
                        </button>
                    </form>
                    @endif

                    <form action="{{ route('sarlaft.sincronizacion.listas.sincronizar-config') }}" method="POST">
                        @csrf
                        <button type="submit" class="btn btn-sm btn-dark">
                            <i class="bi bi-arrow-repeat"></i> Cargar desde config/listas.php
                        </button>
                    </form>
                </div>
            </div>
            <div class="card-body p-0">
                @if($isDev && ! $puedeSincronizarAhora)
                <div class="alert alert-warning mb-0 rounded-0 border-0">
                    La sincronizacion manual de hoy ya fue ejecutada. Se habilita nuevamente manana.
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
                                <td><span class="badge bg-secondary">{{ ucfirst($lista->tipo) }}</span></td>
                                <td>{{ $lista->frecuencia_sync ?? '-' }}</td>
                                <td class="small">
                                    @if($lista->url_fuente)
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
                                <td colspan="6" class="text-center text-muted py-4">No hay listas vinculantes registradas.</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="card shadow-sm">
    <div class="card-header">
        <h6 class="mb-0"><i class="bi bi-clock-history"></i> Historial de Sincronizacion</h6>
    </div>
    <div class="card-body p-0">
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
                        <td>{{ $log->duracion_segundos ? $log->duracion_segundos . 's' : '-' }}</td>
                        <td>{{ $log->created_at?->format('d/m/Y H:i') }}</td>
                        <td>
                            @if($log->error_mensaje)
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
                        <td colspan="9" class="text-center text-muted py-4">No hay logs de sincronizacion.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if($logs->hasPages())
    <div class="card-footer">
        {{ $logs->links() }}
    </div>
    @endif
</div>
@endsection
