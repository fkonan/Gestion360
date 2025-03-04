@extends('layouts.dashboard')
@section('title', 'Registro persona')
@section('content')

<div class="container-fluid p-4">
    <h2 class="mb-4 text-center">{{ 'Formulario de registro' }}</h2>
    <form  id="formPersona" action=" route('persona.store') " method="POST">
        @csrf
        @isset($persona)
            @method('PUT')
        @endisset
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
    </form>
</div>
@endsection

@section('script')
<script>
$(document).ready(function () {

    //Reglas de validación nombres
    $.validator.addMethod("soloLetras", function (value, element) {
        return /^[A-Za-zÁÉÍÓÚáéíóúÑñ]+(?:\s[A-Za-zÁÉÍÓÚáéíóúÑñ]+)*$/.test(value);
    });

    $.extend($.validator.messages, {
        required: "Este campo es obligatorio.",
    });

    $("#formPersona").validate({
        rules: {
            PerApellidos: { required:true, maxlength: 50, soloLetras:true },
            PerNombres: { required:true, maxlength: 50, soloLetras:true },  
           
        },
        messages: {
            PerApellidos: {
                maxlength: "El máximo de caracteres es 50",
                soloLetras: "Solo letras y un espacio entre palabras"
            },
            PerNombres: {
                maxlength: "El máximo de caracteres es 50",
                soloLetras: "Solo letras y un espacio entre palabras"
            },
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
</script>
@endsection
