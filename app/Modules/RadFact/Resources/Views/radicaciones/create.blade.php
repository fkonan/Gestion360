@extends('layouts.dashboard')

@section('title', 'Nueva Radicación')

@section('breadcrumb')
   <x-breadcrumb :items="[
       ['name' => 'Inicio', 'url' => route('home')],
       ['name' => 'Radicaciones', 'url' => route('radfact.radicaciones.index')],
       ['name' => 'Nueva radicación'],
   ]" />
   <br>
@endsection

@section('content')
   <div class="container-fluid p-0 border shadow rounded sidebar-dark-primary">
      <div class="border rounded-top d-flex justify-content-between align-items-center px-4 bg-primary-subtle">
         <span class="text-left text-light fs-5 fw-medium">Información de la factura</span>
      </div>

      <form id="formRadicacion" action="{{ route('radfact.radicaciones.store') }}" method="POST"
         enctype="multipart/form-data">
         @csrf

         <div class="row pt-4 mx-4">
            {{-- Proveedor --}}
            <div class="col-md-6 mb-3">
               <label for="proveedor_id" class="form-label">Proveedor</label>
               <select class="form-select select2 @error('proveedor_id') is-invalid @enderror" id="proveedor_id"
                  name="proveedor_id" required>
                  <option value="">Seleccione un proveedor...</option>
                  @foreach ($proveedores as $proveedor)
                     <option value="{{ $proveedor->id }}" {{ old('proveedor_id') == $proveedor->id ? 'selected' : '' }}>
                        {{ $proveedor->nombre_completo }} - {{ $proveedor->documento }}
                     </option>
                  @endforeach
               </select>
               <span class="error text-danger fw-bold" id="error-proveedor_id">
                  @error('proveedor_id')
                     {{ $message }}
                  @enderror
               </span>
            </div>

            {{-- Número de Factura --}}
            <div class="col-md-3 mb-3">
               <label for="num_factura" class="form-label">Número de Factura</label>
               <input type="text" class="form-control @error('num_factura') is-invalid @enderror" id="num_factura"
                  name="num_factura" value="{{ old('num_factura') }}" required>
               <span class="error text-danger fw-bold" id="error-num_factura">
                  @error('num_factura')
                     {{ $message }}
                  @enderror
               </span>
            </div>

            {{-- Número de Contrato --}}
            <div class="col-md-3 mb-3">
               <label for="num_contrato" class="form-label">Número de Contrato</label>
               <input type="text" class="form-control @error('num_contrato') is-invalid @enderror" id="num_contrato"
                  name="num_contrato" value="{{ old('num_contrato') }}">
               <span class="error text-danger fw-bold" id="error-num_contrato">
                  @error('num_contrato')
                     {{ $message }}
                  @enderror
               </span>
            </div>
         </div>

         <div class="row mx-4">
            {{-- Fecha Radicación --}}
            <div class="col-md-3 mb-3">
               <label for="fecha_radicacion" class="form-label">Fecha de Radicación</label>
               <input type="date" class="form-control @error('fecha_radicacion') is-invalid @enderror"
                  id="fecha_radicacion" name="fecha_radicacion" value="{{ old('fecha_radicacion', date('Y-m-d')) }}"
                  required>
               <span class="error text-danger fw-bold" id="error-fecha_radicacion">
                  @error('fecha_radicacion')
                     {{ $message }}
                  @enderror
               </span>
            </div>

            {{-- Fecha Vencimiento --}}
            <div class="col-md-3 mb-3">
               <label for="fecha_vencimiento" class="form-label">Fecha de Vencimiento</label>
               <input type="date" class="form-control @error('fecha_vencimiento') is-invalid @enderror"
                  id="fecha_vencimiento" name="fecha_vencimiento" value="{{ old('fecha_vencimiento') }}" required>
               <span class="error text-danger fw-bold" id="error-fecha_vencimiento">
                  @error('fecha_vencimiento')
                     {{ $message }}
                  @enderror
               </span>
            </div>

            {{-- Valor Total --}}
            <div class="col-md-3 mb-3">
               <label for="valor" class="form-label">Valor Total</label>
               <div class="input-group">
                  <span class="input-group-text">$</span>
                  <input type="number" class="form-control @error('valor') is-invalid @enderror" id="valor"
                     name="valor" value="{{ old('valor') }}" step="0.01" min="0" required>
               </div>
               <span class="error text-danger fw-bold" id="error-valor">
                  @error('valor')
                     {{ $message }}
                  @enderror
               </span>
            </div>

            {{-- Número de Pagos --}}
            <div class="col-md-3 mb-3">
               <label for="numero_pagos" class="form-label">Número de Pagos</label>
               <input type="number" class="form-control @error('numero_pagos') is-invalid @enderror" id="numero_pagos"
                  name="numero_pagos" value="{{ old('numero_pagos', 1) }}" min="1" required>
               <span class="error text-danger fw-bold" id="error-numero_pagos">
                  @error('numero_pagos')
                     {{ $message }}
                  @enderror
               </span>
            </div>
         </div>

         <div class="row mx-4">
            {{-- Descripción --}}
            <div class="col-md-6 mb-3">
               <label for="descripcion" class="form-label">Descripción</label>
               <textarea class="form-control @error('descripcion') is-invalid @enderror" id="descripcion" name="descripcion"
                  rows="3" required>{{ old('descripcion') }}</textarea>
               <span class="error text-danger fw-bold" id="error-descripcion">
                  @error('descripcion')
                     {{ $message }}
                  @enderror
               </span>
            </div>

            {{-- Observaciones --}}
            <div class="col-md-6 mb-3">
               <label for="observacion" class="form-label">Observaciones</label>
               <textarea class="form-control @error('observacion') is-invalid @enderror" id="observacion" name="observacion"
                  rows="3">{{ old('observacion') }}</textarea>
               <span class="error text-danger fw-bold" id="error-observacion">
                  @error('observacion')
                     {{ $message }}
                  @enderror
               </span>
            </div>
         </div>

         <div class="row mx-4 pb-4">
            {{-- PDF --}}
            <div class="col-md-6 mb-3">
               <label for="pdf" class="form-label">PDF de Factura</label>
               <input type="file" class="form-control @error('pdf') is-invalid @enderror" id="pdf"
                  name="pdf" accept=".pdf">
               <small class="form-text text-muted">Tamaño máximo: 10MB</small>
               <span class="error text-danger fw-bold" id="error-pdf">
                  @error('pdf')
                     {{ $message }}
                  @enderror
               </span>
            </div>

            {{-- Necesita Visto Bueno --}}
            <div class="col-md-6 mb-3">
               <label class="form-label d-block">&nbsp;</label>
               <div class="form-check">
                  <input class="form-check-input" type="checkbox" id="necesita_visto_bueno" name="necesita_visto_bueno"
                     value="1" {{ old('necesita_visto_bueno') ? 'checked' : '' }}>
                  <label class="form-check-label" for="necesita_visto_bueno">
                     ¿Necesita Visto Bueno?
                  </label>
               </div>
            </div>
         </div>

         <div class="border rounded-top d-flex justify-content-between align-items-center px-4 bg-primary-subtle">
            <span class="text-left text-light fs-5 fw-medium">Distribución por áreas</span>
         </div>

         <div class="px-4 pt-4">
            <p class="text-muted">Distribuya el valor total entre las áreas correspondientes. La suma de porcentajes debe
               ser 100%.</p>

            <div id="distribuciones-container">
               {{-- Las distribuciones se agregan dinámicamente aquí --}}
            </div>

            <div class="row mt-3">
               <div class="col-12">
                  <button type="button" class="btn btn-outline-primary" id="btnAgregarDistribucion">
                     <i class="fas fa-plus"></i> Agregar Área
                  </button>
               </div>
            </div>

            <div class="row mt-3 pb-4">
               <div class="col-md-6">
                  <div class="alert alert-info mb-0">
                     <strong>Total Porcentaje:</strong> <span id="totalPorcentaje">0</span>%
                     <span id="alertPorcentaje" class="ms-2"></span>
                  </div>
               </div>
               <div class="col-md-6">
                  <div class="alert alert-secondary mb-0">
                     <strong>Total Valor:</strong> $<span id="totalValor">0.00</span>
                  </div>
               </div>
            </div>
         </div>

         <div class="p-4">
            <button type="submit" class="btn btn-success" id="btnGuardar">
               <i class="fas fa-save"></i> Guardar
            </button>
            <a href="{{ route('radfact.radicaciones.index') }}" class="btn btn-dark">
               <i class="fas fa-times"></i> Cancelar
            </a>
         </div>
      </form>
   </div>

   {{-- Template para distribución --}}
   <template id="distribucion-template">
      <div class="card mb-2 distribucion-item">
         <div class="card-body">
            <div class="row align-items-center">
               <div class="col-md-5">
                  <label class="form-label">Área</label>
                  <select class="form-select select2-dist" name="distribuciones[INDEX][area_id]" required>
                     <option value="">Seleccione área...</option>
                     @foreach ($areas as $area)
                        <option value="{{ $area->id }}">{{ $area->area }}</option>
                     @endforeach
                  </select>
               </div>
               <div class="col-md-3">
                  <label class="form-label">Porcentaje (%)</label>
                  <input type="number" class="form-control porcentaje-input" name="distribuciones[INDEX][porcentaje]"
                     min="0" max="100" step="0.01" required>
               </div>
               <div class="col-md-3">
                  <label class="form-label">Valor Calculado</label>
                  <input type="text" class="form-control valor-calculado" readonly>
               </div>
               <div class="col-md-1">
                  <label class="form-label d-none d-md-block">&nbsp;</label>
                  <button type="button" class="btn btn-danger btn-sm btn-eliminar-dist w-100">
                     <i class="fas fa-trash"></i>
                  </button>
               </div>
            </div>
         </div>
      </div>
   </template>
