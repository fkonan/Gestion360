@extends('emails.layout')

@section('content')
@php
  $tituloCorreo = trim((string) ($datos['titulo_correo'] ?? ''));
  if ($tituloCorreo === '') {
      $tituloCorreo = 'Nuevo permiso radicado';
  }

  $mensajeCorreo = trim((string) ($datos['mensaje_correo'] ?? ''));
  if ($mensajeCorreo === '') {
      $mensajeCorreo = 'Se registro un nuevo permiso de salida y queda pendiente de gestion en el flujo de aprobacion.';
  }

  $urlAccion = trim((string) ($datos['url_accion'] ?? ($datos['url_gestion_jefe'] ?? '')));
  $textoAccion = trim((string) ($datos['texto_accion'] ?? ''));
  if ($textoAccion === '') {
      $textoAccion = 'Gestionar permiso de mi equipo';
  }

  $notaAccion = trim((string) ($datos['nota_accion'] ?? ''));
  if ($notaAccion === '' && !empty($datos['url_gestion_jefe'])) {
      $notaAccion = 'Este enlace es seguro, personal y expira en '.(int) ($datos['magic_link_ttl_minutos'] ?? 20).' minutos.';
  }

  $urlLogin = trim((string) ($datos['url_login'] ?? ''));
  $textoLogin = trim((string) ($datos['texto_login'] ?? ''));
  if ($textoLogin === '') {
      $textoLogin = 'Ir al login';
  }

  $notaLogin = trim((string) ($datos['nota_login'] ?? ''));
  if ($notaLogin === '') {
      $notaLogin = 'Si no tienes acceso directo, inicia sesion con tus credenciales del sistema Logtrans.';
  }
@endphp

<div style="font-family: Arial, sans-serif; color: #1f2937; line-height: 1.5;">
  <h2 style="margin: 0 0 8px; font-size: 20px; color: #0f172a;">{{ $tituloCorreo }}</h2>
  <p style="margin: 0 0 16px; color: #4b5563;">
    {{ $mensajeCorreo }}
  </p>

  <div style="margin: 0 0 16px; padding: 12px 14px; border: 1px solid #dbeafe; background: #eff6ff; border-radius: 8px;">
    <strong style="color: #1d4ed8;">Accion requerida:</strong>
    <span>Ingresar al sistema y gestionar la solicitud de permiso.</span>
  </div>

  @if($urlAccion !== '')
  <div style="margin: 0 0 16px; text-align: center;">
    <a href="{{ $urlAccion }}" target="_blank"
      style="display: inline-block; background: #0b63ce; color: #ffffff; text-decoration: none; padding: 10px 18px; border-radius: 8px; font-weight: 700;">
      {{ $textoAccion }}
    </a>
    @if($notaAccion !== '')
    <p style="margin: 8px 0 0; font-size: 12px; color: #6b7280;">
      {{ $notaAccion }}
    </p>
    @endif
  </div>
  @endif

  @if($urlLogin !== '')
  <div style="margin: 0 0 16px; text-align: center;">
    <a href="{{ $urlLogin }}" target="_blank"
      style="display: inline-block; background: #1f2937; color: #ffffff; text-decoration: none; padding: 10px 18px; border-radius: 8px; font-weight: 700;">
      {{ $textoLogin }}
    </a>
    <p style="margin: 8px 0 0; font-size: 12px; color: #6b7280;">
      {{ $notaLogin }}
    </p>
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
      <td style="padding: 10px 12px; color: #6b7280;">{{ $datos['actor_label'] ?? 'Radicado por' }}</td>
      <td style="padding: 10px 12px; color: #111827;">
        {{ $datos['radicado_por_nombre'] ?: 'N/A' }} ({{ $datos['radicado_por_documento'] ?: 'N/A' }})
      </td>
    </tr>
  </table>

  <h3 style="margin: 0 0 8px; font-size: 15px; color: #0f172a;">Datos del permiso</h3>
  <table width="100%" cellpadding="0" cellspacing="0" border="0" style="border: 1px solid #e5e7eb; border-radius: 8px; margin-bottom: 14px;">
    <tr>
      <td style="padding: 10px 12px; border-bottom: 1px solid #e5e7eb; width: 35%; color: #6b7280;">Fecha permiso</td>
      <td style="padding: 10px 12px; border-bottom: 1px solid #e5e7eb; color: #111827;">{{ $datos['fecha_permiso'] ?: 'N/A' }}</td>
    </tr>
    <tr>
      <td style="padding: 10px 12px; border-bottom: 1px solid #e5e7eb; color: #6b7280;">Hora salida</td>
      <td style="padding: 10px 12px; border-bottom: 1px solid #e5e7eb; color: #111827;">{{ $datos['hora_salida'] ?: 'N/A' }}</td>
    </tr>
    <tr>
      <td style="padding: 10px 12px; border-bottom: 1px solid #e5e7eb; color: #6b7280;">Hora ingreso</td>
      <td style="padding: 10px 12px; border-bottom: 1px solid #e5e7eb; color: #111827;">{{ $datos['hora_ingreso'] ?: 'N/A' }}</td>
    </tr>
    <tr>
      <td style="padding: 10px 12px; border-bottom: 1px solid #e5e7eb; color: #6b7280;">Motivo</td>
      <td style="padding: 10px 12px; border-bottom: 1px solid #e5e7eb; color: #111827;">{{ $datos['motivo'] ?: 'N/A' }}</td>
    </tr>
    <tr>
      <td style="padding: 10px 12px; color: #6b7280;">Actividad</td>
      <td style="padding: 10px 12px; color: #111827;">{{ $datos['actividad'] ?: 'N/A' }}</td>
    </tr>
  </table>

  <h3 style="margin: 0 0 8px; font-size: 15px; color: #0f172a;">Jefe directo</h3>
  <table width="100%" cellpadding="0" cellspacing="0" border="0" style="border: 1px solid #e5e7eb; border-radius: 8px; margin-bottom: 14px;">
    <tr>
      <td style="padding: 10px 12px; border-bottom: 1px solid #e5e7eb; width: 35%; color: #6b7280;">Nombre</td>
      <td style="padding: 10px 12px; border-bottom: 1px solid #e5e7eb; color: #111827;">
        {{ $datos['jefe_nombre'] ?: 'N/A' }} ({{ $datos['jefe_documento'] ?: 'N/A' }})
      </td>
    </tr>
    <tr>
      <td style="padding: 10px 12px; color: #6b7280;">Correo</td>
      <td style="padding: 10px 12px; color: #111827;">{{ $datos['jefe_correo'] ?: 'N/A' }}</td>
    </tr>
  </table>

  <p style="margin: 0; color: #6b7280; font-size: 13px;">
    Fecha de radicado: {{ $datos['fecha_radicado'] ?: 'N/A' }}
  </p>
</div>
@endsection
