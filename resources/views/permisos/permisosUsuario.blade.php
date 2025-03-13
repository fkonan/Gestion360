<!-- Modal para asignar permisos a un usuario -->
<div class="container-fluid p-0 border rounded">
    <form method="POST">
        @csrf
        <div class="row">
            <div class="col-md-12">
                <select multiple="multiple" id="permissions" name="permissions[]" class="form-control">
                    @foreach ($permisosDisponibles as $permiso)
                        <option value="{{ $permiso->id }}"
                            @if ($permisosUsuario->contains('id', $permiso->id)) selected @endif>
                            {{ $permiso->name }}
                        </option>
                    @endforeach
                </select>
            </div>
        </div>
        <button type="submit" class="btn btn-primary mt-3">Guardar</button>
    </form>
</div>
