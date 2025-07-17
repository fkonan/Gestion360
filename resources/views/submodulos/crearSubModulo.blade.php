<!-- modal para crearSubModulos-->
<div class="container-fluid p-0 rounded">
    <form 
        id="formSubModulo" 
        action="{{ route('submodulos.store') }}" 
        method="POST" enctype="multipart/form-data"
        onsubmit="deshabilitarSubmit(this)">

        @csrf
        <div class="row g-3 p-4">       
            <div class="col-md-3">
                <label for="ModNom" class="form-label">Nombre</label>
                <input type="text" class="form-control" id="ModNom" name="ModNom" maxlength="100" required>
                <span class="error text-danger fw-bold" id="error-ModNom"></span>
            </div>
            <div class="col-md-3">
                <label for="Mod_Padre_Id" class="form-label">Modulo Padre</label>
                <select class="form-select select2" id="Mod_Padre_Id" name="Mod_Padre_Id">
                    @foreach($modulos as $modulo)
                        <option value="{{ $modulo->IdModulo }}" >
                            {{ ucfirst(mb_strtolower($modulo->ModNom)) }}
                        </option>
                    @endforeach
                </select>
                <span class="error text-danger fw-bold" id="error-Mod_Padre_Id"></span>
            </div>
            <div class="col-md-3">
                <label for="ModIcono" class="form-label">Icono</label>
                <input type="text" class="form-control" id="ModIcono" name="ModIcono">
                <span class="error text-danger fw-bold" id="error-ModIcono"></span>
            </div>
            <div class="col-md-3">
                <label for="ModRuta" class="form-label">Ruta</label>
                <input type="text" class="form-control" id="ModRuta" name="ModRuta">
                <span class="error text-danger fw-bold" id="error-ModRuta"></span>
            </div>
        </div> 

        <div class="row gx-3 px-4">
            <div class="mb-3">
                <label for="ModDesc" class="form-label">Descripción</label>
                <textarea class="form-control" id="ModDesc" name="ModDesc" maxlength="300" rows="5"></textarea>
                <div class="form-text">Máximo 300 caracteres.</div>
                <span class="error text-danger fw-bold" id="error-ModDesc"></span>
            </div>
        </div>

        <div class="text-start ms-4 mb-4">
            <button type="submit" class="btn btn-success">Guardar</button>
            <button type="button" class="btn btn-dark" data-bs-dismiss="modal">Cancelar</button>
        </div>
    </form>
</div>
