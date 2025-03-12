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
                    Swal.fire({
                        icon: response.type,
                        title: response.title,
                        confirmButtonColor: "#3366CC",
                        confirmButtonText: "Aceptar"
                    }).then(() => {
                        window.location.href = response.redirect;
                    });
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

export function actualizarReloj() {
    const now = new Date();
    let horas = now.getHours();
    const minutos = now.getMinutes();
    const segundos = now.getSeconds();

    let ampm = 'a.m.';
    if (horas >= 12) {
        ampm = 'p.m.';
        if (horas > 12) horas -= 12;  
    } else if (horas == 0) {
        horas = 12;  
    }

    let horaLocal = document.getElementById('horas');
    let minutosLocal = document.getElementById('minutos');
    let segundosLocal = document.getElementById('segundos');
    let ampmLocal = document.getElementById('ampm');

    if(horaLocal) horaLocal.innerText = String(horas).padStart(2, '0');	
    if(minutosLocal) minutosLocal.innerText = String(minutos).padStart(2, '0');
    if(segundosLocal) segundosLocal.innerText = String(segundos).padStart(2, '0');
    if(ampmLocal) ampmLocal.innerText = ampm;
}

setInterval(actualizarReloj, 1000);
actualizarReloj();



//Carga un modal con el contenido de una URL y carga funciones requerias
export function cargarModal(url, modalId, formularioId = null) {
$.get(url)
    .done(function(response) {
        let $modal = $(`#${modalId}`);
        let $modalContent = $modal.find("#modalContent");

        if ($modalContent.length) {
            $modalContent.html(response);
            $modal.modal("show");

            if (formularioId) {
                validarFormulario(formularioId);
            }

            if ($modal.find('.select2').length) {
                $('.select2').select2({
                    dropdownParent: $modal,
                    width: '100%'
                });
            }
        } else {
            console.error("No se encontró el contenedor #modalContent en el modal.");
        }
    })
    .fail(function() {
        alert("Error al cargar el contenido.");
    });
}


