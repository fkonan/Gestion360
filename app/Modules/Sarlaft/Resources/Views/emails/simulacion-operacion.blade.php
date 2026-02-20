<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Simulacion SARLAFT</title>
</head>
<body style="font-family: Arial, sans-serif; color:#1f2937;">
    <h2 style="margin-bottom: 8px;">Simulacion SARLAFT - {{ $escenario }}</h2>
    <p style="margin-top: 0;">Se registro una nueva simulacion operativa en el modulo SARLAFT.</p>

    <h3>Resultado de validacion</h3>
    <ul>
        <li><strong>Consulta ID:</strong> {{ $consulta->id }}</li>
        <li><strong>Nivel de riesgo:</strong> {{ strtoupper($consulta->nivel_riesgo ?? 'ninguno') }}</li>
        <li><strong>Coincidencias:</strong> {{ is_array($consulta->coincidencias) ? count($consulta->coincidencias) : 0 }}</li>
        <li><strong>Decision:</strong> {{ $consulta->presta_servicio ? 'Permitir operacion' : 'No permitir operacion' }}</li>
        <li><strong>Fecha:</strong> {{ $consulta->created_at?->format('d/m/Y H:i:s') }}</li>
    </ul>

    <h3>Datos de la operacion</h3>
    <table cellpadding="6" cellspacing="0" border="1" style="border-collapse: collapse; width: 100%; max-width: 760px;">
        <thead>
            <tr style="background:#f3f4f6;">
                <th align="left">Campo</th>
                <th align="left">Valor</th>
            </tr>
        </thead>
        <tbody>
            @foreach($datosOperacion as $campo => $valor)
            <tr>
                <td><strong>{{ $campo }}</strong></td>
                <td>
                    @if(is_array($valor))
                        {{ json_encode($valor, JSON_UNESCAPED_UNICODE) }}
                    @else
                        {{ (string) $valor }}
                    @endif
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
