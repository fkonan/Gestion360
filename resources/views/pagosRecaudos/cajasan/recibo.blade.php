<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Recibo CAJASAM</title>
    <style>
        @page {
            size: 80mm auto;
            margin: 0 3mm 0 0;
        }

        body {
            font-family: 'DejaVu Sans', Arial, sans-serif;
            font-size: 6px;
            line-height: 1.2;
            margin: 0;
            padding: 0;
            color: #000;
        }

        .header {
            text-align: right;
            font-size: 6px;
            margin-bottom: 5px;
        }

        .titulo-convenio {
            text-align: center;
            font-weight: bold;
            font-size: 8px;
            margin: 8px 0;
        }

        .info-basica {
            margin-bottom: 8px;
        }

        .info-basica table {
            width: 100%;
            border-collapse: collapse;
            font-size: 6px;
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
            font-size: 6px;
        }

        .datos-persona td {
            padding: 1px 2px;
        }

        .tabla-concepto {
            width: 100%;
            border-collapse: collapse;
            margin: 10px 0;
            font-size: 7px;
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
            font-size: 7px;
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
            font-size: 5px;
        }

        .firmas .box {
            height: 11mm; /* altura del recuadro */
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
            font-size: 4.5px;
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

        .mensaje{
            font-size: 4px;
            text-align: justify;
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
                <td>Fecha:</td>
                <td class="label">{{ $fecha }}</td>
                <td>Hora:</td>
                <td class="label">{{ $hora }}</td>
            </tr>
            <tr>
                <td>Agencia:</td>
                <td class="label">{{ $agencia }}</td>
                <td>Ciudad:</td>
                <td class="label">{{ $ciudad }}</td>
            </tr>
            <tr>
                <td>Código:</td>
                <td class="label" colspan="3">{{ $codigo }}</td>
            </tr>
        </table>
    </div>

    <!-- Principal -->
    <div class="datos-persona">
        <table>
            <tr>
                <td style="width: 25%;">Principal:</td>
                <td class="label">{{ $principal }}</td>
            </tr>
            <tr>
                <td>Identificación:</td>
                <td class="label">{{ $identificacion_principal }}</td>
            </tr>
        </table>
    </div>

    <!-- Pagado a -->
    <div class="datos-persona">
        <table>
            <tr>
                <td style="width: 25%;">PAGADO A:</td>
                <td class="label">{{ $pagado_a }}</td>
            </tr>
        </table>
        <table>
            <tr>
                <td>Identificación:</td>
                <td class="label">{{ $identificacion_pagado }}</td>
                <td>Teléfono:</td>
                <td class="label">{{ $telefono }}</td>
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
                <td class="label">{{ $valor }}</td>
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

    <div class="mensaje">
        <p>
            Con mi firma que aparece dentro del presente documento, de manera previa, expresa e inequívoca autorizo a COPETRAN,
            el tratamiento de mis datos personales conforme a la Política de Tratamiento de la Información que se encuentra publicada
            en la página web www.copetran.com.co
        </p>
    </div>

    <!-- Footer -->
    <div class="footer">
        <table>
            <tr>
                <td>Impresión: <b>{{ $fecha_impresion }} {{ $hora_impresion }}</b></td>
                <td class="derecho">Usuario: <b>{{ $usuario_impresion }}</b></td>
            </tr>
        </table>
    </div>
</body>
</html>
