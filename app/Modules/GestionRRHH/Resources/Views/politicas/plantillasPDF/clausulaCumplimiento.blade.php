<style>
  .savePDF {
    font-family: 'DejaVu Sans', sans-serif;
    font-size: 10pt;
    line-height: 1.15;
    color: #000;
  }
  .liNor {
    margin-bottom: 6px;
    text-align: justify;
  }
  p {
    margin: 0 0 8px 0;
  }
</style>

<div class="section savePDF" style="max-width: 8.5in; margin: 0 auto;">
  <div style="padding: 15px 40px;">
    <div>
      <h3 style="text-align:center; font-weight: normal; margin: 0 0 12px 0; font-size: 11pt;">
        OTRO SI AL CONTRATO DE TRABAJO, CELEBRADO ENTRE LA COOPERATIVA SANTADEREANA DE TRANSPORTADORES LTDA – COPETRAN Y
        <strong><span style="display:inline;">{{ $firma->NomCon }} C.C. No {{ $firma->DocCon }}</span></strong>
      </h3>
    </div>

    <div style="text-align: justify; margin-bottom: 10px;">
      <p>Entre los suscritos a saber, <b>LA COOPERATIVA SANTADEREANA DE TRANSPORTADORES LTDA – COPETRAN</b>, quien para todos los efectos del presente contrato se denominará <strong>EL EMPLEADOR</strong> y de otra parte el señor (a) <strong>{{ $firma->NomCon }}</strong>, quien para todos los efectos del presente acto jurídico se denominará <strong>EL TRABAJADOR</strong>, han decidido por mutuo acuerdo lo siguiente:</p>
    </div>

    <h3 style="text-align: center; font-weight: normal; margin: 8px 0; font-size: 11pt;">ANTECEDENTES</h3>

    <div style="text-align: justify; margin-bottom: 10px;">
      <ol style="margin: 0; padding-left: 20px;">
        <li class="liNor">Las partes celebraron contrato de trabajo, el día <strong>{{ $firma->FechaContrato }}</strong>, conforme a su encabezado.</li>
        <li class="liNor">El cargo para el que fue contratado el trabajador(a), es el de <strong>{{ $firma->Cargo }}</strong></li>
      </ol>
    </div>

    <div style="text-align: justify; margin-bottom: 10px;">
      <p style="line-height: 1.15;"><b>Con base en lo anterior, las partes han decidido modificar el contrato de trabajo que los une, conforme a lo que a continuación manifiestan:</b></p>
    </div>

    <div style="text-align: justify; margin-bottom: 10px;">
      <p><strong>PRIMERA.</strong> EL TRABAJADOR manifiesta que ha recibido, conoce y acepta las nuevas políticas internas de la empresa, las cuales le fueron socializadas y entregadas en fecha {{ ucfirst(\Carbon\Carbon::parse($firma->FirFecReg)->locale('es')->isoFormat('D [de] MMMM [de] YYYY')) }}, que se enuncian a continuación:</p>
      <ol style="margin: 5px 0; padding-left: 25px;">
        <li class="liNor">Política Transporte de Mascotas (OD-DO-24).</li>
        <li class="liNor">Política para el viaje de Menores de Edad (OD-DO-25).</li>
        <li class="liNor">Política Transporte de Equipaje (OD-DO-26).</li>
        <li class="liNor">Metodología para la venta, abordaje y descenso de pasajeros menores de edad (PR-FS-22).</li>
      </ol>
    </div>

    <div style="text-align: justify; margin-bottom: 10px;">
      <p><strong>SEGUNDA.</strong> EL TRABAJADOR se compromete a dar estricto cumplimiento a dichas políticas en el desarrollo de sus funciones, entendiendo que forman parte integral del reglamento interno de trabajo y que su inobservancia puede dar lugar a la aplicación de las medidas disciplinarias correspondientes.</p>
    </div>

    <div style="text-align: justify; margin-bottom: 10px;">
      <p><strong>TERCERA.</strong> Las demás cláusulas del contrato de trabajo inicial permanecen sin modificación alguna.</p>
      <p style="margin-top: 6px;">En todo lo que no ha sido modificado a través del presente acto jurídico, el contrato inicial continuará vigente.</p>
    </div>

    <div style="text-align: justify; margin-bottom: 12px;">
      <span>Dado en: </span>
      <span><b>{{ $firma->MunFir }}/{{ $firma->DepFir }}</b></span>
      <span>, el día: {{ ucfirst(\Carbon\Carbon::parse($firma->FirFecReg)->locale('es')->isoFormat('D [de] MMMM [de] YYYY')) }}</span>
    </div>

    <div style="margin-bottom: 8px;">
      <p>Para constancia de lo anterior, firman las partes:</p>
    </div>

    <table style="width: 100%; margin-top: 10px;">
      <tr>
        <td style="width: 50%; text-align: left; vertical-align: top;">
          <b>El Empleador:</b>
          <div style="height: 3.5em; margin-top: 5px;">
            <img src="img/FirmaJorgeGallo.png" alt="Firma del gerente" style="width: 80%;">
          </div>
          <div style="border-bottom: solid 1px #000000; width: 100%; margin: 16px 0 8px 0;"></div>
          <b>COPETRAN</b>
        </td>
        <td style="width: 50%; text-align: left; vertical-align: top;">
          <b>El Trabajador(a):</b>
          <div style="height: 3.5em; margin-top: 5px;">
            <div style="font-size: 9px; line-height: 1.1;">
              Documento Firmado Digitalmente<br>
              Fecha: {{ $firma->FirFecReg }}
              Hora: {{ $firma->FirHorReg }} <br>
              Ip: <b>{{ $firma->FirmaIp }}</b> <br>
              www.copetran.com<br>
              <label id='sitio'><b>{!! 'http&#8203;s://autogestion.copetran.com.co/firmaConduc&#8203;tores' !!}</b></label>
            </div>
          </div>
          <div style="border-bottom: solid 1px #000000; width: 100%; margin: 16px 0 8px 0;"></div>
          <b>{{ $firma->NomCon }}</b>
        </td>
      </tr>
    </table>
  </div>
</div>
