<!-- Modal para validar pago -->
<div class="container-fluid p-0">
  <form
    id="formValidar"
    action="{{ route('pagosConvenios.pagar') }}"
    method="POST"
    enctype="multipart/form-data"
    onsubmit="deshabilitarSubmit(this)">

    @csrf
    <input type="hidden" name="uuid" value="{{ $uuid }}">

    <div class="p-4">
      <!-- Sección: Datos del cliente -->
      <h5 class="mb-3 text-primary fw-bold">Validar información del cliente</h5>

      <div class="card shadow-sm mb-4">
        <div class="card-body">
          <div class="row mb-2">
            <div class="col-sm-4 fw-semibold text-muted">Nombre principal:</div>
            <div class="col-sm-8">{{ $clienteData['nombre'] }}</div>
          </div>
          <div class="row mb-2">
            <div class="col-sm-4 fw-semibold text-muted">Identificación principal:</div>
            <div class="col-sm-8">{{ $clienteData['identificacion'] }}</div>
          </div>
          <div class="row mb-2">
            <div class="col-sm-4 fw-semibold text-muted">Valor total a pagar:</div>
            <div class="col-sm-8 text-success fw-bold">
              ${{ number_format($respuesta['additionalData']['saldo'], 0, ',', '.') }}
            </div>
          </div>
        </div>
      </div>

      <!-- Sección: Información de pago -->
      <h5 class="mb-3 text-primary fw-bold">Información de pago</h5>

      <div class="card shadow-sm mb-4">
        <div class="card-body">
          <div class="row g-3 align-items-end">
            <div class="col-md-6">
              <label for="registrar_nombre" class="form-label fw-semibold text-muted">
                Registrar pago a nombre de
              </label>
              <select class="form-select" id="registrar_nombre" name="registrar_nombre" required>
                <option value="" selected disabled>Seleccione una opción</option>
                <option value="{{ $clienteData['nombre'] }}">{{ $clienteData['nombre'] }}</option>

              </select>
            </div>

            <div class="col-md-6">
              <label for="telefono" class="form-label fw-semibold text-muted">Teléfono de contacto</label>
              <input
                type="number"
                class="form-control"
                id="telefono"
                name="telefono"
                placeholder="Ingrese el número de teléfono"
                required>
            </div>
          </div>
        </div>
      </div>

      <!-- Acciones -->
      <div class="text-end">
        <button type="submit" class="btn btn-success me-2">
          <i class="fas fa-check-circle me-1"></i> Pagar
        </button>
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
          Cancelar
        </button>
      </div>
    </div>
  </form>
</div>
