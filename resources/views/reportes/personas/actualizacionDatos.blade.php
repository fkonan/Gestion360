<!-- Modal reporte firma politica de equipaje conductores -->
<div class="container-fluid p-2">
  <form id="actDatosForm" action="{{ route('filtrar.actDatos') }}" method="POST"
    enctype="multipart/form-data" onsubmit="deshabilitarSubmit(this)">

    @csrf

    <div class="row mb-3 mx-1 pb-2">
      <div class="col-12 col-md-6 mt-3">
        <label for="filtro" class="form-label">Seleccione un filtro de busqueda</label>
        <select name="filtro" id="filtro" class="form-select">
          <option value="todos">Todos los empleados</option>
          <option value="conductores">Todos los conductores</option>
          <option value="carga">Conductores carga</option>
          <option value="pasajes">Conductores pasajes</option>

        </select>
        <span class="error text-danger fw-bold" id="error-filtro"></span>
      </div>
    </div>

    <hr class="p-2">

    <div>
      <button type="submit" class="btn btn-success">Buscar</button>
      <button type="button" class="btn btn-dark" data-bs-dismiss="modal">Cancelar</button>
    </div>
  </form>
</div>
