@extends('layouts.dashboard')

@section('title', 'Mis solicitudes de permisos')

@section('breadcrumb')
<x-breadcrumb :items="[
        ['name' => 'Inicio', 'url' => route('home')],
        ['name' => 'Empleados', 'url' => route('empleados.index')],
        ['name' => 'Mis solicitudes de permisos']
    ]" />
@endsection

@section('content')
<div class="container-fluid tableContainer p-0 border rounded sidebar-dark-primary" style="min-height:150px">
  <x-sectionHeader
    titulo="Mis solicitudes de permisos"
    rutaVolver="{{ route('empleados.index') }}"
    :clasePosition="false" />

  <div style="padding:1.5em">
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

    <div class="d-flex justify-content-end mb-3">
      <a href="{{ route('empleados.permisos.create') }}" class="btn btn-success">Radicar nuevo permiso</a>
    </div>

    <form method="GET" action="{{ route('empleados.permisos.mis-solicitudes') }}" class="row g-3 mb-3">
      <div class="col-12 col-md-6">
        <label for="estado_flujo" class="form-label">Estado flujo</label>
        <select class="form-select" id="estado_flujo" name="estado_flujo">
          <option value="">Todos</option>
          @foreach($opcionesEstadoFlujo as $codigo => $label)
          <option value="{{ $codigo }}" {{ $filtros['estado_flujo'] === $codigo ? 'selected' : '' }}>
            {{ $label }}
          </option>
          @endforeach
        </select>
      </div>

      <div class="col-12 col-md-6 d-flex align-items-end gap-2">
        <button type="submit" class="btn btn-success">Buscar</button>
        <a href="{{ route('empleados.permisos.mis-solicitudes') }}" class="btn btn-dark">Limpiar</a>
      </div>
    </form>

    <div id="no-more-tables" class="table-responsive">
      <table
        id="misPermisosEmpleadosTable"
        class="table table-sm table-striped table-hover align-middle"
        data-toggle="table"
        data-locale="es-ES"
        data-search="true"
        data-pagination="false"
        data-detail-view="true"
        data-detail-formatter="detalleMisPermisosEmpleados">
        <thead class="table-primary">
          <tr>
            <th data-field="documento">Documento</th>
            <th data-field="empleado">Empleado</th>
            <th data-field="actividad">Actividad</th>
            <th data-field="fecha_permiso">Fecha permiso</th>
            <th data-field="hora_salida">Hora salida</th>
            <th data-field="hora_ingreso">Hora ingreso</th>
            <th data-field="estado_flujo">Estado flujo</th>
            <th data-field="estado_novedad">Estado novedad</th>
            <th data-field="acciones" class="text-center">Acciones</th>
          </tr>
        </thead>
        <tbody>
          @forelse($permisos as $permiso)
          <tr>
            <td class="text-nowrap">{{ $permiso->id_persona }}</td>
            <td class="text-nowrap">{{ $permiso->persona_nombre }}</td>
            <td>{{ $permiso->actividad ?? 'N/A' }}</td>
            <td class="text-nowrap">{{ $permiso->fecha_inicio?->format('d/m/Y') ?? 'N/A' }}</td>
            <td class="text-nowrap fw-semibold">{{ $permiso->fecha_inicio?->format('h:i A') ?? 'N/A' }}</td>
            <td class="text-nowrap fw-semibold">{{ $permiso->fecha_fin?->format('h:i A') ?? 'N/A' }}</td>
            <td class="text-nowrap">{{ $opcionesEstadoFlujo[$permiso->estado_flujo] ?? $permiso->estado_flujo }}</td>
            <td class="text-nowrap">{{ $permiso->novedad_estado ?? 'N/A' }}</td>
            <td class="text-nowrap text-center">
              <div class="d-flex flex-wrap gap-1 justify-content-center">
                <a
                  class="d-inline-flex align-items-center justify-content-center p-0 border-0 bg-transparent text-decoration-none"
                  href="{{ route('empleados.permisos.pdf', ['idNovedad' => $permiso->emp_novedad_id]) }}"
                  aria-label="Descargar PDF"
                  title="Descargar PDF"
                  target="_blank">
                  <img
                    src="{{ asset('img/descargarPDF.png') }}"
                    alt="Descargar PDF"
                    class="permiso-action-icon">
                </a>

                @if($permiso->puede_anular)
                <form
                  method="POST"
                  class="js-anular-permiso-form"
                  action="{{ route('empleados.permisos.anular', ['idNovedad' => $permiso->emp_novedad_id]) }}">
                  @csrf
                  <input type="hidden" name="motivo_anulacion" value="">
                  <button
                    type="button"
                    class="js-abrir-modal-anulacion d-inline-flex align-items-center justify-content-center p-0 border-0 bg-transparent"
                    aria-label="Anular permiso"
                    title="Anular permiso">
                    <img
                      src="{{ asset('img/anular.png') }}"
                      alt="Anular permiso"
                      class="permiso-action-icon">
                  </button>
                </form>
                @endif
              </div>
            </td>
          </tr>
          @empty
          <tr>
            <td colspan="9" class="text-center py-3">No hay permisos registrados para la consulta.</td>
          </tr>
          @endforelse
        </tbody>
      </table>
    </div>

    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2 mt-3">
      <small class="text-muted">
        Mostrando {{ $permisos->firstItem() ?? 0 }} - {{ $permisos->lastItem() ?? 0 }} de {{ $permisos->total() }} registros.
      </small>
      {{ $permisos->links('pagination::bootstrap-5') }}
    </div>
  </div>
