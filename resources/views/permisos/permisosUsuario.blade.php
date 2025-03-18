<!-- Modal para asignar permisos a un usuario -->
<div class="container-fluid px-5 py-4 border rounded">
    <form id="formPermisoUsuario" action="{{ route('permisos.update', ['permiso' => $usuario->IdUsuario])}}" method="POST">
        @csrf
        @method('PUT')
        <div class="row pb-3">
            <div class="col-md-12">
                <select multiple="multiple" id="permissions" name="permissions[]" class="form-control">
                    @foreach ($permisosDisponibles as $permiso)
                        <option class="fw-medium" value="{{ $permiso->name }}"
                            @if ($permisosUsuario->contains($permiso->id)) selected @endif>
                            {{ $permiso->name }}
                        </option>
                    @endforeach
                </select>
            </div>
        </div>
        <button type="submit" class="btn btn-primary my-3">Guardar</button>
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
    </form>
</div>
