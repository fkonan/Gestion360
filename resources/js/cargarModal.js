// Esta función puede ser importada en cualquier parte del proyecto y sirve
// para mostrar un modal con el contenido de una URL específica y carga funciones adicionales requerias.
//
// Parámetros:
// - url: La URL de la página que se quiere mostrar en el modal.
// - titulo: (Opcional) El título del modal. Por defecto, se muestra como "Contenido".
// - formularioId: (Opcional) El ID del formulario para activar las validaciones con AJAX.
// - size: (Opcional) El tamaño del modal. Puede ser "modal-xl", "modal-lg", "modal-sm" o ninguno (para tamaño normal).

import { error } from "jquery";
import { mostrarToast, habilitarSubmit } from "./utils";

function cargarModal(url, titulo = "", formularioId = null, size = null, type = "POST") {
    // Validar elementos DOM requeridos
    const $modal = $("#globalModal");
    const $modalContent = $("#globalModalContent");
    const $modalTitle = $("#globalModalTitle");
    const $modalDialog = $modal.find(".modal-dialog");

    if (!$modal.length || !$modalContent.length || !$modalTitle.length) {
        console.error("No se encontraron los elementos del modal requeridos en el DOM.");
        mostrarToast("Error: Modal no disponible", "danger");
        return;
    }

    // Validar URL básica
    if (!url || typeof url !== 'string') {
        console.error("URL no válida proporcionada a cargarModal");
        mostrarToast("Error: URL no válida", "danger");
        return;
    }

    // Mostrar un loader mientras se carga el contenido
    $modalContent.html(`
        <div class="d-flex flex-column align-items-center justify-content-center p-4">
            <i class="fas fa-spinner fa-spin fa-2x mb-2 text-primary"></i>
            <p class="fw-bold text-primary">Cargando, por favor espera...</p>
        </div>
    `);
    $modal.modal("show");

    // Asignar tamaño del modal
    if (size) {
        $modalDialog.removeClass("modal-xl modal-lg modal-sm").addClass(size);
    }

    $modalTitle.text(titulo);

    $.get(url)
        .done((response) => {
            if (response && typeof response === "object" && response.error) {
                const message = response.message || "Error al cargar el contenido";
                $modalContent.html(`
                <div class="alert alert-danger m-3">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    <strong>Error:</strong> ${message}
                </div>
                `);

                mostrarToast(message, "danger");
                return;
            }

            // Agregar contenido y guardar URL cargada
            $modalContent.html(response);
            $modalContent.data("loaded", url);

            // Inicializar componentes del modal
            inicializarComponentesModal($modal, formularioId, type);
        })
        .fail((xhr, textStatus, errorThrown) => {
            console.error("Error al cargar el contenido:", {
                status: xhr.status,
                textStatus,
                errorThrown,
                url
            });

            const errorMsg = xhr.status === 404
                ? "Página no encontrada"
                : xhr.status === 500
                    ? "Error interno del servidor"
                    : "Error al cargar el contenido";

            $modalContent.html(`
                <div class="alert alert-danger m-3">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    <strong>Error:</strong> ${errorMsg}
                </div>
            `);

            mostrarToast(errorMsg, "danger");
        });

    // Resetear el estado del modal cuando se cierra (solo una vez)
    $modal.off('hidden.bs.modal.cargarModal').on('hidden.bs.modal.cargarModal', function () {
        $modalContent.html('');
        $modalContent.removeData("loaded");
        $modalDialog.removeClass("modal-xl modal-lg modal-sm"); // Resetear tamaño
    });
}

function inicializarComponentesModal($modal, formularioId, type) {
    // Inicializar select2 solo si aún no está activado
    $modal.find('.select2').each(function () {
        const $element = $(this);
        if ($element.data('select2') === undefined) {
            $element.select2({
                theme: 'bootstrap-5',
                dropdownParent: $modal,
                width: '100%'
            });
        }
    });

    // Validar formulario si se proporciona
    if (formularioId) {
        validarFormulario(formularioId, type);
    }

    // Inicializar listas duales
    inicializarListasDuales($modal);
}

function inicializarListasDuales($modal) {
    const listasConfig = [
        { selector: '#permissions', nombre: 'Permisos' },
        { selector: '#roles', nombre: 'Roles' },
        { selector: '#permisosRol', nombre: 'Permisos' }
    ];

    listasConfig.forEach(config => {
        if ($modal.find(config.selector).length) {
            bootstrapDualListInit(config.selector, config.nombre);
        }
    });
}

