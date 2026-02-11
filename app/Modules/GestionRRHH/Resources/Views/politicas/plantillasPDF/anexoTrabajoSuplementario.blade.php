@php
$firmaGerente = base64_encode(file_get_contents(public_path('img/FirmaJorgeGallo.png')));
@endphp

<style>
  .savePDF {
    font-family: 'DejaVu Sans', sans-serif;
    font-size: 10pt;
    line-height: 1.15;
    color: #000;
  }
  p {
    margin: 0 0 8px 0;
  }
</style>

<div class="section savePDF" style="max-width: 8.5in; margin: 0 auto;">
  <div style="padding: 15px 40px;">
    <div>
      <h3 style="text-align:center; font-weight: normal; margin: 0 0 12px 0; font-size: 11pt;">
        OTROSÍ O CLÁUSULA ADICIONAL AL CONTRATO INDIVIDUAL DE TRABAJO SUSCRITO EL
        <strong><span style="display:inline;">{{ $firma->FechaContrato }}</span></strong> ENTRE LA COOPERATIVA
        SANTANDEREANA DE TRANSPORTADORES LIMITADA – COPETRAN Y
        <strong><span style="display:inline;">{{ $firma->NomCon }} C.C. {{ $firma->DocCon }}</span></strong>
      </h3>
    </div>

    <div style="text-align: justify; margin-bottom: 10px;">
      <p>Las partes de mutuo acuerdo han decidido modificar la Cláusula del Contrato Individual de Trabajo relativa al reconocimiento del trabajo suplementario, la cual quedará redactada de la siguiente manera:</p>
    </div>

    <div style="text-align: justify; margin-bottom: 10px;">
      <p>Los recargos correspondientes a trabajo nocturno, suplementario, dominical, festivo y horas extras, se liquidarán y pagarán mensualmente sobre una cifra que cubra cada concepto y su valor se ha calculado por mutuo acuerdo tomando como referencia los recorridos realizados en el mes y la modalidad para la que esté contratado. Lo anterior teniendo en cuenta la dificultad que entraña determinar con precisión las jornadas de trabajo y la totalidad de días trabajados en el mes, por la rotación en las diferentes rutas que se cubren en el territorio nacional y guardando las normas de equidad, procurando que sean favorables al trabajador y se ha unificado dicho valor con base en lo dispuesto en el artículo 170 del Código Sustantivo del Trabajo, en los periodos o días en que no exista prestación del servicio por cualquier causa, o el vehículo asignado al trabajador no se encuentre en operación por cualquier motivo, no habrá lugar al pago enunciado en la presente cláusula.</p>
    </div>

    <div style="text-align: justify; margin-bottom: 10px;">
      <p><strong>PARÁGRAFO:</strong> En atención al objeto social de la Cooperativa, que corresponde a la prestación del servicio terrestre de personas y cosas, cubriendo dentro de su operación rutas a nivel nacional, las partes han acordado expresamente que su día de descanso sea distinto al día domingo, de acuerdo con la facultad que nos otorga la Ley 2466 de 2025, que modificó el parágrafo tercero del Artículo 179 del Código Sustantivo de Trabajo.</p>
    </div>

    <div style="text-align: justify; margin-bottom: 10px;">
      <p>En todo lo que no ha sido modificado a través del presente acto jurídico, el contrato se mantiene igual en cada una de sus cláusulas.</p>
    </div>

    <div style="text-align: justify; margin-bottom: 12px;">
      <span>Ciudad y fecha: </span>
      <span><b>{{ $firma->MunFir }}/{{ $firma->DepFir }}</b></span>
      <span>, el día: {{ ucfirst(\Carbon\Carbon::parse($firma->FirFecReg)->locale('es')->isoFormat('D [de] MMMM [de] YYYY')) }}</span>
    </div>

    <div style="margin-bottom: 8px;">
      <p>Para constancia de lo anterior, firman las partes:</p>
    </div>

    <table style="width: 100%; margin-top: 10px;">
      <tr>
        <td style="width: 50%; text-align: left; vertical-align: top;">
          <b>EL EMPLEADOR:</b>
          <div style="height: 3.5em; margin-top: 5px;">
            <img src="data:image/png;base64,{{ $firmaGerente }}" alt="Firma del gerente" style="width: 80%;">
          </div>
          <div style="border-bottom: solid 1px #000000; width: 100%; margin: 16px 0 8px 0;"></div>
          <b>COPETRAN</b>
        </td>
        <td style="width: 50%; text-align: left; vertical-align: top;">
          <b>EL TRABAJADOR:</b>
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
