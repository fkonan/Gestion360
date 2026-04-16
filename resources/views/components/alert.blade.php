<!-- Swal alert componente -->
@if(session('alert'))
<div id="alert-data"
  data-type="{{ session('alert.type') }}"
  data-title="{{ session('alert.title') }}"
  data-description="{{ session('alert.description', '') }}"
  data-redirect="{{ session('alert.redirect_url', '') }}"
  style="display: none;"></div>

<script>
  document.addEventListener("DOMContentLoaded", function() {
    var alertData = document.getElementById('alert-data');
    var redirectUrl = alertData.dataset.redirect || null;
    var description = alertData.dataset.description || '';

    Swal.fire({
      icon: alertData.dataset.type,
      title: alertData.dataset.title,
      html: description ? description : undefined,
      confirmButtonColor: "#3366CC",
      confirmButtonText: "Aceptar",
      customClass: {
        popup: 'swalAlert'
      }
    }).then((result) => {
      if (result.isConfirmed && redirectUrl) {
        window.location.href = redirectUrl;
      }
    });
  });
</script>
@endif


<div class="acordeon-footer">

  <details>
    <summary>Portales</summary>

    <div class="contenido-acordeon">

      <p><strong>Educación</strong></p>
      <ul>
        <li><a href="https://intranet.copetran.com:7443/moodle/">Cursos Nacionales</a></li>
        <li><a href="https://intranet.copetran.com:7443/moodle/">Cursos Bucaramanga</a></li>
      </ul>

      <p><strong>Correo</strong></p>
      <ul>
        <li><a href="https://portal.office.com/">Correo interno</a></li>
        <li><a href="https://portal.office.com/">Office 365</a></li>
      </ul>

      <p><strong>Portales corporativos</strong></p>
      <ul>
        <li><a href="https://intranet.copetran.com.co:8443/">Intranet</a></li>
        <li><a href="https://odin.copetran.com.co:8443/Odin/faces/pages/ingreso.xhtml">Logweb / Odin</a></li>
        <li><a href="https://odin.copetran.com.co:8443/Odin/faces/pages/ingresoCorp.xhtml">Clientes corporativos</a></li>
      </ul>

      <p><strong>Pagos</strong></p>
      <ul>
        <li><a href="https://logtrans.copetran.com.co:8444/LogTrans/pages/ConsultaParticularesWeb.jsf">Pagos particulares</a></li>
        <li><a href="https://odin.copetran.com.co:8443/Odin/faces/pages/fel/main.xhtml#!/">Descargue su factura</a></li>
      </ul>

      <p><strong>Licitación</strong></p>
      <ul>
        <li><a href="https://www.copetran.com/wp-content/uploads/2024/10/PUBLICACION-FUNDACIONES-3-1.pdf">Publicación fundaciones</a></li>
      </ul>

    </div>
  </details>

   <div style="margin-top:30px;">

     <a href="/wp-content/uploads/2025/08/Codigo-de-etica.pdf" target="_blank">Convenios</a>
     <a href="/trabaja-con-nosotros-2/" target="_blank">Trabaja con nosotros</a>
     <a href="/convenios-servicios/" target="_blank">Código de ética y conducta</a>
    </div>
</div>
