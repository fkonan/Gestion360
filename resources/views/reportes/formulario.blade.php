<!-- Modal formulario reporte -->
<form
    id="formReporte" 
    action="{{ route('reportes.get', ['id' => $id]) }}" 
    method="POST" 
    enctype="multipart/form-data"
    onsubmit="deshabilitarSubmit(this)">
    
    @csrf
    <div class="p-3">
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
        <div class="ms-3">
            <button type="submit" class="btn btn-success">Generar Reporte</button>
            <button type="button" class="btn btn-dark" data-bs-dismiss="modal">Cancelar</button>
        </div>

        <div id="innerHtml"></div>
    </div>
</form>
