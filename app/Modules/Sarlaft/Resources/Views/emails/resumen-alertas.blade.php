<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Resumen de coincidencias SARLAFT</title>
</head>
<body style="margin:0;padding:0;background:#f4f5f7;font-family:Arial,Helvetica,sans-serif;color:#1f2937;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f4f5f7;padding:24px 0;">
        <tr>
            <td align="center">
                <table role="presentation" width="600" cellpadding="0" cellspacing="0" style="background:#ffffff;border-radius:8px;overflow:hidden;box-shadow:0 1px 4px rgba(0,0,0,0.08);">
                    <tr>
                        <td style="background:#0f172a;padding:20px 28px;">
                            <h1 style="margin:0;color:#ffffff;font-size:18px;">SARLAFT — Coincidencias detectadas</h1>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:24px 28px;">
                            <p style="margin:0 0 12px;font-size:15px;">
                                Se generaron <strong>{{ $total }}</strong> nueva(s) alerta(s) de coincidencia
                                provenientes de <strong>{{ $origen }}</strong>.
                            </p>
                            <p style="margin:0 0 20px;font-size:14px;color:#6b7280;">
                                Detalle agrupado por persona / documento:
                            </p>

                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="border-collapse:collapse;font-size:14px;">
                                <thead>
                                    <tr>
                                        <th align="left" style="border-bottom:2px solid #e5e7eb;padding:8px;">Documento</th>
                                        <th align="left" style="border-bottom:2px solid #e5e7eb;padding:8px;">Nombre</th>
                                        <th align="center" style="border-bottom:2px solid #e5e7eb;padding:8px;">Intentos</th>
                                        <th align="left" style="border-bottom:2px solid #e5e7eb;padding:8px;">Coincidencia</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($porDocumento as $fila)
                                    @php
                                        $esVinculante = in_array($fila['nivel'], ['vinculante', 'alto'], true);
                                    @endphp
                                    <tr>
                                        <td style="border-bottom:1px solid #f3f4f6;padding:8px;">{{ $fila['numero_documento'] ?? '-' }}</td>
                                        <td style="border-bottom:1px solid #f3f4f6;padding:8px;">{{ $fila['nombre'] ?? '-' }}</td>
                                        <td align="center" style="border-bottom:1px solid #f3f4f6;padding:8px;">{{ $fila['cantidad'] }}</td>
                                        <td style="border-bottom:1px solid #f3f4f6;padding:8px;">
                                            <span style="display:inline-block;padding:2px 8px;border-radius:4px;font-size:12px;color:#ffffff;background:{{ $esVinculante ? '#dc3545' : '#6b7280' }};">
                                                {{ $esVinculante ? 'Lista Vinculante' : 'Lista Restrictiva' }}
                                            </span>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>

                            <p style="margin:24px 0 0;font-size:13px;color:#6b7280;">
                                Ingrese al modulo SARLAFT para revisar y atender las alertas.
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <td style="background:#f9fafb;padding:16px 28px;border-top:1px solid #e5e7eb;">
                            <p style="margin:0;font-size:12px;color:#9ca3af;">
                                Notificacion automatica del sistema SARLAFT. No responder este correo.
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
