<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Recibo CAJASAM</title>
    <style>
        @page {
            size: 80mm auto;
            margin: 3mm;
        }

        body {
            font-family: 'DejaVu Sans', Arial, sans-serif;
            font-size: 8px;
            line-height: 1.2;
            margin: 0;
            padding: 0;
            color: #000;
        }

        .header {
            text-align: right;
            font-size: 8px;
            margin-bottom: 5px;
        }

        .titulo-convenio {
            text-align: center;
            font-weight: bold;
            font-size: 10px;
            margin: 8px 0;
        }

        .info-basica {
            margin-bottom: 8px;
        }

        .info-basica table {
            width: 100%;
            border-collapse: collapse;
            font-size: 8px;
        }

        .info-basica td {
            padding: 1px 2px;
            vertical-align: top;
        }

        .label {
            font-weight: bold;
        }

        .datos-persona {
            margin: 6px 0;
        }

        .datos-persona table {
            width: 100%;
            border-collapse: collapse;
            font-size: 8px;
        }

        .datos-persona td {
            padding: 1px 2px;
        }

        .tabla-concepto {
            width: 100%;
            border-collapse: collapse;
            margin: 10px 0;
            font-size: 9px;
        }

        .tabla-concepto th,
        .tabla-concepto td {
            border: 1px solid #000;
            padding: 3px;
            text-align: center;
        }

        .tabla-concepto th {
            font-weight: bold;
        }

        .valor-total {
            text-align: right;
            font-weight: bold;
            margin: 8px 0;
            font-size: 9px;
        }

        /* Bloque de firmas */
        .firmas {
            width: 100%;
            border-collapse: collapse;
            margin: 12px 0;
        }

        .firmas td {
            vertical-align: top;
            padding: 0 2px;
        }

        .firmas .indice,
        .firmas .firma {
            text-align: center;
            font-size: 7px;
        }

        .firmas .box {
            height: 20mm; /* altura del recuadro */
            position: relative;
        }

        .firmas .box .cuadro {
            height: 100%;
        }

        .firmas .indice .cuadro {
            border: 1px solid #000;
        }

        .firmas .firma .cuadro {
            border-bottom: 1px solid #000;
        }

        .firmas .texto {
            margin-top: 2px; /* espacio pequeño debajo del cuadro */
        }

        .linea-separadora {
            border-bottom: 0.5px solid #000;
            margin: 6px 0;
        }

        .footer {
            font-size: 7px;
            margin-top: 5px;
        }

        .footer table {
            width: 100%;
            border-collapse: collapse;
        }

        .footer td {
            padding: 1px 2px;
        }

        .derecho {
            text-align: right;
        }
    </style>
</head>
<body>
    <!-- Encabezado -->
    <div class="header">
        <div>{{ $vigencia }}</div>
        <div>{{ $linea_atencion }}</div>
    </div>

    <!-- Título -->
    <div class="titulo-convenio">
        ***{{ strtoupper($convenio) }}***
    </div>

    <!-- Información básica -->
    <div class="info-basica">
        <table>
            <tr>
                <td class="label">Fecha:</td>
                <td>{{ $fecha }}</td>
                <td class="label">Hora:</td>
                <td>{{ $hora }}</td>
            </tr>
            <tr>
                <td class="label">Agencia:</td>
                <td>{{ $agencia }}</td>
                <td class="label">Ciudad:</td>
                <td>{{ $ciudad }}</td>
            </tr>
            <tr>
                <td class="label">Código:</td>
                <td colspan="3">{{ $codigo }}</td>
            </tr>
        </table>
    </div>

    <!-- Principal -->
    <div class="datos-persona">
        <table>
            <tr>
                <td class="label" style="width: 25%;">Principal:</td>
                <td>{{ $principal }}</td>
            </tr>
            <tr>
                <td class="label">Identificación:</td>
                <td>{{ $identificacion_principal }}</td>
            </tr>
        </table>
    </div>

    <!-- Pagado a -->
    <div class="datos-persona">
        <table>
            <tr>
                <td class="label" style="width: 25%;">PAGADO A:</td>
                <td>{{ $pagado_a }}</td>
            </tr>
            <tr>
                <td class="label">Identificación:</td>
                <td>{{ $identificacion_pagado }}</td>
            </tr>
        </table>
    </div>

    <!-- Tabla de conceptos -->
    <table class="tabla-concepto">
        <thead>
            <tr>
                <th style="width: 70%;">Concepto</th>
                <th style="width: 30%;">Valor</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>***{{ $concepto }}***</td>
                <td>{{ $valor }}</td>
            </tr>
        </tbody>
    </table>

    <!-- Total -->
    <div class="valor-total">
        Valor total pagado: {{ $valor_total }}
    </div>

    <!-- Firmas -->
    <table class="firmas">
        <tr>
            <td class="indice" style="width:35%;">
                <div class="box">
                    <div class="cuadro"></div>
                    <div class="texto">Índice Derecho</div>
                </div>
            </td>
            <td class="firma" style="width:65%;">
                <div class="box">
                    <div class="cuadro"></div>
                    <div class="texto">Firma del Cliente</div>
                </div>
            </td>
        </tr>
    </table>

    <br>

    <!-- Footer -->
    <div class="footer">
        <table>
            <tr>
                <td>Impresión: {{ $fecha_impresion }} {{ $hora_impresion }}</td>
                <td class="derecho">Usuario: {{ $usuario_impresion }}</td>
            </tr>
        </table>
    </div>
</body>
</html>
