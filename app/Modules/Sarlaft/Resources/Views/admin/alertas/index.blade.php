@extends('layouts.dashboard')
@section('title', 'Alertas')

@section('content')
<div class="card shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>ID</th>
                        <th>Tipo</th>
                        <th>Riesgo</th>
                        <th>Estado</th>
                        <th>Decision</th>
                        <th>Persona</th>
                        <th>Documento</th>
                        <th>Fecha</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($alertas as $alerta)
                    <tr>
                        <td>{{ $alerta->id }}</td>
                        <td>{{ $alerta->tipo }}</td>
                        <td>@include('sarlaft::admin.partials.badge-riesgo', ['nivel' => $alerta->nivel_riesgo])</td>
                        <td>@include('sarlaft::admin.partials.badge-estado-alerta', ['estado' => $alerta->estado])</td>
                        <td>{{ str_replace('_', ' ', ucfirst((string) ($alerta->decision_servicio ?? 'sin_decision'))) }}</td>
                        <td>{{ $alerta->datos_persona['nombre'] ?? '-' }}</td>
                        <td>{{ ($alerta->datos_persona['tipo_documento'] ?? '') . ' ' . ($alerta->datos_persona['numero_documento'] ?? '') }}</td>
                        <td>{{ $alerta->created_at->format('d/m/Y H:i') }}</td>
                        <td>
                            <a href="{{ route('sarlaft.alertas.show', $alerta) }}" class="btn btn-sm btn-outline-primary">
                                <i class="fas fa-eye"></i> Ver
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="9" class="text-center text-muted py-4">No hay alertas registradas.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if($alertas->hasPages())
    <div class="card-footer">
        {{ $alertas->links() }}
    </div>
    @endif
</div>
@endsection
