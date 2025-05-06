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

//Evitar dobles click en forms
export function deshabilitarSubmit(form) {
    const submitButton = form.querySelector('button[type="submit"], input[type="submit"]');
    if (form.checkValidity()) {
        submitButton.disabled = true;
    }
}
export function habilitarSubmit(form) {
    const submitButton = form.querySelector('button[type="submit"], input[type="submit"]');
    submitButton.disabled = false;
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


export function abrirArchivo(url) {
    const overlay = document.createElement('div');
    overlay.style = `
        position: fixed; top: 0; left: 0; width: 100%; height: 100%;
        background: rgba(0, 0, 0, 0.8); z-index: 9999; display: flex;
        justify-content: center; align-items: center; overflow: hidden;
    `;

    let content = '';
    if (url.match(/\.(jpeg|jpg|png|gif)$/i)) {
        // If the file is an image
        content = `<img src="${url}" style="max-width: 80%; max-height: 80%; border: none;" alt="Archivo">`;
    } else {
        // Default to iframe for other file types (e.g., PDF)
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

