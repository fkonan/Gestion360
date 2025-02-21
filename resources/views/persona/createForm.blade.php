@extends('layouts.app')

@section('title','Registro persona')

@section('content')
<div class="container my-5">
    <h2 class="mb-4">Formulario de Registro</h2>
    <form>
        <div class="mb-3">
            <label for="PerTipoDoc" class="form-label">Tipo de Documento</label>
            <select class="form-select" id="PerTipoDoc" name="PerTipoDoc" required>
            @foreach($tiposDocumento as $tiposDocumento)
                <option value="{{ $tiposDocumento->id }}">{{ $tiposDocumento->nombre }}</option>
            @endforeach
            </select>
        </div>
        <div class="mb-3">
            <label for="PerNumDoc" class="form-label">Número de Documento</label>
            <input type="number" class="form-control" id="PerNumDoc" name="PerNumDoc" required>
        </div>
        <div class="mb-3">
            <label for="PerApellidos" class="form-label">Apellidos</label>
            <input type="text" class="form-control" id="PerApellidos" name="PerApellidos" required>
        </div>
        <div class="mb-3">
            <label for="PerNombres" class="form-label">Nombres</label>
            <input type="text" class="form-control" id="PerNombres" name="PerNombres" required>
        </div>
        <div class="mb-3">
            <label for="PerGenero" class="form-label">Género</label>
            <select class="form-select" id="PerGenero" name="PerGenero" required>
                <option value="">Seleccione</option>
                <option value="Masculino">Masculino</option>
                <option value="Femenino">Femenino</option>
                <option value="Otro">Otro</option>
            </select>
        </div>
        <div class="mb-3">
            <label for="PerFecNac" class="form-label">Fecha de Nacimiento</label>
            <input type="date" class="form-control" id="PerFecNac" name="PerFecNac" required>
        </div>
        <div class="mb-3">
            <label for="PerDepNac" class="form-label">Departamento nacimiento</label>
            <select class="form-select" id="PerDepNac" name="PerDepNac" required>
            @foreach( $departamentos as $departamento)
                <option value="{{ $departamento->IdDepartamento }}">{{ $departamento->DepNomMin }}</option>
            @endforeach
            </select>
        </div>
        <div class="mb-3">
            <label for="PerLugNac" class="form-label">Municipio nacimiento</label>
            <select class="form-select" id="PerLugNac" name="PerLugNac" required>
                <option value="">Seleccione un municipio</option>
            </select>
        </div>
        <div class="mb-3">
            <label for="PerFecExp" class="form-label">Fecha de Expedición</label>
            <input type="date" class="form-control" id="PerFecExp" name="PerFecExp" required>
        </div>
        <div class="mb-3">
            <label for="PerLugExp" class="form-label">Lugar de Expedición</label>
            <input type="text" class="form-control" id="PerLugExp" name="PerLugExp" required>
        </div>
        <div class="mb-3">
            <label for="PerGruRh" class="form-label">Grupo Sanguíneo</label>
            <input type="text" class="form-control" id="PerGruRh" name="PerGruRh" required>
        </div>
        <div class="mb-3">
            <label for="PerFechReg" class="form-label">Fecha de Registro</label>
            <input type="date" class="form-control" id="PerFechReg" name="PerFechReg" required>
        </div>
        <div class="mb-3">
            <label for="PerHorReg" class="form-label">Hora de Registro</label>
            <input type="time" class="form-control" id="PerHorReg" name="PerHorReg" required>
        </div>
        <div class="mb-3">
            <label for="PerEstado" class="form-label">Estado</label>
            <select class="form-select" id="PerEstado" name="PerEstado" required>
                <option value="Activo">Activo</option>
                <option value="Inactivo">Inactivo</option>
            </select>
        </div>
        <button type="submit" class="btn btn-primary">Enviar</button>
    </form>
</div>
@endsection

@section('script')
<script>
    $(document).ready(function () {
        $('#PerDepNac').on('change', function () {
            let departamento_id = $(this).val();
            let municipioSelect = $('#PerLugNac');

            municipioSelect.empty().append('<option value="">Seleccione un municipio</option>');

            if (departamento_id) {
                $.ajax({
                    url: '/departamentos/municipios/' + departamento_id,
                    type: 'GET',
                    dataType: 'json',
                    success: function (data) {
                        $.each(data, function (key, municipio) {
                            municipioSelect.append('<option value="' + municipio.IdMunicipio + '">' + municipio.MunNomMin + '</option>');
                        });
                    }
                });
            }
        });
    });
</script>
@endsection
