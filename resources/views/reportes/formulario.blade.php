@php
function param($parametros, $key) {
  return $parametros[$key] ?? null;
}

function req($parametros, $key) {
  return isset($parametros[$key]) && !empty($parametros[$key]['requerido']);
}

function isOptionalParam($parametros, $key) {
  return isset($parametros[$key]) && empty($parametros[$key]['requerido']);
}

@endphp

<!-- Modal formulario reporte -->
<form id="formReporte" action="{{ route('reportes.show') }}" method="GET">
  <input type="hidden" name="id" value="{{ $id }}">

  <div class="p-4">

    <div class="alert alert-info mb-4">
      <strong>Nota:</strong> {{ $mensajeCabecera }}
    </div>

    <div class="row mb-3 mx-1 pb-2">

      {{-- Fecha Inicio --}}
      @if(param($parametros,'paramFechaInicio'))
      <div class="col-md-6">
        <label class="form-label">Fecha Inicial @if(req($parametros,'paramFechaInicio'))* @endif</label>
        <div class="input-group">
          <span class="input-group-text"><i class="bi bi-calendar-date"></i></span>
          <input type="date" name="fechaInicio" class="form-control" @if(req($parametros,'paramFechaInicio')) required @endif>
        </div>
      </div>
      @endif

      {{-- Fecha Fin --}}
      @if(param($parametros,'paramFechaFin'))
      <div class="col-md-6">
        <label class="form-label">Fecha Final @if(req($parametros,'paramFechaFin'))* @endif</label>
        <div class="input-group">
          <span class="input-group-text"><i class="bi bi-calendar-date"></i></span>
          <input type="date" name="fechaFin" class="form-control" @if(req($parametros,'paramFechaFin')) required @endif>
        </div>
      </div>
      @endif

      {{-- Cuenta --}}
      @if(param($parametros,'paramCuenta'))
      <div class="col-md-6 mt-3">
        <label class="form-label">Número de cuenta @if(req($parametros,'paramCuenta'))* @endif</label>
        <div class="input-group">
          <span class="input-group-text"><i class="bi bi-credit-card"></i></span>
          <input type="number" name="cuenta" class="form-control" @if(req($parametros,'paramCuenta')) required @endif>
        </div>
        <small class="text-muted">Digite el número largo de la cuenta LOGTRANS</small>
      </div>
      @endif

      {{-- Categoría Vehículo --}}
      @if(param($parametros,'paramCategoriaVehiculo'))
      <div class="col-md-6 mt-3">
        <label class="form-label">Categoría Vehículo</label>
        <div class="input-group">
          <select class="form-select select2" name="categoriaVehiculo">
            <option value="null">TODAS</option>
            <option value="TURNADOR">TURNADOR</option>
            @foreach($categorias as $categoria)
            <option value="{{ $categoria->servicio }}">{{ $categoria->servicio }}</option>
            @endforeach
          </select>
        </div>
      </div>
      @endif

      {{-- Agencia --}}
      @if(param($parametros,'paramAgencia'))
      <div class="col-md-6 mt-3">
        <label class="form-label">Agencia @if(req($parametros,'paramAgencia'))* @endif</label>
        <div class="input-group">
          <select class="form-select select2" name="agencia" @if(req($parametros,'paramAgencia')) required @endif>
            <option value="null">TODAS</option>
            @foreach($agencias as $agencia)
            <option value="{{ $agencia->codigo }}">{{ $agencia->agencia }}</option>
            @endforeach
          </select>
        </div>
      </div>
      @endif

      {{-- Identificación --}}
      @if(param($parametros,'paramIdentificacion'))
      <div class="col-md-6 mt-3">
        <label class="form-label">Número de documento @if(req($parametros,'paramIdentificacion'))* @endif</label>
        <div class="input-group">
          <span class="input-group-text"><i class="bi bi-person-badge"></i></span>
          <input type="number" name="identificacion" class="form-control" @if(req($parametros,'paramIdentificacion')) required @endif>
        </div>

        @if(isOptionalParam($parametros,'paramIdentificacion'))
          <small class="text-muted fst-italic mt-1 d-block">Campo opcional</small>
        @endif
      </div>
      @endif

      {{-- Tipo filtro --}}
      @if(param($parametros,'paramTipoFiltro'))
      <div class="col-md-6 mt-3">
        <label class="form-label">Filtrar por</label>
        <div class="btn-group w-100" role="group">
          <input type="radio" class="btn-check" name="tipoFiltro" value="identificacion" id="filtroCedula" autocomplete="off" onchange="habilitarInputFiltro(this)">
          <label class="btn btn-outline-primary" for="filtroCedula">Cédula</label>

          <input type="radio" class="btn-check" name="tipoFiltro" value="codigo" id="filtroCodigo" autocomplete="off" onchange="habilitarInputFiltro(this)">
          <label class="btn btn-outline-primary" for="filtroCodigo">Código</label>

          <input type="radio" class="btn-check" name="tipoFiltro" value="todos" id="filtroTodos" autocomplete="off" checked onchange="habilitarInputFiltro(this)">
          <label class="btn btn-outline-primary" for="filtroTodos">Todos</label>
        </div>
      </div>
      @endif

      {{-- Valor filtro --}}
      @if(param($parametros,'paramValorFiltro'))
      <div class="col-md-6 mt-3" id="parametro" style="display:none">
        <label class="form-label">Ingrese el parámetro</label>
        <div class="input-group">
          <span class="input-group-text"><i class="bi bi-search"></i></span>
          <input type="text" name="valorFiltro" class="form-control" @if(req($parametros,'paramValorFiltro')) required @endif>
        </div>
      </div>
      @endif

      {{-- Placa vehículo --}}
      @if(param($parametros,'paramPlacaVehiculo'))
      <div class="col-md-6 mt-3">
        <label class="form-label">Placa del vehículo</label>
        <div class="input-group">
          <span class="input-group-text"><i class="bi bi-car-front"></i></span>
          <input type="text" name="placaVehiculo" class="form-control" pattern="[A-Za-z]{3}[0-9]{2}[A-Za-z0-9]{1}" title="Formato ABC123 o ABC12A" @if(req($parametros,'paramPlacaVehiculo')) required @endif>
        </div>

        @if(isOptionalParam($parametros,'paramPlacaVehiculo'))
          <small class="text-muted fst-italic mt-1 d-block">Campo opcional</small>
        @endif
      </div>
      @endif

      {{-- Concepto carga --}}
      @if(param($parametros,'paramConceptoCarga'))
      <div class="col-md-6 mt-3">
        <label class="form-label">Concepto carga</label>
        <div class="input-group">
          <span class="input-group-text"><i class="bi bi-box-seam"></i></span>
          <select name="conceptoCarga" class="form-select">
            <option value="24">Remesas</option>
            <option value="8">Seguro de carga</option>
            <option value="53812152">Descuento comercial</option>
          </select>
        </div>
      </div>
      @endif

      {{-- Cargo --}}
      @if(param($parametros,'paramCargo'))
      <div class="col-md-6 mt-3">
        <label class="form-label">Cargo</label>
        <div class="input-group">
          <span class="input-group-text"><i class="bi bi-person-workspace"></i></span>
          <select name="cargo" class="form-select">
            <option value="todos">Todos los empleados</option>
            <option value="conductores">Todos los conductores</option>
            <option value="carga">Conductores carga</option>
            <option value="pasajes">Conductores pasajes</option>
          </select>
        </div>
      </div>
      @endif

      {{-- Persona ID --}}
      @if(param($parametros,'paramPersonaId'))
      <div class="col-md-6 mt-3">
        <label class="form-label">NIT</label>
        <div class="input-group">
          <span class="input-group-text"><i class="bi bi-person-vcard"></i></span>
          <input type="text" name="personaId" class="form-control" @if(req($parametros,'paramPersonaId')) required @endif>
        </div>

        @if(isOptionalParam($parametros,'paramPersonaId'))
          <small class="text-muted fst-italic mt-1 d-block">Campo opcional</small>
        @endif
      </div>
      @endif

      {{-- Tercero ID --}}
      @if(param($parametros,'paramTerceroId'))
      <div class="col-md-6 mt-3">
        <label class="form-label">Tercero</label>
        <div class="input-group">
          <span class="input-group-text"><i class="bi bi-person-lines-fill"></i></span>
          <input type="text" name="terceroId" class="form-control">
        </div>

        @if(isOptionalParam($parametros,'paramTerceroId'))
          <small class="text-muted fst-italic mt-1 d-block">Campo opcional</small>
        @endif
      </div>
      @endif

      {{-- Estado --}}
      @if(param($parametros,'paramEstado'))
      <div class="col-md-6 mt-3">
        <label class="form-label">Estado</label>
        <div class="input-group">
          <span class="input-group-text"><i class="bi bi-toggle-on"></i></span>
          <select name="estado" class="form-select">
            <option value="todos">TODOS</option>
            <option value="ACTIVO">ACTIVOS</option>
            <option value="INACTIVO">INACTIVOS</option>
          </select>
        </div>
      </div>
      @endif

      {{-- Nombre --}}
      @if(param($parametros,'paramNombre'))
      <div class="col-md-6 mt-3">
        <label class="form-label">Nombre</label>
        <div class="input-group">
          <span class="input-group-text"><i class="bi bi-person"></i></span>
          <input type="text" name="nombre" class="form-control" @if(req($parametros,'paramNombre')) required @endif>
        </div>

        @if(isOptionalParam($parametros,'paramNombre'))
          <small class="text-muted fst-italic mt-1 d-block">Campo opcional</small>
        @endif
      </div>
      @endif

      {{-- Código --}}
      @if(param($parametros,'paramCodigo'))
      <div class="col-md-6 mt-3">
        <label class="form-label">Código</label>
        <div class="input-group">
          <span class="input-group-text"><i class="bi bi-upc-scan"></i></span>
          <input type="text" name="codigo" class="form-control" @if(req($parametros,'paramCodigo')) required @endif placeholder="Ingrese el código...">
        </div>

        @if(isOptionalParam($parametros,'paramCodigo'))
          <small class="text-muted fst-italic mt-1 d-block">Campo opcional</small>
        @endif
      </div>
      @endif

      {{-- Radicación --}}
      @if(param($parametros,'paramRadicado'))
      <div class="col-md-6 mt-3">
        <label class="form-label">Radicación</label>
        <div class="card p-2">
          <div class="form-check">
            <input class="form-check-input" type="checkbox" name="radicado[]" value="SIN_RADICAR" id="radicado1" checked>
            <label class="form-check-label" for="radicado1">Sin Radicar</label>
          </div>
          <div class="form-check">
            <input class="form-check-input" type="checkbox" name="radicado[]" value="SIN_MORA" id="radicado2">
            <label class="form-check-label" for="radicado2">Radicadas sin Mora</label>
          </div>
          <div class="form-check">
            <input class="form-check-input" type="checkbox" name="radicado[]" value="1_30" id="radicado3">
            <label class="form-check-label" for="radicado3">1-30 días</label>
          </div>
          <div class="form-check">
            <input class="form-check-input" type="checkbox" name="radicado[]" value="31_45" id="radicado4">
            <label class="form-check-label" for="radicado4">31-45 días</label>
          </div>
          <div class="form-check">
            <input class="form-check-input" type="checkbox" name="radicado[]" value="46_60" id="radicado5">
            <label class="form-check-label" for="radicado5">46-60 días</label>
          </div>
          <div class="form-check">
            <input class="form-check-input" type="checkbox" name="radicado[]" value="61_90" id="radicado6">
            <label class="form-check-label" for="radicado6">61-90 días</label>
          </div>
          <div class="form-check">
            <input class="form-check-input" type="checkbox" name="radicado[]" value="MAS_90" id="radicado7">
            <label class="form-check-label" for="radicado7">Más de 90 días</label>
          </div>
        </div>
      </div>
      @endif

      {{-- Tipo cuenta --}}
      @if(param($parametros,'paramTipoCuenta'))
      <div class="col-md-6 mt-3">
        <label class="form-label">Tipo de Cartera</label>
        <div class="card p-2">
          <div class="form-check">
            <input class="form-check-input" type="radio" name="tipoCuenta" value="0" id="tipoCuenta0" checked>
            <label class="form-check-label" for="tipoCuenta0">CORRIENTE</label>
          </div>
          <div class="form-check">
            <input class="form-check-input" type="radio" name="tipoCuenta" value="1" id="tipoCuenta1">
            <label class="form-check-label" for="tipoCuenta1">LEY 550</label>
          </div>
          <div class="form-check">
            <input class="form-check-input" type="radio" name="tipoCuenta" value="2" id="tipoCuenta2">
            <label class="form-check-label" for="tipoCuenta2">C. JURÍDICO</label>
          </div>
          <div class="form-check">
            <input class="form-check-input" type="radio" name="tipoCuenta" value="3" id="tipoCuenta3">
            <label class="form-check-label" for="tipoCuenta3">RECLAMACIÓN</label>
          </div>
          <div class="form-check">
            <input class="form-check-input" type="radio" name="tipoCuenta" value="4" id="tipoCuenta4">
            <label class="form-check-label" for="tipoCuenta4">INCOBRABLE</label>
          </div>
          <div class="form-check">
            <input class="form-check-input" type="radio" name="tipoCuenta" value="0,1,2,3,4" id="tipoCuentaTodos">
            <label class="form-check-label" for="tipoCuentaTodos">TODOS</label>
          </div>
        </div>
      </div>
      @endif

    </div>

    <div class="d-flex justify-content-end pt-3 gap-2">
      <button type="submit" class="btn btn-success">Consultar</button>
      <button type="button" class="btn btn-dark" data-bs-dismiss="modal">Cancelar</button>
    </div>

  </div>
</form>
