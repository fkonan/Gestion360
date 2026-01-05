<style>
    .savePDF {
        font-family: 'DejaVu Sans', sans-serif;
        font-size: 10pt;
        line-height: 1.2;
        color: #000;
    }

</style>

<div class="container border rounded shadow bg-body-tertiary savePDF" style="max-width: 8.5in" id="bogr">
    <div class="container" id="divpdf">
        <div class="col align-self-center">
            <div>
                <h3 style="text-align:center; font-weight: normal;">
                    OTRO SI AL CONTRATO DE TRABAJO CELEBRADO ENTRE COPETRAN Y
                    <strong><span style="display:inline;" id="nombre">{{ $firma->NomPer }}</span></strong>
                    C.C. No.
                    <strong><span id="cedula">{{ $firma->DocNumPer }}</span> del ({{ \Carbon\Carbon::parse($firma->ConTraPer)->format('Y-m-d') }})</span></strong>
                </h3>
            </div>

            <div>
                <p style="text-align: justify;">Entre los suscritos a saber, <b>JORGE ELIECER GALLO SALCEDO</b>, en nombre y representación de <b>COPETRAN</b>, quien para todos los efectos del presente contrato se denominará <b>EL EMPLEADOR</b> y de otra parte el señor <b>{{ $firma->NomPer }}</b>
                , C.C. No. <b>{{ $firma->DocNumPer }}</b> quien para todos los efectos del presente acto jurídico se denominará <b>EL TRABAJADOR</b>, han decidido por mutuo acuerdo lo siguiente:</p>
                <h3 style="text-align: justify; font-weight: normal; margin-bottom: 0;">ANTECEDENTES:</h3>
            </div>
            <div style="text-align: justify">
                <ul style="list-style: none; margin: 0; padding: 0;">
                    <li><span>1.</span> Las partes celebraron contrato de trabajo, el día <b><label id="fechContrato">{{ \Carbon\Carbon::parse($firma->ConTraPer)->format('Y-m-d')}}</label></b> conforme a su encabezado.</li>
                    <li><span>2.</span> El cargo para el que fue contratado el trabajador, es el de: <b>{{ $firma->CargoPer }}</b>.</li>
                    <li><span>3.</span> En dicho contrato se dispone como dirección física del trabajador, la siguiente: </li>
                    <li><b>{{ $firma->DirPer ?? 'NO_REGISTRA'}}</b></li>
                    <li><span>4.</span> Conforme a lo dispuesto a las nuevas tecnologías y las disposiciones procesales, es posible que las partes celebrantes de un contrato de trabajo se notifiquen de sus decisiones a través de los medios tecnológicos dispuestos para ello.</li>
                </ul>
            </div>
            <br>
            <div>
            <span style="text-align: justify; line-height: 0.8;"><b>Con base en lo anterior, las partes han decidido modificar el contrato de trabajo que los une, conforme a lo que a continuación manifiestan:</b></span>
            </div>
            <br>
            <div>
                <ul style="list-style: none; margin: 0; padding: 0;">
                    <li style="text-align: justify; "><span>1.</span> El cargo que desempeña <b>EL TRABAJADOR</b>, seguirá siendo el de <b>{{ $firma->CargoPer }}</b>.</li>
                    <li style="text-align: justify; "><span>2.</span> El trabajador manifiesta que su dirección física es la siguiente:</li>
                    <li><label for="direccionNueva" style="margin-bottom: 0% !important"><b>{{ $firma->DirPer }}</b></li>
                    <li style="text-align: justify;"><span>3.</span> De igual manera, <b>El TRABAJADOR</b> a partir del presente acto jurídico le otorga la facultad a la empresa de ser notificado de cualquier decisión a su correo electrónico personal o a su número telefónico o a través de las plataformas habilitadas para ello.</li>
                    <li style="text-align: justify; "><span>4.</span> Para tal efecto, el correo electrónico personal del <b>TRABAJADOR</b> es el siguiente: </li>
                    <li><label for="correoNuevo" style="margin-bottom: 0% !important;"><b>{{ $firma->MailPer ?? 'NO REGISTRA'}}</b></label> y su número telefónico personal es:
                        <label for="numeroNuevo" style="margin-bottom: 0% !important;"><b>{{ $firma->NumTelPer }}</b></label> y autoriza su notificación a través de estos.</li>
                    <li style="text-align: justify; "><span>5.</span> Finalmente, es obligación del <b>TRABAJADOR</b>, mantener informado al <b>EMPLEADOR</b> de cualquier cambio de su dirección física, electrónica y de su número de teléfono personal. El no hacerlo, se considerará como falta grave.</li>
                </ul>
            </div>
            <br>
            <div style="text-align: justify;">
                <span>En todo lo que no ha sido modificado a través del presente acto jurídico, el contrato inicial continuará vigente. </span>
            </div>
            <br>
            <div style="text-align: justify;">
                <span style="flex: none; width: auto;">Dado en: </span>
                <span><b>{{ $firma->MunFir }}/{{ $firma->DepFir }}<b></span>
                <span>, el día: {{ ucfirst(\Carbon\Carbon::parse($firma->FirFecReg)->locale('es')->isoFormat('D [de] MMMM [de] YYYY')) }}</span>
            </div>
            <div style="margin: 0; padding: 0">
                <p>Para constancia de lo anterior, firman las partes:</p>
            </div>
            <table style="width: 100%; margin-top:0">
                <tr>
                    <td style="width: 50%; text-align: left;">
                        <b>El Empleador:</b>
                        <div style="height: 4.2em;">
                            <img src="img/FirmaJorgeGallo.png" alt="Firma del gerente" style="width: 80%;">
                        </div>
                        <div style="border-bottom: solid 1px #000000; width: 90%; margin: 5px 0;"></div>
                        <b>COPETRAN</b>
                    </td>
                    <td style="width: 50%; text-align: left;">
                        <b>El Trabajador(a):</b>
                        <div style="height: 4.2em;">
                            <div style="font-size: 10px; line-height: 1;">
                            @php
                                use Carbon\Carbon;
                                Carbon::setLocale('es');
                                $fecha = Carbon::parse($firma->FirFecReg)->isoFormat('D [de] MMMM [de] YYYY'); // 23 de abril de 2025
                                $hora = Carbon::parse($firma->FirHorReg)->format('h:i A'); // 10:42 AM
                            @endphp
                                Documento Firmado Digitalmente<br>
                                Fecha: {{ $fecha }}
                                Hora: {{ $hora }} <br>
                                Ip: <b>{{ $firma->FirmaIp }}</b> <br>
                                www.copetran.com<br>
                                <label id='sitio'><b>https://autogestion.copetran.com.co/firmaConductores</b></label>
                            </div>
                        </div>
                        <div style="border-bottom: solid 1px #000000; width: 100%; margin: 5px 0;"></div>
                        <b>{{ $firma->NomPer }}</b>
                    </td>
                </tr>
            </table>
        </div>
    </div>
</div>
