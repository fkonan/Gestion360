//import $ from 'jquery';

//window.$ = window.jQuery = $; 

import 'bootstrap';
import 'bootstrap-table/dist/bootstrap-table.min.js';
import 'bootstrap-table/dist/bootstrap-table.min.css';

// jQuery Validation
//import 'jquery-validation';

import { validarFormulario, getMunicipios, cargarModal } from './utils';
window.validarFormulario = validarFormulario;
window.getMunicipios = getMunicipios;
window.cargarModal = cargarModal;
