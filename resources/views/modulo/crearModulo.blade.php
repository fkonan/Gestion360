<!-- modal para crearModulos-->
<div class="container-fluid p-0 bg-white border rounded">
    
    <form id="formFormato" action="{{ route('modulos.store') }}" method="POST" enctype="multipart/form-data">
        @csrf
        <div class="row g-3 p-4">       
            <div class="col-md-4">
                <label for="ModNom" class="form-label">Nombre</label>
                <input type="text" class="form-control" id="ModNom" name="ModNom" maxlength="100" required>
                <span class="error text-danger fw-bold" id="error-ModNom"></span>
            </div>
            <div class="col-md-4">
                <label for="Mod_Padre_Id" class="form-label">Modulo Padre</label>
                <select class="form-select" id="Mod_Padre_Id" name="Mod_Padre_Id" placeholder="Seleccione si es parte de otro modulo">
                    <option value="">Modulo Principal</option>
                    @foreach($modulos as $modulo)
                        <option value="{{ $modulo->IdModulo }}">{{ $modulo->ModNom }}</option>
                    @endforeach
                </select>
                <span class="error text-danger fw-bold" id="error-Mod_Padre_Id"></span>
            </div>
            <div class="col-md-4">
                <label for="ModEstado" class="form-label">Estado</label>
                <select class="form-select" id="ModEstado" name="ModEstado" required>
                    <option value="ACTIVO">Activo</option>
                    <option value="INACTIVO">Inactivo</option>
                    <option value="ELIMINADO">Eliminado</option>
                </select>
                <span class="error text-danger fw-bold" id="error-ModEstado"></span>
            </div>  
        </div> 

        <div class="row px-4 pb-4">   
            <div class="col-md-4">
                <label for="ModRuta" class="form-label">Ruta</label>
                <input type="text" class="form-control" id="ModRuta" name="ModRuta">
                <span class="error text-danger fw-bold" id="error-ModRuta"></span>
            </div>
            <div class="col-md-4">
                <label for="ModPermiso" class="form-label">Permiso</label>
                <input type="text" class="form-control" id="ModPermiso" name="ModPermiso">
                <span class="error text-danger fw-bold" id="error-ModPermiso"></span>
            </div>
            <div class="col-md-4">
                <label for="ModIcono" class="form-label">Icono</label>
                <input type="text" class="form-control" id="ModIcono" name="ModIcono">
                <span class="error text-danger fw-bold" id="error-ModIcono"></span>
            </div>
        </div>

        <div class="row gx-3 px-4">
            <div class="mb-3">
                <label for="comentario" class="form-label">Descripción</label>
                <textarea class="form-control" id="ModDesc" name="ModDesc" maxlength="300" rows="5"></textarea>
                <div class="form-text">Máximo 300 caracteres.</div>
                <div id="contador" class="text-end text-muted" style="font-size: 0.875rem;">0/300</div>
                <span class="error text-danger fw-bold" id="error-ModDesc"></span>
            </div>
        </div>

        <div class="text-start ms-4 mb-4">
            <button type="submit" class="btn btn-primary">Guardar</button>
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
        </div>
    </form>
</div>
