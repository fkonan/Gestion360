@extends('layouts.dashboard')

@section('title', 'Lista Negra Interna')

@section('content')
<div class="card shadow-sm">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h6 class="mb-0"><i class="fas fa-user-slash"></i> Registros Lista Negra</h6>
        <a href="{{ route('sarlaft.lista-negra.create') }}" class="btn btn-sm btn-primary">
            <i class="fas fa-plus"></i> Agregar Persona
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
                        <td><span class="badge bg-secondary">{{ ucfirst($registro->tipo_entidad) }}</span></td>
                        <td>{{ $registro->tipo_documento }} {{ $registro->numero_documento }}</td>
                        <td>{{ $registro->nombres }}</td>
                        <td>
                            <span class="badge bg-{{ $registro->estado === 'activo' ? 'danger' : 'secondary' }}">
                                {{ ucfirst($registro->estado) }}
                            </span>
                        </td>
                        <td>{{ trim(($registro->creadoPor->persona?->PerNombres ?? '') . ' ' . ($registro->creadoPor->persona?->PerApellidos ?? '')) ?: '-' }}</td>
                        <td>{{ $registro->created_at->format('d/m/Y') }}</td>
                        <td>
                            <div class="btn-group btn-group-sm">
                                <a href="{{ route('sarlaft.lista-negra.show', $registro) }}" class="btn btn-outline-primary"><i class="bi bi-eye"></i></a>
                                <a href="{{ route('sarlaft.lista-negra.edit', $registro) }}" class="btn btn-outline-secondary"><i class="bi bi-pencil"></i></a>
                                <form action="{{ route('sarlaft.lista-negra.destroy', $registro) }}" method="POST" class="d-inline" onsubmit="return confirm('Eliminar este registro?')">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="btn btn-outline-danger btn-sm"><i class="bi bi-trash"></i></button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="text-center text-muted py-4">No hay registros en la lista negra.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if($registros->hasPages())
    <div class="card-footer">
        {{ $registros->links() }}
    </div>
    @endif
</div>
@endsection