</div>

<div class="modal fade" id="modalAnularPermiso" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Confirmar anulacion</h5>
        <button type="button" class="btn-close" aria-label="Cerrar" id="cerrarAnulacionBtn"></button>
      </div>
      <div class="modal-body">
        <p class="mb-2">Para anular este permiso debes indicar la observacion.</p>
        <label for="motivoAnulacionModal" class="form-label">Observacion de anulacion</label>
        <textarea
          id="motivoAnulacionModal"
          class="form-control"
          rows="4"
          maxlength="500"
          placeholder="Escribe la razon de la anulacion"></textarea>
        <small id="motivoAnulacionModalError" class="text-danger d-none"></small>
      </div>
      <div class="modal-footer">
        <button type="button" id="cancelarAnulacionBtn" class="btn btn-secondary">Cancelar</button>
        <button type="button" id="confirmarAnulacionBtn" class="btn btn-danger">Confirmar anulacion</button>
      </div>
    </div>
  </div>
</div>
@endsection

@pushOnce('css')
<style>
  .permiso-action-icon {
    width: 30px;
    height: 30px;
    object-fit: contain;
  }
</style>
@endPushOnce

@pushOnce('script')
<script>
  document.addEventListener('DOMContentLoaded', function() {
    if (typeof initTablaBootstrapTable === 'function') {
      initTablaBootstrapTable(
        '#misPermisosEmpleadosTable', {
          protegidas: ['documento', 'empleado', 'fecha_permiso', 'hora_salida', 'hora_ingreso', 'estado_flujo', 'acciones']
        },
        'detalleMisPermisosEmpleados'
      );
    }

    const modalElement = document.getElementById('modalAnularPermiso');
    const textareaMotivo = document.getElementById('motivoAnulacionModal');
    const errorMotivo = document.getElementById('motivoAnulacionModalError');
    const btnCancelar = document.getElementById('cancelarAnulacionBtn');
    const btnConfirmar = document.getElementById('confirmarAnulacionBtn');
    const btnCerrarModal = document.getElementById('cerrarAnulacionBtn');

    let formPendienteAnulacion = null;
    let modalInstance = null;

    if (window.bootstrap && window.bootstrap.Modal && modalElement) {
      modalInstance = new window.bootstrap.Modal(modalElement);
    }

    function mostrarError(msg) {
      if (!errorMotivo) {
        return;
      }

      errorMotivo.textContent = msg;
      errorMotivo.classList.toggle('d-none', msg.trim() === '');
    }

    function abrirModal() {
      if (!modalElement) {
        return;
      }

      if (modalInstance) {
        modalInstance.show();
        return;
      }

      if (window.jQuery && typeof window.jQuery(modalElement).modal === 'function') {
        window.jQuery(modalElement).modal('show');
        return;
      }

      modalElement.style.display = 'block';
      modalElement.classList.add('show');
      modalElement.removeAttribute('aria-hidden');
    }

    function cerrarModal() {
      if (!modalElement) {
        return;
      }

      if (modalInstance) {
        modalInstance.hide();
        return;
      }

      if (window.jQuery && typeof window.jQuery(modalElement).modal === 'function') {
        window.jQuery(modalElement).modal('hide');
        return;
      }

      modalElement.classList.remove('show');
      modalElement.style.display = 'none';
      modalElement.setAttribute('aria-hidden', 'true');
    }

    function limpiarModalAnulacion() {
      formPendienteAnulacion = null;
      if (textareaMotivo) {
        textareaMotivo.value = '';
      }
      mostrarError('');
    }

    document.addEventListener('click', function(event) {
      const btnAbrir = event.target.closest('.js-abrir-modal-anulacion');
      if (!btnAbrir) {
        return;
      }

      event.preventDefault();

      const form = btnAbrir.closest('form.js-anular-permiso-form');
      if (!form) {
        return;
      }

      formPendienteAnulacion = form;
      mostrarError('');
      if (textareaMotivo) {
        textareaMotivo.value = '';
      }
      abrirModal();
      setTimeout(function() {
        if (textareaMotivo) {
          textareaMotivo.focus();
        }
      }, 120);
    });

    if (btnCancelar) {
      btnCancelar.addEventListener('click', function() {
        cerrarModal();
        limpiarModalAnulacion();
      });
    }

    if (btnCerrarModal) {
      btnCerrarModal.addEventListener('click', function() {
        cerrarModal();
        limpiarModalAnulacion();
      });
    }

    if (btnConfirmar) {
      btnConfirmar.addEventListener('click', function() {
        if (!formPendienteAnulacion) {
          cerrarModal();
          return;
        }

        const motivo = textareaMotivo ? textareaMotivo.value.trim() : '';
        if (motivo === '') {
          mostrarError('Debes indicar la razon de la anulacion.');
          if (textareaMotivo) {
            textareaMotivo.focus();
          }
          return;
        }

        const inputMotivo = formPendienteAnulacion.querySelector('input[name="motivo_anulacion"]');
        if (inputMotivo) {
          inputMotivo.value = motivo;
        }

        formPendienteAnulacion.submit();
      });
    }

    if (modalElement) {
      modalElement.addEventListener('hidden.bs.modal', limpiarModalAnulacion);
    }
  });
</script>
@endPushOnce
