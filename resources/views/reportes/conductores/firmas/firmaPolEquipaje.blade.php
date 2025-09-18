<!-- Modal reporte firma politica de equipaje conductores -->
<div class="container-fluid p-2">
  <form
    id="firmaEquipajeForm"
    action="{{  route('filtrar.firmaEquipaje')  }}"
    method="POST"
    enctype="multipart/form-data"
    onsubmit="deshabilitarSubmit(this)">

    @csrf
    <div class="row mb-2">
      <p class="text-justify lh-sm">Haga una búsqueda parametrizada de los conductores que han firmado y aceptado las diferentes políticas, usted podrá consultar por número de identificación, código y/o todos los conductores.</p>
    </div>

    <div class="row mb-3 mx-1">
      <label for="codigo" class="form-label">Código</label>
      <input type="text" name="codigo" id="codigo" class="form-control" placeholder="Código">
      <span class="error text-danger fw-bold" id="error-codigo"></span>
    </div>

    <div class="row mb-3 mx-1">
      <label for="identificacion" class="form-label">Número de identificación</label>
      <input type="number" name="identificacion" id="identificacion" class="form-control" placeholder="Número de identificación">
      <span class="error text-danger fw-bold" id="error-identificacion"></span>
    </div>

    <hr class="p-2">

    <div>
      <button type="submit" class="btn btn-success">Aceptar</button>
      <button type="button" class="btn btn-dark" data-bs-dismiss="modal">Cancelar</button>
    </div>
  </form>
</div>
