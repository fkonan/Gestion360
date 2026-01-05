<!-- Componente tarjeta para las opciones disponibles en el index del proyecto-->
<div class="card h-100 shadow border hover-shadow transition px-2">
  <div class="card-body text-center py-4">
    <div class="mb-3">
      <span class="rounded-circle p-3 d-inline-block bg-body-tertiary">
        <i class="fa {{ $icono }} fa-2x text-tertiary"></i>
      </span>
    </div>
    <h5 class="fw-semibold mb-2 fw-bold text-tertiary">{{ $titulo }}</h5>
    <p class="card-text text-muted mb-3">{{ $descripcion }}</p>
    @if($ruta === '#')
      <a href="#" class="btn w-75 btn-tertiary disabled">Ingresar</a>
    @else
      <a href="{{ $ruta }}" class="btn w-75 btn-tertiary">Ingresar</a>
    @endif
  </div>
</div>
