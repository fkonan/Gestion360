<!-- Modal actualizar estado conductor -->
<div class="container-fluid p-2 bg-white">
  <form
    id="estadoConductorForm"
    action="{{  route('conductor.actualizarEstado')  }}"
    method="POST"
    enctype="multipart/form-data"
    onsubmit="deshabilitarSubmit(this)">

    @csrf
    @method('PUT')
    <div class="row mb-3 mx-1">
      <label for="legajo" class="form-label">Legajo *</label>
      <input type="text" name="legajo" id="legajo" class="form-control" required>
      <span class="error text-danger fw-bold" id="error-legajo"></span>
    </div>

    <hr class="p-2">

    <div>
      <button type="submit" class="btn btn-success">Actualizar Estado</button>
      <button type="button" class="btn btn-dark" data-bs-dismiss="modal">Cancelar</button>
    </div>
  </form>
</div>
