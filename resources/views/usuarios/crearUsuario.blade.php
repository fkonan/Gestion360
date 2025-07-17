<!-- Modal crear usuario -->

<div class="container-fluid p-0 rounded">
    <form 
        id="formCrearUsuario" 
        action="{{  route('usuarios.store')  }}" 
        method="POST"
        onsubmit="deshabilitarSubmit(this)">

        @csrf
        <div class="p-4">
            <div class="row mb-4">
                <div class="col-md-6 mb-3">
                    <label for="idPersona" class="form-label">Persona *</label>
                    <select class="form-select select2" id="idPersona" name="idPersona" required>
                        <option value="" disabled selected>Seleccione una persona</option>
                        @foreach($personas as $persona)
                            <option value="{{ $persona->IdPersona }}">{{ $persona->PerNombres }} {{ $persona->PerApellidos }}</option>
                        @endforeach
                    </select>
                    <span class="error text-danger fw-bold" id="error-idPersona"></span>
                </div>

                  <div class="col-md-6 mb-3">
                    <label for="rol" class="form-label">Asignar Rol *</label>
                    <select class="form-select select2" id="rol" name="rol" required>
                        <option value="" disabled selected>Seleccione un rol</option>
                        @foreach($roles as $rol)
                            <option value="{{ $rol->name }}">{{ $rol->name }}</option>
                        @endforeach
                    </select>
                    <span class="error text-danger fw-bold" id="error-rol"></span>
                </div>
            </div>

            <button type="submit" class="btn btn-success">Guardar</button>
            <button type="button" class="btn btn-dark" data-bs-dismiss="modal">Cancelar</button>
        </div>
    </form>
</div>

