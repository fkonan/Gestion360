<!-- Resultado busqueda reporte  -->

<hr class="p-2">

<div class="container-fluid my-3 p-4 rounded bg-white border">
    <div class="row mb-3">
        <div class="col-12 col-md-6 d-flex align-items-center mb-2 mb-md-0">
            <h5 class="mb-0 text-dark fw-bold flex-grow-1">Resultados</h5>
        </div>
        <div class="col-12 col-md-6 d-flex align-items-center justify-content-md-end">
            <span class="fw-semibold text-dark me-2">Registros encontrados:</span>
            <span class="fw-bold text-primary fs-5">{{ $registros }}</span>
        </div>
    </div>

    <div class="row g-2 align-items-center mb-3">
        <div class="col-12 col-md-auto">
            <button type="button" id="exportar"
                onclick="exportarExcel('exportar', window.datosParaExportar, '{{ $nombreReporte }}', false)"
                class="btn btn-success fw-semibold w-100"
                title="Descargar Excel">
                Descargar Excel
            </button>
        </div>
    </div>

    <!-- <div class="row g-2 align-items-center mb-3">
        <div class="col-12 mb-2">
            <span class="text-secondary">
                Puede ingresar un correo al cual enviar el reporte en <b> Excel </b>.
            </span>
        </div>
        <div class="col-12 col-md d-flex flex-column flex-md-row align-items-stretch align-items-md-center">
            <input type="email" id="correoDestino" class="form-control mb-2 mb-md-0 me-md-2" placeholder="Ingrese correo" style="max-width: 100%;">
        </div>
        <button onclick="alert('hello')" type="button" id="enviarCorreo" class="btn btn-primary fw-semibold" title="Enviar correo" disabled>
            Enviar correo
        </button>
    </div> -->

    <script>
        window.datosParaExportar = @json($dataJson); 
        const correoInput = document.getElementById('correoDestino');
        const enviarBtn = document.getElementById('enviarCorreo');

        correoInput.addEventListener('input', function() {
            enviarBtn.disabled = !correoInput.value || !correoInput.checkValidity();
        });
    </script>
</div>

