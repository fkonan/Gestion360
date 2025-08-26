@extends('emails.layout')

@section('content')
    <div style="font-family: Arial, sans-serif; line-height: 1.6; color: #333;">
        
        <p style="font-size: 16px; margin-bottom: 20px;">
            Estimado/a {{ $datos['nombre'] }},
        </p>
        
        <p style="font-size: 16px; margin-bottom: 20px;">
            Le damos la bienvenida al sistema de <strong>Autogestión</strong>. 
            Para continuar con el proceso de contratación, utilice el siguiente enlace de acceso temporal:
        </p>

        <!-- Enlace de acceso -->
        <div style="text-align: center; margin: 30px 0;">
            <a href="{{ $datos['url'] }}" target="_blank"
               style="display: inline-block; background-color: #007bff; color: white; padding: 12px 30px; 
                      text-decoration: none; border-radius: 6px; font-weight: bold; font-size: 16px;">
                🌐 Acceder al Sistema
            </a>
        </div>

        <!-- Información importante -->
        <div style="background-color: #fff3cd; border: 1px solid #ffeaa7; border-radius: 6px; padding: 15px; margin: 20px 0;">
            <h4 style="color: #856404; margin-top: 0; font-size: 16px;">
                ⚠️ Información importante:
            </h4>
            <ul style="color: #856404; margin-bottom: 0; padding-left: 20px;">
                <li>Este enlace es <strong>válido únicamente para completar el proceso actual</strong>.</li>
                <li>Expirará automáticamente tras su uso.</li>
                <li>Es de un solo uso y no debe ser compartido con terceros.</li>
            </ul>
        </div>
    </div>
@endsection
