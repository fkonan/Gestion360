@extends('layouts.app')
@section('title', isset($persona) ? 'Actualizar persona' : 'Registro persona')
@section('content')
<div class="container my-5">

    <h2 class="mb-4 text-center">{{ isset($persona) ? 'Actualizar registro' : 'Formulario de registro' }}</h2>

    <form  id="{{ isset($persona) ? '': 'formPersona'}}" action="{{ isset($persona) ? route('persona.update', ['id' => $persona->IdPersona]) : route('persona.store') }}" method="POST">
        @csrf
        @isset($persona)
            @method('PUT')
        @endisset
        <div class="row">
            <div class="col-md-6 mb-3">
                <label for="PerTipoDoc" class="form-label">Tipo de Documento</label>
                <select class="form-select" id="PerTipoDoc" name="PerTipoDoc" {{ isset($persona) ? 'disabled' : '' }} required>
                    @foreach($tiposDocumento as $tiposDocumento)
                        <option value="{{ $tiposDocumento->id }}"  @selected(isset($persona) && $persona->PerTipoDoc == $tiposDocumento->id)>{{ $tiposDocumento->nombre }}</option>
                    @endforeach
                </select>
                <span class="error text-danger fw-bold" id="error-PerTipoDoc"></span>
            </div>
            <div class="col-md-6 mb-3">
                <label for="PerNumDoc" class="form-label">Número de Documento</label>
                <input type="number" class="form-control" id="PerNumDoc" name="PerNumDoc" value="{{isset($persona) ? $persona->PerNumDoc: '' }}" {{ isset($persona) ? 'disabled' : '' }} required>
                <span class="error text-danger fw-bold" id="error-PerNumDoc"></span>
            </div>
        </div>

        <div class="row">
            <div class="col-md-6 mb-3">
                <label for="PerApellidos" class="form-label">Apellidos</label>
                <input type="text" class="form-control" id="PerApellidos" name="PerApellidos" value="{{isset($persona) ? $persona->PerApellidos: ''}}" required>
                <span class="error text-danger fw-bold" id="error-PerApellidos"></span>
            </div>
            <div class="col-md-6 mb-3">
                <label for="PerNombres" class="form-label">Nombres</label>
                <input type="text" class="form-control" id="PerNombres" name="PerNombres" value="{{isset($persona) ? $persona->PerNombres: ''}}" required>
                <span class="error text-danger fw-bold" id="error-PerNombres"></span>
            </div>
        </div>

        <div class="row">
            <div class="col-md-6 mb-3">
                <label for="PerGenero" class="form-label">Género</label>
                <select class="form-select" id="PerGenero" name="PerGenero" required>
                    <option value="">Seleccione</option>
                    <option value="MASCULINO" @selected(isset($persona) && $persona->PerGenero == 'MASCULINO')>Masculino</option>    
                    <option value="FEMENINO" @selected(isset($persona) && $persona->PerGenero == 'FEMENINO')>Femenino</option>
                    <option value="OTRO" @selected(isset($persona) && $persona->PerGenero == 'OTRO')>Otro</option>
                </select>
                <span class="error text-danger fw-bold" id="error-PerGenero"></span>
            </div>
            <div class="col-md-6 mb-3">
                <label for="PerFecNac" class="form-label">Fecha de Nacimiento</label>
                <input type="date" class="form-control" id="PerFecNac" name="PerFecNac" value="{{isset($persona) ? $persona->PerFecNac:''}}" required>
                <span class="error text-danger fw-bold" id="error-PerFecNac"></span>
            </div>
        </div>

        <div class="row">
            <div class="col-md-6 mb-3">
                <label for="PerDepNac" class="form-label">Departamento nacimiento</label>
                <select class="form-select" id="PerDepNac" name="PerDepNac" onchange="getMunicipios(this,`#PerLugNac`)" required>
                    <option value="">Seleccione un departamento</option>
                    @foreach($departamentos as $departamento)
                        <option value="{{ $departamento->IdDepartamento }}" @selected(isset($persona) && 
                            $persona->municipioNac->departamento->IdDepartamento === $departamento->IdDepartamento)>{{ $departamento->DepNomMin }}
                        </option>
                    @endforeach
                </select>
                <span class="error text-danger fw-bold" id="error-PerDepNac"></span>
            </div>
            <div class="col-md-6 mb-3">
                <label for="PerLugNac" class="form-label">Municipio nacimiento</label>
                <select class="form-select" id="PerLugNac" name="PerLugNac" required>
                    <option value="">Seleccione un municipio</option>
                    @isset($persona)
                        <option value="{{ $persona->municipioNac->IdMunicipio }}" selected>{{ $persona->municipioNac->MunNomMin }}</option>
                    @endisset
                </select>
                <span class="error text-danger fw-bold" id="error-PerLugNac"></span>
            </div>
        </div>

        <div class="row">
            <div class="col-md-6 mb-3">
                <label for="PerGruRh" class="form-label">Grupo Sanguíneo</label>
                <input type="text" class="form-control" id="PerGruRh" name="PerGruRh" value="{{ isset($persona) ? $persona->PerGruRh : ''}}">
                <span class="error text-danger fw-bold" id="error-PerGruRh"></span>
            </div>
            <div class="col-md-6 mb-3">
                <label for="PerFecExp" class="form-label">Fecha de Expedición</label>
                <input type="date" class="form-control" id="PerFecExp" name="PerFecExp" value="{{ isset($persona) ? $persona->PerFecExp: ''}}" required>
                <span class="error text-danger fw-bold" id="error-PerFecExp"></span>
            </div>
        </div>
        <div class="row">
            <div class="col-md-6 mb-3">
                <label for="PerDepExp" class="form-label">Departamento expedición documento</label>
                <select class="form-select" id="PerDepExp" name="PerDepExp" onchange="getMunicipios(this,`#PerLugExp`)" required>
                    <option value="">Seleccione un departamento</option>
                    @foreach($departamentos as $departamento)
                        <option value="{{ $departamento->IdDepartamento }}" @selected(isset($persona) && 
                            $persona->minicipioExp->departamento->IdDepartamento === $departamento->IdDepartamento)>{{ $departamento->DepNomMin }}
                        </option>
                    @endforeach
                </select>
                <span class="error text-danger fw-bold" id="error-PerDepExp"></span>
            </div>
            <div class="col-md-6 mb-3">
                <label for="PerLugExp" class="form-label">Municipio de expedición documento</label>
                <select class="form-select" id="PerLugExp" name="PerLugExp" required>
                    <option value="">Seleccione un municipio</option>
                    @isset($persona)
                        <option value="{{ $persona->minicipioExp->IdMunicipio }}" selected>{{ $persona->minicipioExp->MunNomMin }}</option>
                    @endisset
                </select>
                <span class="error text-danger fw-bold" id="error-PerLugExp"></span>
            </div>
        </div>

        <div class="row">
            <div class="col-md-4 mb-2">
                <label for="PerFechReg" class="form-label">Fecha de Registro</label>
                <input type="date" class="form-control" id="PerFechReg" name="PerFechReg" value="{{ isset($persona) ? $persona->PerFechReg : ''}}" required>
                <span class="error text-danger fw-bold" id="error-PerFechReg"></span>
            </div>
            <div class="col-md-4 mb-2">
                <label for="PerHorReg" class="form-label">Hora de Registro</label>
                <input type="time" class="form-control" id="PerHorReg" name="PerHorReg" value="{{ isset($persona) ? $persona->PerHorReg : '' }}" required>
                <span class="error text-danger fw-bold" id="error-PerHorReg"></span>
            </div>
            <div class="col-md-4 mb-2">
                <label for="PerEstado" class="form-label">Estado</label>
                <select class="form-select" id="PerEstado" name="PerEstado" required>
                    <option value="Activo" @selected(isset($persona) && $persona->PerEstado == 'ACTIVO')>Activo</option> 
                    <option value="Inactivo" @selected(isset($persona) && $persona->PerEstado == 'INACTIVO')>Inactivo</option>
                </select>
                <span class="error text-danger fw-bold" id="error-PerEstado"></span>
            </div>
        </div>
        
        <button type="submit" class="btn btn-primary">{{ isset($persona) ? 'Actualizar' : 'Enviar'}}</button>
    </form>
