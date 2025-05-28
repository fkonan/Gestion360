<!-- Modal reporte esquema tarifario pasajes -->
<div class="container-fluid p-2 bg-white">
    <form
        id="esquemaTarifarioForm" 
        action="{{ route('esquemaTarifario.filtrar') }}" 
        method="POST" 
        enctype="multipart/form-data"
        onsubmit="deshabilitarSubmit(this)">
        
        @csrf
        <div class="row mb-3 mx-1">
            <div class="col-12 col-md-6">
                <label for="fechaInicio" class="form-label">Fecha Inicio *</label>
                <input type="date" name="fechaInicio" id="fechaInicio" class="form-control" required>
                <span class="error text-danger fw-bold" id="error-fechaInicio"></span>
            </div>
            <div class="col-12 col-md-6">
                <label for="fechaFin" class="form-label">Fecha Final *</label>
                <input type="date" name="fechaFin" id="fechaFin" class="form-control" required>
                <span class="error text-danger fw-bold" id="error-fechaFin"></span>
            </div>
        </div>

        <div class="row mb-3 mx-1 pt-3">
            <div class="col-12 col-md-6">
                <label for="origen" class="form-label">Origen del viaje *</label>
                <select class="form-select select2" id="origen" name="origen" required>
                    <option value="" selected disabled>Seleccione un origen</option>
                    <option value="%">TODOS</option>
                    @foreach($municipios as $municipio)
                        <option value="{{ $municipio->MunNom }}">{{ $municipio->MunNom }} - ({{ $municipio->departamento->Abreviacion }})</option>
                    @endforeach
                </select>
                <span class="error text-danger fw-bold" id="error-origen"></span>
            </div>

            <div class="col-12 col-md-6">
                <label for="destino" class="form-label">Destino del viaje *</label>
                <select class="form-select select2" id="destino" name="destino" required>
                    <option value="" selected disabled>Seleccione un destino</option>
                    <option value="%">TODOS</option>
                    @foreach($municipios as $municipio)
                        <option value="{{ $municipio->MunNom }}">{{ $municipio->MunNom }} - ({{ $municipio->departamento->Abreviacion }})</option>
                    @endforeach
                </select>
                <span class="error text-danger fw-bold" id="error-destino"></span>
            </div>
        </div>

        <hr class="p-2">

        <div>
            <button type="submit" class="btn btn-success">Aceptar</button>
            <button type="button" class="btn btn-dark" data-bs-dismiss="modal">Cancelar</button>
        </div>
    </form>
</div>