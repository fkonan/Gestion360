<!-- Modal para asignar roles a un usuario -->
<div class="container-fluid px-5 py-4 border rounded">
    <form id="formRolUsuario" action="{{ route('roles.update', ['id' => $usuario->IdUsuario])}}" method="POST">
        @csrf
        @method('PUT')
        <div class="row pb-3">
            <div class="col-md-12">
                <select multiple="multiple" id="roles" name="roles[]" class="form-control">
                    @foreach ($rolesDisponibles as $rol)
                        <option class="fw-medium text-uppercase" value="{{ $rol->name }}"
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
