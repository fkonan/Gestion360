<!-- Modal formulario reporte -->
<form
    id="formPoliticas" 
    action="{{ route('politicas.conductor') }}" 
    method="POST" 
    enctype="multipart/form-data"
    onsubmit="deshabilitarSubmit(this)">
    
    @csrf
    <div class="p-3">
        <div class="row mb-4 mx-1" style="max-width: 400px;">
            <label for="identificacion" class="form-label">Número de identificación</label>
            <input type="number" name="identificacion" id="identificacion" class="form-control">
            <span class="error text-danger fw-bold" id="error-identificacion"></span>
        </div>

        <div class="ms-3">
            <button type="submit" class="btn btn-success">Buscar</button>
            <button type="button" class="btn btn-dark" data-bs-dismiss="modal">Cancelar</button>
        </div>
    </div>
</form>

<!-- Resultado del reporte por peticion AJAX -->
<div id="innerHtml"></div>
