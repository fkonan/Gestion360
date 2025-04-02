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
    submitButton.disabled = true;
}

export function handleMenuActive() {
    var url = window.location.href; 
   
    $('ul.nav-sidebar a').filter(function() {
        let href = this.href;
        return href !== window.location.origin + "/#" && url.startsWith(href);
    }).addClass('active');

    $('ul.nav-treeview a').filter(function() {
        let href = this.href;
        return href !== window.location.origin + "/#" && url.startsWith(href);
    }).parentsUntil(".nav-sidebar > .nav-treeview").addClass('menu-open').prev('a').addClass('active');
}