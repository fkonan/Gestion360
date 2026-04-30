@extends('layouts.dashboard')

@section('title', 'Sistemas Consumidores')

@section('content')
<div class="row g-4">
    {{-- Formulario nuevo sistema --}}
    <div class="col-lg-4">
        <div class="card shadow-sm">
            <div class="card-header">
                <h6 class="mb-0"><i class="bi bi-plus-lg"></i> Nuevo Sistema</h6>
            </div>
            <div class="card-body">
                <form action="{{ route('sarlaft.sistemas-consumidores.store') }}" method="POST">
                    @csrf
                    <div class="mb-3">
                        <label for="nombre" class="form-label">Nombre <span class="text-danger">*</span></label>
                        <input type="text" name="nombre" id="nombre" class="form-control @error('nombre') is-invalid @enderror" value="{{ old('nombre') }}" required>
                        @error('nombre') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="mb-3">
                        <label for="codigo" class="form-label">Codigo <span class="text-danger">*</span></label>
                        <input type="text" name="codigo" id="codigo" class="form-control @error('codigo') is-invalid @enderror" value="{{ old('codigo') }}" required placeholder="ej: logtrans">
                        @error('codigo') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="mb-3">
                        <label for="limite_requests_minuto" class="form-label">Limite req/min <span class="text-danger">*</span></label>
                        <input type="number" name="limite_requests_minuto" id="limite_requests_minuto" class="form-control @error('limite_requests_minuto') is-invalid @enderror" value="{{ old('limite_requests_minuto', 100) }}" min="1" max="10000" required>
                        @error('limite_requests_minuto') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <hr class="my-3">
                    <p class="text-muted small mb-2"><i class="bi bi-arrow-down-circle"></i> Integración Pull (opcional)</p>

                    <div class="mb-3">
                        <label for="pull_endpoint" class="form-label">Endpoint Pull</label>
                        <input type="url" name="pull_endpoint" id="pull_endpoint" class="form-control @error('pull_endpoint') is-invalid @enderror" value="{{ old('pull_endpoint') }}" placeholder="https://api.empresa.com/intentos">
                        <div class="form-text">URL que SARLAFT consultará para obtener los intentos.</div>
                        @error('pull_endpoint') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="mb-3">
                        <label for="pull_token" class="form-label">Token Pull</label>
                        <input type="text" name="pull_token" id="pull_token" class="form-control @error('pull_token') is-invalid @enderror" value="{{ old('pull_token') }}" placeholder="Bearer token del sistema externo">
                        @error('pull_token') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <button type="submit" class="btn btn-success w-100">
                        <i class="bi bi-plus-lg"></i> Crear Sistema
                    </button>
                </form>
            </div>
        </div>
    </div>

    {{-- Tabla sistemas --}}
    <div class="col-lg-8">
        <div class="card shadow-sm">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Nombre / Codigo</th>
                                <th>Estado</th>
                                <th>Limite/min</th>
                                <th>Token API</th>
                                <th>Pull</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($sistemas as $sistema)
                            <tr>
                                <td>
                                    <div class="fw-semibold">{{ $sistema->nombre }}</div>
                                    <code class="small text-muted">{{ $sistema->codigo }}</code>
                                </td>
                                <td>
                                    <span class="badge bg-{{ $sistema->estado === 'activo' ? 'success' : 'secondary' }}">
                                        {{ ucfirst($sistema->estado) }}
                                    </span>
                                </td>
                                <td>{{ $sistema->limite_requests_minuto }}</td>
                                <td>
                                    <code class="user-select-all small">{{ substr($sistema->api_token ?? '', 0, 16) }}...</code>
                                </td>
                                <td>
                                    @if($sistema->pull_endpoint)
                                        <span class="badge bg-primary" title="{{ $sistema->pull_endpoint }}">
                                            <i class="bi bi-arrow-down-circle"></i> Configurado
                                        </span>
                                    @else
                                        <span class="badge bg-light text-secondary border">Sin endpoint</span>
                                    @endif
                                </td>
                                <td class="text-end">
                                    <button type="button"
                                        class="btn btn-sm btn-outline-primary me-1"
                                        data-bs-toggle="modal"
                                        data-bs-target="#modalEditarPull"
                                        data-id="{{ $sistema->id }}"
                                        data-nombre="{{ $sistema->nombre }}"
                                        data-endpoint="{{ $sistema->pull_endpoint }}"
                                        data-token="{{ $sistema->pull_token }}"
                                        data-limite="{{ $sistema->limite_requests_minuto }}"
                                        title="Editar configuración">
                                        <i class="bi bi-pencil"></i>
                                    </button>
                                    <form action="{{ route('sarlaft.sistemas-consumidores.destroy', $sistema) }}" method="POST" class="d-inline" onsubmit="return confirm('Eliminar sistema {{ $sistema->nombre }}?')">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-danger"><i class="bi bi-trash"></i></button>
                                    </form>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="6" class="text-center text-muted py-4">No hay sistemas registrados.</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            @if($sistemas->hasPages())
            <div class="card-footer">
                {{ $sistemas->links() }}
            </div>
            @endif
        </div>
    </div>
