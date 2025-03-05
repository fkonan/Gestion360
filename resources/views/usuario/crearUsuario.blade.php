@extends('layouts.dashboard')
@section('title', 'Registro usuario')
@section('content')

<div class="container-fluid p-0 bg-white border rounded">

    <div class="border rounded-top d-flex justify-content-between align-items-center" style="background-color: #2C3643">
        <h5 class="text-left text-light p-2 ps-4">Crear Usuario</h5>
        <a class="btn btn-warning fw-bold m-2" onclick="window.history.back()" >Volver</a>
    </div>

    <form  id="formPersona" action=" route('persona.store') " method="POST">
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
                    <label for="PerFecNac" class="form-label">Fecha de Nacimiento</label>
                    <input type="date" class="form-control" id="PerFecNac" name="PerFecNac" required>
                    <span class="error text-danger fw-bold" id="error-PerFecNac"></span>
                </div>
            </div>

            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="PerDepNac" class="form-label">Departamento nacimiento</label>
                    <select class="form-select" id="PerDepNac" name="PerDepNac" onchange="getMunicipios(this,`#PerLugNac`,'/departamentos/municipios/')" required>
                        <option value="">Seleccione un departamento</option>
                        @foreach($departamentos as $departamento)
                            <option value="{{ $departamento->IdDepartamento }}"> {{ $departamento->DepNomMin }}
                            </option>
                        @endforeach
                    </select>
                    <span class="error text-danger fw-bold" id="error-PerDepNac"></span>
                </div>
                <div class="col-md-6 mb-3">
                    <label for="PerLugNac" class="form-label">Municipio nacimiento</label>
                    <select class="form-select" id="PerLugNac" name="PerLugNac" required>
                        <option value="">Seleccione un municipio</option>
                    </select>
                    <span class="error text-danger fw-bold" id="error-PerLugNac"></span>
                </div>
            </div>

            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="PerGruRh" class="form-label">Grupo Sanguíneo</label>
                    <input type="text" class="form-control" id="PerGruRh" name="PerGruRh">
                    <span class="error text-danger fw-bold" id="error-PerGruRh"></span>
                </div>
                <div class="col-md-6 mb-3">
                    <label for="PerFecExp" class="form-label">Fecha de Expedición</label>
                    <input type="date" class="form-control" id="PerFecExp" name="PerFecExp" required>
                    <span class="error text-danger fw-bold" id="error-PerFecExp"></span>
                </div>
            </div>
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="PerDepExp" class="form-label">Departamento expedición documento</label>
                    <select class="form-select" id="PerDepExp" name="PerDepExp" onchange="getMunicipios(this,`#PerLugExp`,'/departamentos/municipios/')" required>
                        <option value="">Seleccione un departamento</option>
                        @foreach($departamentos as $departamento)
                            <option value="{{ $departamento->IdDepartamento }}">{{ $departamento->DepNomMin }}
                            </option>
                        @endforeach
                    </select>
                    <span class="error text-danger fw-bold" id="error-PerDepExp"></span>
                </div>
                <div class="col-md-6 mb-3">
                    <label for="PerLugExp" class="form-label">Municipio de expedición documento</label>
                    <select class="form-select" id="PerLugExp" name="PerLugExp" required>
                        <option value="">Seleccione un municipio</option>
                    </select>
                    <span class="error text-danger fw-bold" id="error-PerLugExp"></span>
                </div>
            </div>
    
            <button type="submit" class="btn btn-primary">Enviar</button>
        </div>
    </form>
</div>
@endsection