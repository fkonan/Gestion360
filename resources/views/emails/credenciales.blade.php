@extends('emails.layout')

@section('content')
<p>Le damos la bienvenida. A continuación encontrará sus credenciales de acceso:</p>
<ul style="padding-left: 20px;">
  <li><strong>Usuario:</strong> {{ $datos['usuario'] }}</li>
  <li><strong>Contraseña:</strong> {{ $datos['contraseña'] }}</li>
</ul>
<p>Por razones de seguridad, le recomendamos cambiar su contraseña tras el primer inicio de sesión.</p>
<p style="font-size: 12px; color: #888888; margin-top: 20px;">
  Este mensaje ha sido generado automáticamente. Por favor, no responda a este correo.
</p>
@endsection
