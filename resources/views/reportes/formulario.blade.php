<!-- Modal formulario reporte -->
<form
  id="formReporte"
  action="{{ route('reportes.show') }}"
  method="GET"
  {{-- onsubmit="deshabilitarSubmit(this)" --}}>

  <input type="hidden" name="id" value="{{ $id }}">

  <div class="p-4">
    <!-- Mensaje informativo sobre el rango de fechas -->
    <div class="alert alert-info mb-4" role="alert">
      <div class="d-flex align-items-center">
        <i class="fas fa-info-circle me-2 text-info"></i>
        <div>
          <strong>Importante:</strong> El rango de fechas para la búsqueda no puede ser mayor a 1 mes (30 días).
          <br><small class="text-muted">Seleccione fechas dentro de este límite para generar el reporte correctamente.</small>
        </div>
      </div>
    </div>

    <div class="row mb-3 mx-1 pb-2">
      @if(in_array('paramFechaInicio', $parametros))
      <div class="col-md-6">
        <label for="fechaInicio" class="form-label">Fecha Inicial *</label>
        <input type="date" name="fechaInicio" id="fechaInicio" class="form-control" required>
        <span class="error text-danger fw-bold" id="error-fechaInicio"></span>
      </div>
      @endif
      @if(in_array('paramFechaFin', $parametros))
      <div class="col-md-6">
        <label for="fechaFin" class="form-label">Fecha Final *</label>
        <input type="date" name="fechaFin" id="fechaFin" class="form-control" required>
        <span class="error text-danger fw-bold" id="error-fechaFin"></span>
      </div>
      @endif
      @if(in_array('paramAgencia', $parametros) && $origen_db == "FICS")
      <div class="col-md-6 mt-3">
        <label for="agencia" class="form-label">Agencia *</label>
        <select class="form-select select2" id="agencia" name="agencia">
          <option value="null">TODOS</option>
          @foreach($agenciasFICS as $agencia)
          <option value="{{ $agencia->codigo }}">
            {{ $agencia->nombre }}
          </option>
          @endforeach
        </select>
        <span class="error text-danger fw-bold" id="error-agencia"></span>
      </div>
      @endif
      @if(in_array('paramTipoFiltro', $parametros))
      <div class="col-12 col-md-6 mt-3">
        <label class="form-label d-block">Filtrar por</label>
        <div class="form-check form-check-inline">
          <input class="form-check-input" type="radio" name="tipoFiltro" id="filtroCedula" value="identificacion" onchange="habilitarInputFiltro(this)">
          <label class="form-check-label" for="filtroCedula">Cédula</label>
        </div>
        <div class="form-check form-check-inline">
          <input class="form-check-input" type="radio" name="tipoFiltro" id="filtroCodigo" value="codigo" onchange="habilitarInputFiltro(this)">
          <label class="form-check-label" for="filtroCodigo">Código</label>
        </div>
        <div class="form-check form-check-inline">
          <input class="form-check-input" type="radio" name="tipoFiltro" id="filtroTodos" value="todos" checked onchange="habilitarInputFiltro(this)">
          <label class="form-check-label" for="filtroTodos">Todos</label>
        </div>
        <span class="error text-danger fw-bold" id="error-tipoFiltro"></span>
      </div>
      @endif

      @if(in_array('paramValorFiltro', $parametros))
      <div class="col-12 col-md-6 mt-3" id="parametro" style="display: none">
        <label for="valorFiltro" class="form-label">Ingrese el parametro</label>
        <input type="text" name="valorFiltro" id="valorFiltro" class="form-control">
        <span class="error text-danger fw-bold" id="error-valorFiltro"></span>
      </div>
      @endif
    </div>

    <div class="d-flex justify-content-end ms-3 pt-2 gap-2">
      <button type="submit" class="btn btn-success">Consultar</button>
      <button type="button" class="btn btn-dark" data-bs-dismiss="modal">Cancelar</button>
    </div>
  </div>
</form>
