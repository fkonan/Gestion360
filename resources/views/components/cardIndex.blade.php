<!-- Componente tarjeta para las opciones disponibles en el index -->
@pushOnce('styles')
<style>
    .custom-hover-btn:hover {
        background: #193366 !important;
        color: #fff !important;
        box-shadow: 0 4px 12px rgba(14,33,70,0.15);
        transition: background 0.2s, box-shadow 0.2s;
    }
</style>
@endpushOnce
<div class="card h-100 shadow border hover-shadow transition cardIndex px-2" style="border-top: 4px solid #0e2146;">
    <div class="card-body text-center py-4">
        <div class="mb-3">
            <span class="rounded-circle p-3 d-inline-block" style="background: #0e214610;">
                <i class="fa {{ $icono }} fa-2x" style="color: #0e2146;"></i>
            </span>
        </div>
        <h5 class="fw-semibold mb-2 fw-bold" style="color: #0e2146;">{{ $titulo }}</h5>
        <p class="card-text text-muted mb-3">{{ $descripcion }}</p>
        <a href="{{ $ruta }}" class="btn w-75 custom-hover-btn" style="background: #0e2146; color: #fff;">Ingresar</a>
    </div>
</div>