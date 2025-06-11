import { mostrarToast } from "./utils"; 

/**
 * Carga contenido de una URL en un modal global de Bootstrap.
 * @param {string} url - URL del contenido a cargar.
 * @param {string} [titulo=""] - Título del modal.
 * @param {string|null} [formularioId=null] - ID del formulario para validación.
 * @param {string|null} [size=null] - Clases de tamaño de modal (ej: "modal-xl", "modal-lg").
 */
function cargarModal(url, titulo = "", formularioId = null, size = null) {
    const $modal = $("#globalModal");
    const $modalContent = $("#globalModalContent");
    const $modalTitle = $("#globalModalTitle");
    const $modalDialog = $modal.find(".modal-dialog");

    // Muestra un loader mientras se carga el contenido
    $modalContent.html(`
        <div class="d-flex flex-column align-items-center justify-content-center p-4">
            <i class="fas fa-spinner fa-spin fa-2x mb-2 text-primary"></i>
            <p class="fw-bold text-primary">Cargando, por favor espera...</p>
        </div>
    `);
    $modal.modal("show");

    // Asigna el tamaño del modal si se especifica
    if (size) {
        $modalDialog.removeClass("modal-xl modal-lg modal-sm").addClass(size);
    }

    $modalTitle.text(titulo);

    $.get(url)
        .done((response) => {
            if (!$modalContent.length) {
                console.error("El contenedor #globalModalContent no fue encontrado.");
                return;
            }

            $modalContent.html(response).data("loaded", url);

            // Inicializa Select2 si hay elementos y no está ya inicializado
            if ($('.select2').length && $('.select2').data('select2') === undefined) {
                $('.select2').select2({
                    dropdownParent: $modal,
                    width: '100%'
                });
            }

            // Valida el formulario si se proporciona un ID
            if (formularioId) {
                validarFormulario(`#${formularioId}`);
            }

            // Inicializa listas duales de Bootstrap si los elementos existen
            const dualListSelectors = ['#permissions', '#roles', '#permisosRol'];
            dualListSelectors.forEach(selector => {
                if ($modal.find(selector).length) {
                    // El nombre de la lista se infiere del selector para ser más genérico
                    const listName = selector.replace('#', '').replace(/([A-Z])/g, ' $1').trim();
                    bootstrapDualListInit(selector, listName);
                }
            });
        })
        .fail((jqXHR, textStatus, errorThrown) => {
            console.error("Error al cargar el contenido del modal:", textStatus, errorThrown, jqXHR);
            mostrarToast("Error al cargar el contenido. Inténtalo de nuevo.", "error"); 
            $modal.modal("hide"); 
        });

    // Limpia el contenido y los datos cuando el modal se cierra
    $modal.off('hidden.bs.modal').on('hidden.bs.modal', function () {
        $modalContent.empty().removeData("loaded");
        // Remueve las clases de tamaño para futuras aperturas
        $modalDialog.removeClass("modal-xl modal-lg modal-sm");
    });
}

function bootstrapDualListInit(id, nombre) {
    $(id).bootstrapDualListbox({
        preserveSelectionOnMove: 'moved',
        infoText: `Mostrando {0} ${nombre}`,
        infoTextEmpty: `No hay ${nombre} disponibles`,
        infoTextFiltered: `<span class="badge bg-warning">Filtrados</span> {0} de {1}`,
        moveAllLabel: 'Mover todos',
        removeAllLabel: 'Quitar todos',
        selectorMinimalHeight: 320
    });

    $(`.box1[data-id="${$(id).attr('id')}"]`).attr('data-title', `Todos los ${nombre} disponibles`);
    $(`.box2[data-id="${$(id).attr('id')}"]`).attr('data-title', `${nombre} asignados`);
    $('.moveall').text('Agregar todos »');
    $('.removeall').text('« Quitar todos');
}

/**
 * Configura la validación y el manejo de envío de un formulario usando jQuery Validation.
 * @param {string} formSelector - Selector del formulario a validar (ej: "#miFormulario").
 * @param {string} [TYPE="POST"] - Método HTTP para el envío del formulario.
 */
function validarFormulario(formSelector, TYPE = "POST") {
    if (!$.validator.messages.required) {
        $.extend($.validator.messages, {
            required: "Este campo es obligatorio.",
            email: "Por favor ingrese un email válido.",
            number: "Por favor ingrese un número válido."
        });
    }

    const $form = $(formSelector);

    if ($form.data('validator')) {
        $form.data('validator').destroy(); // Destruye la instancia existente si la hay
    }

    $form.validate({
        errorClass: "text-danger fw-bold is-invalid",
        submitHandler: function (formElement) {
            const URL = $form.attr("action");
            const formData = new FormData(formElement);
            // Deshabilita el botón de submit para evitar envíos dobles
            const $submitButton = $(formElement).find('button[type="submit"], input[type="submit"]');
            $submitButton.prop('disabled', true).addClass('loading');

            $.ajax({
                url: URL,
                type: TYPE,
                data: formData,
                processData: false,
                contentType: false,
                dataType: "json",
                success: function (response) {
                    $submitButton.prop('disabled', false).removeClass('loading'); // Habilita el botón
                    if (response.redirect === '#') {
                        mostrarToast(response.title, response.type);
                    } else {
                        sessionStorage.setItem('toastTitle', response.title);
                        sessionStorage.setItem('toastType', response.type);
                    }
                    window.location.href = response.redirect;
                },
                error: function (jqXHR) {
                    $submitButton.prop('disabled', false).removeClass('loading'); // Habilita el botón en caso de error
                    $(".error").text(""); // Limpia mensajes de error previos

                    if (jqXHR.status === 422) { 
                        const errors = jqXHR.responseJSON.errors;
                        $.each(errors, function (key, value) {
                            $(`#error-${key}`).text(value[0]);
                            $(`#${key}`).addClass("is-invalid");
                        });
                    } else {
                        mostrarToast("Error de servidor. Inténtalo de nuevo.", "error");
                    }
                }
            });
        }
    });

    // Limpia errores visuales al cambiar inputs/selects
    $form.find("input, select, textarea").on("input change", function () {
        $(this).removeClass("is-invalid");
        $(`#error-${$(this).attr("id")}`).text("");
    });
}

window.cargarModal = cargarModal;
window.validarFormulario = validarFormulario;