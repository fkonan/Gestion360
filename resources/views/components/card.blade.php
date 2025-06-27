<!-- Componente de tarjeta-->
<a href="{{ $ruta }}" class="text-decoration-none" data-bs-toggle="tooltip" data-bs-placement="top" title="{{ $tooltip ?? '' }}">
    <div class="card cardComponent d-flex flex-row align-items-center p-2 shadow">
        <div class="{{ $color }} text-white p-3 d-flex align-items-center justify-content-center text-center rounded">
            <i class="fas {{ $icono }} fa-2x" style="width: 35px"></i>
        </div>
        <div class="ms-3">
            <p class="mb-0 text-muted">{{ $descripcion }}</p>
            <h6 class="fw-bold mb-0">{{ $titulo }}</h6>
        </div>
    </div>
</a>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });
    });
</script>
<style>
.tooltip-inner {
    background-color: var(--bs-dark); 
    color: var(--bs-white); 
    font-size: 1rem;
    border-radius: var(--bs-border-radius); 
}

.tooltip.bs-tooltip-auto[data-popper-placement^=top] .tooltip-arrow::before,
.tooltip.bs-tooltip-top .tooltip-arrow::before {
    border-top-color: var(--bs-dark); 
}
</style>