export function validarFormulario(form, URL){
    
    //Reglas de validación nombres
    $.validator.addMethod("soloLetras", function (value, element) {
        return /^[A-Za-zÁÉÍÓÚáéíóúÑñ]+(?:\s[A-Za-zÁÉÍÓÚáéíóúÑñ]+)*$/.test(value);
    });

    $(form).validate({
        errorClass: "text-danger fw-bold is-invalid",
        validClass: "is-valid",     
        
        submitHandler: function (form) {
            $.ajax({
                url: URL, 
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
}