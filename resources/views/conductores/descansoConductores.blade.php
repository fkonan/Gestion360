<!-- Modal registro descanso de conductores -->
<div class="container-fluid p-2">
    <form
        id="descansoConductorForm" 
        action="{{ route('registrar.evento') }}" 
        method="POST" 
        enctype="multipart/form-data"
        onsubmit="deshabilitarSubmit(this)">
        
        @csrf
        <div class="row mb-4 mx-1">
            <label for="identificacion" class="form-label">Número de identificación Conductor</label>
            <input type="text" name="identificacion" id="identificacion" class="form-control" 
                   placeholder="Número de identificación" required onblur="buscarUltimoEvento()">
            <span class="error text-danger fw-bold" id="error-identificacion"></span>
        </div>

        <!-- Sección último evento registrado -->
        <div id="ultimoEventoSection" style="display: none;" class="row mb-4 mx-1">
            <div class="col-12">
                <div class="card border border-info shadow-sm">
                    <div class="card-body p-3">
                        <div class="d-flex align-items-center">
                            <div class="flex-shrink-0">
                                <div class="rounded-circle p-2" style="width: 40px; height: 40px;">
                                    <i class="fas fa-user-clock"></i>
                                </div>
                            </div>
                            <h6 class=" fw-bold">Último evento registrado</h6>
                            <div class="flex-grow-1 ms-3">
                                <div id="ultimoEventoContent">
                                    <div class="d-flex align-items-center text-muted">
                                        <div class="spinner-border spinner-border-sm me-2" role="status">
                                            <span class="visually-hidden">Loading...</span>
                                        </div>
                                        Cargando información...
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <hr class="my-4">
        </div>

        <div class="row mb-4">
            <div class="col-12 col-lg-8">
                <label for="evento" class="form-label ms-3 mx-1">Seleccione el evento a registrar</label>
                <select onchange="habilitarOpciones()" class="form-select" id="evento" name="evento">
                    <option value="0" selected disabled>Seleccione una opción</option>
                    @foreach ($parametrosDescansoConductores as $parametro)
                        <option id="opcion-{{ $parametro->ParNom }}" value="{{ $parametro->ParNom }}">
                            {{ $parametro->ParDes }}
                        </option>
                    @endforeach
                </select>
                <span class="error text-danger fw-bold" id="error-evento"></span>
            </div>
        </div>

        <!-- Opcion solo visible al seleccionar un evento  -->
        <div id="opcionEvento" style="display: none" class="row mb-2 mx-1">
            <label id="opcionEventoLabel" for="fecha" class="form-label">Fecha y hora de reingreso de descanso</label>
            <input 
                type="datetime-local" 
                name="fecha" 
                id="fecha" 
                class="form-control" 
                required
            >
            <span class="error text-danger fw-bold" id="error-fecha"></span>
        </div>

        <div class="row mb-4 mx-1">
            <label for="observacion" class="form-label">Observaciones</label>
            <textarea class="form-control" id="observacion" name="observacion" rows="3" placeholder="Observaciones" required></textarea>
            <span class="error text-danger fw-bold" id="error-observacion"></span>
        </div>

        <div id="opciones" style="display: none">
            <hr class="p-2">
            <button type="submit" class="btn btn-success">Aceptar</button>
            <button type="button" class="btn btn-dark" data-bs-dismiss="modal">Cancelar</button>
        </div>
    </form>
</div>

