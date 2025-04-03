<!-- Modal nuevo seguimiento de incapacidad -->
<div class="container-fluid p-0 bg-white border rounded">
    <form 
        id="formNuevoSeguimiento" 
        action="{{ route('gestion-incapacidades.seguimiento.detalle.store', ['id' => $incapacidad->IdIncapacidad]) }}"
        method="POST" 
        enctype="multipart/form-data">
        
        @csrf
        <div class="row g-3 px-4 py-4">
            <div class="col-md-12">
                <label for="Observacion" class="form-label">Observación</label>
                <textarea class="form-control" id="Observacion" name="Observacion" rows="3"></textarea>
                <span class="error text-danger fw-bold" id="error-Observacion"></span>
            </div>
        </div>

        <div class="text-start m-4">
            <button type="submit" class="btn btn-success">Guardar</button>
            <button type="button" class="btn btn-dark" data-bs-dismiss="modal">Cancelar</button>
        </div>
    </form>
</div>