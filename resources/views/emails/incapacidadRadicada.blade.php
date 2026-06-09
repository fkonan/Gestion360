@extends('emails.layout')

@section('content')
@php
  $tituloCorreo = trim((string) ($datos['titulo_correo'] ?? ''));
  if ($tituloCorreo === '') {
      $tituloCorreo = 'Nueva incapacidad radicada';
  }

  $mensajeCorreo = trim((string) ($datos['mensaje_correo'] ?? ''));
  if ($mensajeCorreo === '') {
      $mensajeCorreo = 'Se registro una nueva incapacidad pendiente de gestion por RRHH.';
  }

  $urlAccion = trim((string) ($datos['url_accion'] ?? ''));
  $textoAccion = trim((string) ($datos['texto_accion'] ?? ''));
  if ($textoAccion === '') {
      $textoAccion = 'Ir a gestion RRHH';
  }
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
        {{ $datos['empleado_nombre'] ?: 'N/A' }} ({{ $datos['empleado_documento'] ?: 'N/A' }})
      </td>
    </tr>
    <tr>
      <td style="padding: 10px 12px; color: #6b7280;">Radicado por</td>
      <td style="padding: 10px 12px; color: #111827;">
        {{ $datos['radicado_por_nombre'] ?: 'N/A' }} ({{ $datos['radicado_por_documento'] ?: 'N/A' }})
      </td>
    </tr>
  </table>

  <h3 style="margin: 0 0 8px; font-size: 15px; color: #0f172a;">Datos de incapacidad</h3>
  <table width="100%" cellpadding="0" cellspacing="0" border="0" style="border: 1px solid #e5e7eb; border-radius: 8px; margin-bottom: 14px;">
    <tr>
      <td style="padding: 10px 12px; border-bottom: 1px solid #e5e7eb; width: 35%; color: #6b7280;">Radicado</td>
      <td style="padding: 10px 12px; border-bottom: 1px solid #e5e7eb; color: #111827;">{{ $datos['id_radicado'] ?: 'N/A' }}</td>
    </tr>
    <tr>
      <td style="padding: 10px 12px; border-bottom: 1px solid #e5e7eb; color: #6b7280;">Tipo incapacidad</td>
      <td style="padding: 10px 12px; border-bottom: 1px solid #e5e7eb; color: #111827;">{{ $datos['tipo_incapacidad'] ?: 'N/A' }}</td>
    </tr>
    <tr>
      <td style="padding: 10px 12px; border-bottom: 1px solid #e5e7eb; color: #6b7280;">Causa</td>
      <td style="padding: 10px 12px; border-bottom: 1px solid #e5e7eb; color: #111827;">{{ $datos['causa'] ?: 'N/A' }}</td>
    </tr>
    <tr>
      <td style="padding: 10px 12px; border-bottom: 1px solid #e5e7eb; color: #6b7280;">Diagnostico</td>
      <td style="padding: 10px 12px; border-bottom: 1px solid #e5e7eb; color: #111827;">{{ $datos['diagnostico'] ?: 'N/A' }}</td>
    </tr>
    <tr>
      <td style="padding: 10px 12px; border-bottom: 1px solid #e5e7eb; color: #6b7280;">EPS</td>
      <td style="padding: 10px 12px; border-bottom: 1px solid #e5e7eb; color: #111827;">{{ $datos['eps'] ?: 'N/A' }}</td>
    </tr>
    <tr>
      <td style="padding: 10px 12px; border-bottom: 1px solid #e5e7eb; color: #6b7280;">ARL</td>
      <td style="padding: 10px 12px; border-bottom: 1px solid #e5e7eb; color: #111827;">{{ $datos['arl'] ?: 'N/A' }}</td>
    </tr>
    <tr>
      <td style="padding: 10px 12px; border-bottom: 1px solid #e5e7eb; color: #6b7280;">Fecha inicio</td>
      <td style="padding: 10px 12px; border-bottom: 1px solid #e5e7eb; color: #111827;">{{ $datos['fecha_inicio'] ?: 'N/A' }}</td>
    </tr>
    <tr>
      <td style="padding: 10px 12px; border-bottom: 1px solid #e5e7eb; color: #6b7280;">Fecha fin</td>
      <td style="padding: 10px 12px; border-bottom: 1px solid #e5e7eb; color: #111827;">{{ $datos['fecha_fin'] ?: 'N/A' }}</td>
    </tr>
    <tr>
      <td style="padding: 10px 12px; color: #6b7280;">Observacion</td>
      <td style="padding: 10px 12px; color: #111827;">{{ $datos['observacion'] ?: 'N/A' }}</td>
    </tr>
  </table>

  <p style="margin: 0; color: #6b7280; font-size: 13px;">
    Fecha de radicado: {{ $datos['fecha_radicado'] ?: 'N/A' }}
  </p>
</div>
@endsection
