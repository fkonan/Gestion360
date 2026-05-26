@extends('layouts.dashboard')

@section('title', 'Radicar permiso permanente')

@section('breadcrumb')
<x-breadcrumb :items="[
        ['name' => 'Inicio', 'url' => route('home')],
        ['name' => 'Solicitudes y novedades', 'url' => route('gestionRRHH.solicitudes.index')],
        ['name' => 'Radicar solicitud', 'url' => route('gestionRRHH.solicitudes.radicar')],
        ['name' => 'Radicar permiso permanente']
    ]" />
@endsection

@section('content')
@php
  $jornadaOld = \App\Modules\GestionRRHH\Services\Novedades\PermisosPermanentes\EmpleadoPermisoPermanenteService::normalizarJornada(old('jornada'));
@endphp
<div class="container-fluid tableContainer p-0 border rounded sidebar-dark-primary permiso-radicar-page" style="min-height:150px">
  <x-sectionHeader
    titulo="Radicar permiso permanente"
    rutaVolver="{{ route('gestionRRHH.solicitudes.radicar') }}"
    :clasePosition="false" />

  <div style="padding:1.5em">
    @if ($errors->any())
    <div class="alert alert-danger">
      <ul class="mb-0">
        @foreach ($errors->all() as $error)
        <li>{{ $error }}</li>
        @endforeach
      </ul>
    </div>
    @endif

    @if(session('alert'))
    <div class="alert alert-{{ session('alert.type', 'info') }}">
      <strong>{{ session('alert.title') }}</strong>
      @if(!empty(session('alert.description')))
      <div>{!! session('alert.description') !!}</div>
      @endif
    </div>
    @endif

    @if(session('error'))
    <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    <form method="POST" action="{{ route('gestionRRHH.permisos.permisos-permanentes.store') }}" class="permiso-form" enctype="multipart/form-data">
      @csrf

      <div class="row g-3">
        <div class="col-12 col-xl-7">
          <div class="permiso-panel h-100">
            <h6 class="permiso-panel-title">Empleado</h6>
            <div class="row g-3">
              <div class="col-12 col-md-6">
                <label for="identificacion" class="form-label">Documento</label>
                <div class="input-group">
                  <input
                    type="text"
                    class="form-control"
                    id="identificacion"
                    name="identificacion"
                    maxlength="50"
                    value="{{ old('identificacion', $documentoUsuarioDefault ?? '') }}"
                    required>
                  <button type="button" class="btn btn-outline-dark" id="consultarPersonaBtn">Consultar</button>
                </div>
                <small class="text-muted">Consulta automática al salir del campo.</small>
                <div id="estado_persona" class="form-text"></div>
              </div>

              <div class="col-12 col-md-6">
                <label for="persona_nombre" class="form-label">Nombre completo</label>
                <input type="text" class="form-control" id="persona_nombre" readonly>
              </div>

              <div class="col-6 col-md-3">
                <label for="persona_codigo" class="form-label">Código</label>
                <input type="text" class="form-control" id="persona_codigo" readonly>
              </div>

              <div class="col-6 col-md-5">
                <label for="persona_seccion" class="form-label">Sección</label>
                <input type="text" class="form-control" id="persona_seccion" readonly>
              </div>

              <div class="col-6 col-md-2">
                <label for="persona_sexo" class="form-label">Sexo</label>
                <input type="text" class="form-control" id="persona_sexo" readonly>
              </div>

              <div class="col-6 col-md-2">
                <label for="persona_edad" class="form-label">Edad</label>
                <input type="text" class="form-control" id="persona_edad" readonly>
              </div>
            </div>
          </div>
        </div>

        <div class="col-12 col-xl-5">
          <div class="permiso-panel h-100">
            <h6 class="permiso-panel-title">Rango del permiso</h6>
            <div class="row g-3">
              <div class="col-12">
                <label for="fecha_inicio" class="form-label">Fecha inicio</label>
                <input
                  type="date"
                  class="form-control"
                  id="fecha_inicio"
                  name="fecha_inicio"
                  value="{{ old('fecha_inicio') }}"
                  required>
              </div>
              <div class="col-12">
                <label for="fecha_fin" class="form-label">Fecha fin</label>
                <input
                  type="date"
                  class="form-control"
                  id="fecha_fin"
                  name="fecha_fin"
                  value="{{ old('fecha_fin') }}"
                  required>
              </div>
            </div>
          </div>
        </div>

        <div class="col-12 col-lg-6">
          <div class="permiso-panel h-100">
            <h6 class="permiso-panel-title">Aplicacion por jornada</h6>
            <div class="row g-3">
              <div class="col-12">
                <label for="jornada" class="form-label">Jornada</label>
                <select class="form-select" id="jornada" name="jornada" required>
                  <option value="">Seleccione...</option>
                  @foreach($opcionesJornada as $codigo => $label)
                  <option value="{{ $codigo }}" {{ $jornadaOld === $codigo ? 'selected' : '' }}>{{ $label }}</option>
                  @endforeach
                </select>
              </div>

              <div class="col-12">
                <div class="form-check">
                  <input class="form-check-input" type="checkbox" value="1" id="horario_fijo" name="horario_fijo" {{ old('horario_fijo') ? 'checked' : '' }}>
                  <label class="form-check-label" for="horario_fijo">
                    Definir horario alterno fijo
                  </label>
                </div>
                <small class="text-muted">Si no marcas esta opción, solo se aplicará exención de tardanza para la jornada indicada.</small>
              </div>
            </div>
          </div>
        </div>

        <div class="col-12 col-lg-6">
          <div class="permiso-panel h-100">
            <h6 class="permiso-panel-title">Horario alterno (opcional)</h6>
            <div class="row g-3">
              <div class="col-6">
                <label for="hora_ingreso_j1" class="form-label">Ingreso jornada 1</label>
                <input type="time" class="form-control" id="hora_ingreso_j1" name="hora_ingreso_j1" value="{{ old('hora_ingreso_j1') }}">
              </div>
              <div class="col-6">
                <label for="hora_salida_j1" class="form-label">Salida jornada 1</label>
                <input type="time" class="form-control" id="hora_salida_j1" name="hora_salida_j1" value="{{ old('hora_salida_j1') }}">
              </div>
               <div class="col-6">
                <label for="hora_ingreso_j2" class="form-label">Ingreso jornada 2</label>
                <input type="time" class="form-control" id="hora_ingreso_j2" name="hora_ingreso_j2" value="{{ old('hora_ingreso_j2') }}">
              </div>
              <div class="col-6">
                <label for="hora_salida_j2" class="form-label">Salida jornada 2</label>
                <input type="time" class="form-control" id="hora_salida_j2" name="hora_salida_j2" value="{{ old('hora_salida_j2') }}">
              </div>
            </div>
          </div>
        </div>

        <div class="col-12 col-lg-6">
          <div class="permiso-panel h-100">
            <h6 class="permiso-panel-title">Observación</h6>
            <textarea
              class="form-control"
              id="observacion"
              name="observacion"
              rows="6"
              maxlength="1000">{{ old('observacion') }}</textarea>
            <small class="text-muted">Opcional.</small>
          </div>
        </div>

        <div class="col-12 col-lg-6">
          <div class="permiso-panel h-100">
            <h6 class="permiso-panel-title">Soportes obligatorios</h6>
            <div class="mb-3">
              <label for="carta_solicitud" class="form-label">Carta de solicitud</label>
              <input type="file" class="form-control" id="carta_solicitud" name="carta_solicitud" accept=".pdf,.jpg,.jpeg,.png" required>
            </div>
            <div class="mb-2">
              <label for="documento_soporte" class="form-label">Documento soporte</label>
              <input type="file" class="form-control" id="documento_soporte" name="documento_soporte" accept=".pdf,.jpg,.jpeg,.png" required>
            </div>
            <small class="text-muted">Formatos permitidos: PDF, JPG, JPEG y PNG (max. 10 MB por archivo).</small>
          </div>
        </div>

        <div class="col-12">
          <div class="permiso-actions">
            <button type="submit" class="btn btn-success px-4">Radicar permiso permanente</button>
            <button type="reset" class="btn btn-outline-secondary">Limpiar</button>
          </div>
        </div>
      </div>
    </form>
  </div>
