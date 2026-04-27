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
                        <input type="text" name="codigo" id="codigo" class="form-control @error('codigo') is-invalid @enderror" value="{{ old('codigo') }}" required placeholder="ej: taquilla_web">
                        @error('codigo') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="mb-3">
                        <label for="limite_requests_minuto" class="form-label">Limite req/min <span class="text-danger">*</span></label>
                        <input type="number" name="limite_requests_minuto" id="limite_requests_minuto" class="form-control @error('limite_requests_minuto') is-invalid @enderror" value="{{ old('limite_requests_minuto', 100) }}" min="1" max="10000" required>
                        @error('limite_requests_minuto') <div class="invalid-feedback">{{ $message }}</div> @enderror
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
                                <th>Nombre</th>
                                <th>Codigo</th>
                                <th>Estado</th>
                                <th>Limite/min</th>
                                <th>Token</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($sistemas as $sistema)
                            <tr>
                                <td>{{ $sistema->nombre }}</td>
                                <td><code>{{ $sistema->codigo }}</code></td>
                                <td>
                                    <span class="badge bg-{{ $sistema->estado === 'activo' ? 'success' : 'secondary' }}">
                                        {{ ucfirst($sistema->estado) }}
                                    </span>
                                </td>
                                <td>{{ $sistema->limite_requests_minuto }}</td>
                                <td>
                                    <code class="user-select-all small">{{ substr($sistema->api_token, 0, 16) }}...</code>
                                </td>
                                <td>
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
@endsection
