@extends('emails.layout')

@section('content')
<div style="font-family: Arial, sans-serif; color: #1f2937; line-height: 1.5;">
  <h2 style="margin: 0 0 8px; font-size: 20px; color: #0f172a;">Citacion a descargos</h2>
  <p style="margin: 0 0 16px; color: #4b5563;">
    Has sido citado(a) a descargos. Por favor revisa la informacion registrada y presenta tu version de los hechos en la fecha indicada.
  </p>

  <h3 style="margin: 0 0 8px; font-size: 15px; color: #0f172a;">Datos de la citacion</h3>
  <table width="100%" cellpadding="0" cellspacing="0" border="0" style="border: 1px solid #e5e7eb; border-radius: 8px; margin-bottom: 14px;">
    <tr>
      <td style="padding: 10px 12px; border-bottom: 1px solid #e5e7eb; width: 35%; color: #6b7280;">Radicado</td>
      <td style="padding: 10px 12px; border-bottom: 1px solid #e5e7eb; color: #111827;">{{ $datos['id_citacion'] ?? 'N/A' }}</td>
    </tr>
    <tr>
      <td style="padding: 10px 12px; border-bottom: 1px solid #e5e7eb; color: #6b7280;">Empleado</td>
      <td style="padding: 10px 12px; border-bottom: 1px solid #e5e7eb; color: #111827;">
        {{ $datos['empleado_nombre'] ?? 'N/A' }} ({{ $datos['empleado_documento'] ?? 'N/A' }})
      </td>
    </tr>
    <tr>
      <td style="padding: 10px 12px; border-bottom: 1px solid #e5e7eb; color: #6b7280;">Fecha citacion</td>
      <td style="padding: 10px 12px; border-bottom: 1px solid #e5e7eb; color: #111827;">{{ $datos['fecha_citacion'] ?? 'N/A' }}</td>
    </tr>
    <tr>
      <td style="padding: 10px 12px; border-bottom: 1px solid #e5e7eb; color: #6b7280;">Registrado por</td>
      <td style="padding: 10px 12px; border-bottom: 1px solid #e5e7eb; color: #111827;">
        {{ $datos['radicado_por_nombre'] ?? 'N/A' }} ({{ $datos['radicado_por_documento'] ?? 'N/A' }})
      </td>
    </tr>
    <tr>
      <td style="padding: 10px 12px; color: #6b7280;">Observacion</td>
      <td style="padding: 10px 12px; color: #111827;">{{ $datos['observacion'] ?? 'N/A' }}</td>
    </tr>
  </table>

  <p style="margin: 0; color: #6b7280; font-size: 13px;">
    Fecha de registro: {{ $datos['fecha_registro'] ?? now()->format('Y-m-d H:i:s') }}
  </p>
</div>
@endsection

