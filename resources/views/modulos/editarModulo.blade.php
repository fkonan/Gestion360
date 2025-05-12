<!-- modal para listarModulos-->
<div class="container-fluid p-0 bg-white rounded">
    <form 
        id="formFormato" 
        action="{{  route('modulos.update', ['id' => $moduloEdit->IdModulo])  }}" 
        method="POST" 
        enctype="multipart/form-data"
        onsubmit="deshabilitarSubmit(this)">
        
        @csrf
        @method('PUT')
        <div class="row g-3 p-4">       
            <div class="col-md-4">
                <label for="ModNom" class="form-label">Nombre</label>
                <input type="text" class="form-control" id="ModNom" name="ModNom" maxlength="100" value="{{ $moduloEdit->ModNom }}" required>
                <span class="error text-danger fw-bold" id="error-ModNom"></span>
            </div>

            <div class="col-md-4">
                <label for="ModIcono" class="form-label">Icono</label>
                <input type="text" class="form-control" id="ModIcono" name="ModIcono" value="{{ $moduloEdit->ModIcono }}">
                <span class="error text-danger fw-bold" id="error-ModIcono"></span>
            </div>

            <div class="col-md-4">
                <label for="ModRuta" class="form-label">Ruta</label>
                <input type="text" class="form-control" id="ModRuta" name="ModRuta" value="{{ $moduloEdit->ModRuta }}">
                <span class="error text-danger fw-bold" id="error-ModRuta"></span>
            </div>
        </div>
        <div class="row px-4 pb-4">       
            <div class="col-md-4">
                <label for="ModPermiso" class="form-label">Permiso</label>
                <select class="form-select select2" id="ModPermiso" name="ModPermiso">
                    <option value="">Sin permiso</option>
                    @foreach ($permisos as $permiso)
                        <option value="{{ $permiso->name }}" @selected($permiso->name == $moduloEdit->ModPermiso)>{{ $permiso->name }}</option>
                    @endforeach
                </select>
                <span class="error text-danger fw-bold" id="error-ModPermiso"></span>
            </div>

            <div class="col-md-4">
                <label for="ModuloEstado" class="form-label">Estado</label>
                <select class="form-select" id="ModuloEstado" name="ModuloEstado" required>
                    <option value="" disabled selected>Seleccione</option>
                    <option value="ACTIVO" @selected($moduloEdit->ModEstado == 'ACTIVO')>Activo</option>
                    <option value="INACTIVO" @selected($moduloEdit->ModEstado == 'INACTIVO')>Inactivo</option>
                    <option value="ELIMINADO" @selected($moduloEdit->ModEstado == 'ELIMINADO')>Eliminado</option>
                </select>
                <span class="error text-danger fw-bold" id="error-ModuloEstado"></span>
            </div>   
        </div>

        <div class="row gx-3 px-4">
            <div class="mb-3">
                <label for="ModDes" class="form-label">Descripción</label>
                <textarea class="form-control" id="ModDes" name="ModDes" maxlength="300" rows="5">{{ $moduloEdit->ModDes }}</textarea>
                <div class="form-text">Máximo 300 caracteres.</div>
                <!--<div id="contador" class="text-end text-muted" style="font-size: 0.875rem;">0/300</div>-->
                <span class="error text-danger fw-bold" id="error-ModDes"></span>
            </div>
        </div>

        <div class="text-start ms-4 mb-4">
            <button type="submit" class="btn btn-success">Guardar</button>
            <button type="button" class="btn btn-dark" data-bs-dismiss="modal">Cancelar</button>
        </div>
    </form>
</div>


