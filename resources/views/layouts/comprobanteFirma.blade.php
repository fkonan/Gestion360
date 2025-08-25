<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Comprobante de Firma</title>
    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
            margin: 15px 30px;
            font-size: 11px;
            line-height: 1.4;
        }

        .header, .footer {
            text-align: center;
        }

        .header h2 {
            margin: 8px 0;
            font-size: 16px;
            font-weight: bold;
        }

        .header p {
            margin: 3px 0;
        }

        .section {
            margin-top: 15px;
        }

        .section h4 {
            font-size: 12px;
            margin-bottom: 8px;
            border-bottom: 1px solid #000;
            font-weight: bold;
            padding-bottom: 2px;
        }

        .info-box span {
            margin: 4px 0;
            line-height: 1.3;
            font-size: 12px;
        }

        .footer p {
            font-size: 10px;
            margin: 3px 0;
        }

        hr {
            margin: 12px 0;
            border: 1px solid #000;
        }

        .savePDF {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 11pt;
            line-height: 1.4;
            color: #000;
        }

        .politica-detalle {
            border: 1px solid #000;
            padding: 8px;
            margin: 8px 0;
            font-size: 12px;
            line-height: 1.4;
        }

        .politica-detalle-resumido{
            padding: 2px 0 0 6px;
            padding: 2px 0 0 6px;
            font-size: 12px;
            line-height: 1.4;
        }

        .politica-item {
            margin-bottom: 10px;
            width: 100%;
        }

        .politica-titulo {
            font-weight: bold;
            margin-bottom: 5px;
            font-size: 11px;
        }

        .importante {
            padding: 8px;
            margin: 10px 0;
            text-align: center;
            font-weight: bold;
            font-size: 10px;
            line-height: 1.3;
        }

        .politicas-container {
            width: 100%;
            margin: 0;
            padding: 0;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }

        .info-row {
            margin: 3px 0;
        }

        .info-label {
            font-weight: bold;
            display: inline-block;
            width: 80px;
        }

        .datos-grid {
            width: 100%;
        }

        .datos-grid td {
            padding: 2px 5px;
            vertical-align: top;
        }

        .punto-importante {
            font-weight: bold;
            margin: 0 3px;
        }
    </style>
</head>
<body class="savePDF">

    <div class="header">
        <h2>COMPROBANTE DE FIRMA</h2>
        <p><strong>Generado:</strong> {{ ucfirst(\Carbon\Carbon::parse($firma->FirFecReg)->locale('es')->isoFormat('D [de] MMMM [de] YYYY')) }}</p>
    </div>

    <div class="section">
        <h4>DATOS DEL USUARIO</h4>
        <div class="info-box">
            <span><strong>Nombre:</strong> {{ $firma->NomCon }}</span><br>
            <span><strong>Documento:</strong> {{ $firma->DocCon }}</span><br>
            <span><strong>Correo:</strong> {{ $firma->Correo }}</span><br>
            <span><strong>Lugar:</strong> {{ $firma->DepFir ?? 'NO REGISTRA' }}, {{ $firma->MunFir ?? 'NO REGISTRA' }}</span><br>
            <span><strong>Fecha y hora de firma:</strong> {{ $firma->FirFecReg }} {{ $firma->FirHorReg }}</span>
        </div>
    </div>

    <div class="section">
        <h4>DETALLES FIRMA</h4>
        <div class="politicas-container">
            @yield('content')
        </div>
    </div>

    <div class="section firmas">
        <table style="width: 100%">
            <tr>
                <td style="width: 50%; text-align: left;">
                    <b>El Empleador:</b>
                    <div style="height: 3.7em;">
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
                            Ip: <b>{{ $firma->FirmaIp ?? 'NO REGISTRA' }}</b> <br>
                            www.copetran.com<br>
                            <label id='sitio'><b>{!! 'http&#8203;s://autogestion.copetran.com.co/firmaConduc&#8203;tores' !!}</b></label>
                        </div>
                    </div>
                    <br>
                    <div style="border-bottom: solid 1px #000000; width: 100%; margin: 6px 0;"></div>
                    <b>{{ $firma->NomCon }}</b>
                </td>
            </tr>
        </table>
    </div>


    <div class="footer section">
        <hr style="border-top: 1px solid #000;">
        <p><strong>Este comprobante certifica la aceptación de las normas y politicas de COPETRAN.</strong></p>
    </div>
</body>
</html>