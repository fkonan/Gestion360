<!-- Modal editar usuario -->

<div class="container-fluid p-0 bg-white rounded">
    <form id="formEditUsuario" action="{{  route('usuarios.update', ['id' => $usuario->IdUsuario])  }}" method="POST">
        @csrf
        @method('PUT')
        <div class="p-4">
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="idPersona" class="form-label">Nombre Persona</label>
                    <input class="form-control" id="idPersona" name="idPersona" value="{{ $usuario->persona->PerNombres }} {{ $usuario->persona->PerApellidos }}" disabled required>
                    <span class="error text-danger fw-bold" id="error-idPersona"></span>
                </div>

                <div class="col-md-6 mb-3">
                    <label for="Password" class="form-label">Contraseña</label>
                    <div class="input-group">
                        <input type="password" class="form-control" id="Password" name="Password" placeholder="********" value="{{ $usuario->Password }}" disabled required>
                        <div class="input-group-text">
                            <span class="fas fa-edit" onclick="togglePasswordVisibility()"></span>
                        </div>
                        <span class="error text-danger fw-bold" id="error-Password"></span>
                    </div>
                </div>
            </div>

            <div class="row pb-4">
                <div class="col-md-6 mb-3">
                    <label for="UsuarioEstado" class="form-label">Estado</label>
                    <select class="form-select" id="UsuarioEstado" name="UsuarioEstado" required>
                        <option value="ACTIVO" @selected($usuario->UsuarioEstado == 'ACTIVO')>ACTIVO</option>
                        <option value="INACTIVO" @selected($usuario->UsuarioEstado == 'INACTIVO')>INACTIVO</option>
                        <option value="SUSPENDIDO" @selected($usuario->UsuarioEstado == 'SUSPENDIDO')>SUSPENDIDO</option>
                    </select>
                    <span class="error text-danger fw-bold" id="error-UsuarioEstado"></span>
                </div>

                <div class="col-md-6 mb-3">
                    <label for="Verificado" class="form-label">Verificado</label>
                    <select class="form-select" id="Verificado" name="Verificado" required>
                        <option value="TRUE" @selected($usuario->Verificado == 'TRUE')>TRUE</option>
                        <option value="FALSE" @selected($usuario->Verificado == 'FALSE')>FALSE</option>
                    </select>
                    <span class="error text-danger fw-bold" id="error-Verificado"></span>
                </div>
            </div>

            <button type="submit" class="btn btn-success">Guardar</button>
            <button type="button" class="btn btn-dark" data-bs-dismiss="modal">Cancelar</button>
        </div>
    </form>
</div>
