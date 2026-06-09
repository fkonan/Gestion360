@extends('layouts.dashboard')

@section('title','Gestionar regularizacion Cajasan')

@section('breadcrumb')
<x-breadcrumb :items="[
        ['name' => 'Inicio', 'url' => route('home')],
        ['name' => 'Pago convenio', 'url' => route('pagosConvenios.index')],
        ['name' => 'Regularizaciones', 'url' => route('pagosConvenios.regularizaciones.index')],
        ['name' => 'Caso '.$caso->id]
    ]" />
<br>
@endsection

@section('headerInfo')
<div class="header-info-pagos-recaudos d-flex flex-wrap justify-content-end align-items-center gap-2 text-end">
  <span class="fw-bold small">CASO</span>
  <span>
    <small>Detalle:</small> <span class="fw-bold small">{{ $caso->id }}</span>
  </span>
  <span>
    <small>Estado:</small> <span class="fw-bold small">{{ $caso->estado }}</span>
  </span>
</div>
@endsection

@pushOnce('css')
<style>
  .regularizacion-page {
    color: var(--bs-body-color);
  }

  .regularizacion-card {
    background-color: var(--bs-body-bg);
    color: var(--bs-body-color);
    border-color: var(--bs-border-color);
  }

  .regularizacion-card-header {
    background-color: var(--bs-tertiary-bg);
    color: var(--bs-body-color);
    border-bottom: 1px solid var(--bs-border-color);
  }

  .regularizacion-summary-box {
    background-color: var(--bs-tertiary-bg);
    color: var(--bs-body-color);
    border: 1px solid var(--bs-border-color) !important;
    box-shadow: inset 0 0 0 1px rgba(var(--bs-primary-rgb), 0.04);
  }

  .regularizacion-page .text-muted,
  .regularizacion-page .small.text-muted {
    color: var(--bs-secondary-color) !important;
  }

  .regularizacion-page .alert {
    border-width: 1px;
  }

  .regularizacion-preview-table th,
  .regularizacion-preview-table td {
    background-color: var(--bs-body-bg);
    color: var(--bs-body-color);
    border-color: var(--bs-border-color);
    vertical-align: top;
  }

  .regularizacion-preview-table thead th {
    background-color: var(--bs-tertiary-bg);
    color: var(--bs-body-color);
  }

  .regularizacion-preview-table tbody tr:hover td {
    background-color: rgba(var(--bs-primary-rgb), 0.05);
  }

  .regularizacion-json-preview {
    margin: 0;
    padding: 0.85rem 1rem;
    background-color: var(--bs-tertiary-bg);
    color: var(--bs-body-color);
    border: 1px solid var(--bs-border-color);
    border-radius: 0.5rem;
    white-space: pre-wrap;
    word-break: break-word;
    overflow-x: auto;
    line-height: 1.5;
    font-size: 0.8rem;
    box-shadow: inset 0 0 0 1px rgba(var(--bs-primary-rgb), 0.04);
  }

  [data-bs-theme="dark"] .regularizacion-card {
    background: linear-gradient(180deg, rgba(17, 27, 45, 0.98), rgba(13, 21, 36, 0.98));
    border-color: rgba(79, 163, 255, 0.18);
  }

  [data-bs-theme="dark"] .regularizacion-card-header {
    background: linear-gradient(180deg, rgba(79, 163, 255, 0.14), rgba(79, 163, 255, 0.05));
    color: #e7f1ff;
    border-bottom-color: rgba(79, 163, 255, 0.2);
  }

  [data-bs-theme="dark"] .regularizacion-summary-box {
    background-color: rgba(22, 35, 59, 0.95);
    border-color: rgba(79, 163, 255, 0.18) !important;
    box-shadow: inset 0 0 0 1px rgba(79, 163, 255, 0.08);
  }

  [data-bs-theme="dark"] .regularizacion-page .text-muted,
  [data-bs-theme="dark"] .regularizacion-page .small.text-muted {
    color: #aebfd5 !important;
  }

  [data-bs-theme="dark"] .regularizacion-preview-table th,
  [data-bs-theme="dark"] .regularizacion-preview-table td {
    border-color: rgba(79, 163, 255, 0.14);
  }

  [data-bs-theme="dark"] .regularizacion-preview-table thead th {
    background-color: rgba(79, 163, 255, 0.12);
    color: #e7f1ff;
  }

  [data-bs-theme="dark"] .regularizacion-preview-table tbody tr:hover td {
    background-color: rgba(79, 163, 255, 0.09);
  }

  [data-bs-theme="dark"] .regularizacion-json-preview {
    background-color: rgba(8, 16, 29, 0.95);
    color: #dfe9f8;
    border-color: rgba(79, 163, 255, 0.18);
    box-shadow: inset 0 0 0 1px rgba(79, 163, 255, 0.08);
  }
