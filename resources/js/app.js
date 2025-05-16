//jQuery
import $ from 'jquery';
import 'jquery-validation';
window.$ = window.jQuery = $;

// Select2 jQuery
import select2 from 'select2';
select2();

// Bootstrap;
import 'bootstrap';
import * as bootstrap from 'bootstrap';
window.bootstrap = bootstrap;

// Bootstrap table
import 'bootstrap-table/dist/bootstrap-table.min.js';
import 'bootstrap-table/dist/locale/bootstrap-table-es-ES.min.js';
import 'bootstrap-table/dist/bootstrap-table.min.css';
import 'bootstrap-table/dist/extensions/mobile/bootstrap-table-mobile.min.js';


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

// XLSX (excels)
import * as XLSX from 'xlsx';
window.XLSX = XLSX;

import { deshabilitarSubmit, 
        habilitarSubmit, 
        actualizarReloj,
        handleMenuActive, 
        abrirArchivo,
        exportarExcel
    } from './utils';

window.deshabilitarSubmit = deshabilitarSubmit;
window.habilitarSubmit = habilitarSubmit;
window.abrirArchivo = abrirArchivo;
window.exportarExcel = exportarExcel;

//Inicializar funciones
setInterval(actualizarReloj, 1000);
actualizarReloj();
handleMenuActive();
