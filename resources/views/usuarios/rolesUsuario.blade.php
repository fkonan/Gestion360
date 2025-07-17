<!-- Modal para asignar roles a un usuario -->
<div class="container-fluid px-5 pt-1 pb-3 rounded">
    <form id="formRolUsuario" action="{{ route('roles.update', ['id' => $usuario->IdUsuario])}}" method="POST">
        @csrf
        @method('PUT')
        <div class="alert alert-info p-2 small">
            <div class="d-flex align-items-center gap-5 d-none d-md-flex">
            <span><strong>Nombre:</strong> {{ $usuario->persona->nombreCompleto() }}</span>
            <span><strong>Documento:</strong> {{ $usuario->persona->PerNumDoc }}</span>
            <span><strong>Estado:</strong> 
            @if($usuario->UsuarioEstado === 'ACTIVO')
                <span class="text-success fw-bold">Activo</span>
            @else
                <span class="text-danger fw-bold">Inactivo</span>
            @endif
            </span>
            </div>
            <div class="d-block d-md-none">
            <div><strong>Nombre:</strong> {{ $usuario->persona->nombreCompleto() }}</div>
            <div><strong>Documento:</strong> {{ $usuario->persona->PerNumDoc }}</div>
            <div><strong>Estado:</strong> 
            @if($usuario->UsuarioEstado === 'ACTIVO')
                <span class="text-success fw-bold">Activo</span>
            @else
                <span class="text-danger fw-bold">Inactivo</span>
            @endif
            </div>
            </div>
        </div>

        <div class="row pb-3">
            <div class="col-md-12">
                <select multiple="multiple" id="roles" name="roles[]" class="form-control">
                    @foreach ($rolesDisponibles as $rol)
                        <option class="fw-medium text-uppercase sidebar-dark-primary" value="{{ $rol->name }}"
                            @if ($rolesUsuario->contains($rol->name)) selected @endif>
                            {{ $rol->name }}
                        </option>
                    @endforeach
                </select>
            </div>
        </div>
        <button type="submit" class="btn btn-success my-3">Guardar</button>
        <button type="button" class="btn btn-dark" data-bs-dismiss="modal">Cancelar</button>
    </form>
</div>