</div>
@endsection

@pushOnce('css')
@vite('resources/css/gestionrrhh/novedades-radicar.css')
@endPushOnce

@pushOnce('script')
<script>
  const rutaPersonaTemplate = @json(route('gestionRRHH.permisos.persona', ['documento' => '__DOC__']));

  document.addEventListener('DOMContentLoaded', function() {
    const form = document.querySelector('form.permiso-form');
    const identificacionInput = document.getElementById('identificacion');
    const consultarPersonaBtn = document.getElementById('consultarPersonaBtn');
    const estadoPersona = document.getElementById('estado_persona');
    const personaNombre = document.getElementById('persona_nombre');
    const personaCodigo = document.getElementById('persona_codigo');
    const personaSeccion = document.getElementById('persona_seccion');
    const personaSexo = document.getElementById('persona_sexo');
    const personaEdad = document.getElementById('persona_edad');
    const jornadaSelect = document.getElementById('jornada');
    const checkHorarioFijo = document.getElementById('horario_fijo');
    const horaSalidaJ1 = document.getElementById('hora_salida_j1');
    const horaIngresoJ1 = document.getElementById('hora_ingreso_j1');
    const horaSalidaJ2 = document.getElementById('hora_salida_j2');
    const horaIngresoJ2 = document.getElementById('hora_ingreso_j2');

    const limpiarDatosPersona = () => {
      personaNombre.value = '';
      personaCodigo.value = '';
      personaSeccion.value = '';
      personaSexo.value = '';
      personaEdad.value = '';
    };

    const setEstadoPersona = (mensaje, tipo = 'muted') => {
      estadoPersona.className = `form-text text-${tipo}`;
      estadoPersona.textContent = mensaje;
    };

    const cargarDatosPersona = async () => {
      const documento = identificacionInput.value.trim();
      if (documento === '') {
        limpiarDatosPersona();
        setEstadoPersona('');
        return;
      }

      setEstadoPersona('Consultando empleado...', 'secondary');
      consultarPersonaBtn.disabled = true;

      try {
        const url = rutaPersonaTemplate.replace('__DOC__', encodeURIComponent(documento));
        const response = await fetch(url, {
          method: 'GET',
          headers: {
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
          },
        });

        const payload = await response.json();
        if (!response.ok || !payload || !payload.ok || !payload.data) {
          limpiarDatosPersona();
          setEstadoPersona((payload && payload.message) ? payload.message : 'No se encontró la persona activa.', 'danger');
          return;
        }

        const data = payload.data;
        personaNombre.value = data.nombre ?? '';
        personaCodigo.value = data.codigo ?? '';
        personaSeccion.value = data.seccion ?? '';
        personaSexo.value = data.sexo ?? '';
        personaEdad.value = data.edad ?? '';
        setEstadoPersona('Datos cargados correctamente.', 'success');
      } catch (error) {
        limpiarDatosPersona();
        setEstadoPersona('No fue posible consultar la persona en este momento.', 'danger');
      } finally {
        consultarPersonaBtn.disabled = false;
      }
    };

    const actualizarCamposHorario = () => {
      const jornada = jornadaSelect.value;
      const usaHorarioFijo = checkHorarioFijo.checked;
      const incluyeJ1 = jornada === 'MANANA' || jornada === 'AMBAS';
      const incluyeJ2 = jornada === 'TARDE' || jornada === 'AMBAS';

      horaSalidaJ1.disabled = !usaHorarioFijo || !incluyeJ1;
      horaIngresoJ1.disabled = !usaHorarioFijo || !incluyeJ1;
      horaSalidaJ2.disabled = !usaHorarioFijo || !incluyeJ2;
      horaIngresoJ2.disabled = !usaHorarioFijo || !incluyeJ2;

      if (!incluyeJ1 || !usaHorarioFijo) {
        horaSalidaJ1.value = '';
        horaIngresoJ1.value = '';
      }
      if (!incluyeJ2 || !usaHorarioFijo) {
        horaSalidaJ2.value = '';
        horaIngresoJ2.value = '';
      }
    };

    identificacionInput?.addEventListener('blur', cargarDatosPersona);
    consultarPersonaBtn?.addEventListener('click', cargarDatosPersona);
    jornadaSelect?.addEventListener('change', actualizarCamposHorario);
    checkHorarioFijo?.addEventListener('change', actualizarCamposHorario);

    if (identificacionInput && identificacionInput.value.trim() !== '') {
      cargarDatosPersona();
    }
    actualizarCamposHorario();

    const mostrarLoaderRadicacion = () => {
      if (!window.Swal || typeof window.Swal.fire !== 'function') {
        return;
      }

      window.Swal.fire({
        title: 'Radicando permiso permanente...',
        text: 'Por favor espera...',
        allowOutsideClick: false,
        allowEscapeKey: false,
        showConfirmButton: false,
        customClass: {
          popup: 'swalAlert'
        },
        didOpen: () => {
          if (typeof window.Swal.showLoading === 'function') {
            window.Swal.showLoading();
          }
        }
      });
    };

    form?.addEventListener('submit', function(event) {
      if (form.dataset.submitting === '1') {
        event.preventDefault();
        return;
      }

      form.dataset.submitting = '1';
      const elementos = form.querySelectorAll('button, input[type="submit"]');
      elementos.forEach((elemento) => {
        elemento.disabled = true;
      });

      mostrarLoaderRadicacion();
    });
  });
</script>
@endPushOnce
