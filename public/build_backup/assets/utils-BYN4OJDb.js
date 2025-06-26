function m(){const t=new Date;let e=t.getHours();const o=t.getMinutes(),n=t.getSeconds();let s="a.m.";e>=12?(s="p.m.",e>12&&(e-=12)):e==0&&(e=12);let i=document.getElementById("horas"),d=document.getElementById("minutos"),a=document.getElementById("segundos"),r=document.getElementById("ampm");i&&(i.innerText=String(e).padStart(2,"0")),d&&(d.innerText=String(o).padStart(2,"0")),a&&(a.innerText=String(n).padStart(2,"0")),r&&(r.innerText=s)}function c(){const t=document.getElementById("fullscreen-loader");t&&(t.style.display="flex")}function u(){const t=document.getElementById("fullscreen-loader");t&&(t.style.display="none")}function f(t,e=!1){const o=t.querySelector('button[type="submit"], input[type="submit"]');(t.checkValidity()||e)&&(o.disabled=!0,c())}function b(t){const e=t.querySelector('button[type="submit"], input[type="submit"]');e.disabled=!1,u()}function p(){var t=window.location.href.split("#")[0];$("ul.nav-sidebar a").filter(function(){let e=this.href;return e!==window.location.origin+"/"&&t.startsWith(e)}).addClass("active"),$("ul.nav-treeview a").filter(function(){let e=this.href;return e!==window.location.origin+"/"&&t.startsWith(e)}).parentsUntil(".nav-sidebar > .nav-treeview").addClass("menu-open").prev("a").addClass("active")}function g(t){const e=document.createElement("div");e.style=`
        position: fixed; top: 0; left: 0; width: 100%; height: 100%;
        background: rgba(0, 0, 0, 0.8); z-index: 9999; display: flex;
        justify-content: center; align-items: center; overflow: hidden;
    `;let o="";t.match(/\.(jpeg|jpg|png|gif)$/i)?o=`<img src="${t}" style="max-width: 80%; max-height: 80%; border: none;" alt="Archivo">`:o=`<iframe src="${t}" style="width: 80%; height: 90%; border: none;"></iframe>`,e.innerHTML=`
        ${o}
        <div style="position: absolute; top: 10px; right: 10px; display: flex; gap: 10px;">
            <button style="
                padding: 10px 20px; background: #ff0000; color: #fff; border: none;
                cursor: pointer; font-size: 16px; font-weight: bold">Cerrar</button>
        </div>
    `,e.querySelector("button").addEventListener("click",()=>document.body.removeChild(e)),document.body.appendChild(e)}function h(t,e,o){const n=document.getElementById(t),s=n.innerText.trim();n.disabled=!0,n.innerText="";const i=document.createElement("i");i.className="fas fa-spinner fa-spin me-2",n.appendChild(i),n.append("Descargando..."),$.ajax({url:e,method:"GET",success:function(d){let a=XLSX.utils.json_to_sheet(d),r=XLSX.utils.book_new();XLSX.utils.book_append_sheet(r,a,"Datos"),XLSX.writeFile(r,o+".xlsx"),n.disabled=!1,n.innerText=s,l("Excel descargado","success")},error:function(){l("Error al exportar los datos. Por favor, intente nuevamente.","danger"),n.disabled=!1,n.innerText=s}})}function y(t){$.ajax({url:t,type:"POST",data:{_token:document.querySelector('meta[name="csrf-token"]').getAttribute("content")},success:function(e){l(e.message,e.type)},error:function(){l("Error al cambiar el ESTADO","danger")}})}function l(t,e="primary"){const o=document.getElementById("toastContainer");if(!o){console.error("No se encontró el contenedor para el toast");return}const n={success:"bi-check-circle-fill text-success",danger:"bi-exclamation-triangle-fill text-danger",warning:"bi-exclamation-diamond-fill text-warning",info:"bi-info-circle-fill text-info",primary:"bi-bell-fill text-primary"},s=n[e]||n.primary;document.body.classList.contains("dark-mode");const i=`toast-${Date.now()}`,d=`
        <div class="toast shadow-sm show toastAJAX" role="alert" aria-live="assertive" aria-atomic="true" id="${i}">
            <div class="toast-progress bg-${e}" style="height: 3px; width: 100%;"></div>

            <div class="d-flex align-items-center px-2 py-1">
                <div class="p-2">
                    <i class="bi ${s} fs-4 me-3 flex-shrink-0"></i>
                </div>
                <div class="toast-body fw-semibold text-dark">
                    ${t}
                </div>
                <button type="button" class="btn-close me-2 m-auto" data-bs-dismiss="toast" aria-label="Cerrar"></button>
            </div>
        </div>
    `;o.insertAdjacentHTML("beforeend",d);const a=document.getElementById(i),r=new bootstrap.Toast(a,{delay:5e3});a.addEventListener("hidden.bs.toast",()=>{a.remove()}),r.show(),setTimeout(()=>{a.classList.contains("show")&&(a.classList.remove("show"),a.classList.add("toast-hide"))},4800)}function x(){const t=document.body,e=document.getElementById("toggleDarkMode");localStorage.getItem("darkMode")==="enabled"&&t.classList.add("dark-mode"),e==null||e.addEventListener("click",()=>{t.classList.toggle("dark-mode");const n=t.classList.contains("dark-mode");localStorage.setItem("darkMode",n?"enabled":"disabled"),document.cookie="darkMode="+(n?"enabled":"disabled")+"; path=/"})}export{g as a,y as b,x as c,f as d,h as e,m as f,p as g,b as h,l as m};
