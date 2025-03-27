<!-- Modal para asignar permisos a un usuario -->
<div class="container-fluid px-5 py-4 border rounded">
    <form id="formPermisoUsuario" action="{{ route('permisos.update', ['id' => $usuario->IdUsuario])}}" method="POST">
        @csrf
        @method('PUT')
        <div class="row pb-3">
            <div class="col-md-12">
                <select multiple="multiple" id="permissions" name="permissions[]" class="form-control">
                    @foreach ($permisosDisponibles as $permiso)
                        @php
                            $isDirect = in_array($permiso->id, $permisosDirectos);
                            $isInherited = in_array($permiso->id, $permisosHeredados);
                        @endphp
                        <option 
                            class="fw-medium {{ $isInherited ? 'text-danger' : '' }}" 
                            value="{{ $permiso->name }}"
                            @if ($isInherited) disabled @endif
                            @if ($isDirect || $isInherited) selected @endif>
                            {{ $permiso->name }} {{ $isInherited ? '(Permiso de rol)' : '' }}
                            <i class="nav-icon fas fa-edit"></i>
                        </option>
                    @endforeach
                </select>
            </div>
        </div>
        <button type="submit" class="btn btn-success my-3">Guardar</button>
        <button type="button" class="btn btn-dark" data-bs-dismiss="modal">Cancelar</button>
    </form>
</div>
