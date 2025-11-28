@extends('layouts.dashboard')

@section('title','Permisos Rol')

@section('breadcrumb')
<x-breadcrumb :items="[
        ['name' => 'Inicio', 'url' => route('home')],
        ['name' => 'Gestión Sistema' , 'url' => route('gestion-sistema.index')],
        ['name' => 'Administrar Roles', 'url' => route('roles.index')],
        ['name' => 'Permisos Rol'],
    ]" />
<br>
@endsection

@section('content')
<div class="container-fluid p-0 border rounded shadow sidebar-dark-primary">

  {{-- Encabezado --}}
  <div class="border rounded-top d-flex justify-content-between align-items-center px-4 bg-primary-subtle">
    <span class="text-left text-light fs-5 fw-medium py-2">Editar Rol</span>
  </div>

  <div class="row p-4 m-0">
    <form id="formPermisoRol" action="{{ route('roles.permisos.update', ['id' => $role->id]) }}" method="POST">
      @csrf
      @method('PUT')

      {{-- Nombre del rol --}}
      <div class="input-group mb-2">
        <input type="text" class="form-control" id="name" name="name" value="{{ $role->name }}" disabled required>
        <button class="input-group-text" type="button" onclick="editarNombreRol()">
          <i class="fas fa-edit"></i>
        </button>
      </div>

      @error('name')
      <small class="text-danger fw-bold">{{ $message }}</small>
      @enderror

      <br>

      {{-- Módulos --}}
      @foreach ($modulos as $index => $modulo)
      @php
      $idCollapse = 'modulo_edit_' . $index;
      $nombreModulo = normalizarNombre($modulo->ModNom);
      $permisoModulo = \Spatie\Permission\Models\Permission::where('name', "$nombreModulo.acceder")->first();
      @endphp

      <div class="border border-primary rounded mb-4">

        {{-- Título clicable --}}
        <h5 class="p-2 px-3 bg-primary-subtle text-light d-flex justify-content-between align-items-center"
          data-bs-toggle="collapse" data-bs-target="#{{ $idCollapse }}" aria-expanded="false" style="cursor:pointer;">
          {{ ucfirst($modulo->ModNom) }}
          <i class="fa fa-chevron-down"></i>
        </h5>

        {{-- Contenido --}}
        <div class="collapse" id="{{ $idCollapse }}">
          <div class="px-3 pt-3 pb-2">

            {{-- Acceso al módulo --}}
            @if ($permisoModulo)
            <div class="form-check form-switch mb-3">
              <input class="form-check-input" type="checkbox" name="permissions[]" value="{{ $permisoModulo->name }}" {{
                in_array($permisoModulo->id, $permisosAsignados) ? 'checked' : '' }}>

              <label class="form-check-label fw-medium">Acceso al módulo</label>
            </div>
            @endif

            {{-- Submódulos --}}
            @foreach ($modulo->submodulos as $submodulo)
            <div class="pb-3 mb-3 border-bottom">

              <p class="text-secondary fw-semibold mb-2">
                {{ ucfirst($submodulo->SubModNom) }}
              </p>

              <div class="row g-2">
                @foreach ($submodulo->permisos as $permiso)

                <div class="col-md-3">
                  <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" name="permissions[]" value="{{ $permiso->name }}" {{
                      in_array($permiso->id, $permisosAsignados) ? 'checked' : '' }}>

                    <label class="form-check-label" title="{{ $permiso->display_name }}">
                        {{ Str::limit($permiso->name, 25) }}
                    </label>

                  </div>
                </div>

                @endforeach
              </div>

            </div>
            @endforeach

          </div>
        </div>
      </div>
      @endforeach

      {{-- Botones --}}
      <button type="submit" class="btn btn-success my-3">Guardar</button>
      <a href="{{ route('roles.index') }}" class="btn btn-dark">Cancelar</a>

    </form>
  </div>
</div>
@endsection

@pushOnce('script')
<script>
  function editarNombreRol() {
    const rolField = document.getElementById("name");
    rolField.disabled = !rolField.disabled;
  }
</script>
@endpushOnce
