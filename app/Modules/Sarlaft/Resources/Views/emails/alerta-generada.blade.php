@extends('emails.layout', ['title' => 'Alerta SARLAFT'])

@section('content')
   <h2 style="margin: 0 0 16px; color: {{ $colorNivel }};">
      Nueva alerta SARLAFT - Nivel {{ $etiquetaNivel }}
   </h2>

   <p style="margin: 0 0 12px;">
      Se ha generado una nueva alerta en el sistema SARLAFT que requiere su revision.
   </p>

   <table width="100%" cellpadding="8" cellspacing="0" border="0"
      style="border: 1px solid #e0e0e0; border-radius: 6px; margin: 16px 0;">
      <tr style="background-color: {{ $colorNivel }}; color: #ffffff;">
         <td colspan="2" style="font-weight: bold; padding: 10px;">
            Detalles de la alerta
         </td>
      </tr>
      <tr>
         <td style="font-weight: bold; width: 35%;">ID de alerta</td>
         <td>#{{ $alerta->id }}</td>
      </tr>
      <tr style="background-color: #f9f9f9;">
         <td style="font-weight: bold;">Nivel de riesgo</td>
         <td>
            <span style="background-color: {{ $colorNivel }}; color: #ffffff; padding: 3px 8px; border-radius: 4px; font-size: 12px;">
               {{ strtoupper($nivel) }}
            </span>
         </td>
      </tr>
      <tr>
         <td style="font-weight: bold;">Tipo</td>
         <td>{{ $alerta->tipo }}</td>
      </tr>
      <tr style="background-color: #f9f9f9;">
         <td style="font-weight: bold;">Estado</td>
         <td>{{ ucfirst($alerta->estado) }}</td>
      </tr>
      <tr>
         <td style="font-weight: bold;">Documento</td>
         <td>{{ $alerta->tipo_documento }} {{ $alerta->numero_documento }}</td>
      </tr>
      @if (! empty($alerta->datos_persona['nombre']))
         <tr style="background-color: #f9f9f9;">
            <td style="font-weight: bold;">Nombre consultado</td>
            <td>{{ $alerta->datos_persona['nombre'] }}</td>
         </tr>
      @endif
      <tr>
         <td style="font-weight: bold;">Fecha de generacion</td>
         <td>{{ $alerta->created_at?->format('d/m/Y H:i:s') }}</td>
      </tr>
   </table>

   @if (! empty($alerta->listas_coincidentes) && is_array($alerta->listas_coincidentes))
      <h3 style="margin: 16px 0 8px; color: #333;">Coincidencias detectadas</h3>
      <table width="100%" cellpadding="8" cellspacing="0" border="0"
         style="border: 1px solid #e0e0e0; border-radius: 6px;">
         <tr style="background-color: #005CA3; color: #ffffff;">
            <td style="font-weight: bold; padding: 10px;">Lista</td>
            <td style="font-weight: bold;">Tipo coincidencia</td>
         </tr>
         @foreach ($alerta->listas_coincidentes as $coincidencia)
            <tr style="border-top: 1px solid #e0e0e0;">
               <td>{{ $coincidencia['lista'] ?? 'N/A' }}</td>
               <td>{{ $coincidencia['tipo_coincidencia'] ?? 'N/A' }}</td>
            </tr>
         @endforeach
      </table>
   @endif

   <p style="margin: 24px 0 12px;">
      Por favor, ingrese al sistema para revisar y gestionar esta alerta.
   </p>

   <p style="margin: 24px 0 0; text-align: center;">
      <a href="{{ route('sarlaft.alertas.show', $alerta->id) }}"
         style="background-color: {{ $colorNivel }}; color: #ffffff; padding: 12px 24px; text-decoration: none; border-radius: 6px; display: inline-block; font-weight: bold;">
         Revisar alerta
      </a>
   </p>
@endsection
