<!-- Modal reporte firma politica de equipaje conductores -->
<div class="container-fluid p-2">
  <form id="firmaPoliticasForm" action="{{ route('filtrar.firmaPoliticas') }}" method="POST" enctype="multipart/form-data"
    onsubmit="deshabilitarSubmit(this)">

    @csrf
    <div class="row mb-3 mx-1 pb-2">
      <div class="col-md-6">
        <label for="fechaInicio" class="form-label">Fecha Inicial *</label>
        <input type="date" name="fechaInicio" id="fechaInicio" class="form-control" required>
        <span class="error text-danger fw-bold" id="error-fechaInicio"></span>
      </div>

      <div class="col-md-6">
        <label for="fechaFin" class="form-label">Fecha Final *</label>
        <input type="date" name="fechaFin" id="fechaFin" class="form-control" required>
        <span class="error text-danger fw-bold" id="error-fechaFin"></span>
      </div>
    </div>

    <div class="row mb-3 mx-1 pb-2">
      <div class="col-md-6">
        <label class="form-label d-block">Filtrar por</label>
        <div class="form-check form-check-inline">
          <input class="form-check-input" type="radio" name="tipoFiltro" id="filtroCedula" value="identificacion"
            onchange="habilitarInputFiltro(this)">
          <label class="form-check-label" for="filtroCedula">Cédula</label>
        </div>
        <div class="form-check form-check-inline">
          <input class="form-check-input" type="radio" name="tipoFiltro" id="filtroCodigo" value="codigo"
            onchange="habilitarInputFiltro(this)">
          <label class="form-check-label" for="filtroCodigo">Código</label>
        </div>
        <div class="form-check form-check-inline">
          <input class="form-check-input" type="radio" name="tipoFiltro" id="filtroTodos" value="todos" checked
            onchange="habilitarInputFiltro(this)">
          <label class="form-check-label" for="filtroTodos">Todos</label>
        </div>
        <span class="error text-danger fw-bold" id="error-tipoFiltro"></span>
      </div>
      <div class="col-md-6" id="parametro" style="display: none">
        <label for="valorFiltro" class="form-label">Ingrese el parametro</label>
        <input type="text" name="valorFiltro" id="valorFiltro" class="form-control">
        <span class="error text-danger fw-bold" id="error-valorFiltro"></span>
      </div>
    </div>

    <hr class="p-2">

    <div>
      <button type="submit" class="btn btn-success">Buscar</button>
      <button type="button" class="btn btn-dark" data-bs-dismiss="modal">Cancelar</button>
    </div>
  </form>
</div>
