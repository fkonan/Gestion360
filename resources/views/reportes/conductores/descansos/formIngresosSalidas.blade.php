<!-- Modal actualizar ingreso y salida conductores -->
<div class="container-fluid p-2">
    <form
        id="ingSalConForm" 
        action="{{ route('reporte.ingresoSalidas') }}" 
        method="POST" 
        enctype="multipart/form-data"
        onsubmit="deshabilitarSubmit(this,true)">
        
        @csrf
        <div class="row mb-3 mx-1">
            <div class="col-12 col-md-6">
                <label for="fechaInicial" class="form-label">Fecha Inicial *</label>
                <input type="date" name="fechaInicial" id="fechaInicial" class="form-control">
                <span class="error text-danger fw-bold" id="error-fechaInicial"></span>
            </div>
            <div class="col-12 col-md-6">
                <label for="fechaFinal" class="form-label">Fecha Final *</label>
                <input type="date" name="fechaFinal" id="fechaFinal" class="form-control">
                <span class="error text-danger fw-bold" id="error-fechaFinal"></span>
            </div>
        </div>

         <div class="row mb-3 mx-1">

           <!--  <div class="col-12 col-md-6">
                <label for="evento" class="form-label">Evento</label>
                <select name="evento" id="evento" class="form-select" required>
                    <option value="0" selected>Todos</option>
                    <option value="50">Salida de conductores</option>
                    <option value="49">Reintegro de conductores</option>
                </select>
                <span class="error text-danger fw-bold" id="error-evento"></span>
            </div> -->

             <div class="col-12 col-md-6 mt-3">
                <label class="form-label d-block">Filtrar por</label>
                <div class="form-check form-check-inline">
                    <input class="form-check-input" type="radio" name="filtro" id="filtroCedula" value="identificacion" onchange="habilitarInputFiltro(this)">
                    <label class="form-check-label" for="filtroCedula">Cédula</label>
                </div>
                <div class="form-check form-check-inline">
                    <input class="form-check-input" type="radio" name="filtro" id="filtroCodigo" value="codigo" onchange="habilitarInputFiltro(this)">
                    <label class="form-check-label" for="filtroCodigo">Código</label>
                </div>
                <div class="form-check form-check-inline">
                    <input class="form-check-input" type="radio" name="filtro" id="filtroTodos" value="todos" checked onchange="habilitarInputFiltro(this)">
                    <label class="form-check-label" for="filtroTodos">Todos</label>
                </div>
                <span class="error text-danger fw-bold" id="error-filtro"></span>
            </div>

            <div class="col-12 col-md-6" id="parametro" style="display: none">
                <label for="parametroInput" class="form-label">Ingrese el parametro</label>
                <input type="text" name="parametroInput" id="parametroInput" class="form-control" required>
                <span class="error text-danger fw-bold" id="error-parametroInput"></span>
            </div>    
        </div>

        <hr class="pb-2">

        <div>
            <button type="submit" class="btn btn-success">Generar Reporte</button>
            <button type="button" class="btn btn-dark" data-bs-dismiss="modal">Cancelar</button>
        </div>
    </form>
</div>