<!-- Swal alert componente -->
@if(session('alert'))
<div id="alert-data" 
     data-type="{{ session('alert.type') }}" 
     data-title="{{ session('alert.title') }}" 
     data-redirect="{{ session('alert.redirect_url', '') }}" 
     style="display: none;"></div>

<script>
document.addEventListener("DOMContentLoaded", function() {
    var alertData = document.getElementById('alert-data');
    var redirectUrl = alertData.dataset.redirect || null;
    
    Swal.fire({
        icon: alertData.dataset.type,
        title: alertData.dataset.title,
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