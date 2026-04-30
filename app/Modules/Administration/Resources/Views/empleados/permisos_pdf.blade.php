@php
use Carbon\Carbon;

$fechaInicio = isset($novedad->fecha_inicio) ? Carbon::parse($novedad->fecha_inicio) : null;
$fechaFin = isset($novedad->fecha_fin) ? Carbon::parse($novedad->fecha_fin) : null;
$motivos = [
  'REUNION_ESCOLAR' => 'REUNION ESCOLAR',
  'CITA_MEDICA_FAMILIARES' => 'CITA MEDICA FAMILIARES',
  'ESTUDIO' => 'ESTUDIO',
  'ACTIVIDAD_LABORAL_EXTERNA' => 'ACTIVIDAD LABORAL EXTERNA',
  'MEDICINA_GENERAL' => 'MEDICINA GENERAL',
  'MEDICINA_ESPECIALIZADA' => 'MEDICINA ESPECIALIZADA',
  'TERAPIAS' => 'TERAPIAS',
  'ODONTOLOGIA' => 'ODONTOLOGIA',
  'URGENCIA_O_CITA_PRIORITARIA' => 'URGENCIAS O CITA PRIORITARIA',
  'ACCIDENTE_DE_TRABAJO' => 'ACCIDENTE DE TRABAJO',
  'EXAMENES' => 'EXAMENES',
  'OTROS' => 'OTROS',
];

$motivoSeleccionado = (string) ($permiso->motivo_catalogo ?? '');
@endphp
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Permiso salida empleados</title>
  <style>
    body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #111; }
    .title { text-align: center; font-size: 14px; font-weight: bold; margin-bottom: 4px; }
    .subtitle { text-align: center; margin-bottom: 16px; }
    table { width: 100%; border-collapse: collapse; margin-bottom: 10px; }
    th, td { border: 1px solid #222; padding: 4px 6px; vertical-align: top; }
    th { background: #f3f3f3; text-align: left; }
    .no-border { border: none !important; }
    .label { font-weight: bold; }
    .firma-box { height: 42px; }
    .small { font-size: 10px; color: #444; }
    .motivo-grid td { width: 50%; }
  </style>
</head>
<body>
  <div class="title">PERMISO SALIDA DE EMPLEADOS</div>
  <div class="subtitle">Cooperativa Santandereana de Transportadores Ltda.</div>

  <table>
    <tr>
      <th>Apellidos y nombres</th>
      <th>Cedula</th>
      <th>Codigo</th>
      <th>Seccion</th>
    </tr>
    <tr>
      <td>{{ $persona['nombre'] ?? '' }}</td>
      <td>{{ $persona['identificacion'] ?? '' }}</td>
      <td>{{ $persona['codigo'] ?? '' }}</td>
      <td>{{ $persona['seccion'] ?? '' }}</td>
    </tr>
  </table>

  <table>
    <tr>
      <th>Fecha del permiso</th>
      <th>Hora de salida</th>
      <th>Hora de ingreso</th>
      <th>Sexo</th>
      <th>Edad</th>
    </tr>
    <tr>
      <td>{{ $fechaInicio?->format('d/m/Y') ?? '' }}</td>
      <td>{{ $fechaInicio?->format('h:i A') ?? '' }}</td>
      <td>{{ $fechaFin?->format('h:i A') ?? '' }}</td>
      <td>{{ $persona['sexo'] ?? '' }}</td>
      <td>{{ $persona['edad'] ?? '' }}</td>
    </tr>
  </table>

  <table class="motivo-grid">
    <tr>
      <th colspan="2">Motivo del permiso (marque con una X)</th>
    </tr>
    @foreach(array_chunk($motivos, 2, true) as $fila)
    <tr>
      @foreach($fila as $codigo => $label)
      <td>
        [{{ $motivoSeleccionado === $codigo ? 'X' : ' ' }}] {{ $label }}
        @if($codigo === 'OTROS' && !empty($permiso->otro_motivo))
          : {{ $permiso->otro_motivo }}
        @endif
      </td>
      @endforeach
      @if(count($fila) === 1)
      <td></td>
      @endif
    </tr>
    @endforeach
  </table>

  <table>
    <tr>
      <th>Especifique la actividad que va a realizar</th>
    </tr>
    <tr>
      <td style="height:55px;">{{ $permiso->actividad ?? '' }}</td>
    </tr>
  </table>

  <table>
    <tr>
      <th>Firma y cedula empleado</th>
      <th>Vo.Bo jefe inmediato</th>
      <th>Vo.Bo coord. recurso humano</th>
    </tr>
    <tr>
      <td class="firma-box">
        {{ $persona['nombre'] ?? '' }}<br>
        {{ $persona['identificacion'] ?? '' }}
      </td>
      <td class="firma-box">
        {{ $permiso->jefe_aprobado_por_nombre ?? 'PENDIENTE' }}<br>
        {{ $permiso->jefe_aprobado_por_documento ?? '' }}
      </td>
      <td class="firma-box">
        {{ $permiso->rrhh_aprobado_por_nombre ?? 'PENDIENTE' }}<br>
        {{ $permiso->rrhh_aprobado_por_documento ?? '' }}
      </td>
    </tr>
  </table>

  <table>
    <tr>
      <td class="small no-border">
        Estado EMP_NOVEDADES: {{ $novedad->estado ?? '' }} | Estado flujo: {{ $permiso->estado_flujo ?? '' }} | ID novedad: {{ $permiso->emp_novedad_id ?? '' }}
      </td>
    </tr>
  </table>
</body>
</html>

