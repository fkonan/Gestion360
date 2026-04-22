@extends('layouts.dashboard')

@section('title', 'Novedades empleados')

@section('breadcrumb')
<x-breadcrumb :items="[
        ['name' => 'Inicio', 'url' => route('home')],
        ['name' => 'Empleados', 'url' => route('empleados.index')],
        ['name' => 'Novedades']
    ]" />
@endsection

@section('content')
<div class="container-fluid tableContainer p-0 border rounded sidebar-dark-primary" style="min-height:150px">
  <x-sectionHeader
    titulo="Novedades de empleados"
    rutaVolver="{{ route('empleados.index') }}"
    :clasePosition="false" />

  <div style="padding:1.5em">

    <form method="GET" action="{{ route('empleados.novedades') }}" class="row g-3 mb-3">
      <div class="col-12 col-md-6">
        <label for="persona" class="form-label">Persona</label>
        <input
          type="text"
          class="form-control"
          id="persona"
          name="persona"
          placeholder="Documento o nombre"
          value="{{ $filtros['persona'] }}">
      </div>

      <div class="col-12 col-md-6 d-flex align-items-end">
        <div class="form-check form-switch mb-2">
          <input type="hidden" name="solo_activas" value="0">
          <input
            class="form-check-input"
            type="checkbox"
            role="switch"
            id="solo_activas"
            name="solo_activas"
            value="1"
            {{ $filtros['solo_activas'] ? 'checked' : '' }}>
          <label class="form-check-label" for="solo_activas">
            Mostrar solo novedades activas ahora
          </label>
        </div>
      </div>

      <div class="col-12 d-flex gap-2">
        <button type="submit" class="btn btn-success">Buscar</button>
        <a href="{{ route('empleados.novedades') }}" class="btn btn-dark">Limpiar</a>
      </div>
    </form>

    @if ($errors->any())
    <div class="alert alert-danger">
      <ul class="mb-0">
        @foreach ($errors->all() as $error)
        <li>{{ $error }}</li>
        @endforeach
      </ul>
    </div>
    @endif

    @if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    @if(session('error'))
    <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    <div id="no-more-tables" class="table-responsive">
      <table
        id="novedadesEmpleadosTable"
        class="table table-sm table-striped table-hover align-middle"
        data-toggle="table"
        data-locale="es-ES"
        data-search="true"
        data-pagination="false"
        data-detail-view="true"
        data-detail-formatter="detalleNovedadesEmpleados">
        <thead class="table-primary">
          <tr>
            <th data-field="documento">Documento</th>
            <th data-field="nombre">Nombre</th>
            <th data-field="tipo_novedad">Tipo novedad</th>
            <th data-field="estado">Estado</th>
            <th data-field="fecha_inicio">Fecha inicio</th>
            <th data-field="hora_inicio">Hora inicio</th>
            <th data-field="fecha_fin">Fecha fin</th>
            <th data-field="hora_fin">Hora fin</th>
            <th data-field="observacion">Observacion</th>
            <th data-field="acciones" class="text-center">Acciones</th>
          </tr>
        </thead>
        <tbody>
          @forelse($novedades as $novedad)
          <tr>
            <td class="text-nowrap">{{ $novedad->id_persona }}</td>
            <td class="text-nowrap">{{ $novedad->persona_nombre }}</td>
            <td class="text-nowrap">{{ $novedad->tipo_novedad ?? 'Sin tipo' }}</td>
            <td class="text-nowrap">{{ $novedad->estado }}</td>
            <td class="text-nowrap">{{ $novedad->inicio_fecha }}</td>
            <td class="text-nowrap fw-semibold">{{ $novedad->inicio_hora }}</td>
            <td class="text-nowrap">{{ $novedad->fin_fecha }}</td>
            <td class="text-nowrap fw-semibold">{{ $novedad->fin_hora }}</td>
            <td>{{ $novedad->observacion ?? 'N/A' }}</td>
            <td class="text-nowrap text-center">
              <a
                title="Editar horas de novedad"
                class="sig-action-btn accion-editar-horas"
                data-title="Editar horas"
                aria-label="Editar horas"
                style="cursor: pointer;"
                data-novedad-id="{{ $novedad->id }}"
                data-documento="{{ $novedad->id_persona }}"
                data-nombre="{{ $novedad->persona_nombre }}"
                data-hora-inicio="{{ $novedad->hora_inicio_24 }}"
                data-hora-fin="{{ $novedad->hora_fin_24 }}">
                <img src="{{ asset('img/edit.png') }}" alt="Editar horas" style="width: 32px; height: 32px;">
              </a>
            </td>
          </tr>
          @empty
          <tr>
            <td colspan="10" class="text-center py-3">No se encontraron novedades para la consulta.</td>
          </tr>
          @endforelse
        </tbody>
      </table>
    </div>

    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2 mt-3">
      <small class="text-muted">
        Mostrando {{ $novedades->firstItem() ?? 0 }} - {{ $novedades->lastItem() ?? 0 }} de {{ $novedades->total() }} registros.
      </small>
      {{ $novedades->links('pagination::bootstrap-5') }}
    </div>
  </div>
</div>

<div class="modal fade" id="editarHorasModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-md">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Editar horas de novedad</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
      </div>
      <form id="formEditarHorasNovedad" method="POST">
        @csrf
        @method('PUT')
        <div class="modal-body">
          <div class="mb-2">
            <div class="small text-muted">Empleado</div>
            <div id="modalPersonaNovedad" class="fw-semibold"></div>
          </div>

          <div class="row g-3">
            <div class="col-12 col-md-6">
              <label for="hora_inicio" class="form-label">Hora inicio</label>
              <input type="time" class="form-control" id="hora_inicio" name="hora_inicio" required>
            </div>
            <div class="col-12 col-md-6">
              <label for="hora_fin" class="form-label">Hora fin</label>
              <input type="time" class="form-control" id="hora_fin" name="hora_fin">
              <small class="text-muted">Si la dejas vacía, se conserva la hora actual de fin.</small>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="submit" class="btn btn-success">Guardar cambios</button>
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
        </div>
      </form>
    </div>
  </div>
</div>
@endsection

@pushOnce('script')
<script>
  const rutaActualizarHorasTemplate = @json(route('empleados.novedades.horas.update', ['id' => '__ID__']));

  document.addEventListener("DOMContentLoaded", () => {
    initTablaBootstrapTable(
      '#novedadesEmpleadosTable', {
        protegidas: ['documento', 'nombre', 'hora_inicio', 'hora_fin', 'acciones']
      },
      'detalleNovedadesEmpleados'
    );
  });

  document.addEventListener('click', function(event) {
    const btn = event.target.closest('.accion-editar-horas');
    if (!btn) {
      return;
    }

    const novedadId = btn.dataset.novedadId;
    const documento = btn.dataset.documento || '';
    const nombre = btn.dataset.nombre || '';
    const horaInicio = btn.dataset.horaInicio || '';
    const horaFin = btn.dataset.horaFin || '';

    const form = document.getElementById('formEditarHorasNovedad');
    const personaEl = document.getElementById('modalPersonaNovedad');
    const inputHoraInicio = document.getElementById('hora_inicio');
    const inputHoraFin = document.getElementById('hora_fin');

    form.action = rutaActualizarHorasTemplate.replace('__ID__', encodeURIComponent(novedadId));
    personaEl.textContent = `${documento} - ${nombre}`;
    inputHoraInicio.value = horaInicio;
    inputHoraFin.value = horaFin;

    const modal = bootstrap.Modal.getOrCreateInstance(document.getElementById('editarHorasModal'));
    modal.show();
  });
</script>
@endPushOnce
