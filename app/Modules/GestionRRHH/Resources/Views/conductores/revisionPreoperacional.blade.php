<!-- Modal desbloqueo preoperacional -->
<div class="container-fluid p-2">
  <form
    id="preoperacionalForm"
    action="{{ route('conductor.preoperacional.novedad') }}"
    method="POST"
    onsubmit="deshabilitarSubmit(this)">

    @csrf

    <div class="alert bg-light border text-dark mb-3" role="alert">
      <div class="d-flex align-items-start">
        <i class="fas fa-unlock-alt mt-1 me-2 text-secondary"></i>
        <div>
          <strong>Desbloqueo preoperacional de conductor</strong><br>
          Este proceso levanta el bloqueo preoperacional para la identificacion consultada.
        </div>
      </div>
    </div>

    <div class="mb-3">
      <label for="identificacion" class="form-label">Numero de identificacion <span class="text-danger">*</span></label>
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

    <div id="infoPersona" class="border rounded p-3 mb-3" style="display: none;">
      <h6 class="text-muted mb-3">
        <i class="fas fa-user-check me-2"></i>Datos encontrados para verificacion:
      </h6>

      <div class="mb-2">
        <label class="form-label text-muted small mb-1">Nombres</label>
        <div id="nombresInfo" class="form-control bg-white"></div>
      </div>

      <div class="mb-0">
        <label class="form-label text-muted small mb-1">Apellidos</label>
        <div id="apellidosInfo" class="form-control bg-white"></div>
      </div>
    </div>

    <div id="noDataMessage" class="alert alert-warning" style="display: none;">
      <i class="fas fa-exclamation-triangle me-2"></i>
      No se encontraron datos para este numero de identificacion.
    </div>

    <div class="mb-3">
      <label for="observacion" class="form-label">Observación <span class="text-danger">*</span></label>
      <textarea
        name="observacion"
        id="observacion"
        class="form-control"
        rows="3"
        maxlength="500"
        placeholder="Escriba una observacion del desbloqueo"
        required></textarea>
      <span class="error text-danger fw-bold" id="error-observacion"></span>
    </div>

    <div id="opciones">
      <hr class="p-2">
      <button type="submit" class="btn btn-success">
        <i class="fas fa-unlock me-1"></i>Desbloquear conductor
      </button>
      <button type="button" class="btn btn-dark" data-bs-dismiss="modal">Cancelar</button>
    </div>
  </form>
</div>
