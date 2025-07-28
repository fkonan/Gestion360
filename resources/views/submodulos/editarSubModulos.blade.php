<!-- modal para listarSubModulos-->
<div class="container-fluid p-0 rounded">
    <form 
        id="formFormato" 
        action="{{  route('submodulos.update', ['id' => $moduloEdit->IdModulo])  }}" 
        method="POST" 
        enctype="multipart/form-data"
        onsubmit="deshabilitarSubmit(this)">
        
        @csrf
        @method('PUT')
        <div class="row g-3 p-4">       
            <div class="col-md-4">
                <label for="SubModNom" class="form-label">Nombre</label>
                <input type="text" class="form-control" id="SubModNom" name="SubModNom" maxlength="100" value="{{ $moduloEdit->ModNom }}" readonly>
                <span class="error text-danger fw-bold" id="error-SubModNom"></span>
            </div>

            <div class="col-md-4">
                <label for="SubModIcono" class="form-label">Icono</label>
                <input type="text" class="form-control" id="SubModIcono" name="SubModIcono" value="{{ $moduloEdit->ModIcono }}">
                <span class="error text-danger fw-bold" id="error-SubModIcono"></span>
            </div>

            <div class="col-md-4">
                <label for="ModuloId" class="form-label">Modulo Padre</label>
                <select class="form-select select2" id="ModuloId" name="ModuloId" placeholder="Seleccione si es parte de otro modulo">
                    @foreach($modulos as $modulo)
                        <option value="{{ $modulo->IdModulo }}" @selected($moduloEdit->ModuloId == $modulo->IdModulo)>
                            {{ ucfirst(mb_strtolower($modulo->ModNom)) }} 
                        </option>
                    @endforeach
                </select>
                <span class="error text-danger fw-bold" id="error-ModuloId"></span>
            </div>

        </div>
        <div class="row px-4 pb-4">       
            <div class="col-md-4">
                <label for="SubModRuta" class="form-label">Ruta</label>
                <input type="text" class="form-control" id="SubModRuta" name="SubModRuta" value="{{ $moduloEdit->ModRuta }}">
                <span class="error text-danger fw-bold" id="error-SubModRuta"></span>
            </div> 
            <div class="col-md-4">
                <label for="SubModPermiso" class="form-label">Permiso</label>
                <select class="form-select select2" id="SubModPermiso" name="SubModPermiso">
                    <option value="">Sin permiso</option>
                    @foreach ($permisos as $permiso)
                        <option value="{{ $permiso->name }}" @selected($permiso->name == $moduloEdit->ModPermiso)>{{ $permiso->name }}</option>
                    @endforeach
                </select>
                <span class="error text-danger fw-bold" id="error-SubModPermiso"></span>
            </div>

            <div class="col-md-4">
                <label for="SubModuloEstado" class="form-label">Estado</label>
                <select class="form-select" id="SubModuloEstado" name="SubModuloEstado" required>
                    <option value="" disabled selected>Seleccione</option>
                    <option value="ACTIVO" @selected($moduloEdit->ModEstado == 'ACTIVO')>Activo</option>
                    <option value="INACTIVO" @selected($moduloEdit->ModEstado == 'INACTIVO')>Inactivo</option>
                    <option value="ELIMINADO" @selected($moduloEdit->ModEstado == 'ELIMINADO')>Eliminado</option>
                </select>
                <span class="error text-danger fw-bold" id="error-SubModuloEstado"></span>
            </div>   
        </div>

        <div class="row gx-3 px-4">
            <div class="mb-3">
                <label for="SubModDes" class="form-label">Descripción</label>
                <textarea class="form-control" id="SubModDes" name="SubModDes" maxlength="300" rows="5">{{ $moduloEdit->ModDesc }}</textarea>
                <div class="form-text">Máximo 300 caracteres.</div>
                <!--<div id="contador" class="text-end text-muted" style="font-size: 0.875rem;">0/300</div>-->
                <span class="error text-danger fw-bold" id="error-SubModDes"></span>
            </div>
        </div>

        <div class="text-start ms-4 mb-4">
            <button type="submit" class="btn btn-success">Guardar</button>
            <button type="button" class="btn btn-dark" data-bs-dismiss="modal">Cancelar</button>
        </div>
    </form>
</div>


