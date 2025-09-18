<!-- Modal solicitud nuevo ingreso empleado -->
<form
  id="formNuevoIngreso"
  action="{{ route('gestion.nuevoIngreso') }}"
  method="POST"
  enctype="multipart/form-data"
  onsubmit="deshabilitarSubmit(this)">

  @csrf
  <div class="p-3" style="max-width: 500px;">

    <!-- Mensaje informativo -->
    <div class="alert alert-info mb-3 d-flex align-items-center" role="alert">
      <i class="fas fa-info-circle me-2 text-info"></i>
      <div>
        <strong>Importante:</strong> Ingrese el número de identificación para verificar los datos del empleado.
      </div>
    </div>

    <!-- Número de identificación -->
    <div class="mb-3">
      <label for="identificacion" class="form-label">Número de identificación <span class="text-danger">*</span></label>
      <input
        type="number"
        name="identificacion"
        id="identificacion"
        class="form-control"
        required
        data-url="{{ route('persona.buscar', ['identificacion' => 'ID_PLACEHOLDER']) }}"
        oninput="buscarPersona(this)">
      <span class="error text-danger fw-bold" id="error-identificacion"></span>
    </div>

    <!-- Contenedor de información (solo se muestra cuando hay datos) -->
    <div id="infoPersona" class="border rounded p-3 mb-3 bg-light" style="display: none;">
      <h6 class="text-muted mb-3">
        <i class="fas fa-user me-2"></i>Información de la persona:
      </h6>

      <!-- Nombres (solo informativo) -->
      <div class="mb-2">
        <label class="form-label text-muted small">Nombres</label>
        <div
          id="nombresInfo"
          class="form-control bg-white border-0 text-dark fw-medium"
          style="background-color: #f8f9fa !important;">
        </div>
      </div>

      <!-- Apellidos (solo informativo) -->
      <div class="mb-2">
        <label class="form-label text-muted small">Apellidos</label>
        <div
          id="apellidosInfo"
          class="form-control bg-white border-0 text-dark fw-medium"
          style="background-color: #f8f9fa !important;">
        </div>
      </div>

      <!-- Correo electrónico (solo informativo) -->
      <div class="mb-0">
        <label class="form-label text-muted small">Correo electrónico</label>
        <div
          id="emailInfo"
          class="form-control bg-white border-0 text-dark fw-medium"
          style="background-color: #f8f9fa !important;">
        </div>
      </div>
    </div>

    <!-- Mensaje cuando no se encuentran datos -->
    <div id="noDataMessage" class="alert alert-warning" style="display: none;">
      <i class="fas fa-exclamation-triangle me-2"></i>
      No se encontraron datos para este número de identificación.
    </div>

    <!-- Botones -->
    <div class="pt-3">
      <button type="submit" class="btn btn-success">
        <i class="fas fa-plus me-2"></i>Registrar Nuevo Ingreso
      </button>
      <button type="button" class="btn btn-dark" data-bs-dismiss="modal">
        <i class="fas fa-times me-2"></i>Cancelar
      </button>
    </div>

  </div>
</form>
