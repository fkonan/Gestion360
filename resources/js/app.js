// jQuery
import $ from 'jquery';
import 'jquery-validation';
window.$ = window.jQuery = $;

// Select2 jQuery
import select2 from 'select2';
select2();

// Bootstrap;
import 'bootstrap';

// Bootstrap table
import 'bootstrap-table/dist/bootstrap-table.min.js';
import 'bootstrap-table/dist/locale/bootstrap-table-es-ES.min.js';
import 'bootstrap-table/dist/bootstrap-table.min.css';

// Bootstrap modal
import { Modal } from 'bootstrap';
$.fn.modal = function (...args) {
    return this.each(function () {
        const modal = Modal.getOrCreateInstance(this);
        if (typeof args[0] === 'string') {
            modal[args[0]]();
        }
    });
};

// SweetAlert2
import Swal from 'sweetalert2';
window.Swal = Swal; 

import { validarFormulario, cargarModal, deshabilitarSubmit, actualizarReloj } from './utils';
window.validarFormulario = validarFormulario;
window.cargarModal = cargarModal;
window.deshabilitarSubmit = deshabilitarSubmit;

setInterval(actualizarReloj, 1000);
actualizarReloj();
