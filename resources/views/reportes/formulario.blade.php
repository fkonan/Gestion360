<!-- Modal formulario reporte -->
<form id="formReporte" action="{{ route('reportes.show') }}" method="GET" {{-- onsubmit="deshabilitarSubmit(this)" --}}>

  <input type="hidden" name="id" value="{{ $id }}">

  <div class="p-4">
    @if($parametros && !in_array('paramFirmaPoliticas', $parametros))
    <!-- Mensaje informativo sobre el rango de fechas -->
    <div class="alert alert-info mb-4" role="alert">
      <div class="d-flex align-items-center">
        <i class="fas fa-info-circle me-2 text-info"></i>
        <div>
          <strong>Importante:</strong> El rango de fechas para la búsqueda no puede ser mayor a 1 mes (30 días).
          <br><small class="text-muted">Seleccione fechas dentro de este límite para generar el reporte
            correctamente.</small>
        </div>
      </div>
    </div>
    @else
    <!-- Mensaje cuando el reporte no requiere parámetros -->
    <div class="alert alert-info mb-4" role="alert">
      <div class="d-flex align-items-center">
        <i class="fas fa-check-circle me-2 text-info"></i>
        <div>
          <strong>Nota:</strong> Este reporte no requiere parámetros para su consulta.
          <br><small class="text-muted">Puede generarlo directamente sin seleccionar opciones adicionales.</small>
        </div>
      </div>
    </div>
    @endif

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

      @if(in_array('paramCuenta', $parametros))
      <div class="col-12 col-md-6 mt-3">
        <label for="cuenta" class="form-label">Numero de cuenta *</label>
        <input type="number" name="cuenta" id="cuenta" class="form-control" required>
        <span class="error text-danger fw-bold" id="error-cuenta"></span>
        <small class="text-muted">Digite el número largo de la cuenta LOGTRANS</small>
      </div>
      @endif

      @if(in_array('paramCategoriaVehiculo', $parametros))
      <div class="col-md-6 mt-3">
        <label for="categoriaVehiculo" class="form-label">Categoria Vehiculo</label>
        <select class="form-select select2" id="categoriaVehiculo" name="categoriaVehiculo">
          <option value="null">TODAS</option>
          <option value="TURNADOR">TURNADOR</option>
          @foreach($categorias as $categoria)
          <option value="{{ $categoria->servicio }}">
            {{ $categoria->servicio }}
          </option>
          @endforeach
        </select>
        <span class="error text-danger fw-bold" id="error-agencia"></span>
      </div>
      @endif

      @if(in_array('paramAgencia', $parametros))
      <div class="col-md-6 mt-3">
        <label for="agencia" class="form-label">Agencia *</label>
        <select class="form-select select2" id="agencia" name="agencia">
          <option value="null">TODAS</option>
          @foreach($agencias as $agencia)
          <option value="{{ $agencia->codigo }}">
            {{ $agencia->agencia }}
          </option>
          @endforeach
        </select>
        <span class="error text-danger fw-bold" id="error-agencia"></span>
      </div>
      @endif

      @if(in_array('paramIdentificacion', $parametros))
      <div class="col-12 col-md-6 mt-3">
        <label for="identificacion" class="form-label">Número de documento</label>
        <input type="number" name="identificacion" id="identificacion" class="form-control">
        <span class="error text-danger fw-bold" id="error-identificacion"></span>
      </div>
      @endif

      @if(in_array('paramTipoFiltro', $parametros))
      <div class="col-12 col-md-6 mt-3">
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
      @endif

      @if(in_array('paramValorFiltro', $parametros))
      <div class="col-12 col-md-6 mt-3" id="parametro" style="display: none">
        <label for="valorFiltro" class="form-label">Ingrese el parametro</label>
        <input type="text" name="valorFiltro" id="valorFiltro" class="form-control">
        <span class="error text-danger fw-bold" id="error-valorFiltro"></span>
      </div>
      @endif

      @if(in_array('paramPlacaVehiculo', $parametros))
      <div class="col-12 col-md-6 mt-3" id="parametro">
        <label for="placaVehiculo" class="form-label">
          Número de la placa de vehículo
        </label>
        <input type="text" name="placaVehiculo" id="placaVehiculo" class="form-control"
          pattern="[A-Za-z]{3}[0-9]{2}[A-Za-z0-9]{1}"
          title="Ingrese la placa en formato ABC123 o ABC12A, sin espacios ni rayas">
        <span class="error text-danger fw-bold" id="error-placaVehiculo"></span>
        <small class="text-muted">Formato: <strong>ABC123</strong> (sin espacios, rayas ni puntos)</small>
      </div>
      @endif

      <!-- Parametro concepto carga (reporte acuerdos)-->
      @if(in_array('paramConceptoCarga', $parametros))
      <div class="col-12 col-md-6 mt-3" id="parametro">
        <label for="conceptoCarga" class="form-label">Seleccione el concepto</label>
        <select name="conceptoCarga" id="conceptoCarga" class="form-select">
          <option value="24">Remesas</option>
          <option value="8">Seguro de carga</option>
          <option value="53812152">Descuento comercial</option>
        </select>
        <span class="error text-danger fw-bold" id="error-conceptoCarga"></span>
      </div>
      @endif

      @if(in_array('paramCargo', $parametros))
      <div class="col-12 col-md-6 mt-3" id="parametro">
        <label for="cargo" class="form-label">Seleccione un filtro de busqueda</label>
        <select name="cargo" id="cargo" class="form-select">
          <option value="todos">Todos los empleados</option>
          <option value="conductores">Todos los conductores</option>
          <option value="carga">Conductores carga</option>
          <option value="pasajes">Conductores pasajes</option>
        </select>
        <span class="error text-danger fw-bold" id="error-cargo"></span>
      </div>
      @endif

      @if(in_array('paramPersonaId', $parametros))
      <div class="col-12 col-md-6 mt-3">
        <label for="personaId" class="form-label">Ingrese el NIT</label>
        <input type="string" name="personaId" id="personaId" class="form-control">
        <span class="error text-danger fw-bold" id="error-personaId"></span>
      </div>
      @endif

      @if(in_array('paramTerceroId', $parametros))
      <div class="col-12 col-md-6 mt-3">
        <label for="terceroId" class="form-label">Ingrese el tercero</label>
        <input type="string" name="terceroId" id="terceroId" class="form-control">
        <span class="error text-danger fw-bold" id="error-terceroId"></span>
        <small class="text-muted">Este parametro es opcional</small>
      </div>
      @endif

      <!-- Parametros estado (ACTIVO,INACTIVO) -->
      @if(in_array('paramEstado', $parametros))
      <div class="col-12 col-md-6 mt-3" id="parametro">
        <label for="estado" class="form-label">Seleccione el estado</label>
        <select name="estado" id="estado" class="form-select">
          <option value="todos">TODOS</option>
          <option value="ACTIVO">ACTIVOS</option>
          <option value="INACTIVO">INACTIVOS</option>
        </select>
        <span class="error text-danger fw-bold" id="error-estado"></span>
      </div>
      @endif

      @if(in_array('paramNombre', $parametros))
      <div class="col-12 col-md-6 mt-3">
        <label for="nombre" class="form-label">Nombre persona</label>
        <input type="string" name="nombre" id="nombre" class="form-control">
        <span class="error text-danger fw-bold" id="error-nombre"></span>
      </div>
      @endif

    </div>

    <div class="d-flex justify-content-end ms-3 pt-2 gap-2">
      <button type="submit" class="btn btn-success">Consultar</button>
      <button type="button" class="btn btn-dark" data-bs-dismiss="modal">Cancelar</button>
    </div>
  </div>
</form>
