//import $ from 'jquery';
//window.$ = window.jQuery = $; 

import 'bootstrap/dist/js/bootstrap.bundle.min.js';
import 'bootstrap-table/dist/bootstrap-table.min.js';
import 'bootstrap-table/dist/bootstrap-table.min.css';

import Swal from 'sweetalert2';
window.Swal = Swal; 


// jQuery Validation
//import 'jquery-validation';

import { validarFormulario, cargarModal } from './utils';
window.validarFormulario = validarFormulario;
window.cargarModal = cargarModal;

