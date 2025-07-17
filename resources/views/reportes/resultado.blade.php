<!-- Resultado busqueda reporte  -->
<hr class="p-2">

<div class="container-fluid my-3 p-4 rounded border">
    <div class="row mb-3">
        <div class="col-12 col-md-6 d-flex align-items-center mb-2 mb-md-0">
            <h5 class="mb-0 fw-bold flex-grow-1 d-flex align-items-center">
                Resultados
                <a class="text-decoration-none ms-3" 
                    id="exportar" 
                    title="Descargar Excel"
                    style="cursor: pointer;"
                    onclick="exportarExcel('exportar', window.datosParaExportar, '{{ $nombreReporte }}', false)">
                    <img src="{{ asset('img/descargar-excel.png') }}" alt="Descargar Excel" style="width: 40px; height: 40px;">
                </a>
            </h5>
        </div>
        <div class="col-12 col-md-6 d-flex align-items-center justify-content-md-end">
            <span class="fw-semibold me-2">Registros encontrados:</span>
            <span class="fw-bold text-primary fs-5">{{ $registros }}</span>
        </div>
    </div>

    <script>
        window.datosParaExportar = @json($dataJson); 
    </script>
</div>

