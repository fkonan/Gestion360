<!-- Modal registro descanso de conductores -->
<div class="container-fluid p-2">
  <form
    id="preoperacionalForm"
    action="{{ route('conductor.preoperacional.novedad') }}"
    method="POST"
    enctype="multipart/form-data"
    onsubmit="deshabilitarSubmit(this)">

    @csrf
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
    <div id="infoPersona" class="border rounded p-3 mb-3" style="display: none;">
      <h6 class="text-muted mb-3">
        <i class="fas fa-user me-2"></i>Información de la persona:
      </h6>

      <!-- Nombres (solo informativo) -->
      <div class="mb-2 d-flex align-items-center">
        <label class="form-label text-muted small me-2 mb-0">Nombres:</label>
        <span id="nombresInfo" class="fw-medium"></span>
      </div>

      <!-- Apellidos (solo informativo) -->
      <div class="mb-2 d-flex align-items-center">
        <label class="form-label text-muted small me-2 mb-0">Apellidos:</label>
        <span id="apellidosInfo" class="fw-medium"></span>
      </div>
    </div>

    <!-- Mensaje cuando no se encuentran datos -->
    <div id="noDataMessage" class="alert alert-warning" style="display: none;">
      <i class="fas fa-exclamation-triangle me-2"></i>
      No se encontraron datos para este número de identificación.
    </div>

    <!-- Campo para adjuntar archivo -->
    <div class="mb-3">
      <label for="adjunto" class="form-label">
        Adjuntar archivo
      </label>
      <input
        type="file"
        name="adjunto"
        id="adjunto"
        class="form-control"
        accept=".jpg,.jpeg,.png">
      <div class="form-text">Formatos permitidos: jpg, jpeg, png</div>
      <span class="error text-danger fw-bold" id="error-adjunto"></span>
    </div>


    <div id="opciones">
      <hr class="p-2">
      <button type="submit" class="btn btn-success">Aceptar</button>
      <button type="button" class="btn btn-dark" data-bs-dismiss="modal">Cancelar</button>
    </div>
  </form>
</div>
