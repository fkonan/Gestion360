<!-- modal para listarModulos-->
<div class="container-fluid p-0 bg-white border rounded">
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
                <label for="Mod_Padre_Id" class="form-label">Modulo Padre</label>
                <select class="form-select select2" id="Mod_Padre_Id" name="Mod_Padre_Id" placeholder="Seleccione si es parte de otro modulo">
                    <option value="" @selected($moduloEdit->Mod_Padre_Id == null)>Sin módulo padre (Módulo principal)</option>
                    @foreach($modulos as $modulo)
                        <optgroup label="{{ strtoupper($modulo->ModNom) }}">
                            <option value="{{ $modulo->IdModulo }}" class="grupo-option" @selected($moduloEdit->Mod_Padre_Id == $modulo->IdModulo)>
                                    - {{ ucfirst(mb_strtolower($modulo->ModNom)) }} (Principal)
                            </option>
                            @if($modulo->submodulos->count()) 
                                @foreach($modulo->submodulos as $submodulo)
                                    <option value="{{ $submodulo->IdModulo }}" @selected($moduloEdit->Mod_Padre_Id == $submodulo->IdModulo)>
                                    - {{ ucfirst(mb_strtolower($submodulo->ModNom)) }}
                                    </option>
                                @endforeach
                            @endif
                        </optgroup>
                    @endforeach
                </select>
                <span class="error text-danger fw-bold" id="error-Mod_Padre_Id"></span>
            </div>

            <div class="col-md-4">
                <label for="ModEstado" class="form-label">Estado</label>
                <select class="form-select" id="ModEstado" name="ModEstado" required>
                    <option value="">Seleccione</option>
                    <option value="ACTIVO" @selected($moduloEdit->ModEstado == 'ACTIVO')>Activo</option>
                    <option value="INACTIVO" @selected($moduloEdit->ModEstado == 'INACTIVO')>Inactivo</option>
                    <option value="ELIMINADO" @selected($moduloEdit->ModEstado == 'ELIMINADO')>Eliminado</option>
                </select>
                <span class="error text-danger fw-bold" id="error-ModEstado"></span>
            </div>   
        </div>
        <div class="row px-4 pb-4">       
            <div class="col-md-4">
                <label for="ModRuta" class="form-label">Ruta</label>
                <input type="text" class="form-control" id="ModRuta" name="ModRuta" value="{{ $moduloEdit->ModRuta }}">
                <span class="error text-danger fw-bold" id="error-ModRuta"></span>
            </div>

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
                <label for="ModIcono" class="form-label">Icono</label>
                <input type="text" class="form-control" id="ModIcono" name="ModIcono" value="{{ $moduloEdit->ModIcono }}">
                <span class="error text-danger fw-bold" id="error-ModIcono"></span>
            </div>
        </div>

        <div class="row gx-3 px-4">
            <div class="mb-3">
                <label for="ModDesc" class="form-label">Descripción</label>
                <textarea class="form-control" id="ModDesc" name="ModDesc" maxlength="300" rows="5">{{ $moduloEdit->ModDesc }}</textarea>
                <div class="form-text">Máximo 300 caracteres.</div>
                <!--<div id="contador" class="text-end text-muted" style="font-size: 0.875rem;">0/300</div>-->
                <span class="error text-danger fw-bold" id="error-ModDesc"></span>
            </div>
        </div>

        <div class="text-start ms-4 mb-4">
            <button type="submit" class="btn btn-primary">Guardar</button>
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
        </div>
    </form>
</div>


