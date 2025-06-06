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


function mostrarLoader() {
    const loader = document.getElementById('fullscreen-loader');
    if (loader) loader.style.display = 'flex';
}

function ocultarLoader() {
    const loader = document.getElementById('fullscreen-loader');
    if (loader) loader.style.display = 'none';
}

//Evitar dobles click en forms y da un feedback de carga 
export function deshabilitarSubmit(form , validity=false) {
    const submitButton = form.querySelector('button[type="submit"], input[type="submit"]');
    if (form.checkValidity() || validity) {
        submitButton.disabled = true;
        mostrarLoader();
    }
}

//vuelve a habilitar en caso de un error 
export function habilitarSubmit(form) {
    const submitButton = form.querySelector('button[type="submit"], input[type="submit"]');
    submitButton.disabled = false;
    ocultarLoader();
}

//Esta funcion lleva control de cuando un elemento de menu debe ser active segun las rutas
export function handleMenuActive() {
    var url = window.location.href.split('#')[0];
   
    $('ul.nav-sidebar a').filter(function() {
        let href = this.href;
        return href !== window.location.origin + "/" && url.startsWith(href);
    }).addClass('active');

    $('ul.nav-treeview a').filter(function() {
        let href = this.href;
        return href !== window.location.origin + "/" && url.startsWith(href);
    }).parentsUntil(".nav-sidebar > .nav-treeview").addClass('menu-open').prev('a').addClass('active');
}

//Abrir archivo adjunto
export function abrirArchivo(url) {
    const overlay = document.createElement('div');
    overlay.style = `
        position: fixed; top: 0; left: 0; width: 100%; height: 100%;
        background: rgba(0, 0, 0, 0.8); z-index: 9999; display: flex;
        justify-content: center; align-items: center; overflow: hidden;
    `;

    let content = '';
    if (url.match(/\.(jpeg|jpg|png|gif)$/i)) {
        //Si es una imagen
        content = `<img src="${url}" style="max-width: 80%; max-height: 80%; border: none;" alt="Archivo">`;
    } else {
        //Si es otro tipo de archivo valido
        content = `<iframe src="${url}" style="width: 80%; height: 90%; border: none;"></iframe>`;
    }

    overlay.innerHTML = `
        ${content}
        <div style="position: absolute; top: 10px; right: 10px; display: flex; gap: 10px;">
            <button style="
                padding: 10px 20px; background: #ff0000; color: #fff; border: none;
                cursor: pointer; font-size: 16px; font-weight: bold">Cerrar</button>
        </div>
    `;

    overlay.querySelector('button').addEventListener('click', () => document.body.removeChild(overlay));
    document.body.appendChild(overlay);
}

export function exportarExcel(button, urlDatos, nombreArchivo) {
    const btnExportar = document.getElementById(button);
    const textoOriginal = btnExportar.innerText.trim();

    // Desactivar botón
    btnExportar.disabled = true;
    btnExportar.innerText = "";

    // Crear spinner FA dinámicamente
    const spinner = document.createElement("i");
    spinner.className = "fas fa-spinner fa-spin me-2";
    
    // Insertar spinner y texto al botón
    btnExportar.appendChild(spinner);
    btnExportar.append("Descargando...");

    $.ajax({
        url: urlDatos,
        method: 'GET',

        success: function (data) {
            let ws = XLSX.utils.json_to_sheet(data);
            let wb = XLSX.utils.book_new();
            XLSX.utils.book_append_sheet(wb, ws, "Datos");
            XLSX.writeFile(wb, nombreArchivo + ".xlsx");

            // Restaurar estado del botón
            btnExportar.disabled = false;
            btnExportar.innerText = textoOriginal;

            mostrarToast('Excel descargado','success');
        },
        error: function () {
            alert('Error al exportar los datos. Por favor, intente nuevamente.');
            btnExportar.disabled = false;
            btnExportar.innerText = textoOriginal;
        }
    });
}


//actualiza el estado de un registro de una tabla con un switch asincronamente
export function actualizarEstado(ruta){
    $.ajax({
        url: ruta,
        type: 'POST',
        data: {
            _token: document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        },
        success: function (response) {
            mostrarToast(response.message, response.type);
        },
        error: function() {
            mostrarToast('Error al cambiar el ESTADO', 'danger');
        }
    });
}

//Funcion para mostrar toast desde el front (AJAX)
export function mostrarToast(message, type = 'primary') {
    const container = document.getElementById('toastContainer');
    if (!container) {
        console.error('No se encontró el contenedor para el toast');
        return;
    }

    // Íconos según el tipo
    const icons = {
        success: 'bi-check-circle-fill text-success',
        danger: 'bi-exclamation-triangle-fill text-danger',
        warning: 'bi-exclamation-diamond-fill text-warning',
        info: 'bi-info-circle-fill text-info',
        primary: 'bi-bell-fill text-primary'
    };
    const icon = icons[type] || icons.primary;

    // Crear el contenido del toast
    const isDark = document.body.classList.contains('dark-mode');
    const toastId = `toast-${Date.now()}`;
    const toastHTML = `
        <div class="toast ${isDark ? 'bg-dark text-white' : 'bg-white text-dark'} shadow-sm show" role="alert" aria-live="assertive" aria-atomic="true" id="${toastId}">
            <div class="toast-progress bg-${type}" style="height: 3px; width: 100%;"></div>

            <div class="d-flex align-items-center px-2 py-1">
                <div class="p-2">
                    <i class="bi ${icon} fs-4 me-3 flex-shrink-0"></i>
                </div>
                <div class="toast-body fw-semibold text-dark">
                    ${message}
                </div>
                <button type="button" class="btn-close me-2 m-auto" data-bs-dismiss="toast" aria-label="Cerrar"></button>
            </div>
        </div>
    `;

    // Limpiar y añadir el nuevo toast
    container.insertAdjacentHTML('beforeend', toastHTML);

    // Mostrarlo
    const toastEl = document.getElementById(toastId);
    const bsToast = new bootstrap.Toast(toastEl, { delay: 5000 });

    toastEl.addEventListener('hidden.bs.toast', () => {
        toastEl.remove();
    });

    bsToast.show();

    setTimeout(() => {
        if (toastEl.classList.contains('show')) {
            toastEl.classList.remove('show');
            toastEl.classList.add('toast-hide'); 
        }
    }, 4800);
}



