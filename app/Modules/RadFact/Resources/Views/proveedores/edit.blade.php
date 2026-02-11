@extends('layouts.dashboard')

@section('title', 'Editar Proveedor')

@section('breadcrumb')
   <x-breadcrumb :items="[
       ['name' => 'Inicio', 'url' => route('home')],
       ['name' => 'Proveedores', 'url' => route('radfact.proveedores.index')],
       ['name' => 'Editar proveedor'],
   ]" />
   <br>
@endsection

@section('content')
   <div class="container-fluid p-0 border shadow rounded sidebar-dark-primary">
      <div class="border rounded-top d-flex justify-content-between align-items-center px-4 bg-primary-subtle">
         <span class="text-left text-light fs-5 fw-medium">Datos del proveedor</span>
      </div>

      <form id="formProveedor" action="{{ route('radfact.proveedores.update', $proveedor) }}" method="POST">
         @csrf
         @method('PUT')

         <div class="row pt-4 mx-4">
            {{-- Tipo Documento --}}
            <div class="col-md-3 mb-3">
               <label for="tipo_documento" class="form-label">Tipo de Documento</label>
               <select class="form-select @error('tipo_documento') is-invalid @enderror" id="tipo_documento"
                  name="tipo_documento" required>
                  <option value="" disabled>Seleccione...</option>
                  <option value="NIT"
                     {{ old('tipo_documento', $proveedor->tipo_documento) === 'NIT' ? 'selected' : '' }}>NIT</option>
                  <option value="CC"
                     {{ old('tipo_documento', $proveedor->tipo_documento) === 'CC' ? 'selected' : '' }}>Cédula de
                     Ciudadanía</option>
                  <option value="CE"
                     {{ old('tipo_documento', $proveedor->tipo_documento) === 'CE' ? 'selected' : '' }}>Cédula de
                     Extranjería</option>
               </select>
               <span class="error text-danger fw-bold" id="error-tipo_documento">
                  @error('tipo_documento')
                     {{ $message }}
                  @enderror
               </span>
            </div>

            {{-- Documento --}}
            <div class="col-md-3 mb-3">
               <label for="documento" class="form-label">Documento/NIT</label>
               <input type="text" class="form-control @error('documento') is-invalid @enderror" id="documento"
                  name="documento" value="{{ old('documento', $proveedor->documento) }}" required>
               <span class="error text-danger fw-bold" id="error-documento">
                  @error('documento')
                     {{ $message }}
                  @enderror
               </span>
            </div>

            {{-- Razón Social (para NIT) --}}
            <div class="col-md-6 mb-3" id="div_razon_social">
               <label for="razon_social" class="form-label">Razón Social</label>
               <input type="text" class="form-control @error('razon_social') is-invalid @enderror" id="razon_social"
                  name="razon_social" value="{{ old('razon_social', $proveedor->razon_social) }}">
               <span class="error text-danger fw-bold" id="error-razon_social">
                  @error('razon_social')
                     {{ $message }}
                  @enderror
               </span>
            </div>

            <div class="col-md-6" id="div_nombres_apellidos">
               {{-- Nombres (para personas naturales) --}}
               <div class="col-md-6 mb-3">
                  <label for="nombres" class="form-label">Nombres</label>
                  <input type="text" class="form-control @error('nombres') is-invalid @enderror" id="nombres"
                     name="nombres" value="{{ old('nombres', $proveedor->nombres) }}">
                  <span class="error text-danger fw-bold" id="error-nombres">
                     @error('nombres')
                        {{ $message }}
                     @enderror
                  </span>
               </div>

               {{-- Apellidos (para personas naturales) --}}
               <div class="col-md-6 mb-3">
                  <label for="apellidos" class="form-label">Apellidos</label>
                  <input type="text" class="form-control @error('apellidos') is-invalid @enderror" id="apellidos"
                     name="apellidos" value="{{ old('apellidos', $proveedor->apellidos) }}">
                  <span class="error text-danger fw-bold" id="error-apellidos">
                     @error('apellidos')
                        {{ $message }}
                     @enderror
                  </span>
               </div>
            </div>
         </div>

         <div class="border rounded-top d-flex justify-content-between align-items-center px-4 bg-primary-subtle mt-3">
            <span class="text-left text-light fs-5 fw-medium">Información de contacto</span>
         </div>

         <div class="row pt-4 mx-4 pb-4">
            {{-- Correo --}}
            <div class="col-md-4 mb-3">
               <label for="correo" class="form-label">Correo Electrónico</label>
               <input type="email" class="form-control @error('correo') is-invalid @enderror" id="correo"
                  name="correo" value="{{ old('correo', $proveedor->correo) }}">
               <span class="error text-danger fw-bold" id="error-correo">
                  @error('correo')
                     {{ $message }}
                  @enderror
               </span>
            </div>

            {{-- Teléfono --}}
            <div class="col-md-4 mb-3">
               <label for="telefono" class="form-label">Teléfono</label>
               <input type="text" class="form-control @error('telefono') is-invalid @enderror" id="telefono"
                  name="telefono" value="{{ old('telefono', $proveedor->telefono) }}">
               <span class="error text-danger fw-bold" id="error-telefono">
                  @error('telefono')
                     {{ $message }}
                  @enderror
               </span>
            </div>
         </div>

         <div class="border rounded-top d-flex justify-content-between align-items-center px-4 bg-primary-subtle">
            <span class="text-left text-light fs-5 fw-medium">Información de registro</span>
         </div>

         <div class="row pt-4 mx-4 pb-4">
            <div class="col-md-4 mb-3">
               <label class="form-label">Creado por</label>
               <input type="text" class="form-control" value="{{ $proveedor->usuario->Usuario ?? 'N/A' }}" disabled>
            </div>
            <div class="col-md-4 mb-3">
               <label class="form-label">Fecha de creación</label>
               <input type="text" class="form-control" value="{{ $proveedor->created_at->format('d/m/Y H:i') }}"
                  disabled>
            </div>
            <div class="col-md-4 mb-3">
               <label class="form-label">Última actualización</label>
               <input type="text" class="form-control" value="{{ $proveedor->updated_at->format('d/m/Y H:i') }}"
                  disabled>
            </div>
         </div>

         <div class="p-4">
            <button type="submit" class="btn btn-success">
               <i class="fas fa-save"></i> Guardar
            </button>
            <a href="{{ route('radfact.proveedores.index') }}" class="btn btn-dark">
               <i class="fas fa-times"></i> Cancelar
            </a>
         </div>
      </form>
   </div>
