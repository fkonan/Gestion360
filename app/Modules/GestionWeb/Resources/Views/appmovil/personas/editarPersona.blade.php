@extends('layouts.dashboard')

@section('title','Editar persona appmovil')

@section('breadcrumb')
<x-breadcrumb :items="[
        ['name' => 'Inicio', 'url' => route('home')],
        ['name' => 'Gestion appmovil', 'url' => route('gestion-appmovil.index')],
        ['name' => 'Gestion personas', 'url' => route('personas-appmovil.index')],
        ['name' => 'Editar persona'],
    ]" />
<br>
@endsection

@section('content')
<div class="container-fluid p-0 border shadow rounded sidebar-dark-primary">
  <div class="border rounded-top d-flex justify-content-between align-items-center px-4 bg-primary-subtle">
    <span class="text-left text-light fs-5 fw-medium">Datos persona</span>
  </div>
  <form id="formEditPersonaAppmovil" action="{{ route('personas-appmovil.update', ['id' => $persona->IdPersona]) }}" method="POST">
    @csrf
    @method('PUT')
    <div>
      <div class="row pt-4 mx-4">
        <div class="col-md-4 mb-3">
          <label for="PerTipoDoc" class="form-label">Tipo documento</label>
          <input type="text" class="form-control" id="PerTipoDoc" value="{{ $persona->tipoDocumento?->nombre ?? $persona->PerTipoDoc }}" disabled>
        </div>
        <div class="col-md-4 mb-3">
          <label for="PerNumDoc" class="form-label">Numero documento</label>
          <input type="text" class="form-control" id="PerNumDoc" value="{{ $persona->PerNumDoc }}" disabled>
        </div>
        <div class="col-md-4 mb-3">
          <label for="PerEstado" class="form-label">Estado persona</label>
          <select class="form-select" id="PerEstado" name="PerEstado" required>
            <option value="ACTIVO" @selected($persona->PerEstado === 'ACTIVO')>ACTIVO</option>
            <option value="INACTIVO" @selected($persona->PerEstado === 'INACTIVO')>INACTIVO</option>
          </select>
          <span class="error text-danger fw-bold" id="error-PerEstado"></span>
        </div>
      </div>

      <div class="row mx-4">
        <div class="col-md-4 mb-3">
          <label for="PerApellidos" class="form-label">Apellidos</label>
          <input type="text" class="form-control" id="PerApellidos" name="PerApellidos" value="{{ $persona->PerApellidos }}" required>
          <span class="error text-danger fw-bold" id="error-PerApellidos"></span>
        </div>
        <div class="col-md-4 mb-3">
          <label for="PerNombres" class="form-label">Nombres</label>
          <input type="text" class="form-control" id="PerNombres" name="PerNombres" value="{{ $persona->PerNombres }}" required>
          <span class="error text-danger fw-bold" id="error-PerNombres"></span>
        </div>
        <div class="col-md-4 mb-3">
          <label for="PerGenero" class="form-label">Genero</label>
          <select class="form-select" id="PerGenero" name="PerGenero" required>
            <option value="">Seleccione</option>
            <option value="MASCULINO" @selected($persona->PerGenero === 'MASCULINO')>MASCULINO</option>
            <option value="FEMENINO" @selected($persona->PerGenero === 'FEMENINO')>FEMENINO</option>
            <option value="OTRO" @selected($persona->PerGenero === 'OTRO')>OTRO</option>
          </select>
          <span class="error text-danger fw-bold" id="error-PerGenero"></span>
        </div>
      </div>

      <div class="row mx-4 pb-4">
        <div class="col-md-4 mb-3">
          <label for="PerFecNac" class="form-label">Fecha nacimiento</label>
          <input type="date" class="form-control" id="PerFecNac" name="PerFecNac" value="{{ $persona->PerFecNac }}" required>
          <span class="error text-danger fw-bold" id="error-PerFecNac"></span>
        </div>
        <div class="col-md-4 mb-3">
          <label for="PerFecExp" class="form-label">Fecha expedicion</label>
          <input type="date" class="form-control" id="PerFecExp" name="PerFecExp" value="{{ $persona->PerFecExp }}" required>
          <span class="error text-danger fw-bold" id="error-PerFecExp"></span>
        </div>
        <div class="col-md-4 mb-3">
          <label for="PerGruRh" class="form-label">Grupo RH</label>
          <input type="text" class="form-control" id="PerGruRh" name="PerGruRh" value="{{ $persona->PerGruRh }}">
          <span class="error text-danger fw-bold" id="error-PerGruRh"></span>
        </div>
      </div>

      <div class="border rounded-top d-flex justify-content-between align-items-center px-4 bg-primary-subtle">
        <span class="text-left text-light fs-5 fw-medium">Informacion de contacto</span>
      </div>
      <div class="row mx-4 pt-4">
        <div class="col-md-4 mb-3">
          <label for="PerTelefono" class="form-label">Telefono</label>
          <input type="text" class="form-control" id="PerTelefono" name="PerTelefono" value="{{ $persona->datos?->PerTelefono }}" required>
          <span class="error text-danger fw-bold" id="error-PerTelefono"></span>
        </div>
        <div class="col-md-4 mb-3">
          <label for="PerEmail" class="form-label">Correo</label>
          <input type="email" class="form-control" id="PerEmail" name="PerEmail" value="{{ $persona->datos?->PerEmail }}" required>
          <span class="error text-danger fw-bold" id="error-PerEmail"></span>
        </div>
        <div class="col-md-4 mb-3">
          <label for="PerDir" class="form-label">Direccion</label>
          <input type="text" class="form-control" id="PerDir" name="PerDir" value="{{ $persona->datos?->PerDir }}">
          <span class="error text-danger fw-bold" id="error-PerDir"></span>
        </div>
      </div>
      <div class="row mx-4 pb-4">
        <div class="col-md-4 mb-3">
          <label for="PerBar" class="form-label">Barrio</label>
          <input type="text" class="form-control" id="PerBar" name="PerBar" value="{{ $persona->datos?->PerBar }}">
          <span class="error text-danger fw-bold" id="error-PerBar"></span>
        </div>
      </div>

      <div class="border rounded-top d-flex justify-content-between align-items-center px-4 bg-primary-subtle">
        <span class="text-left text-light fs-5 fw-medium">Datos usuario appmovil</span>
      </div>
      <div class="row mx-4 pt-4">
        <div class="col-md-4 mb-3">
          <label for="UsuarioEstado" class="form-label">Estado usuario</label>
          <select class="form-select" id="UsuarioEstado" name="UsuarioEstado" required>
            <option value="ACTIVO" @selected($persona->usuario?->UsuarioEstado === 'ACTIVO')>ACTIVO</option>
            <option value="INACTIVO" @selected($persona->usuario?->UsuarioEstado === 'INACTIVO')>INACTIVO</option>
            <option value="SUSPENDIDO" @selected($persona->usuario?->UsuarioEstado === 'SUSPENDIDO')>SUSPENDIDO</option>
          </select>
          <span class="error text-danger fw-bold" id="error-UsuarioEstado"></span>
        </div>
        <div class="col-md-4 mb-3">
          <label for="Verificado" class="form-label">Verificado</label>
          <select class="form-select" id="Verificado" name="Verificado" required>
            <option value="TRUE" @selected($persona->usuario?->Verificado === 'TRUE')>TRUE</option>
            <option value="FALSE" @selected($persona->usuario?->Verificado === 'FALSE')>FALSE</option>
          </select>
          <span class="error text-danger fw-bold" id="error-Verificado"></span>
        </div>
        <div class="col-md-4 mb-3">
          <label for="Password" class="form-label">Nueva contrasena</label>
          <div class="input-group">
            <input type="password" class="form-control" id="Password" name="Password" placeholder="Opcional: minimo 8 caracteres">
            <button type="button" class="btn btn-outline-secondary" id="togglePassword">Mostrar</button>
          </div>
          <span class="error text-danger fw-bold" id="error-Password"></span>
        </div>
      </div>

      <div class="p-4">
        <button type="submit" class="btn btn-success">Guardar</button>
        <a type="button" class="btn btn-dark" href="{{ route('personas-appmovil.index') }}">Cancelar</a>
      </div>
    </div>
  </form>
</div>
@endsection

@pushOnce('script')
@vite(['resources/js/cargarModal.js'])
<script>
  document.addEventListener('DOMContentLoaded', function() {
    validarFormulario('#formEditPersonaAppmovil', 'PUT');

    const toggleButton = document.getElementById('togglePassword');
    const passwordInput = document.getElementById('Password');

    if (toggleButton && passwordInput) {
      toggleButton.addEventListener('click', function() {
        const isPassword = passwordInput.type === 'password';
        passwordInput.type = isPassword ? 'text' : 'password';
        toggleButton.textContent = isPassword ? 'Ocultar' : 'Mostrar';
      });
    }
  });
</script>
@endpushOnce
