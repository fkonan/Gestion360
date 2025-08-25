<!-- Modal reporte firma politica de equipaje conductores -->
<div class="container-fluid p-2">
    <form
        id="firmaNormasForm" 
        action="{{ route('filtrar.firmaNormas') }}" 
        method="POST" 
        enctype="multipart/form-data"
        onsubmit="deshabilitarSubmit(this)">
        
        @csrf
        <div class="row mb-2">
            <p class="lh-sm">Ingrese un número de documento para buscar un empleado. Si deja el campo vacío, verá todos los que han firmado.</p>
        </div>

        <div class="row mb-3 mx-1">
            <label for="identificacion" class="form-label">Número de identificación</label>
            <input type="number" name="identificacion" id="identificacion" class="form-control" placeholder="Número de identificación">
            <span class="error text-danger fw-bold" id="error-identificacion"></span>
        </div>

        <hr class="p-2">

        <div>
            <button type="submit" class="btn btn-success">Buscar</button>
            <button type="button" class="btn btn-dark" data-bs-dismiss="modal">Cancelar</button>
        </div>
    </form>
</div>