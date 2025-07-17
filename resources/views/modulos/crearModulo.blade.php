<!-- modal para crearModulos-->
<div class="container-fluid p-0 rounded">
    <form 
        id="formFormato" 
        action="{{ route('modulos.store') }}" 
        method="POST" enctype="multipart/form-data"
        onsubmit="deshabilitarSubmit(this)">

        @csrf
        <div class="row g-3 p-4">       
            <div class="col-md-4">
                <label for="ModNom" class="form-label">Nombre</label>
                <input type="text" class="form-control" id="ModNom" name="ModNom" maxlength="100" required>
                <span class="error text-danger fw-bold" id="error-ModNom"></span>
            </div>
            <div class="col-md-4">
                <label for="ModIcono" class="form-label">Icono</label>
                <input type="text" class="form-control" id="ModIcono" name="ModIcono">
                <span class="error text-danger fw-bold" id="error-ModIcono"></span>
            </div>
            <div class="col-md-4">
                <label for="ModRuta" class="form-label">Ruta</label>
                <input type="text" class="form-control" id="ModRuta" name="ModRuta">
                <span class="error text-danger fw-bold" id="error-ModRuta"></span>
            </div>
        </div> 

        <div class="row gx-3 px-4">
            <div class="mb-3">
                <label for="ModDes" class="form-label">Descripción</label>
                <textarea class="form-control" id="ModDes" name="ModDes" maxlength="300" rows="5"></textarea>
                <div class="form-text">Máximo 300 caracteres.</div>
                <span class="error text-danger fw-bold" id="error-ModDes"></span>
            </div>
        </div>

        <div class="text-start ps-4 mb-4">
            <button type="submit" class="btn btn-success">Guardar</button>
            <button type="button" class="btn btn-dark" data-bs-dismiss="modal">Cancelar</button>
        </div>
    </form>
</div>
