import $ from 'jquery';

window.$ = window.jQuery = $; 

import 'bootstrap';
import 'bootstrap-table/dist/bootstrap-table.min.js';
import 'bootstrap-table/dist/bootstrap-table.min.css';

// jQuery Validation
import 'jquery-validation';

import { validarFormulario, getMunicipios } from './utils';
window.validarFormulario = validarFormulario;
window.getMunicipios = getMunicipios;