@endsection

@pushOnce('script')
   @vite(['resources/js/cargarModal.js'])

   <script>
      document.addEventListener('DOMContentLoaded', function() {
         let distribucionIndex = 0;
         let contratoTimer = null;
         const buscarContratoUrl = "{{ route('radfact.radicaciones.buscar_contrato') }}";

         const formRadicacion = document.getElementById('formRadicacion');
         const proveedorSelect = document.getElementById('proveedor_id');
         const valorInput = document.getElementById('valor');
         const totalPorcentajeSpan = document.getElementById('totalPorcentaje');
         const totalValorSpan = document.getElementById('totalValor');
         const alertPorcentaje = document.getElementById('alertPorcentaje');
         const btnGuardar = document.getElementById('btnGuardar');
         const distribucionesContainer = document.getElementById('distribuciones-container');
         const btnAgregarDistribucion = document.getElementById('btnAgregarDistribucion');
         const numContratoInput = document.getElementById('num_contrato');
         const numeroPagosInput = document.getElementById('numero_pagos');
         const fechaVencimientoInput = document.getElementById('fecha_vencimiento');
         const descripcionInput = document.getElementById('descripcion');
         const observacionInput = document.getElementById('observacion');
         const necesitaVistoBuenoInput = document.getElementById('necesita_visto_bueno');

         function initSelect2(element, options) {
            if (!element) {
               return;
            }

            if (typeof window.jQuery !== 'undefined' && typeof window.jQuery.fn.select2 !== 'undefined') {
               const select2Options = {
                  ...options
               };
               if (select2Options.dropdownParent instanceof HTMLElement) {
                  select2Options.dropdownParent = window.jQuery(select2Options.dropdownParent);
               }
               window.jQuery(element).select2(select2Options);
            }
         }

         function setSelectValue(element, value) {
            if (!element) {
               return;
            }

            element.value = value;

            if (typeof window.jQuery !== 'undefined' && typeof window.jQuery.fn.select2 !== 'undefined') {
               window.jQuery(element).trigger('change');
               return;
            }

            element.dispatchEvent(new Event('change', {
               bubbles: true
            }));
         }

         initSelect2(proveedorSelect, {
            dropdownParent: formRadicacion,
            width: '100%',
            placeholder: 'Seleccione un proveedor...',
            allowClear: true
         });

         function calcularTotales() {
            let totalPorcentaje = 0;
            const valorTotal = parseFloat(valorInput.value) || 0;
            let totalValor = 0;

            const distribucionItems = distribucionesContainer.querySelectorAll('.distribucion-item');
            distribucionItems.forEach(function(item) {
               const porcentajeInput = item.querySelector('.porcentaje-input');
               const valorCalculadoInput = item.querySelector('.valor-calculado');
               const porcentaje = parseFloat(porcentajeInput ? porcentajeInput.value : '') || 0;
               const valorCalculado = (porcentaje / 100) * valorTotal;

               if (valorCalculadoInput) {
                  valorCalculadoInput.value = '$' + valorCalculado.toFixed(2);
               }

               totalPorcentaje += porcentaje;
               totalValor += valorCalculado;
            });

            totalPorcentajeSpan.textContent = totalPorcentaje.toFixed(2);
            totalValorSpan.textContent = totalValor.toFixed(2);

            if (Math.abs(totalPorcentaje - 100) < 0.01 && totalPorcentaje > 0) {
               alertPorcentaje.innerHTML =
                  '<span class="badge bg-success"><i class="fas fa-check"></i> Correcto</span>';
               btnGuardar.disabled = false;
            } else if (totalPorcentaje === 0) {
               alertPorcentaje.innerHTML =
                  '<span class="badge bg-secondary"><i class="fas fa-info-circle"></i> Agregue distribuciones</span>';
               btnGuardar.disabled = true;
            } else {
               alertPorcentaje.innerHTML =
                  '<span class="badge bg-warning text-dark"><i class="fas fa-exclamation-triangle"></i> Debe sumar 100%</span>';
               btnGuardar.disabled = true;
            }
         }

         function agregarDistribucion(data = null) {
            const template = document.getElementById('distribucion-template');
            if (!template) {
               console.error('Template de distribucion no encontrado');
               return;
            }

            const templateContent = template.innerHTML;
            const newDistribucion = templateContent.replace(/INDEX/g, distribucionIndex);
            distribucionesContainer.insertAdjacentHTML('beforeend', newDistribucion);

            const selectName = `distribuciones[${distribucionIndex}][area_id]`;
            const porcentajeName = `distribuciones[${distribucionIndex}][porcentaje]`;
            const selectElement = formRadicacion.querySelector(`select[name="${selectName}"]`);

            initSelect2(selectElement, {
               dropdownParent: document.body,
               width: '100%',
               placeholder: 'Seleccione area...'
            });

            if (data) {
               setSelectValue(selectElement, data.area_id);
               const porcentajeElement = formRadicacion.querySelector(`input[name="${porcentajeName}"]`);
               if (porcentajeElement) {
                  porcentajeElement.value = data.porcentaje;
               }
            }

            distribucionIndex++;
            calcularTotales();
         }

         btnAgregarDistribucion.addEventListener('click', function(e) {
            e.preventDefault();
            agregarDistribucion();
         });

         document.addEventListener('click', function(e) {
            const button = e.target.closest('.btn-eliminar-dist');
            if (!button) {
               return;
            }

            e.preventDefault();
            const distribucionItem = button.closest('.distribucion-item');
            if (distribucionItem) {
               distribucionItem.remove();
            }
            calcularTotales();
         });

         document.addEventListener('input', function(e) {
            if (!e.target.classList.contains('porcentaje-input')) {
               return;
            }

            calcularTotales();
         });

         valorInput.addEventListener('input', function() {
            calcularTotales();
         });

         function buscarYAutocompletarPorContrato(numeroContrato) {
            const contrato = (numeroContrato || '').trim();
            if (!contrato) {
               return;
            }

            const params = new URLSearchParams({
               num_contrato: contrato
            });

            fetch(`${buscarContratoUrl}?${params.toString()}`, {
                  method: 'GET',
                  headers: {
                     'X-Requested-With': 'XMLHttpRequest',
                     Accept: 'application/json'
                  }
               })
               .then(function(response) {
                  if (!response.ok) {
                     return null;
                  }

                  return response.json();
               })
               .then(function(response) {
                  if (!response || !response.encontrado || !response.data) {
                     return;
                  }

                  const data = response.data;

                  setSelectValue(proveedorSelect, data.proveedor_id);
                  numeroPagosInput.value = data.numero_pagos;
                  fechaVencimientoInput.value = data.fecha_vencimiento;
                  valorInput.value = data.valor;
                  descripcionInput.value = data.descripcion || '';
                  observacionInput.value = data.observacion || '';
                  necesitaVistoBuenoInput.checked = !!data.necesita_visto_bueno;

                  if (Array.isArray(data.distribuciones) && data.distribuciones.length > 0) {
                     distribucionesContainer.innerHTML = '';
                     distribucionIndex = 0;

                     data.distribuciones.forEach(function(distribucion) {
                        agregarDistribucion(distribucion);
                     });
                  } else {
                     calcularTotales();
                  }
               })
               .catch(function() {
                  // No bloquear el flujo si falla la consulta
               });
         }

         numContratoInput.addEventListener('input', function() {
            clearTimeout(contratoTimer);
            contratoTimer = setTimeout(function() {
               buscarYAutocompletarPorContrato(numContratoInput.value);
            }, 500);
         });

         numContratoInput.addEventListener('blur', function() {
            clearTimeout(contratoTimer);
            buscarYAutocompletarPorContrato(numContratoInput.value);
         });

         setTimeout(function() {
            btnAgregarDistribucion.click();
         }, 100);

         function mostrarMensajeValidacion(mensaje) {
            if (window.Swal && typeof window.Swal.fire === 'function') {
               window.Swal.fire({
                  icon: 'warning',
                  title: 'Validacion del formulario',
                  text: mensaje,
                  confirmButtonText: 'Entendido'
               });
               return;
            }

            if (typeof window.mostrarToast === 'function') {
               window.mostrarToast(mensaje, 'warning');
               return;
            }

            console.warn(mensaje);
         }

         formRadicacion.addEventListener('submit', function(e) {
            if (btnGuardar.dataset.saving === '1') {
               e.preventDefault();
               return false;
            }

            const totalPorcentaje = parseFloat(totalPorcentajeSpan.textContent);
            const numDistribuciones = distribucionesContainer.querySelectorAll('.distribucion-item').length;

            if (Math.abs(totalPorcentaje - 100) >= 0.01) {
               e.preventDefault();
               mostrarMensajeValidacion('El total de porcentajes debe ser 100%. Actualmente es: ' +
                  totalPorcentaje + '%');
               return;
            }

            if (numDistribuciones === 0) {
               e.preventDefault();
               mostrarMensajeValidacion('Debe agregar al menos una distribucion');
               return false;
            }

            btnGuardar.dataset.saving = '1';
            btnGuardar.disabled = true;
            btnGuardar.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Guardando...';

            if (typeof window.deshabilitarSubmit === 'function') {
               window.deshabilitarSubmit(formRadicacion, true);
            } else {
               const loader = document.getElementById('fullscreen-loader');
               if (loader) {
                  loader.style.display = 'flex';
               }
            }

            return true;
         });

         if (typeof validarFormulario === 'function') {
            validarFormulario('#formRadicacion');
         }

         calcularTotales();
      });
   </script>
@endpushOnce
