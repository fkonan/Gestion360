@extends('emails.layout')

@section('content')
    <p>Hemos recibido una solicitud para restablecer su contraseña.</p>
    <p>
        Para cambiar su contraseña, haga clic en el siguiente enlace:
    </p>
    <p style="text-align: center; margin: 30px 0;">
        <a href="{{ $datos['link'] }}" style="background-color: #005CA3; color: #ffffff; padding: 12px 24px; border-radius: 5px; text-decoration: none; font-weight: bold;">
            Cambiar contraseña
        </a>
    </p>
    <p>Si usted no solicitó este cambio, puede ignorar este mensaje.</p>
    <p style="font-size: 12px; color: #888888; margin-top: 20px;">
        Este mensaje ha sido generado automáticamente. Por favor, no responda a este correo.
    </p>
    <p style="font-size: 12px; color: #888888; margin-top: 10px;">
        Por motivos de seguridad, este enlace expirará en 60 minutos.
    </p>
@endsection