</div>

{{-- Modal editar sistema --}}
<div class="modal fade" id="modalEditarPull" tabindex="-1" aria-labelledby="modalEditarPullLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="formEditarPull" method="POST">
                @csrf
                @method('PATCH')
                <div class="modal-header">
                    <h5 class="modal-title" id="modalEditarPullLabel">
                        <i class="bi bi-pencil"></i> Editar sistema: <span id="modalNombreSistema"></span>
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="edit_limite" class="form-label">Limite req/min <span class="text-danger">*</span></label>
                        <input type="number" name="limite_requests_minuto" id="edit_limite" class="form-control" min="1" max="10000" required>
                    </div>

                    <hr class="my-3">
                    <p class="text-muted small mb-2"><i class="bi bi-arrow-down-circle"></i> Integración Pull</p>

                    <div class="mb-3">
                        <label for="edit_pull_endpoint" class="form-label">Endpoint Pull</label>
                        <input type="url" name="pull_endpoint" id="edit_pull_endpoint" class="form-control" placeholder="https://api.empresa.com/intentos">
                        <div class="form-text">URL que SARLAFT consultará. Dejar vacío para deshabilitar Pull.</div>
                    </div>
                    <div class="mb-3">
                        <label for="edit_pull_token" class="form-label">Token Pull</label>
                        <input type="text" name="pull_token" id="edit_pull_token" class="form-control" placeholder="Bearer token del sistema externo">
                        <div class="form-text">Se envía como <code>Authorization: Bearer …</code> al hacer el Pull.</div>
                    </div>

                    <div class="alert alert-info small mb-0">
                        <i class="bi bi-info-circle"></i>
                        El comando <code>php artisan sarlaft:pull-intentos</code> (o su cron) consultará este endpoint
                        enviando <code>?fecha_desde=…&fecha_hasta=…</code> y esperará un JSON <code>{ "data": [ ... ] }</code>.
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary"><i class="bi bi-save"></i> Guardar</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.getElementById('modalEditarPull').addEventListener('show.bs.modal', function (event) {
    const btn = event.relatedTarget;
    const id = btn.dataset.id;

    document.getElementById('modalNombreSistema').textContent = btn.dataset.nombre;
    document.getElementById('edit_limite').value      = btn.dataset.limite;
    document.getElementById('edit_pull_endpoint').value = btn.dataset.endpoint || '';
    document.getElementById('edit_pull_token').value    = btn.dataset.token || '';

    document.getElementById('formEditarPull').action =
        '{{ url("sarlaft/sistemas-consumidores") }}/' + id;
});
</script>
@endpush

