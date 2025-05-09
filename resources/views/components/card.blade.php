<!-- Componente de tarjeta-->
<a href="{{ $ruta }}" class="text-decoration-none">
    <div class="card d-flex flex-row align-items-center p-2 shadow-sm">
        <div class="{{ $color }} text-white p-3 d-flex align-items-center justify-content-center text-center" style="border-radius: 8px;">
            <i class="fas {{ $icono }} fa-2x" style="width: 35px"></i>
        </div>
        <div class="ms-3">
            <p class="mb-0 text-muted">{{ $descripcion }}</p>
            <h6 class="fw-bold mb-0">{{ $titulo }}</h6>
        </div>
    </div>
</a>
