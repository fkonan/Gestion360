<div class="container-fluid p-0 rounded">
  <form
    id="formPermiso"
    action="{{ route('gestion-permisos.update', ['id' => $permiso->id]) }}"
    method="POST"
    enctype="multipart/form-data"
    onsubmit="deshabilitarSubmit(this)">

    @csrf
    @method('PUT')

    <div class="row g-3 p-4">
      <div class="col-md-2">
        <label for="permisoId" class="form-label">ID</label>
        <input type="text" class="form-control" id="permisoId" value="{{ $permiso->id }}" readonly>
      </div>

      <div class="col-md-6">
        <label for="name" class="form-label">Nombre del permiso</label>
        <input type="text" class="form-control" id="name" name="name" maxlength="150" value="{{ $permiso->name }}" required>
        <span class="error text-danger fw-bold" id="error-name"></span>
      </div>

      <div class="col-md-4">
        <label for="guard_name" class="form-label">Guard</label>
        <select class="form-select select2" id="guard_name" name="guard_name" required>
          @foreach($guards as $guard)
          <option value="{{ $guard }}" @selected($permiso->guard_name === $guard)>{{ $guard }}</option>
          @endforeach
        </select>
        <span class="error text-danger fw-bold" id="error-guard_name"></span>
      </div>
    </div>

    <div class="row px-4 pb-2">
      <div class="col-md-12">
        <label for="nombre_limpio" class="form-label">Nombre limpio</label>
        <input
          type="text"
          class="form-control"
          id="nombre_limpio"
          name="nombre_limpio"
          maxlength="150"
          value="{{ $permiso->nombre_limpio ?? $permiso->name }}"
          required>
        <span class="error text-danger fw-bold" id="error-nombre_limpio"></span>
      </div>
    </div>

    <div class="px-4 pb-2">
      <small class="text-muted">Cambiar el nombre de un permiso afecta las validaciones que usan ese identificador.</small>
    </div>

    <div class="text-start ms-4 mb-4">
      <button type="submit" class="btn btn-success">Guardar</button>
      <button type="button" class="btn btn-dark" data-bs-dismiss="modal">Cancelar</button>
    </div>
  </form>
</div>
