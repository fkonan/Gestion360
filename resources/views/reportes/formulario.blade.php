<!-- Modal formulario reporte -->
<form
    id="formReporte"
    action="{{ route('reportes.show') }}"
    method="GET"
    onsubmit="deshabilitarSubmit(this)">

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
        </div>

        <div class="d-flex justify-content-end ms-3 pt-2 gap-2">
            <button type="submit" class="btn btn-success">Consultar</button>
            <button type="button" class="btn btn-dark" data-bs-dismiss="modal">Cancelar</button>
        </div>
    </div>
</form>
