export function validarFormulario(form, TYPE="POST") {

    $.extend($.validator.messages, {
        required: "Este campo es obligatorio.",
        email: "Por favor ingrese un email válido.",
        number: "Por favor ingrese un número válido."
    });
    
    $(form).validate({
        errorClass: "text-danger fw-bold is-invalid",
        validClass: "is-valid",

        submitHandler: function (form) {
            let URL = $(form).attr("action");
            let formData = new FormData(form);

            $.ajax({
                url: URL,
                type: TYPE,
                data: formData,
                processData: false,
                contentType: false,
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
}

//Obtiene los municipios por departamento
export function getMunicipios(depSelect, muniSelect, RUTA){
    let departamento_id = $(depSelect).val();
    let municipioSelect = $(muniSelect);

    municipioSelect.empty().append('<option value="">Seleccione un municipio</option>');

    if (departamento_id) {
        $.ajax({
            url: RUTA + departamento_id,
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

export function togglePasswordVisibility() {
    let passwordField = document.getElementById("password");
    if (passwordField.type === "password") {
        passwordField.type = "text";
    } else {
        passwordField.type = "password";
    }
}

export function actualizarReloj() {
    const now = new Date();
    let horas = now.getHours();
    const minutos = now.getMinutes();
    const segundos = now.getSeconds();

    let ampm = 'AM';
    if (horas >= 12) {
        ampm = 'PM';
        if (horas > 12) horas -= 12;  
    } else if (horas == 0) {
        horas = 12;  
    }

    document.getElementById('horas').innerText = String(horas).padStart(2, '0');
    document.getElementById('minutos').innerText = String(minutos).padStart(2, '0');
    document.getElementById('segundos').innerText = String(segundos).padStart(2, '0');
    document.getElementById('ampm').innerText = ampm;
}

setInterval(actualizarReloj, 1000);