@endsection

@pushOnce('script')
   @vite(['resources/js/cargarModal.js'])

   <script>
      document.addEventListener('DOMContentLoaded', function() {
         const tipoDocSelect = document.getElementById('tipo_documento');
         const divRazonSocial = document.getElementById('div_razon_social');
         const divNombresApellidos = document.getElementById('div_nombres_apellidos');
         const razonSocialInput = document.getElementById('razon_social');
         const nombresInput = document.getElementById('nombres');
         const apellidosInput = document.getElementById('apellidos');
         const formProveedor = document.getElementById('formProveedor');

         // Mostrar/ocultar campos según tipo de documento
         function toggleCampos() {
            const tipoDoc = tipoDocSelect.value;

            if (tipoDoc === 'NIT') {
               // Para empresas (NIT) mostrar Razón Social
               divRazonSocial.style.display = 'block';
               divNombresApellidos.style.display = 'none';
               razonSocialInput.required = true;
               nombresInput.required = false;
               apellidosInput.required = false;
            } else if (tipoDoc === 'CC' || tipoDoc === 'CE') {
               // Para personas naturales mostrar Nombres y Apellidos
               divRazonSocial.style.display = 'none';
               divNombresApellidos.style.display = 'flex';
               razonSocialInput.required = false;
               nombresInput.required = true;
               apellidosInput.required = true;
            } else {
               // Sin selección, ocultar campos condicionales
               divRazonSocial.style.display = 'none';
               divNombresApellidos.style.display = 'none';
               razonSocialInput.required = false;
               nombresInput.required = false;
               apellidosInput.required = false;
            }
         }

         // Evento change
         tipoDocSelect.addEventListener('change', toggleCampos);

         // Trigger inicial
         toggleCampos();

         // Validación del formulario
         formProveedor.addEventListener('submit', function(e) {
            const tipoDoc = tipoDocSelect.value;

            if (tipoDoc === 'NIT') {
               const razonSocial = razonSocialInput.value.trim();
               if (!razonSocial) {
                  alert('La razón social es requerida para NIT');
                  e.preventDefault();
                  return false;
               }
            } else if (tipoDoc === 'CC' || tipoDoc === 'CE') {
               const nombres = nombresInput.value.trim();
               const apellidos = apellidosInput.value.trim();
               if (!nombres || !apellidos) {
                  alert('Los nombres y apellidos son requeridos para persona natural');
                  e.preventDefault();
                  return false;
               }
            }

            return true;
         });

         // Validar formulario con la función global
         if (typeof validarFormulario === 'function') {
            validarFormulario("#formProveedor");
         }
      });
   </script>
@endpushOnce
