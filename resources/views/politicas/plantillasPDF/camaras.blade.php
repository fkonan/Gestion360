<style>
    .savePDF {
        font-family: 'DejaVu Sans', sans-serif;
        font-size: 10pt;
        line-height: 1.2;
        color: #000;
    }

</style>

<div class="section mt-5 savePDF" style="max-width: 8.5in">
    <div class="pt-4 border rounded shadow p-3 mb-5 bg-body-tertiary">
        <div class="px-5 m-0" id="">
            <div>
                <h3 style="text-align:center; font-weight: normal;">
                    OTRO SI AL CONTRATO DE TRABAJO CELEBRADO ENTRE COPETRAN Y 
                    <strong><span style="display:inline;" id="nombre">{{ $firma->NomCon }}</span></strong>
    
                </h3>
            </div>
            <div class="section" style="text-align: justify;">
                <p>Entre los suscritos a saber, <b>JORGE ELIECER GALLO SALCEDO</b>, en nombre y representación de <b>COPETRAN LTDA</b> quien para todos
                    los efectos del presente contrato se denominará <strong>EL EMPLEADOR</strong> y de otra parte <strong>{{$firma->NomCon}}</strong>, quien para todos
                    los efectos del presente acto jurídico se denominará <strong>EL TRABAJADOR</strong>, han decidido por mutuo acuerdo lo siguiente:</p>
                <h3 style="text-align: center; font-weight: normal; margin:0; padding:0">ANTECEDENTES</h3>
                <div class="section" style="text-align: justify;">
                    <ol>
                        <li class="liNor">Las partes celebraron contrato de trabajo a término fijo el día <strong>{{ $firma->FechaContrato }}</strong>, conforme a su encabezado.</li>
                        <li class="liNor">El cargo para el que fue contratado el trabajador(a), es el de <strong>{{ $firma->Cargo }}</strong></li>
                        <li class="liNor">El salario que devenga actualmente es: $1.300.000.oo</li>
                        <li class="liNor">De igual forma, las funciones desempeñadas por el <strong>TRABAJADOR</strong> son las
                            siguientes:
                            <ul type="A" style="list-style-type: disc;">
                                @foreach ($funcionesCargo as $funcion)
                                    <li class="liNor">{{ $funcion->FunDes }}</li>
                                @endforeach
                            </ul>
                        </li>
                        <li class="liNor">Es pertinente destacar que el Ministerio de Transporte en ejercicio de sus
                            facultades constitucionales y legales expidió Resolución No. 20223040040595
                            del 12 de julio de 2022, mediante la cual adoptó metodología para el diseño,
                            implementación y verificación de planes estratégicos de Seguridad Vial y se
                            dictan otras disposiciones, en las que se resuelve implementar la “metodología
                            para el diseño, implementación y verificación de los Planes estratégicos de
                            Seguridad Vial” la cual es de obligatorio cumplimiento por parte de la Empresa.</li>
                        <li class="liNor">Conforme a lo anterior, <strong>COPETRAN</strong>, se encuentra en la obligación de adoptar
                            organizaciones y diseñar un Plan estratégicos de Seguridad Vial, con el fin de
                            optimizar recursos tecnológicos, procedimientos y actividades de capacitación,
                            planes de emergencia, primeros auxilios, entre otras, promoviendo en sus
                            colaboradores la formación de hábitos, comportamientos y conductas seguras
                            en la vía, al ser una <strong>ORGANIZACIÓN DEBIDA A LA PRESTACIÓN DE
                            SERVICIO TERRESTRE AUTOMOTOR DE TAMAÑO AVANZADO</strong>, al tener a
                            su servicio más de 50 vehículos y 50 conductores.</li>
                        <li class="liNor">De igual forma, el <strong>MINISTERIO DE TRANSPORTE y la SUPERINTENDENCIA
                            DE TRANSPORTE</strong> expidieron Resolución No. 5178 de 24 de julio de 2023,
                            mediante la cual se adiciona la circular única de infraestructura y Transporte y
                            se dictan otras disposiciones, consolidando así el TITULO VI de dicha
                            normativa, implementando un Proceso Institucional para la verificación y
                            seguimiento de los planes estratégicos de Seguridad Vial - PI/PESV.</li>
                        <li class="liNor">En tal sentido, <strong>COPETRA LTDA</strong> en calidad de empleadora adoptará las
                            medidas necesarias, en aras de dar cumplimiento a lo ordenado en la precitada
                            Normativa, en los siguientes términos:</li>
                        <ol class="olLet">
                            <li class="liNor"><strong>COPETRAN</strong> LTDA implementará cámaras de vigilancia dentro de los
                            vehículos que prestan el servicio público de transporte masivo. Lo
                                anterior implica que el conductor designado y los pasajeros serán
                                sometidos a tratamiento de sus datos personales, lo cual no es
                                violatorio del derecho a la intimidad conforme ha indicado la Corte
                                Constitucional.</li>
                            <li class="liNor">
                                <p>Por lo anterior, la <strong>EMPRESA</strong> dispondrá la utilización de señales, avisos
                                    distintivos o anuncios de audio en las zonas de video vigilancia al
                                    interior del vehículo automotor.</p>
                                <p style="line-height: 1;">La Corte Constitucional ha señalado que la instalación de cámaras de
                                    seguridad en vehículos de transporte público no vulnera el derecho al
                                    habeas data. El alto tribunal ha señalado que: <em>“(…) la instalación de cámaras 
                                        de vigilancia en lugares semiprivados y semipúblicos no
                                        resulta, en principio, inconstitucional. Por el contrario, resulta
                                        constitucional siempre y cuando su instalación atienda los principios de
                                        finalidad, necesidad y proporcionalidad. De manera general, los
                                        vehículos de transporte público pueden catalogarse como espacios
                                        semipúblicos, pues se trata de lugares de acceso relativamente abierto
                                        en los que diferentes personas se encuentran en determinado momento
                                        para realizar cierta actividad puntual dentro de un espacio compartido.
                                        Según la jurisprudencia de esta Corte, los espacios semipúblicos,
                                        cuentan con menores limitaciones a las libertades individuales, pero,
                                        por lo mismo, hay mayor tolerancia al control y vigilancia sobre las
                                        conductas de las personas con el fin de evitar y prevenir situaciones de
                                        riesgo ya que las repercusiones sociales son mayores. En esencia, se
                                        trata de lugares en los que la expectativa de privacidad se reduce, dado
                                        que las personas no pueden considerar válidamente que su actividad se
                                        encuentre resguardada absolutamente de la interferencia de otros”</em>.</p>
                            <li class="liNor"><strong>COPETRAN LTDA</strong> respetará las disposiciones contenidas en Ley 1581
                                de 2012, sus normas reglamentarias, en lo que respecta al tratamiento
                                de datos personales, así como las instrucciones impartidas por la
                                Superintendencia de Industria y Comercio, Superintendencia de
                                Transporte y Ministerio de Transporte, en concordancia a lo antedicho.</li>
                            <li class="liNor">De igual forma, conforme a lo señalado en el Decreto 348 de 2015 del
                                Ministerio de Transporte, las empresas de servicio público de transporte
                                terrestre automotor deben contar con la plataforma tecnológica, los
                                GPS, el centro de control y las cámaras de video en los vehículos; por
                                tanto, y de conformidad a la normativa señalada, cada vehículo deberá
                                llevar un dispositivo transmisor GPS y cámaras de seguridad
                                precitadas, que emita las señales correspondientes de localización,
                                audio y video a la <strong>EMPRESA</strong>, para así dar cumplimiento a la Normativa
                                expuesta.</li>
                        </ol>
                    </ol>
                </div>
            </div>
            <div class="section" style="text-align: justify;">
                <p style="line-height: 1;"><b>Con base en lo anterior, las partes han decidido modificar el contrato de trabajo
                        que los une, conforme a lo que a continuación manifiestan:</b></p>
            </div>
            <div style="text-align: justify;">
                <ol>
                    <li class="liUnder">El salario del <b>TRABAJADOR</b> seguirá siendo la suma de $<u>1.300.000</u>.oo, el
                        cual ya venía devengando.</li>
                    <li class="liUnder">La<b> EMPRESA</b>, en cumplimiento de la Normativa señalada con antelación,
                        dotará los <b>VEHICULOS AUTOMOTORES</b> al servicio de ésta, con dispositivo
                        GPS, y las <b>CÁMARAS DE AUDIO - VIDEO</b> al interior de los vehículos,
                        deponiendo para tal fin avisos distintivos o anuncios de audio en las zonas de
                        video vigilancia al interior del vehículo automotor. Aspecto que en ninguna
                        circunstancia puede considerarse violatorio al Derecho de Intimidad,
                        atendiendo dicha medida principios de finalidad, necesidad y proporcionalidad
                        previamente señalados en cumplimiento de la Normatividad Legal Vigente.</li>
                    <li class="liUnder"><b>El TRABAJADOR</b> se encuentra en la obligación de <b>SALVAGUARDAR,
                            PROTEGER, CUIDAR y PROPENDER POR EL CORRECTO
                            FUNCIONAMIENTO DE ESTOS, y de informar a la EMPRESA cualquier
                            daño a estos equipos.</b></li>
                    <li class="liUnder"><b>EL TRABAJADOR</b>, tiene <b>PROHIBIDO</b> manipular o dañar el dispositivo <b>GPS</b>, y
                        las <b>CÁMARAS DE AUDIO - VIDEO</b> al interior de los vehículos, y la violación  a
                        ello se considerará como falta grave.</li>
                    <li class="liUnder">La manipulación o daño comprobado de dichos elementos tecnológicos por
                        parte del <b>TRABAJADOR</b>, constituirá <b>FALTA GRAVE</b>, y será sujeto de <b>PROCESO DISCIPLINARIO</b>
                        por parte de COPETRAN, conforme al
                        procedimiento establecido en el <b>REGLAMENTO INTERNO DE TRABAJO.</b></li>
                    <li class="liUnder">De igual forma <b>El TRABAJADOR</b> autoriza <u>expresamente</u> el descuento por los
                        daños ocasionados a los dispositivos <b>GPS</b>, y las <b>CÁMARAS DE AUDIO -
                            VIDEO</b> al interior de los vehículos, conforme a lo indicado de forma precedente,
                        de sus prestaciones sociales y salarios en aras de resarcir el daño patrimonial
                        causado y comprobado por parte de la <b>EMPRESA.</b></li>
                    <li class="liUnder">Así mismo, de comprobarse la manipulación o daño a los elementos
                        tecnológicos dispositivo <b>GPS</b>, y las <b>CÁMARAS DE AUDIO - VIDEO</b> al interior
                        de los vehículoss, la <b>EMPRESA</b> multará al <b>TRABAJADOR</b> con la suma de
                        <b>CINCO (5) SALARIOS MINIMOS LEGALES MENSUALES VIGENTES,</b> por el
                        incumplimiento a lo pactado, para lo cual el trabajador acepta desde ya el
                        descuento de esa suma de dinero de su liquidación de salarios y prestaciones
                        sociales, sin perjuicio de las sanciones disciplinarias o la terminación del
                        contrato por la comisión de la falta aquí anotada.
                    </li>
                    <li class="liUnder">La <b>EMPRESA</b> dará correcto manejo a la información  que incluya datos
                        personales, dando estricto cumplimiento a las disposiciones constitucionales y
                        legales sobre la protección del derecho fundamental de habeas data, en
                        particular lo dispuesto en el artículo 15 de la Constitución Política y la ley 1581
                        de 2012, el cual, conforme a los antecedentes enunciados, la medida adoptada
                        no vulnera los Derechos Fundamentales del <b>TRABAJADOR</b> y los <b>USUARIOS</b>
                        del transporte público  prestado por la <b>COMPAÑÍA.</b></li>
                    <li class="liUnder"> Los mencionados cambios rigen a partir del 1° de febrero de 2024.</li>
                </ol>
            </div>
            <div style="text-align: justify;">
                <span style="flex: none; width: auto;">Dado en: </span>
                <span><b>{{ $firma->MunFir }}/{{ $firma->DepFir }}<b></span>
                <span>, el día: {{ ucfirst(\Carbon\Carbon::parse($firma->FirFecReg)->locale('es')->isoFormat('D [de] MMMM [de] YYYY')) }}</span>
            </div>
            <div class="section">
                <p>Para constancia de lo anterior, firman las partes:</p>
            </div>
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
    </div>
</div>