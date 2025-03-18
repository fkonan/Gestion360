<!-- Swal alert componente -->
@if(session('alert'))
<script>
    document.addEventListener("DOMContentLoaded", function() {
        Swal.fire({
            icon: "{{ session('alert.type') }}",
            title: "{{ session('alert.title') }}",
            confirmButtonColor: "#3366CC",
            confirmButtonText: "Aceptar"
        });
    });
</script>
@endif