import $ from 'jquery';

window.$ = window.jQuery = $; 

import 'bootstrap';
import 'bootstrap-table/dist/bootstrap-table.min.js';
import 'bootstrap-table/dist/bootstrap-table.min.css';
import 'select2/dist/css/select2.min.css';

// Importa select2      
import select2 from 'select2';
$.fn.select2 = select2;

// jQuery Validation
import 'jquery-validation';

import { validarFormulario, getMunicipios } from './utils';
window.validarFormulario = validarFormulario;
window.getMunicipios = getMunicipios;


$('.mySelect2').select2({
    dropdownParent: $('.myModal')
});