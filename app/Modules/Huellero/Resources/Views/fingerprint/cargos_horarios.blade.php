@extends('layouts.dashboard')

@section('title', 'Horarios por cargo')

@section('breadcrumb')
<x-breadcrumb :items="[
    ['name' => 'Inicio', 'url' => route('home')],
    ['name' => 'Gestion Huellero', 'url' => route('fingerprint.gestion')],
    ['name' => 'Horarios por cargo'],
]" />
@endsection

@php
  $cargoSeleccionado = $cargosDisponibles->firstWhere('id', $cargoSeleccionadoId);
  $diasSemanaTexto = $diasSemana;
  $anclaHorario = '#seccion-asignacion-horario';
  $modoJornadas = old('modo_jornadas', '1');
  $horarioFormulario = old('horario_id') ? [
    'id' => (int) old('horario_id'),
    'jornada' => old('jornada'),
    'dia_inicio' => old('dia_inicio'),
    'dia_fin' => old('dia_fin'),
    'hora_inicio' => old('hora_inicio'),
    'hora_fin' => old('hora_fin'),
    'estado' => old('estado', '1'),
  ] : $horarioEdicion;
@endphp

@section('content')
<div class="container-fluid p-0 cargo-horarios-page">
  <x-sectionHeader titulo="Asignacion de horarios por cargo" rutaVolver="{{ route('fingerprint.gestion') }}" :crear="false" />

  <div class="card mt-4 border-info">
    <div class="card-body">
      <h6 class="mb-2 text-info">Horario por defecto</h6>
      <p class="mb-0">
        El horario general ya configurado es de <strong>7:00 am a 12:00 am</strong> y de
        <strong>2:00 pm a 5:30 pm</strong> de <strong>lunes a jueves</strong>, y el
        <strong>viernes de 2:00 pm a 5:00 pm</strong>. Si el cargo cumple ese horario, no necesitas crear un
        horario aqui. Esta pantalla es solo para <strong>horarios especiales</strong>.
      </p>
    </div>
  </div>

  <div class="card mt-4">
    <div class="card-body">
      @if($esSuperAdmin)
      <form method="GET" action="{{ route('fingerprint.cargos-horarios.index') }}" class="row g-2 align-items-end">
        <div class="col-md-6">
          <label class="form-label">Jefe</label>
          <select name="identificacion" class="form-select">
            @foreach($jefesDisponibles as $jefe)
              <option value="{{ $jefe['identificacion'] }}" {{ $jefe['identificacion'] === $identificacionJefe ? 'selected' : '' }}>
                {{ $jefe['nombre'] }} ({{ $jefe['identificacion'] }})
              </option>
            @endforeach
          </select>
        </div>
        <div class="col-md-4">
          <button class="btn btn-primary" type="submit">Consultar empleados a cargo</button>
        </div>
      </form>
      @else
      <div class="row g-2">
        <div class="col-md-6">
          <label class="form-label">Identificacion del jefe</label>
          <input type="text" class="form-control" value="{{ $identificacionJefe }}" readonly>
        </div>
      </div>
      @endif

      @if(!empty($datosJefe))
      <div class="alert alert-light border mt-3 mb-0">
        <div class="row g-2">
          <div class="col-md-4">
            <div class="small text-muted">Jefe</div>
            <div><strong>{{ $datosJefe['nombre'] }}</strong></div>
          </div>
          <div class="col-md-4">
            <div class="small text-muted">Cargo del jefe</div>
            <div><strong>{{ $datosJefe['cargo'] !== '' ? $datosJefe['cargo'] : '-' }}</strong></div>
          </div>
          <div class="col-md-4">
            <div class="small text-muted">Centro de costo del jefe</div>
            <div><strong>{{ $datosJefe['centro_costo'] !== '' ? $datosJefe['centro_costo'] : '-' }}</strong></div>
          </div>
        </div>
      </div>
      @endif
    </div>
  </div>

  @if(session('status'))
  <div class="alert alert-success mt-3 mb-0">{{ session('status') }}</div>
  @endif

  @if(!empty($erroresConsulta))
  <div class="alert alert-danger mt-3 mb-0">
    @foreach($erroresConsulta as $error)
      <div>{{ $error }}</div>
    @endforeach
  </div>
  @endif

  @if($errors->any())
  <div class="alert alert-danger mt-3 mb-0">
    @foreach($errors->all() as $error)
      <div>{{ $error }}</div>
    @endforeach
  </div>
  @endif

  <div class="row mt-3">
    <div class="col-lg-7 mb-3">
      <div class="card h-100">
        <div class="card-header">
          <strong>Empleados a cargo</strong>
          <span class="text-muted">({{ $empleados->count() }})</span>
        </div>
        <div class="card-body p-0">
          <div class="table-responsive">
            <table class="table table-sm table-striped mb-0">
              <thead>
                <tr>
                  <th>Documento</th>
                  <th>Empleado</th>
                  <th>Cargo</th>
                </tr>
              </thead>
              <tbody>
                @forelse($empleados as $empleado)
                <tr>
                  <td>{{ $empleado['doc_empleado'] }}</td>
                  <td>{{ $empleado['nombre_empleado'] }}</td>
                  <td>{{ $empleado['cargo_empleado'] }}</td>
                </tr>
                @empty
                <tr>
                  <td colspan="3" class="text-center text-muted py-3">No hay empleados para mostrar.</td>
                </tr>
                @endforelse
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>

    <div class="col-lg-5 mb-3">
      <div class="card h-100">
        <div class="card-header">
          <strong>Cargos disponibles para parametrizar</strong>
        </div>
        <div class="card-body">
          @if($cargosDisponibles->isEmpty())
            <p class="text-muted mb-0">No hay cargos disponibles para este jefe.</p>
          @else
            <div class="small text-muted mb-2">
              Verde: con horarios configurados. Gris: sin horarios.
            </div>
            <div class="d-grid gap-2">
              @foreach($cargosDisponibles as $cargo)
                @php
                  $seleccionado = (int) $cargo['id'] === (int) $cargoSeleccionadoId;
                  $tieneHorario = (bool) ($cargo['tiene_horario'] ?? false);
                  if ($seleccionado) {
                      $btnClass = $tieneHorario ? 'btn-success' : 'btn-primary';
                  } else {
                      $btnClass = $tieneHorario ? 'btn-outline-success' : 'btn-outline-secondary';
                  }
                @endphp
                <a
                  class="btn btn-sm text-start {{ $btnClass }}"
                  href="{{ route('fingerprint.cargos-horarios.index', ['identificacion' => $identificacionJefe, 'cargo_id' => $cargo['id']]) . $anclaHorario }}">
                  <div>{{ $cargo['nombre'] }} ({{ $cargo['total_empleados'] }})</div>
                  <span class="small">
                    {{ $tieneHorario ? 'Con horario' : 'Sin horario' }}
                  </span>
                </a>
              @endforeach
            </div>
          @endif
        </div>
      </div>
    </div>
  </div>

  @if($cargoSeleccionado)
  <div class="row" id="seccion-asignacion-horario" style="scroll-margin-top: 90px;">
    <div class="col-lg-5 mb-3">
      <div class="card">
        <div class="card-header">
          <strong>{{ $horarioFormulario ? 'Editar horario' : 'Nuevo horario' }}</strong>
          <div class="small text-muted">Cargo: {{ $cargoSeleccionado['nombre'] }}</div>
        </div>
        <div class="card-body">
          <form method="POST" action="{{ route('fingerprint.cargos-horarios.guardar') }}">
            @csrf
            <input type="hidden" name="identificacion" value="{{ $identificacionJefe }}">
            <input type="hidden" name="cargo_id" value="{{ $cargoSeleccionado['id'] }}">
            <input type="hidden" name="horario_id" value="{{ $horarioFormulario['id'] ?? '' }}">

            <div class="row g-2">
              @if($horarioFormulario)
                <div class="col-12">
                  <div class="alert alert-warning mb-1">
                    <div class="fw-semibold">Modo edicion activo</div>
                    <div class="small mb-0">
                      Estas editando el horario #{{ $horarioFormulario['id'] ?? '' }}
                      (jornada {{ $horarioFormulario['jornada'] ?? '' }}).
                      Si no deseas cambiarlo, usa "Cancelar edicion".
                    </div>
                  </div>
                </div>
                <div class="col-md-6">
                  <label class="form-label">Jornada</label>
                  <input type="number" class="form-control" name="jornada" min="1" max="10" value="{{ $horarioFormulario['jornada'] ?? old('jornada', 1) }}" required>
                </div>
                <div class="col-md-6">
                  <label class="form-label">Estado</label>
                  <select class="form-select" name="estado" required>
                    <option value="1" {{ (string) ($horarioFormulario['estado'] ?? '1') === '1' ? 'selected' : '' }}>Activo</option>
                    <option value="0" {{ (string) ($horarioFormulario['estado'] ?? '1') === '0' ? 'selected' : '' }}>Inactivo</option>
                  </select>
                </div>
                <div class="col-md-6">
                  <label class="form-label">Dia de inicio</label>
                  <select class="form-select" name="dia_inicio" required>
                    @foreach($diasSemanaTexto as $diaNumero => $diaNombre)
                      <option value="{{ $diaNumero }}" {{ (string) ($horarioFormulario['dia_inicio'] ?? old('dia_inicio', 1)) === (string) $diaNumero ? 'selected' : '' }}>
                        {{ $diaNombre }}
                      </option>
                    @endforeach
                  </select>
                </div>
                <div class="col-md-6">
                  <label class="form-label">Dia de fin</label>
                  <select class="form-select" name="dia_fin" required>
                    @foreach($diasSemanaTexto as $diaNumero => $diaNombre)
                      <option value="{{ $diaNumero }}" {{ (string) ($horarioFormulario['dia_fin'] ?? old('dia_fin', 5)) === (string) $diaNumero ? 'selected' : '' }}>
                        {{ $diaNombre }}
                      </option>
                    @endforeach
                  </select>
                </div>
                <div class="col-md-6">
                  <label class="form-label">Hora inicio</label>
                  <input type="time" class="form-control" name="hora_inicio" value="{{ $horarioFormulario['hora_inicio'] ?? old('hora_inicio', '07:00') }}" required>
                </div>
                <div class="col-md-6">
                  <label class="form-label">Hora fin</label>
                  <input type="time" class="form-control" name="hora_fin" value="{{ $horarioFormulario['hora_fin'] ?? old('hora_fin', '17:00') }}" required>
                </div>
              @else
                <div class="col-12">
                  <label class="form-label d-block mb-2">Este cargo tiene jornada partida?</label>
                  <div class="form-check form-check-inline">
                    <input class="form-check-input" type="radio" name="modo_jornadas" id="modo_jornadas_1" value="1" {{ (string) $modoJornadas === '1' ? 'checked' : '' }}>
                    <label class="form-check-label" for="modo_jornadas_1">No, una jornada</label>
                  </div>
                  <div class="form-check form-check-inline">
                    <input class="form-check-input" type="radio" name="modo_jornadas" id="modo_jornadas_2" value="2" {{ (string) $modoJornadas === '2' ? 'checked' : '' }}>
                    <label class="form-check-label" for="modo_jornadas_2">Si, dos jornadas</label>
                  </div>
                  <div class="form-text">
                    Solo debes crear horarios cuando el cargo tenga un horario especial.
                  </div>
                </div>

                <div class="col-12">
                  <div class="border rounded p-2">
                    <div class="fw-semibold mb-2">Jornada principal</div>
                    <div class="row g-2">
                      <div class="col-md-6">
                        <label class="form-label">Dia de inicio</label>
                        <select class="form-select" name="dia_inicio_1" id="dia_inicio_1" required>
                          @foreach($diasSemanaTexto as $diaNumero => $diaNombre)
                            <option value="{{ $diaNumero }}" {{ (string) old('dia_inicio_1', 1) === (string) $diaNumero ? 'selected' : '' }}>
                              {{ $diaNombre }}
                            </option>
                          @endforeach
                        </select>
                      </div>
                      <div class="col-md-6">
                        <label class="form-label">Dia de fin</label>
                        <select class="form-select" name="dia_fin_1" id="dia_fin_1" required>
                          @foreach($diasSemanaTexto as $diaNumero => $diaNombre)
                            <option value="{{ $diaNumero }}" {{ (string) old('dia_fin_1', 5) === (string) $diaNumero ? 'selected' : '' }}>
                              {{ $diaNombre }}
                            </option>
                          @endforeach
                        </select>
                      </div>
                      <div class="col-md-6">
                        <label class="form-label">Hora de inicio</label>
                        <input type="time" class="form-control" name="hora_inicio_1" id="hora_inicio_1" value="{{ old('hora_inicio_1', '07:00') }}" required>
                      </div>
                      <div class="col-md-6">
                        <label class="form-label">Hora de fin</label>
                        <input type="time" class="form-control" name="hora_fin_1" id="hora_fin_1" value="{{ old('hora_fin_1', '12:00') }}" required>
                      </div>
                    </div>
                  </div>
                </div>

                <div class="col-12" id="bloque_jornada_2" style="{{ (string) $modoJornadas === '2' ? '' : 'display:none;' }}">
                  <div class="border rounded p-2">
                    <div class="fw-semibold mb-2">Jornada adicional</div>
                    <div class="row g-2">
                      <div class="col-md-6">
                        <label class="form-label">Dia de inicio</label>
                        <select class="form-select" name="dia_inicio_2" id="dia_inicio_2">
                          @foreach($diasSemanaTexto as $diaNumero => $diaNombre)
                            <option value="{{ $diaNumero }}" {{ (string) old('dia_inicio_2', 1) === (string) $diaNumero ? 'selected' : '' }}>
                              {{ $diaNombre }}
                            </option>
                          @endforeach
                        </select>
                      </div>
                      <div class="col-md-6">
                        <label class="form-label">Dia de fin</label>
                        <select class="form-select" name="dia_fin_2" id="dia_fin_2">
                          @foreach($diasSemanaTexto as $diaNumero => $diaNombre)
                            <option value="{{ $diaNumero }}" {{ (string) old('dia_fin_2', 5) === (string) $diaNumero ? 'selected' : '' }}>
                              {{ $diaNombre }}
                            </option>
                          @endforeach
                        </select>
                      </div>
                      <div class="col-md-6">
                        <label class="form-label">Hora de inicio</label>
                        <input type="time" class="form-control" name="hora_inicio_2" id="hora_inicio_2" value="{{ old('hora_inicio_2', '14:00') }}">
                      </div>
                      <div class="col-md-6">
                        <label class="form-label">Hora de fin</label>
                        <input type="time" class="form-control" name="hora_fin_2" id="hora_fin_2" value="{{ old('hora_fin_2', '17:30') }}">
                      </div>
                    </div>
                  </div>
                </div>

                <div class="col-12">
                  <div class="alert alert-light border mb-0" id="resumen_horario">
                    <div class="small text-muted mb-1">Resumen</div>
                    <div id="resumen_texto">Completa los datos para ver el resumen del horario.</div>
                  </div>
                </div>
              @endif
            </div>

            <div class="mt-3 d-flex gap-2">
              <button class="btn btn-primary" type="submit">{{ $horarioFormulario ? 'Actualizar horario' : 'Crear horarios' }}</button>
              @if($horarioFormulario)
                <a class="btn btn-outline-secondary" href="{{ route('fingerprint.cargos-horarios.index', ['identificacion' => $identificacionJefe, 'cargo_id' => $cargoSeleccionado['id']]) }}">Cancelar edicion</a>
              @endif
            </div>
          </form>
        </div>
      </div>
    </div>

    <div class="col-lg-7 mb-3">
      <div class="card">
        <div class="card-header">
          <strong>Horarios del cargo</strong>
        </div>
        <div class="card-body p-0">
          <div class="table-responsive">
            <table class="table table-sm table-striped mb-0">
              <thead>
                <tr>
                  <th>Jornada</th>
                  <th>Dia de inicio</th>
                  <th>Dia de fin</th>
                  <th>Hora inicio</th>
                  <th>Hora fin</th>
                  <th>Estado</th>
                  <th>Acciones</th>
                </tr>
              </thead>
              <tbody>
                @forelse($horarios as $horario)
                <tr>
                  <td>{{ $horario['jornada'] }}</td>
                  <td>{{ $diasSemanaTexto[$horario['dia_inicio']] ?? $horario['dia_inicio'] }}</td>
                  <td>{{ $diasSemanaTexto[$horario['dia_fin']] ?? $horario['dia_fin'] }}</td>
                  <td>{{ $horario['hora_inicio'] ?? '-' }}</td>
                  <td>{{ $horario['hora_fin'] ?? '-' }}</td>
                  <td>
                    <span class="badge {{ $horario['estado'] === 1 ? 'bg-success' : 'bg-secondary' }}">
                      {{ $horario['estado'] === 1 ? 'Activo' : 'Inactivo' }}
                    </span>
                  </td>
                  <td>
                    <div class="d-flex gap-1">
                      <a class="btn btn-sm btn-outline-primary"
                         href="{{ route('fingerprint.cargos-horarios.index', ['identificacion' => $identificacionJefe, 'cargo_id' => $cargoSeleccionado['id'], 'horario_id' => $horario['id']]) . $anclaHorario }}">
                        Editar
                      </a>
                      <form method="POST" action="{{ route('fingerprint.cargos-horarios.estado', ['id' => $horario['id']]) }}">
                        @csrf
                        <input type="hidden" name="identificacion" value="{{ $identificacionJefe }}">
                        <input type="hidden" name="cargo_id" value="{{ $cargoSeleccionado['id'] }}">
                        <input type="hidden" name="estado" value="{{ $horario['estado'] === 1 ? 0 : 1 }}">
                        <button class="btn btn-sm btn-outline-dark" type="submit">
                          {{ $horario['estado'] === 1 ? 'Inactivar' : 'Activar' }}
                        </button>
                      </form>
                    </div>
                  </td>
                </tr>
                @empty
                <tr>
                  <td colspan="7" class="text-center text-muted py-3">No hay horarios configurados para este cargo.</td>
                </tr>
                @endforelse
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
  </div>
  @endif
