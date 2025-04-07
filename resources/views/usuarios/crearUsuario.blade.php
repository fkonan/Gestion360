<!-- Modal crear usuario -->

<div class="container-fluid p-0 bg-white border rounded">
    <form id="formCrearUsuario" action="{{  route('usuarios.store')  }}" method="POST">
        @csrf
        <div class="p-4">
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="idPersona" class="form-label">Nombre Persona</label>
                    <select class="form-select select2" id="idPersona" name="idPersona" required>
                        <option value="" disabled selected>Seleccione una persona</option>
                        @foreach($personas as $persona)
                            <option value="{{ $persona->IdPersona }}">{{ $persona->PerNombres }} {{ $persona->PerApellidos }}</option>
                        @endforeach
                    </select>
                    <span class="error text-danger fw-bold" id="error-idPersona"></span>
                </div>

                <div class="col-md-6 mb-3">
                    <label for="Password" class="form-label">Contraseña</label>
                    <input type="password" class="form-control" id="Password" name="Password" required>
                    <span class="error text-danger fw-bold" id="error-Password"></span>
                </div>
            </div>

            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="UsuarioEstado" class="form-label">Estado</label>
                    <select class="form-select" id="UsuarioEstado" name="UsuarioEstado" required>
                        <option value="ACTIVO">ACTIVO</option>
                        <option value="INACTIVO">INACTIVO</option>
                        <option value="SUSPENDIDO">SUSPENDIDO</option>
                    </select>
                    <span class="error text-danger fw-bold" id="error-UsuarioEstado"></span>
                </div>

                <div class="col-md-6 mb-3">
                    <label for="Verificado" class="form-label">Verificado</label>
                    <select class="form-select" id="Verificado" name="Verificado" required>
                        <option value="TRUE">TRUE</option>
                        <option value="FALSE">FALSE</option>
                    </select>
                    <span class="error text-danger fw-bold" id="error-Verificado"></span>
                </div>
            </div>

            <button type="submit" class="btn btn-success">Guardar</button>
            <button type="button" class="btn btn-dark" data-bs-dismiss="modal">Cancelar</button>
        </div>
    </form>
</div>

