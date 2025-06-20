<!-- Modal formulario de reporte -->
<div class="container-fluid p-4 bg-white">
    <form
        id="fechasReporteForm"
        action="{{ route('reportes.filtrarTiquetes') }}"
        method="POST"
        enctype="multipart/form-data"
        onsubmit="deshabilitarSubmit(this)"
        data-update="#resultadoTiquetes">


        @csrf
        <div class="row mb-3">
            <div class="col-md-4">
                <label for="fechaInicio" class="form-label">Fecha Inicial *</label>
                <input type="date" name="fechaInicio" id="fechaInicio" class="form-control" required>
                <span class="error text-danger fw-bold" id="error-fechaInicio"></span>
            </div>

            <div class="col-md-4">
                <label for="fechaFin" class="form-label">Fecha Final *</label>
                <input type="date" name="fechaFin" id="fechaFin" class="form-control" required>
                <span class="error text-danger fw-bold" id="error-fechaFin"></span>
            </div>

            <div class="col-md-4">
                <label for="agencia" class="form-label">Agencia</label>
                <select name="agencia" id="agencia" class="form-select" required>
                    <option value="" disabled selected>Elija una opción</option>
                    <option value="todas">Todas</option>
                    <option value="BOOKAWAY LTD">BOOKAWAY LTD</option>
                    <option value="FELIZ VIAJE (CONVENIO)">FELIZ VIAJE (CONVENIO)</option>
                    <option value="MAGANGUE DIRECTA">MAGANGUE DIRECTA</option>
                    <option value="PIN BUS">PIN BUS</option>
                    <option value="REDBUS">REDBUS</option>
                    <option value="SAN GIL">SAN GIL</option>
                    <option value="SINCELEJO">SINCELEJO</option>
                    <option value="VONLINE">VONLINE</option>
                </select>
            </div>
        </div>

        <hr>

        <div class="d-flex justify-content-between align-items-center">
            <div>
                <strong>Total tiquetes impresos:</strong> {{ number_format($totalTiquetes, 0, ',', '.') }}
            </div>

            <div>
                <button type="submit" class="btn btn-success">Generar Reporte</button>
                <button type="button" class="btn btn-dark" data-bs-dismiss="modal">Cancelar</button>
            </div>
        </div>
    </form>

    <div id="resultadoTiquetes" class="mt-4"></div>
</div>