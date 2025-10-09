<style>
  .savePDF {
    font-family: 'DejaVu Sans', sans-serif;
    font-size: 9.5pt;
    line-height: 1.1;
    color: #000;
  }
  p {
    margin: 0 0 6px 0;
  }
</style>

<div class="section savePDF" style="max-width: 8.5in; margin: 0 auto;">
  <div style="padding: 10px 35px;">
    <div>
      <h3 style="text-align:center; font-weight: normal; margin: 0 0 8px 0; font-size: 10.5pt; line-height: 1.2;">
        OTRO SI AL CONTRATO DE TRABAJO, CELEBRADO ENTRE LA COOPERATIVA SANTADEREANA DE TRANSPORTADORES LTDA – COPETRAN Y
        <strong><span style="display:inline;">{{ $firma->NomCon }} C.C. No. {{ $firma->DocCon }}</span></strong>
      </h3>
    </div>

    <div style="text-align: justify; margin-bottom: 7px;">
      <p>Entre los suscritos, a saber: <b>COOPERATIVA SANTANDEREANA DE TRANSPORTADORES LTDA. – COPETRAN</b>, identificada con NIT No.890200928, con domicilio en la ciudad de Bucaramanga, quien en adelante se denominará <strong>EL EMPLEADOR; y de la otra parte,</strong> <strong>{{ $firma->NomCon }}</strong>, mayor de edad, identificado(a) con cédula de ciudadanía No. <strong>{{ $firma->DocCon }}</strong>, domiciliado en <strong>{{ $firma->MunFir }}/{{ $firma->DepFir }}</strong>, quien en adelante se denominará <strong>EL TRABAJADOR</strong>, hemos convenido en suscribir el presente otrosí al contrato de trabajo celebrado entre las partes, al tenor de las siguientes:</p>
    </div>

    <div style="text-align: justify; margin-bottom: 7px;">
      <p style="margin-bottom: 4px;"><strong>CLÁUSULA PRIMERA – OBJETO DE LA MODIFICACIÓN</strong></p>
      <p>Las partes acuerdan modificar la <strong>CLÁUSULA DÉCIMA</strong> del contrato de trabajo suscrito, la cual quedará redactada de la siguiente manera:</p>
    </div>

    <div style="text-align: justify; margin-bottom: 7px;">
      <p style="margin-bottom: 4px;"><strong>CLÁUSULA DÉCIMA – OBLIGACIONES SARLAFT Y CUMPLIMIENTO NORMATIVO</strong></p>
      <p style="margin-bottom: 5px;">El empleado se compromete a dar cumplimiento a las políticas y procedimientos en materia de prevención del Riesgo de Lavado de Activos, Financiación del Terrorismo y la Financiación de la Proliferación de Armas de Destrucción Masiva, así como la Política de Conflicto de Interés y la Política de Transparencia y No Tolerancia a la Corrupción, de conformidad con el Manual SARLAFT y el Código de Ética y Conducta conocido por todos los empleados de la Cooperativa.</p>
      <p style="margin-bottom: 5px;">Así mismo, el Empleado autoriza a la Cooperativa, o a quien se designe, a consultar en cualquier tiempo su nombre y documento de identidad en listas, bases de datos y centrales de riesgo de carácter público, tanto nacionales como internacionales. El Empleado también autoriza que se realice una debida diligencia continua sobre la relación laboral, garantizando que la información suministrada sea consistente con el conocimiento almacenado por la Cooperativa.</p>
      <p style="margin-bottom: 5px;">Lo anterior se establece en cumplimiento de las resoluciones y circulares en materia de prevención del Lavado de Activos, Financiación del Terrorismo y lucha contra la Corrupción, emitidas por la <strong>Superintendencia de Transporte</strong>, entidad que vigila a la Cooperativa.</p>
      <p>El Trabajador declara haber leído cuidadosamente y comprendido a cabalidad el contenido de esta cláusula, razón por la cual entiende sus alcances e implicaciones. El incumplimiento de cualquiera de las responsabilidades aquí contenidas constituirá falta grave por parte del empleado y facultará al empleador a dar por terminado el contrato de trabajo de manera unilateral y por justa causa.</p>
    </div>

    <div style="text-align: justify; margin-bottom: 7px;">
      <p style="margin-bottom: 4px;"><strong>CLÁUSULA SEGUNDA – VIGENCIA</strong></p>
      <p>El presente otrosí hace parte integral del contrato de trabajo suscrito entre las partes y entra en vigor a partir de la fecha de su firma, permaneciendo vigente durante toda la relación laboral.</p>
    </div>

    <div style="text-align: justify; margin-bottom: 7px;">
      <p style="margin-bottom: 4px;"><strong>EN CONSTANCIA</strong></p>
      <p>Se firma el presente otrosí en dos (2) ejemplares de igual tenor y valor, cada uno de los cuales quedará en poder de las partes.</p>
    </div>

    <div style="text-align: justify; margin-bottom: 8px;">
      <span>En </span>
      <span><b>{{ $firma->MunFir }}/{{ $firma->DepFir }}</b></span>
      <span>, a los {{ ucfirst(\Carbon\Carbon::parse($firma->FirFecReg)->locale('es')->isoFormat('D [días del mes de] MMMM [de] YYYY')) }}</span>
    </div>

    <table style="width: 100%; margin-top: 9px;">
      <tr>
        <td style="width: 50%; text-align: left; vertical-align: top;">
          <b>El Empleador:</b>
          <div style="height: 3.2em; margin-top: 3px;">
            <img src="img/FirmaJorgeGallo.png" alt="Firma del gerente" style="width: 80%;">
          </div>
          <div style="border-bottom: solid 1px #000000; width: 90%; margin: 16px 0 2px 0;"></div>
          <b>COPETRAN</b>
        </td>
        <td style="width: 50%; text-align: left; vertical-align: top;">
          <b>El Trabajador(a):</b>
          <div style="height: 3.2em; margin-top: 3px;">
            <div style="font-size: 8.5px; line-height: 1.05;">
              Documento Firmado Digitalmente<br>
              Fecha: {{ $firma->FirFecReg }}
              Hora: {{ $firma->FirHorReg }} <br>
              Ip: <b>{{ $firma->FirmaIp }}</b> <br>
              www.copetran.com<br>
              <label id='sitio'><b>{!! 'http&#8203;s://autogestion.copetran.com.co/firmaConduc&#8203;tores' !!}</b></label>
            </div>
          </div>
          <div style="border-bottom: solid 1px #000000; width: 100%; margin: 16px 0 2px 0;"></div>
          <b>{{ $firma->NomCon }}</b>
        </td>
      </tr>
    </table>
  </div>
</div>
