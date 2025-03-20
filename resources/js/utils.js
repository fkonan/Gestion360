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
export function cargarModal(url, titulo = "", formularioId = null, size = null) {
    const $modal = $("#globalModal");
    const $modalContent = $("#globalModalContent");
    const $modalTitle = $("#globalModalTitle");
    const $modalDialog = $modal.find(".modal-dialog");

    // Asignar tamaño del modal
    if(size != null){
        $modalDialog.removeClass("modal-xl");
        $modalDialog.addClass(size);
    }
   
    if ($modalContent.data("loaded") === url) {
        $modal.modal("show");
        return;
    }

    $modalTitle.text(titulo);

    $.get(url)
        .done((response) => {
            if (!$modalContent.length) {
                console.error("No se encontró el contenedor #modalContent en el modal.");
                return;
            }

            // Agregar contenido y guardar URL cargada
            $modalContent.html(response);
            $modalContent.data("loaded", url);
            $modal.modal("show");

            // Inicializar select2 solo si aún no está activado
            if ($('.select2').data('select2') === undefined) {
                $('.select2').select2({
                    dropdownParent: $modal,
                    width: '100%'
                });
            }

            // Validar formulario si se proporciona
            if (formularioId) {
                validarFormulario(formularioId);
            }

            // Inicializar listas duales solo si existen en el DOM
            if ($modal.find('#permissions').length){
                bootstrapDualListInit('#permissions','Permisos');
            }
            if ($modal.find('#roles').length){
                bootstrapDualListInit('#roles','Roles');
            }
        })
        .fail((textStatus, errorThrown) => {
            console.error("Error al cargar el contenido:", textStatus, errorThrown);
            alert("Error al cargar el contenido.");
        });
}


function bootstrapDualListInit(id, nombre){
    $(id).bootstrapDualListbox({
        nonSelectedListLabel: nombre + ' disponibles',
        selectedListLabel: nombre + ' del usuario',
        preserveSelectionOnMove: 'moved', 
        moveAllLabel: 'Mover todos', 
        removeAllLabel: 'Quitar todos',
        infoText: 'Mostrando {0} ' + nombre,
        infoTextEmpty: 'No hay ' + nombre + ' disponibles',
        infoTextFiltered: '<span class="badge bg-warning">Filtrados</span> {0} de {1}'
    });

    $('.moveall').text('» Agregar todos');
    $('.removeall').text('« Quitar todos');    
}

//Evitar dobles click en forms
export function deshabilitarSubmit(form) {
    const submitButton = form.querySelector('button[type="submit"], input[type="submit"]');
    submitButton.disabled = true;
}