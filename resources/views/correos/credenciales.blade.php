<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Credenciales de acceso</title>
</head>
<body style="margin: 0; padding: 10px; background-color: #f6f6f6; font-family: Arial, sans-serif;">
    <center>
        <table width="100%" cellpadding="0" cellspacing="0" border="0" bgcolor="#f6f6f6">
            <tr>
                <td align="center">
                    <table width="600" cellpadding="0" cellspacing="0" border="0" style="background-color: #ffffff; border: 1px solid grey; border-radius: 8px; box-shadow: 0 2px 6px rgba(0,0,0,0.1);">
                        
                        <!-- HEADER CON FONDO AZUL -->
                        <tr>
                            <td bgcolor="#005CA3" align="center" style="padding: 20px;">
                                <img src="https://autogestion.copetran.com.co/cdn/img/logos/logo-blango.png" alt="Logo Copetran" style="height: 45px; display: block;" />
                            </td>
                        </tr>

                        <!-- CONTENIDO -->
                        <tr>
                            <td style="padding: 30px; color: #333333;">
                                <p>Le damos la bienvenida. A continuación encontrará sus credenciales de acceso:</p>
                                <ul style="padding-left: 20px;">
                                    <li><strong>Usuario:</strong> {{ $datos['usuario'] }}</li>
                                    <li><strong>Contraseña:</strong> {{ $datos['contraseña'] }}</li>
                                </ul>
                                <p>Por razones de seguridad, le recomendamos cambiar su contraseña tras el primer inicio de sesión.</p>
                                <p style="font-size: 12px; color: #888888; margin-top: 20px;">
                                    Este mensaje ha sido generado automáticamente. Por favor, no responda a este correo.
                                </p>
                            </td>
                        </tr>

                    </table>
                </td>
            </tr>
        </table>
    </center>
</body>
</html>