function bootstrapDualListInit(id, nombre) {
    $(id).bootstrapDualListbox({
        preserveSelectionOnMove: 'moved',
        infoText: 'Mostrando {0} ' + nombre,
        infoTextEmpty: 'No hay ' + nombre + ' disponibles',
        infoTextFiltered: '<span class="badge bg-warning">Filtrados</span> {0} de {1}',
        moveAllLabel: 'Mover todos',
        removeAllLabel: 'Quitar todos',
        selectorMinimalHeight: 320
    });

    $('.box1').attr('data-title', 'Todos los ' + nombre + ' disponibles');
    $('.box2').attr('data-title', nombre + ' asignados');
    $('.moveall').text('Agregar todos »');
    $('.removeall').text('« Quitar todos');
}

function validarFormulario(form, TYPE = "POST") {
    $.extend($.validator.messages, {
        required: "Este campo es obligatorio.",
        email: "Por favor ingrese un email válido.",
        number: "Por favor ingrese un número válido."
    });

    $(form).validate({
        errorClass: "text-danger fw-bold is-invalid",

        submitHandler: function (form) {
            const $form = $(form);
            const URL = $form.attr("action");

            if (!URL) {
                habilitarSubmit(form);
                mostrarToast("Error: No se encontró la URL del formulario", "danger");
                return;
            }

            const formData = new FormData(form);

            $.ajax({
                url: URL,
                type: TYPE,
                data: formData,
                processData: false,
                contentType: false,
                dataType: "json",
                success: function (response) {
                    $("#innerHtml").html('');

                    // Caso 1: Se retorne un html -> se carga el html en la misma vista
                    if (response.success && response.html) {
                        $("#innerHtml").html(response.html);

                    // Caso 2: No se retorne un redirect -> se queda en la misma vista y se muestra un toast con los datos
                    } else if (response.redirect == '#') {
                        mostrarToast(response.title, response.type);

                    // Caso 3: Se retorna un redirect -> se redirige y al recargar se carga un toast con los datos en session
                    } else {
                        sessionStorage.setItem('toastTitle', response.title);
                        sessionStorage.setItem('toastType', response.type);
                        window.location.href = response.redirect;
                    }
                    habilitarSubmit(form);
                },

                error: function (xhr) {
                    $(".error").text("");
                    if (xhr.status === 422) {
                        let errors = xhr.responseJSON?.errors;
                        if (errors) {
                            $.each(errors, function (key, value) {
                                $("#error-" + key).text(value[0]);
                                $("#" + key).addClass("is-invalid");
                            });
                        }
                    } else {
                        mostrarToast("Error al procesar el formulario", "danger");
                    }
                    habilitarSubmit(form);
                }
            });
        }
    });

    // Limpia los errores al editar o agregar un nuevo registro
    $(form).find("input, select").on("input change", function () {
        const fieldId = $(this).attr("id");
        $(this).removeClass("is-invalid");
        if (fieldId) {
            $("#error-" + fieldId).text("");
        }
    });
}

function abrirPdfModal(url, titulo = "Documento PDF", size = "modal-xl") {
    const $modal = $("#globalModal");
    const $modalContent = $("#globalModalContent");
    const $modalTitle = $("#globalModalTitle");
    const $modalDialog = $modal.find(".modal-dialog");

    if (!$modal.length || !$modalContent.length || !$modalTitle.length) {
        console.error("No se encontraron los elementos del modal requeridos en el DOM.");
        mostrarToast("Error: Modal no disponible", "danger");
        return;
    }

    if (!url || typeof url !== "string") {
        console.error("URL no valida proporcionada a abrirPdfModal");
        mostrarToast("Error: URL no valida", "danger");
        return;
    }

    const separator = url.includes("#") ? "&" : "#";
    const iframeUrl = `${url}${separator}toolbar=0&navpanes=0&scrollbar=1`;

    if (size) {
        $modalDialog.removeClass("modal-xl modal-lg modal-sm").addClass(size);
    }

    $modalTitle.text(titulo);
    $modalContent.html(`
        <div class="sig-pdf-viewer" oncontextmenu="return false">
            <iframe class="sig-pdf-frame" src="${iframeUrl}" title="${titulo}" loading="lazy" oncontextmenu="return false"></iframe>
        </div>
    `);

    const bloquearTeclas = (event) => {
        const key = String(event.key || "").toLowerCase();
        if ((event.ctrlKey || event.metaKey) && (key === "s" || key === "p")) {
            event.preventDefault();
            event.stopPropagation();
        }
    };

    const bloquearMenu = (event) => {
        event.preventDefault();
    };

    document.addEventListener("keydown", bloquearTeclas, true);
    $modalContent.on("contextmenu.sigpdf", bloquearMenu);

    $modal.modal("show");

    $modal.off("hidden.bs.modal.pdfModal").on("hidden.bs.modal.pdfModal", function () {
        document.removeEventListener("keydown", bloquearTeclas, true);
        $modalContent.off("contextmenu.sigpdf", bloquearMenu);
        $modalDialog.removeClass("modal-xl modal-lg modal-sm");
    });
}

window.cargarModal = cargarModal;
window.validarFormulario = validarFormulario;
window.abrirPdfModal = abrirPdfModal;
