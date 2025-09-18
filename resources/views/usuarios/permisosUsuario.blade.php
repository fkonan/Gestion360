<!-- Modal para asignar permisos a un usuario -->
<div class="container-fluid px-5 py-4 rounded">
  <form id="formPermisoUsuario" action="{{ route('permisos.update', ['id' => $usuario->IdUsuario])}}" method="POST">
    @csrf
    @method('PUT')
    <div class="row pb-3">
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
            <span class="text-success">Activo</span>
            @else
            <span class="text-danger">Inactivo</span>
            @endif
          </div>
        </div>
      </div>
      <div class="col-md-12">
        <select multiple="multiple" id="permissions" name="permissions[]" class="form-control">
          @foreach ($permisosDisponibles as $permiso)
          @php
          $isDirect = in_array($permiso->id, $permisosDirectos);
          $isInherited = in_array($permiso->id, $permisosHeredados);
          $rolName = $isInherited ? $permisosHeredadosConRol[$permiso->id] : null;
          @endphp
          <option
            class="fw-medium sidebar-dark-primary {{ $isInherited ? 'isInherited' : '' }}"
            style="cursor: pointer;"
            @if ($isInherited) disabled @endif
            value="{{ $permiso->name }}"
            title="{{ $isInherited ? 'Este permiso no puede eliminarse manualmente porque es parte del ROL: ' . $rolName : '' }}"
            @if ($isDirect || $isInherited) selected @endif>
            @if ($isInherited)🔒@endif
            {{ $permiso->name }}
          </option>
          @endforeach
        </select>
      </div>
    </div>
    <button type="submit" class="btn btn-success my-2">Guardar</button>
    <button type="button" class="btn btn-dark" data-bs-dismiss="modal">Cancelar</button>
  </form>
</div>
