<!-- Resultado búsqueda reporte  -->
<div class="mt-4">
    <div class="border-top mb-4"></div>

    <div class="rounded-3 p-4 border shadow-sm">
        
        <div class="row align-items-center mb-3">
            <div class="col-12 col-lg-6 mb-3 mb-lg-0">
                <div class="d-flex align-items-center">
                    <div class="me-3">
                        <i class="fas fa-chart-bar fs-4"></i>
                    </div>
                    <div>
                        <h5 class="mb-1 fw-bold">Resultados del Reporte</h5>
                    </div>
                </div>
            </div>
            
            <div class="col-12 col-lg-6">
                <div class="d-flex align-items-center justify-content-lg-end gap-3">
                    <!-- Contador de registros -->
                    <div class="d-flex align-items-center">
                        <span class="text-muted me-2">Registros encontrados:</span>
                        <span class="badge fs-6 px-3 py-2">{{ number_format($registros) }}</span>
                    </div>
                    
                    <!-- Botón de exportar -->
                    <button type="button" 
                            class="btn btn-sm d-flex align-items-center gap-2"
                            id="exportar" 
                            title="Descargar reporte en Excel"
                            onclick="exportarExcel('exportar', window.datosParaExportar, '{{ $nombreReporte }}', false)">
                         <img src="{{ asset('img/descargar-excel.png') }}" alt="Descargar Excel" style="width: 40px; height: 40px;">
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Script para datos de exportación -->
    <script>
        window.datosParaExportar = @json($dataJson); 
    </script>
</div>