<!-- Componente de tarjeta-->
<div class="card d-flex flex-row align-items-center p-3 shadow-sm" style="max-width: 300px;">
    <div class="{{ $color }} text-white p-3 d-flex align-items-center justify-content-center" style="border-radius: 8px;">
        <i class="fas {{ $icono }} fs-2"></i>
    </div>
    <div class="ms-3">
        <p class="mb-0 text-muted">{{ $descripcion }}</p>
        <h6 class="fw-bold mb-0">{{ $titulo }}</h6>
    </div>
</div>
