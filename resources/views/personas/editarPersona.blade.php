@extends('layouts.dashboard')

@section('title','Editar persona')

@section('breadcrumb')
<x-breadcrumb :items="[
        ['name' => 'Home', 'url' => route('home')],
        ['name' => 'Personas','url' => route('personas.index')],
        ['name' => 'Editar persona'],
    ]" />
<br>
@endsection

@section('content')
<div class="container-fluid p-0 border shadow bg-white rounded">
    <div class="border rounded-top d-flex justify-content-between align-items-center px-4 bg-secondary">
        <span class="text-left text-light fs-5 fw-medium">Datos persona</span>
    </div>
    <form id="formEditPersona" action="{{  route('personas.update', ['id' => $persona->IdPersona])  }}" method="POST">
        @csrf
        @method('PUT')
        <div>
            <div class="row pt-4 px-4">
                <div class="col-md-4 mb-3">
                    <label for="PerTipoDoc" class="form-label">Tipo de Documento</label>
                    <select class="form-select" id="PerTipoDoc" name="PerTipoDoc" disabled required>
                        @foreach($tiposDocumento as $tiposDocumento)
                            <option value="{{ $tiposDocumento->id }}"  @selected($persona->PerTipoDoc == $tiposDocumento->id)>{{ $tiposDocumento->nombre }}</option>
                        @endforeach
                    </select>
                    <span class="error text-danger fw-bold" id="error-PerTipoDoc"></span>
                </div>
                <div class="col-md-4 mb-3">
                    <label for="PerNumDoc" class="form-label">Número de Documento</label>
                    <input type="number" class="form-control" id="PerNumDoc" name="PerNumDoc" value="{{ $persona->PerNumDoc }}" disabled required>
                    <span class="error text-danger fw-bold" id="error-PerNumDoc"></span>
                </div>
                <div class="col-md-4 mb-3">
                    <label for="PerApellidos" class="form-label">Apellidos</label>
                    <input type="text" class="form-control" id="PerApellidos" name="PerApellidos" value="{{ $persona->PerApellidos }}" required>
                    <span class="error text-danger fw-bold" id="error-PerApellidos"></span>
                </div>
            </div>

            <div class="row px-4">
                <div class="col-md-4 mb-3">
                    <label for="PerNombres" class="form-label">Nombres</label>
                    <input type="text" class="form-control" id="PerNombres" name="PerNombres" value="{{ $persona->PerNombres }}" required>
                    <span class="error text-danger fw-bold" id="error-PerNombres"></span>
                </div>
                <div class="col-md-4 mb-3">
                    <label for="PerGenero" class="form-label">Género</label>
                    <select class="form-select" id="PerGenero" name="PerGenero" required>
                        <option value="">Seleccione</option>
                        <option value="MASCULINO" @selected($persona->PerGenero == 'MASCULINO')>Masculino</option>    
                        <option value="FEMENINO" @selected($persona->PerGenero == 'FEMENINO')>Femenino</option>
                        <option value="OTRO" @selected($persona->PerGenero == 'OTRO')>Otro</option>
                    </select>
                    <span class="error text-danger fw-bold" id="error-PerGenero"></span>
                </div>
                <div class="col-md-4 mb-3">
                    <label for="PerLugNac" class="form-label">Lugar nacimiento</label>
                    <select class="form-select select2" id="PerLugNac" name="PerLugNac" required>
                        <option value="">Seleccione un lugar</option>
                        @foreach($departamentos as $departamento)
                            <optgroup label="{{ $departamento->DepNom }}">
                            @foreach($departamento->municipios as $municipio)
                                <option value="{{ $municipio->IdMunicipio }}" @selected($persona->PerLugNac == $municipio->IdMunicipio)> {{ $municipio->MunNomMin }}
                            @endforeach
                            </optgroup>
                        @endforeach
                    </select>
                    <span class="error text-danger fw-bold" id="error-PerLugNac"></span>
                </div>
            </div>

            <div class="row px-4">
                <div class="col-md-4 mb-3">
                    <label for="PerFecNac" class="form-label">Fecha de Nacimiento</label>
                    <input type="date" class="form-control" id="PerFecNac" name="PerFecNac" value="{{ $persona->PerFecNac }}" required>
                    <span class="error text-danger fw-bold" id="error-PerFecNac"></span>
                </div>

                 <div class="col-md-4 mb-3">
                    <label for="PerLugExp" class="form-label">Lugar expedición documento</label>
                    <select class="form-select select2" id="PerLugExp" name="PerLugExp" required>
                        <option value="">Seleccione un lugar</option>
                            @foreach($departamentos as $departamento)
                                <optgroup label="{{ $departamento->DepNom }}">
                                @foreach($departamento->municipios as $municipio)
                                    <option value="{{ $municipio->IdMunicipio }}" @selected($persona->PerLugExp == $municipio->IdMunicipio)> {{ $municipio->MunNomMin }}
                                @endforeach
                                </optgroup>
                            @endforeach
                        </select>
                    <span class="error text-danger fw-bold" id="error-PerLugExp"></span>
                </div>
                <div class="col-md-4 mb-3">
                    <label for="PerGruRh" class="form-label">Grupo Sanguíneo</label>
                    <input type="text" class="form-control" id="PerGruRh" name="PerGruRh" value="{{ $persona->PerGruRh }}">
                    <span class="error text-danger fw-bold" id="error-PerGruRh"></span>
                </div>
            </div>

            <div class="row px-4 pb-4">
                <div class="col-md-4 mb-3">
                    <label for="PerFecExp" class="form-label">Fecha de Expedición</label>
                    <input type="date" class="form-control" id="PerFecExp" name="PerFecExp" value="{{ $persona->PerFecExp }}" required>
                    <span class="error text-danger fw-bold" id="error-PerFecExp"></span>
                </div>

                <div class="col-md-4 mb-4">
                    <label for="PerEstado" class="form-label">Estado</label>
                    <select class="form-select" id="PerEstado" name="PerEstado" required>
                        <option value="Activo" @selected($persona->PerEstado == 'ACTIVO')>Activo</option> 
                        <option value="Inactivo" @selected($persona->PerEstado == 'INACTIVO')>Inactivo</option>
                    </select>
                    <span class="error text-danger fw-bold" id="error-PerEstado"></span>
                </div>
            </div>

            <div class="border rounded-top d-flex justify-content-between align-items-center px-4 bg-secondary">
                <span class="text-left text-light fs-5 fw-medium ">Información adicional</span>
            </div>
            <div class="row px-4 pt-4">
                <div class="col-md-4 mb-3">
                    <label for="PerTelefono" class="form-label">Telefono</label>
                    <input type="number" class="form-control" id="PerTelefono" name="PerTelefono" value="{{ $persona->datos?->PerTelefono }}" required>
                    <span class="error text-danger fw-bold" id="error-PerTelefono"></span>
                </div>
                <div class="col-md-4 mb-3">
                    <label for="PerEmail" class="form-label">Correo</label>
                    <input type="email" class="form-control" id="PerEmail" name="PerEmail" value="{{ $persona->datos?->PerEmail }}" required>
                    <span class="error text-danger fw-bold" id="error-PerEmail"></span>
                </div>
                <div class="col-md-4 mb-3">
                    <label for="PerDir" class="form-label">Dirección</label>
                    <input type="text" class="form-control" id="PerDir" name="PerDir" value="{{ $persona->datos?->PerDir }}" required>
                    <span class="error text-danger fw-bold" id="error-PerDir"></span>
                </div>
            </div>

            <div class="row px-4 pb-4">
                <div class="col-md-4 mb-3">
                    <label for="PerBar" class="form-label">Barrio</label>
                    <input type="text" class="form-control" id="PerBar" name="PerBar" value="{{ $persona->datos?->PerBar }}" required>
                    <span class="error text-danger fw-bold" id="error-PerBar"></span>
                </div>
                <div class="col-md-4 mb-3">
                    <label for="PerMunRes" class="form-label">Lugar residencia</label>
                    <select class="form-select select2" id="PerMunRes" name="PerMunRes" required>
                        <option value="" disabled selected>Seleccione un lugar</option>
                            @foreach($departamentos as $departamento)
                                <optgroup label="{{ $departamento->DepNom }}">
                                @foreach($departamento->municipios as $municipio)
                                    <option value="{{ $municipio->IdMunicipio }}" @selected($persona->datos?->PerMunRes == $municipio->IdMunicipio)> {{ $municipio->MunNomMin }}
                                @endforeach
                                </optgroup>
                            @endforeach
                    </select>
                    <span class="error text-danger fw-bold" id="error-PerMunRes"></span>
                </div>
            </div>
            
            <div class="p-4">
                <button type="submit" class="btn btn-success">Guardar</button>
                <a type="button" class="btn btn-dark" href="{{ route('personas.index') }}">Cancelar</a>
            </div>
        </div>
    </form>
</div>
@endsection

@pushOnce('script')
    @vite(['resources/js/cargarModal.js'])
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        $(document).ready(function () {
            validarFormulario("#formEditPersona");
            $('.select2').select2({
                dropdownParent: $("#formEditPersona"),
                width: '100%'
            });
        });
    </script>
@endpushOnce