</style>
@endpushOnce

@section('content')
@php
  $authorizationCodeValue = old('authorization_code', $formData['authorization_code'] ?? '');
  $userIdValue = old('user_id', $formData['user_id'] ?? '');
  $telefonoValue = old('telefono', $formData['telefono'] ?? '0');
  $turnoIdValue = old('turno_id', $formData['turno_id'] ?? '');
@endphp

<div class="container-fluid p-0 border rounded sidebar-dark-primary tableContainer pb-4 regularizacion-page" style="min-height:150px;">
  <div class="border rounded-top d-flex justify-content-between align-items-center px-4 bg-primary-subtle">
    <span class="text-left text-light fs-5 fw-medium py-1">Gestion administrativa del caso {{ $caso->id }}</span>
    <span class="badge bg-light text-dark">Estado actual: {{ $caso->estado }}</span>
  </div>

  <div class="d-flex flex-wrap gap-2 ms-4 mt-3">
    <a
      style="width: 180px;"
      class="btn btn-success fw-bold js-regularizacion-link"
      data-loader-message="Cargando listado de regularizaciones..."
      href="{{ route('pagosConvenios.regularizaciones.index') }}">Volver al listado</a>
    <a
      style="width: 150px;"
      class="btn btn-outline-secondary fw-bold js-regularizacion-link"
      data-loader-message="Cargando modulo de pagos..."
      href="{{ route('pagosConvenios.index') }}">Modulo pagos</a>
    @if(($regularizacionResultado['estado_final'] ?? null) === 'P')
    <a
      style="width: 180px;"
      class="btn btn-outline-danger fw-bold"
      target="_blank"
      href="{{ route('pagosConvenios.recibo', ['IdDetallePago' => $caso->id]) }}">
      Ver recibo
    </a>
    @endif
  </div>

  <div class="row g-4 p-4">
    <div class="col-12 col-xl-5">
      <div class="card shadow-sm h-100 regularizacion-card">
        <div class="card-header fw-bold regularizacion-card-header">Datos del caso</div>
        <div class="card-body">
          <dl class="row mb-0">
            <dt class="col-sm-5">Detalle</dt>
            <dd class="col-sm-7">{{ $caso->id }}</dd>

            <dt class="col-sm-5">Fecha creacion</dt>
            <dd class="col-sm-7">{{ \Carbon\Carbon::parse($caso->feccreacion)->format('d/m/Y H:i:s') }}</dd>

            <dt class="col-sm-5">Cliente</dt>
            <dd class="col-sm-7">{{ $caso->clienteprincipal }}</dd>

            <dt class="col-sm-5">Identificacion</dt>
            <dd class="col-sm-7">{{ $caso->iden_clienteprincipal }}</dd>

            <dt class="col-sm-5">Valor</dt>
            <dd class="col-sm-7">${{ number_format((float) $caso->valortotal, 0, ',', '.') }}</dd>

            <dt class="col-sm-5">UsrCreacion</dt>
            <dd class="col-sm-7">{{ $caso->usrcreacion }}</dd>

            <dt class="col-sm-5">EmpCreacion</dt>
            <dd class="col-sm-7">{{ $caso->empcreacion }}</dd>

            <dt class="col-sm-5">Nro interno</dt>
            <dd class="col-sm-7">{{ $caso->nro_interno ?: 'N/A' }}</dd>

            <dt class="col-sm-5">Cod agencia</dt>
            <dd class="col-sm-7">{{ $caso->codagencia ?: 'N/A' }}</dd>

            <dt class="col-sm-5">Agencia</dt>
            <dd class="col-sm-7">{{ $caso->agencia ?: 'N/A' }}</dd>

            <dt class="col-sm-5">Detalle contable</dt>
            <dd class="col-sm-7">
              <span class="badge {{ (int) $caso->tiene_detalle_contable === 1 ? 'bg-warning text-dark' : 'bg-secondary' }}">
                {{ (int) $caso->tiene_detalle_contable === 1 ? 'SI' : 'NO' }}
              </span>
            </dd>
          </dl>
        </div>
      </div>
    </div>

    <div class="col-12 col-xl-7">
      <div class="card shadow-sm h-100 regularizacion-card">
        <div class="card-header fw-bold regularizacion-card-header">Acciones de regularizacion</div>
        <div class="card-body">
          @if((int) ($caso->es_regularizable ?? 0) !== 1)
          <div class="alert alert-info">
            Este caso aparece en el panel por no tener numero interno, pero el flujo actual de regularizacion solo ejecuta pagos en estado <b>C</b>.
          </div>
          @endif

          @if($errors->any())
          <div class="alert alert-danger">
            <ul class="mb-0">
              @foreach($errors->all() as $error)
              <li>{{ $error }}</li>
              @endforeach
            </ul>
          </div>
          @endif

          @if($previewError)
          <div class="alert alert-warning">{{ $previewError }}</div>
          @endif

          @if(session('error'))
          <div class="alert alert-danger">{{ session('error') }}</div>
          @endif

          @if(session('success'))
          <div class="alert alert-success">{{ session('success') }}</div>
          @endif

          <form method="POST" action="{{ route('pagosConvenios.regularizaciones.preview', $caso->id) }}" class="js-regularizacion-action-form">
            @csrf

            <div class="row g-3">
              <div class="col-12 col-md-6">
                <label for="authorization_code" class="form-label">Codigo de autorizacion</label>
                <input
                  type="text"
                  class="form-control"
                  id="authorization_code"
                  name="authorization_code"
                  value="{{ $authorizationCodeValue }}"
                  maxlength="60"
                  required>
              </div>

              <div class="col-12 col-md-6">
                <label for="user_id" class="form-label">User ID cajero (PER_PERSONAS)</label>
                <input
                  type="number"
                  class="form-control"
                  id="user_id"
                  name="user_id"
                  value="{{ $userIdValue }}"
                  min="1"
                  step="1"
                  required>
                <small class="text-muted">Este valor define el usuario contable/cajero al que quedara asociado el pago regularizado.</small>
              </div>

              <div class="col-12 col-md-6">
                <label for="telefono" class="form-label">Telefono</label>
                <input
                  type="text"
                  class="form-control"
                  id="telefono"
                  name="telefono"
                  value="{{ $telefonoValue }}"
                  maxlength="30">
              </div>

              <div class="col-12 col-md-6">
                <label for="turno_id" class="form-label">Turno ID forzado (opcional)</label>
                <input
                  type="number"
                  class="form-control"
                  id="turno_id"
                  name="turno_id"
                  value="{{ $turnoIdValue }}"
                  min="1"
                  step="1">
                <small class="text-muted">Si lo defines, controlas exactamente la caja/turno destino. Si lo dejas vacio, se usa la resolucion automatica del servicio.</small>
              </div>

              <div class="col-12">
                <div class="d-flex flex-wrap gap-2">
                  <button
                    type="submit"
                    class="btn btn-primary"
                    data-loading-text="Previsualizando..."
                    data-loader-message="Previsualizando regularizacion..."
                    @disabled((int) ($caso->es_regularizable ?? 0) !== 1)>
                    Previsualizar
                  </button>
                  <button
                    type="submit"
                    class="btn btn-danger"
                    formaction="{{ route('pagosConvenios.regularizaciones.regularizar', $caso->id) }}"
                    data-loading-text="Regularizando..."
                    data-loader-message="Ejecutando regularizacion..."
                    data-confirm-regularizacion="true"
                    @disabled((int) ($caso->es_regularizable ?? 0) !== 1)>
                    Ejecutar regularizacion
                  </button>
                </div>
              </div>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>

  @if($previewResult)
  <div class="px-4">
    <div class="card shadow-sm border-0 regularizacion-card">
      <div class="card-header fw-bold regularizacion-card-header">Previsualizacion de la regularizacion</div>
      <div class="card-body">
        <div class="row g-3 mb-4">
          <div class="col-12 col-md-4">
            <div class="rounded p-3 h-100 regularizacion-summary-box">
              <div class="small text-muted">Turno</div>
              <div class="fw-bold">{{ $previewResult['turno_id'] }}</div>
              <div class="small text-muted mt-2">Sucursal</div>
              <div class="fw-bold">{{ $previewResult['idsucursal'] }}</div>
            </div>
          </div>
          <div class="col-12 col-md-4">
            <div class="rounded p-3 h-100 regularizacion-summary-box">
              <div class="small text-muted">EN_ID</div>
              <div class="fw-bold">{{ $previewResult['en_id'] }}</div>
              <div class="small text-muted mt-2">Centro costo</div>
              <div class="fw-bold">{{ $previewResult['centro_costo'] }}</div>
            </div>
          </div>
          <div class="col-12 col-md-4">
            <div class="rounded p-3 h-100 regularizacion-summary-box">
              <div class="small text-muted">Valor</div>
              <div class="fw-bold">${{ number_format((float) $previewResult['saldo'], 0, ',', '.') }}</div>
              <div class="small text-muted mt-2">Nro interno objetivo</div>
              <div class="fw-bold">{{ $previewResult['nro_interno_objetivo'] }}</div>
            </div>
          </div>
          <div class="col-12 col-md-4">
            <div class="rounded p-3 h-100 regularizacion-summary-box">
              <div class="small text-muted">User ID destino</div>
              <div class="fw-bold">{{ $userIdValue }}</div>
              <div class="small text-muted mt-2">Turno forzado</div>
              <div class="fw-bold">{{ $turnoIdValue ?: 'No definido' }}</div>
            </div>
          </div>
        </div>

        <div class="row g-3 mb-4">
          <div class="col-12 col-md-6">
            <div class="rounded p-3 h-100 regularizacion-summary-box">
              <div class="small text-muted">Cliente</div>
              <div class="fw-bold">{{ $previewResult['cliente_identificacion'] }} - {{ $previewResult['cliente_nombre'] }}</div>
            </div>
          </div>
          <div class="col-12 col-md-6">
            <div class="rounded p-3 h-100 regularizacion-summary-box">
              <div class="small text-muted">Agencia destino</div>
              <div class="fw-bold">{{ $previewResult['agencia_objetivo'] ?: 'N/A' }}</div>
            </div>
          </div>
        </div>

        <div class="table-responsive">
          <table class="table table-sm table-hover align-middle mb-0 regularizacion-preview-table">
            <thead>
              <tr>
                <th>Tabla</th>
                <th>Accion</th>
                <th>Detalle</th>
              </tr>
            </thead>
            <tbody>
              @foreach($previewResult['preview'] as $tabla => $detalle)
              <tr>
                <td class="fw-bold">{{ $tabla }}</td>
                <td>{{ $detalle['accion'] ?? 'N/A' }}</td>
                <td>
                  <pre class="small regularizacion-json-preview">{{ json_encode($detalle, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                </td>
              </tr>
              @endforeach
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
  @endif

  @if($regularizacionResultado)
  <div class="px-4 mt-4">
    <div class="card shadow-sm border-success">
      <div class="card-header bg-success text-white fw-bold">Resultado de regularizacion</div>
      <div class="card-body">
        <div class="row g-3">
          <div class="col-12 col-md-4">
            <div class="small text-muted">Comprobante ID</div>
            <div class="fw-bold">{{ $regularizacionResultado['comprobante_id'] ?? 'N/A' }}</div>
          </div>
          <div class="col-12 col-md-4">
            <div class="small text-muted">Comprobante</div>
            <div class="fw-bold">{{ $regularizacionResultado['comprobante'] ?? 'N/A' }}</div>
          </div>
          <div class="col-12 col-md-4">
            <div class="small text-muted">Estado final</div>
            <div class="fw-bold">{{ $regularizacionResultado['estado_final'] ?? 'N/A' }}</div>
          </div>
        </div>
      </div>
    </div>
  </div>
  @endif
</div>
@endsection

@pushOnce('script')
<script>
  document.addEventListener('DOMContentLoaded', function () {
    const loader = document.getElementById('fullscreen-loader');
    const loaderLabel = loader ? loader.querySelector('span') : null;

    const showLoader = (message) => {
      if (loaderLabel && message) {
        loaderLabel.textContent = message;
      }
      if (loader) {
        loader.style.display = 'flex';
      }
    };

    document.querySelectorAll('.js-regularizacion-link').forEach((link) => {
      link.addEventListener('click', function () {
        showLoader(this.dataset.loaderMessage || 'Cargando...');
      });
    });

    document.querySelectorAll('.js-regularizacion-action-form').forEach((form) => {
      let confirmacionAprobada = false;

      const regularizarButton = form.querySelector('[data-confirm-regularizacion="true"]');
      if (regularizarButton) {
        regularizarButton.addEventListener('click', function (event) {
          if (confirmacionAprobada) {
            confirmacionAprobada = false;
            return;
          }

          event.preventDefault();

          const authorizationCode = form.querySelector('#authorization_code')?.value?.trim() || 'N/A';
          const userId = form.querySelector('#user_id')?.value?.trim() || 'N/A';
          const turnoId = form.querySelector('#turno_id')?.value?.trim() || 'Automatico';
          const detalleId = @json((string) $caso->id);
          const clienteIdentificacion = @json((string) $caso->iden_clienteprincipal);
          const clienteNombre = @json((string) $caso->clienteprincipal);

          if (!window.Swal || typeof window.Swal.fire !== 'function') {
            return;
          }

          window.Swal.fire({
            icon: 'warning',
            title: 'Confirmar regularización',
            html: `
              <div class="text-start">
                <p class="mb-2">Verifique la información antes de continuar.</p>
                <div><b>Detalle:</b> ${detalleId}</div>
                <div><b>Cliente:</b> ${clienteIdentificacion} - ${clienteNombre}</div>
                <div><b>Autorización:</b> ${authorizationCode}</div>
                <div><b>User ID cajero:</b> ${userId}</div>
                <div><b>Turno ID:</b> ${turnoId}</div>
              </div>
            `,
            showCancelButton: true,
            focusCancel: true,
            confirmButtonText: 'Sí, regularizar',
            cancelButtonText: 'Cancelar',
            confirmButtonColor: '#b02a37',
            cancelButtonColor: '#6c757d',
            customClass: {
              popup: 'swalAlert'
            }
          }).then((result) => {
            if (result.isConfirmed) {
              confirmacionAprobada = true;
              form.requestSubmit(regularizarButton);
            }
          });
        });
      }

      form.addEventListener('submit', function (event) {
        const submitter = event.submitter || document.activeElement;
        const buttons = form.querySelectorAll('button[type="submit"]');

        buttons.forEach((button) => {
          button.disabled = true;

          if (button === submitter) {
            button.dataset.originalText = button.dataset.originalText || button.innerHTML;
            button.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>' + (button.dataset.loadingText || 'Procesando...');
          }
        });

        const inputs = form.querySelectorAll('input, select, textarea');
        inputs.forEach((input) => {
          input.readOnly = true;
        });

        showLoader(submitter?.dataset.loaderMessage || 'Procesando solicitud...');
      });
    });
  });
</script>
@endpushOnce
