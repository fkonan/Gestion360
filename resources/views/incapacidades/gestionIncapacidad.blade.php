<!-- Modal Gestion Incapacidad -->
<div class="container-fluid p-0 bg-white border rounded">
    <form 
        id="formGestionIncapacidad" 
        method="POST" 
        enctype="multipart/form-data">
        
        @csrf
        <div class="row g-5 p-4">       
            <div class="col-md-5">
                <label for="IncapacidadEstado" class="form-label">Radicado</label>
                <input type="text" class="form-control" id="IdIncapacidad" name="IdIncapacidad" value="{{ $incapacidad->IdIncapacidad}}" disabled>
                <span class="error text-danger fw-bold" id="error-IdIncapacidad"></span>
            </div>

            <div class="col-md-7">
                <h6 class="fw-bold">Respuesta Radicado</h6>
                <div class="mt-2">
                    <div class="form-check form-check-inline d-flex align-items-center">
                        <input class="form-check-input" 
                                type="radio" 
                                id="IncapacidadEstadoAprobado"
                                name="IncapacidadEstado" 
                                value="APROBADO" 
                                onchange="mostrarObservacion()"/>
                        <label class="form-check-label" for="IncapacidadEstadoAprobado">Aprobado</label>
                    </div>
                    <div class="form-check form-check-inline d-flex align-items-center">
                        <input class="form-check-input" 
                                type="radio" id="IncapacidadEstadoRechazado" 
                                name="IncapacidadEstado" 
                                value="RECHAZADO" 
                                onchange="mostrarObservacion()"/>
                        <label class="form-check-label" for="IncapacidadEstadoRechazado">Rechazado</label>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-3 px-4 pb-4">
            <div class="col-md-12" id="observacionDiv" style="display: none">
                <label for="Observacion" class="form-label">Observacion</label>
                <textarea class="form-control" id="Observacion" name="Observacion" rows="3" required></textarea>
                <span class="error text-danger fw-bold" id="error-Observacion"></span>
            </div>
        </div>

        <div class="text-start m-4">
            <button type="submit" class="btn btn-success">Guardar</button>
            <button type="button" class="btn btn-dark" data-bs-dismiss="modal">Cancelar</button>
        </div>
    </form>
</div>


