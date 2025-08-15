<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Comprobante de Firma</title>
    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
            margin: 10px 25px;
            font-size: 11px;
        }

        .header, .footer {
            text-align: center;
        }

        .header h2 {
            margin: 5px 0;
            font-size: 14px;
        }

        .section {
            margin-top: 10px;
        }

        .section h4 {
            font-size: 12px;
            margin-bottom: 5px;
            border-bottom: 1px solid #ccc;
        }

        .info-box p {
            margin: 2px 0;
        }

        ul {
            margin: 0;
            padding-left: 15px;
        }

        .row {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .footer p {
            font-size: 10px;
        }

        hr {
            margin: 10px 0;
        }

        .savePDF {
        font-family: 'DejaVu Sans', sans-serif;
        font-size: 10pt;
        line-height: 1.2;
        color: #000;
        }
    </style>
</head>
<body class="savePDF">

    <div class="header">
        <h2>Recibo de Firma</h2>
        <p>Generado: {{ ucfirst(\Carbon\Carbon::parse($firma->FirFecReg)->locale('es')->isoFormat('D [de] MMMM [de] YYYY')) }}</p>
    </div>

    <div class="section">
        <h4>Datos del Usuario</h4>
        <div class="info-box">
            <p><strong>Nombre:</strong> {{ $firma->NomCon }}</p>
            <p><strong>Documento:</strong> {{ $firma->DocCon }}</p>
            <p><strong>Correo:</strong> {{ $firma->Correo }}</p>
            <p><strong>Departamento de firma:</strong> {{ $firma->DepFir }}</p>
            <p><strong>Municipio de firma:</strong> {{ $firma->MunFir }}</p>
            <p><strong>Fecha y hora:</strong> {{ $firma->FirFecReg }} {{ $firma->FirHorReg }}</p>
        </div>
    </div>

    <div class="section">
        <h4>Políticas Aceptadas</h4>
        <ul>
            <li>Viaje de menores</li>
            <li>Transporte de mascotas</li>
            <li>Política de equipaje</li>
        </ul>
    </div>

    <div class="section firmas">
        <h4>Firmas</h4>
        <table style="width: 100%">
            <tr>
                <td style="width: 50%; text-align: left;">
                    <b>El Empleador:</b>
                    <div style="height: 3.8em;">
                        <img src="img/FirmaJorgeGallo.png" alt="Firma del gerente" style="width: 80%;">
                    </div>
                    <br>
                    <div style="border-bottom: solid 1px #000000; width: 90%; margin: 10px 0;"></div>
                    <b>COPETRAN</b>
                </td>
                <td style="width: 50%; text-align: left;">
                    <b>El Trabajador(a):</b>
                    <div style="height: 3.8em;">
                        <div style="font-size: 10px; line-height: 1.1;">
                            Documento Firmado Digitalmente<br>
                            Fecha: {{ $firma->FirFecReg }}
                            Hora: {{ $firma->FirHorReg }} <br>
                            Ip: <b>{{ $firma->FirmaIp }}</b> <br>
                            www.copetran.com<br>
                            <label id='sitio'><b>{!! 'http&#8203;s://autogestion.copetran.com.co/firmaConduc&#8203;tores' !!}</b></label>
                        </div>
                    </div>
                    <br>
                    <div style="border-bottom: solid 1px #000000; width: 100%; margin: 10px 0;"></div>
                    <b>{{ $firma->NomCon }}</b>
                </td>
            </tr>
        </table>
    </div>

    <div class="footer section pt-5">
        <hr>
        <p><small>Este comprobante certifica la aceptación de políticas. Conserve este documento como respaldo.</small></p>
    </div>
</body>
</html>