</div>

<script>
  document.addEventListener('DOMContentLoaded', function () {
    if (window.location.hash === '#seccion-asignacion-horario') {
      var seccionHorario = document.getElementById('seccion-asignacion-horario');
      if (seccionHorario) {
        setTimeout(function () {
          seccionHorario.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }, 80);
      }
    }

    var modoJornadas = document.querySelectorAll('input[name="modo_jornadas"]');
    var bloqueJornada2 = document.getElementById('bloque_jornada_2');
    if (!modoJornadas.length || !bloqueJornada2) {
      return;
    }

    var resumenTexto = document.getElementById('resumen_texto');
    var diaInicio1 = document.getElementById('dia_inicio_1');
    var diaFin1 = document.getElementById('dia_fin_1');
    var horaInicio1 = document.getElementById('hora_inicio_1');
    var horaFin1 = document.getElementById('hora_fin_1');
    var diaInicio2 = document.getElementById('dia_inicio_2');
    var diaFin2 = document.getElementById('dia_fin_2');
    var horaInicio2 = document.getElementById('hora_inicio_2');
    var horaFin2 = document.getElementById('hora_fin_2');
    var dias = {
      '1': 'Lunes',
      '2': 'Martes',
      '3': 'Miercoles',
      '4': 'Jueves',
      '5': 'Viernes',
      '6': 'Sabado',
      '7': 'Domingo'
    };
    var camposJornada2 = bloqueJornada2.querySelectorAll('select, input');
    var modoActual = function () {
      var seleccionado = document.querySelector('input[name="modo_jornadas"]:checked');
      return seleccionado ? seleccionado.value : '1';
    };
    var textoRango = function (ini, fin) {
      if (!ini || !fin) {
        return '-';
      }
      return (dias[String(ini)] || ini) + ' a ' + (dias[String(fin)] || fin);
    };
    var textoHora = function (ini, fin) {
      if (!ini || !fin) {
        return '-';
      }
      return ini + ' - ' + fin;
    };
    var syncResumen = function () {
      if (!resumenTexto || !diaInicio1 || !diaFin1 || !horaInicio1 || !horaFin1) {
        return;
      }
      var resumen = 'Jornada principal: ' + textoRango(diaInicio1.value, diaFin1.value) + ' (' + textoHora(horaInicio1.value, horaFin1.value) + ')';
      if (modoActual() === '2' && diaInicio2 && diaFin2 && horaInicio2 && horaFin2) {
        resumen += ' | Jornada adicional: ' + textoRango(diaInicio2.value, diaFin2.value) + ' (' + textoHora(horaInicio2.value, horaFin2.value) + ')';
      }
      resumenTexto.textContent = resumen;
    };
    var syncJornada2 = function () {
      var esDobleJornada = modoActual() === '2';
      bloqueJornada2.style.display = esDobleJornada ? '' : 'none';
      camposJornada2.forEach(function (campo) {
        campo.required = esDobleJornada;
      });
      syncResumen();
    };
    modoJornadas.forEach(function (radio) {
      radio.addEventListener('change', syncJornada2);
    });
    [diaInicio1, diaFin1, horaInicio1, horaFin1, diaInicio2, diaFin2, horaInicio2, horaFin2].forEach(function (campo) {
      if (campo) {
        campo.addEventListener('change', syncResumen);
      }
      if (campo) {
        campo.addEventListener('input', syncResumen);
      }
    });
    syncJornada2();
  });
</script>
@endsection

