<!-- Modal para asignar permisos a un rol -->
<div class="container-fluid px-5 py-4 border rounded">
    <form id="formPermisoRol" action="{{ route('roles.permisos.update', ['id' => $rol->id]) }}" method="POST">
        @csrf
        @method('PUT')
        <div class="row pb-3">
            <div class="col-md-12">
                <select multiple="multiple" id="permisosRol" name="permisosRol[]" class="form-control">
                    @foreach ($permisosDisponibles as $permiso)
                        <option class="fw-medium" value="{{ $permiso->name }}"
                             @if ($permisosRol->contains($permiso->id)) selected @endif>
                             {{ $permiso->name }}
                        </option>
                    @endforeach
                </select>
            </div>
        </div>
        <button type="submit" class="btn btn-success my-3">Guardar</button>
        <button type="button" class="btn btn-dark" data-bs-dismiss="modal">Cancelar</button>
    </form>
</div>
