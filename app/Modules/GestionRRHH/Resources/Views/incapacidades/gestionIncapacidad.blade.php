<!-- Modal Gestion Incapacidad -->
<div class="container-fluid bg-white rounded">
  <form
    id="formGestionIncapacidad"
    action="{{ route('gestion-empleado.incapacidades.estado', ['id' => $incapacidad->IdIncapacidad]) }}"
    method="POST"
    enctype="multipart/form-data">

    @csrf
    @method('PUT')
    <div class="row g-3 p-4">
      <div class="col-md-2">
        <label for="IdIncapacidad" class="form-label">Radicado</label>
        <input type="text" class="form-control" id="IdIncapacidad" name="IdIncapacidad" value="{{ $incapacidad->IdIncapacidad}}" disabled>
        <span class="error text-danger fw-bold" id="error-IdIncapacidad"></span>
      </div>
      <div class="col-md-6">
        <label for="IncPerNom" class="form-label">Persona</label>
        <input type="text" class="form-control" id="IncPerNom" name="IncPerNom" value="{{ $incapacidad->IncPerNom}}" disabled>
        <span class="error text-danger fw-bold" id="error-IncPerNom"></span>
      </div>

      <div class="col-md-4">
        <h6 class="fw-bold">Respuesta Radicado</h6>
        <div class="mt-2">
          <div class="form-check form-check-inline d-flex align-items-center">
            <input class="form-check-input"
              type="radio"
              id="IncapacidadEstadoAprobado"
              name="IncapacidadEstado"
              value="APROBADO"
              checked="checked"
              onchange="mostrarObservacion()" />
            <label class="form-check-label" for="IncapacidadEstadoAprobado">Aprobado</label>
          </div>
          <div class="form-check form-check-inline d-flex align-items-center">
            <input class="form-check-input"
              type="radio" id="IncapacidadEstadoRechazado"
              name="IncapacidadEstado"
              value="RECHAZADO"
              onchange="mostrarObservacion()" />
            <label class="form-check-label" for="IncapacidadEstadoRechazado">Rechazado</label>
          </div>
        </div>
      </div>
    </div>

    <div class="row g-3 px-4 pb-4">
      <div class="col-md-12" id="observacionDiv" style="display: none">
        <label for="Observacion" class="form-label">Observacion</label>
        <textarea class="form-control" id="Observacion" name="Observacion" rows="3" placeholder="Describa el motivo del rechazo..."></textarea>
        <span class="error text-danger fw-bold" id="error-Observacion"></span>
      </div>
    </div>

    <div class="text-start m-4">
      <button type="submit" class="btn btn-success">Guardar</button>
      <button type="button" class="btn btn-dark" data-bs-dismiss="modal">Cancelar</button>
    </div>
  </form>
</div>
