<!--  Modal crear persona -->

<div class="container-fluid p-0 bg-white border rounded">
    <form  id="formPersonaCrear" action="{{ route('personas.store') }}" method="POST">
        @csrf
        <div class="p-4">
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="PerTipoDoc" class="form-label">Tipo de Documento</label>
                    <select class="form-select" id="PerTipoDoc" name="PerTipoDoc" required>
                        @foreach($tiposDocumento as $tiposDocumento)
                            <option value="{{ $tiposDocumento->id }}">{{ $tiposDocumento->nombre }}</option>
                        @endforeach
                    </select>
                    <span class="error text-danger fw-bold" id="error-PerTipoDoc"></span>
                </div>
                <div class="col-md-6 mb-3">
                    <label for="PerNumDoc" class="form-label">Número de Documento</label>
                    <input type="number" class="form-control" id="PerNumDoc" name="PerNumDoc" required>
                    <span class="error text-danger fw-bold" id="error-PerNumDoc"></span>
                </div>
            </div>

            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="PerApellidos" class="form-label">Apellidos</label>
                    <input type="text" class="form-control" id="PerApellidos" name="PerApellidos" required>
                    <span class="error text-danger fw-bold" id="error-PerApellidos"></span>
                </div>
                <div class="col-md-6 mb-3">
                    <label for="PerNombres" class="form-label">Nombres</label>
                    <input type="text" class="form-control" id="PerNombres" name="PerNombres" required>
                    <span class="error text-danger fw-bold" id="error-PerNombres"></span>
                </div>
            </div>

            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="PerGenero" class="form-label">Género</label>
                    <select class="form-select" id="PerGenero" name="PerGenero" required>
                        <option value="">Seleccione</option>
                        <option value="MASCULINO">Masculino</option>    
                        <option value="FEMENINO">Femenino</option>
                        <option value="OTRO">Otro</option>
                    </select>
                    <span class="error text-danger fw-bold" id="error-PerGenero"></span>
                </div>

                <div class="col-md-6 mb-3">
                    <label for="PerLugNac" class="form-label">Lugar nacimiento</label>
                    <select class="form-select select2" id="PerLugNac" name="PerLugNac" required>
                        <option value="">Seleccione un lugar</option>
                        @foreach($departamentos as $departamento)
                            <optgroup label="{{ $departamento->DepNom }}">
                            @foreach($departamento->municipios as $municipio)
                                <option value="{{ $municipio->IdMunicipio }}"> {{ $municipio->MunNomMin }}
                            @endforeach
                            </optgroup>
                        @endforeach
                    </select>
                    <span class="error text-danger fw-bold" id="error-PerLugNac"></span>
                </div>
            </div>

            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="PerFecNac" class="form-label">Fecha de Nacimiento</label>
                    <input type="date" class="form-control" id="PerFecNac" name="PerFecNac" required>
                    <span class="error text-danger fw-bold" id="error-PerFecNac"></span>
                </div>

                <div class="col-md-6 mb-3">
                    <label for="PerLugExp" class="form-label">Lugar expedición documento</label>
                    <select class="form-select select2" id="PerLugExp" name="PerLugExp" required>
                        <option value="">Seleccione un lugar</option>
                            @foreach($departamentos as $departamento)
                                <optgroup label="{{ $departamento->DepNom }}">
                                @foreach($departamento->municipios as $municipio)
                                    <option value="{{ $municipio->IdMunicipio }}"> {{ $municipio->MunNomMin }}
                                @endforeach
                                </optgroup>
                            @endforeach
                        </select>
                    <span class="error text-danger fw-bold" id="error-PerLugExp"></span>
                </div>
            </div>

            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="PerFecExp" class="form-label">Fecha de Expedición</label>
                    <input type="date" class="form-control" id="PerFecExp" name="PerFecExp" required>
                    <span class="error text-danger fw-bold" id="error-PerFecExp"></span>
                </div>
                
                <div class="col-md-6 mb-3">
                    <label for="PerGruRh" class="form-label">Grupo Sanguíneo</label>
                    <input type="text" class="form-control" id="PerGruRh" name="PerGruRh">
                    <span class="error text-danger fw-bold" id="error-PerGruRh"></span>
                </div> 
            </div>

            <button type="submit" class="btn btn-primary">Enviar</button>
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
        </div>
    </form>
</div>
