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
            Swal.fire({
                icon: response.type,
                title: response.title,
                confirmButtonColor: "#3366CC",
                confirmButtonText: "Aceptar"
            }).then(() => {
                window.location.href = response.redirect;
            });
        },
        error: function() {
            alert('Error al cambiar el estado');
        }
    });
}