</div>
@endsection

@section('script')
<script>
validateForm();

$(document).ready(function () {
    //Reglas de validación nombres
    $.validator.addMethod("soloLetras", function (value, element) {
        return /^[A-Za-zÁÉÍÓÚáéíóúÑñ]+(?:\s[A-Za-zÁÉÍÓÚáéíóúÑñ]+)*$/.test(value);
    });

    $("#formPersona").validate({
        rules: {
            PerApellidos: { required:true, maxlength: 50, soloLetras:true },
            PerNombres: { required:true, maxlength: 50, soloLetras:true },  
           
        },
        messages: {
            PerTipoDoc: "Este campo es obligatorio",
            PerNumDoc: "Este campo es obligatorio",
            PerApellidos: {
                required: "Este campo es obligatorio",
                maxlength: "El máximo de caracteres es 50",
                soloLetras: "Solo letras y un espacio entre palabras"
            },
            PerNombres: {
                required: "Este campo es obligatorio",
                maxlength: "El máximo de caracteres es 50",
                soloLetras: "Solo letras y un espacio entre palabras"
            },
            PerGenero: "Este campo es obligatorio",
            PerDepNac: "Este campo es obligatorio",
            PerFecNac: "Este campo es obligatorio",
            PerLugNac: "Este campo es obligatorio",
            PerFecExp: "Este campo es obligatorio",
            PerDepExp: "Este campo es obligatorio",
            PerLugExp: "Este campo es obligatorio",
            PerFechReg: "Este campo es obligatorio",
            PerHorReg: "Este campo es obligatorio",
            PerEstado: "Este campo es obligatorio"
        },
        errorClass: "text-danger fw-bold is-invalid",
        validClass: "is-valid",     
        
        submitHandler: function (form) {
            $.ajax({
                url: "/personas", 
                type: "POST",
                data: $(form).serialize(),
                dataType: "json",
                success: function (response) {
                    alert(response.message);
                    window.location.href = response.redirect;
                },
                error: function (xhr) {
                    $(".error").text(""); 

                    if (xhr.status === 422) {
                        let errors = xhr.responseJSON.errors;
                        $.each(errors, function (key, value) {
                            $("#error-" + key).text(value[0]); 
                            $("#" + key).addClass("is-invalid");
                        });
                    }
                }
            });
        }
    });

    //Limpia los errores al editar o agregar un nuevo registro
    $("input, select").on("input", function () {
        $(this).removeClass("is-invalid");
        $("#error-" + $(this).attr("id")).text("");
    });
});

 //Carga los municipios según el departamento seleccionado
function getMunicipios(depSelect, muniSelect){
    let departamento_id = $(depSelect).val();
    let municipioSelect = $(muniSelect);

    municipioSelect.empty().append('<option value="">Seleccione un municipio</option>');

    if (departamento_id) {
        $.ajax({
            url: '/departamentos/municipios/' + departamento_id,
            type: 'GET',
            dataType: 'json',
            success: function (data) {               
                $.each(data, function (key, municipio) {
                    municipioSelect.append('<option value="' + municipio.IdMunicipio + '" >' + municipio.MunNomMin + '</option>');
                });
            }
        });
    }
}
</script>
@endsection
