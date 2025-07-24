<!-- Resultado AJAX en index pagos-recaudos  -->
<br>
<div class="container-fluid p-0 border rounded sidebar-dark-primary tableContainer pb-2" style="min-height:150px">
    <div class="border rounded-top d-flex justify-content-between align-items-center px-4 bg-primary-subtle">
        <span class="text-left text-light fs-5 fw-medium py-1">Pago disponible</span>
    </div>

    <div class="container-fluid py-4">
        <div class="table-responsive">
            <table 
                id="tablePagos"
                class="table table-hover table-sm align-middle mb-4"
                data-toggle="table"
                data-detail-view="true"
                data-detail-formatter="detallePago">
                
                <thead class="table-light">
                    <tr>
                        <th scope="col">Identificación</th>
                        <th scope="col">Cliente principal</th>
                        <th scope="col">Identificación secundaria</th>
                        <th scope="col">Cliente secundario</th>
                        <th scope="col">Valor total</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>{{ $userData['identificacion'] }}</td>
                        <td>{{ $userData['nombre'] }}</td>
                        <td>{{ $userData['identificacion'] }}</td>
                        <td>{{ $userData['nombre'] }}</td>
                        <td>${{ number_format($data['additionalData']['saldo'], 0, ',', '.') }}</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="text-end mb-2">
            <button type="button" 
                    class="btn btn-success shadow-sm me-1"
                    onclick="cargarModal(`{{ route('pagosConvenios.validarModal') }}`, 'Validar pago', '#formPago', 'modal-lg')">
                <i class="fas fa-check-circle me-1"></i> Validar
            </button>
        </div>
    </div>
</div>
