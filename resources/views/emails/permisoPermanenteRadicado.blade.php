@extends('emails.layout')

@section('content')
@php
  $tituloCorreo = trim((string) ($datos['titulo_correo'] ?? ''));
  if ($tituloCorreo === '') {
      $tituloCorreo = 'Nuevo permiso permanente radicado';
  }

  $mensajeCorreo = trim((string) ($datos['mensaje_correo'] ?? ''));
  if ($mensajeCorreo === '') {
      $mensajeCorreo = 'Se registro una nueva solicitud de permiso permanente pendiente de gestion.';
  }

  $urlAccion = trim((string) ($datos['url_accion'] ?? ''));
  $textoAccion = trim((string) ($datos['texto_accion'] ?? ''));
  if ($textoAccion === '') {
      $textoAccion = 'Ir a solicitudes del equipo';
  }

  $fechaRadicado = trim((string) ($datos['fecha_radicado'] ?? ''));
  $idNovedad = trim((string) ($datos['id_novedad'] ?? ''));
@endphp

<div style="font-family: Arial, sans-serif; color: #1f2937; line-height: 1.5;">
  <h2 style="margin: 0 0 8px; font-size: 20px; color: #0f172a;">{{ $tituloCorreo }}</h2>
  <p style="margin: 0 0 16px; color: #4b5563;">
    {{ $mensajeCorreo }}
  </p>

  @if($urlAccion !== '')
  <div style="margin: 0 0 16px; text-align: center;">
    <a href="{{ $urlAccion }}" target="_blank"
      style="display: inline-block; background: #0b63ce; color: #ffffff; text-decoration: none; padding: 10px 18px; border-radius: 8px; font-weight: 700;">
      {{ $textoAccion }}
    </a>
  </div>
  @endif

  <h3 style="margin: 0 0 8px; font-size: 15px; color: #0f172a;">Datos del empleado</h3>
  <table width="100%" cellpadding="0" cellspacing="0" border="0" style="border: 1px solid #e5e7eb; border-radius: 8px; margin-bottom: 14px;">
    <tr>
      <td style="padding: 10px 12px; border-bottom: 1px solid #e5e7eb; width: 35%; color: #6b7280;">Empleado</td>
      <td style="padding: 10px 12px; border-bottom: 1px solid #e5e7eb; color: #111827;">
        {{ trim((string) ($datos['empleado_nombre'] ?? '')) ?: 'N/A' }} ({{ trim((string) ($datos['empleado_documento'] ?? '')) ?: 'N/A' }})
      </td>
    </tr>
    <tr>
      <td style="padding: 10px 12px; color: #6b7280;">Radicado por</td>
      <td style="padding: 10px 12px; color: #111827;">
        {{ trim((string) ($datos['radicado_por_nombre'] ?? '')) ?: 'N/A' }} ({{ trim((string) ($datos['radicado_por_documento'] ?? '')) ?: 'N/A' }})
      </td>
    </tr>
  </table>

  <h3 style="margin: 0 0 8px; font-size: 15px; color: #0f172a;">Datos del permiso permanente</h3>
  <table width="100%" cellpadding="0" cellspacing="0" border="0" style="border: 1px solid #e5e7eb; border-radius: 8px; margin-bottom: 14px;">
    <tr>
      <td style="padding: 10px 12px; border-bottom: 1px solid #e5e7eb; width: 35%; color: #6b7280;">Radicado</td>
      <td style="padding: 10px 12px; border-bottom: 1px solid #e5e7eb; color: #111827;">{{ $idNovedad !== '' ? $idNovedad : 'N/A' }}</td>
    </tr>
    <tr>
      <td style="padding: 10px 12px; border-bottom: 1px solid #e5e7eb; color: #6b7280;">Fecha inicio</td>
      <td style="padding: 10px 12px; border-bottom: 1px solid #e5e7eb; color: #111827;">{{ trim((string) ($datos['fecha_inicio'] ?? '')) ?: 'N/A' }}</td>
    </tr>
    <tr>
      <td style="padding: 10px 12px; border-bottom: 1px solid #e5e7eb; color: #6b7280;">Fecha fin</td>
      <td style="padding: 10px 12px; border-bottom: 1px solid #e5e7eb; color: #111827;">{{ trim((string) ($datos['fecha_fin'] ?? '')) ?: 'N/A' }}</td>
    </tr>
    <tr>
      <td style="padding: 10px 12px; border-bottom: 1px solid #e5e7eb; color: #6b7280;">Jornada</td>
      <td style="padding: 10px 12px; border-bottom: 1px solid #e5e7eb; color: #111827;">{{ trim((string) ($datos['jornada'] ?? '')) ?: 'N/A' }}</td>
    </tr>
    <tr>
      <td style="padding: 10px 12px; border-bottom: 1px solid #e5e7eb; color: #6b7280;">Horario fijo</td>
      <td style="padding: 10px 12px; border-bottom: 1px solid #e5e7eb; color: #111827;">{{ trim((string) ($datos['horario_fijo'] ?? '')) ?: 'N/A' }}</td>
    </tr>
    <tr>
      <td style="padding: 10px 12px; color: #6b7280;">Observacion</td>
      <td style="padding: 10px 12px; color: #111827;">{{ trim((string) ($datos['observacion'] ?? '')) ?: 'N/A' }}</td>
    </tr>
  </table>

  <h3 style="margin: 0 0 8px; font-size: 15px; color: #0f172a;">Aprobador inicial</h3>
  <table width="100%" cellpadding="0" cellspacing="0" border="0" style="border: 1px solid #e5e7eb; border-radius: 8px; margin-bottom: 14px;">
    <tr>
      <td style="padding: 10px 12px; border-bottom: 1px solid #e5e7eb; width: 35%; color: #6b7280;">Nombre</td>
      <td style="padding: 10px 12px; border-bottom: 1px solid #e5e7eb; color: #111827;">
        {{ trim((string) ($datos['aprobador_real_nombre'] ?? '')) ?: 'N/A' }}
      </td>
    </tr>
    <tr>
      <td style="padding: 10px 12px; border-bottom: 1px solid #e5e7eb; color: #6b7280;">Documento</td>
      <td style="padding: 10px 12px; border-bottom: 1px solid #e5e7eb; color: #111827;">
        {{ trim((string) ($datos['aprobador_real_documento'] ?? '')) ?: 'N/A' }}
      </td>
    </tr>
    <tr>
      <td style="padding: 10px 12px; color: #6b7280;">Correo</td>
      <td style="padding: 10px 12px; color: #111827;">
        {{ trim((string) ($datos['aprobador_real_correo'] ?? '')) ?: 'N/A' }}
      </td>
    </tr>
  </table>

  <p style="margin: 0; color: #6b7280; font-size: 13px;">
    Fecha de radicado: {{ $fechaRadicado !== '' ? $fechaRadicado : 'N/A' }}
  </p>
</div>
@endsection
