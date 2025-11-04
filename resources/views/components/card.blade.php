
<style>
  .cardComponent:hover {
    transform: translateY(-4px);
    box-shadow: 0 12px 24px rgba(0, 0, 0, 0.15) !important;
    border-color: #0d6efd;
  }

  .cardComponent::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 3px;
    background: linear-gradient(90deg, #0d6efd, #0dcaf0);
    transform: scaleX(0);
    transition: transform 0.3s ease;
  }

  .cardComponent:hover::before {
    transform: scaleX(1);
  }

  .card-icon-wrapper {
    min-width: 70px;
    width: 70px;
    height: 70px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    position: relative;
    overflow: hidden;
    transition: transform 0.3s ease;
  }

  .cardComponent:hover .card-icon-wrapper {
    transform: scale(1.05);
  }

  .card-icon-wrapper::after {
    content: '';
    position: absolute;
    top: -50%;
    right: -50%;
    width: 200%;
    height: 200%;
    background: radial-gradient(circle, rgba(255,255,255,0.3) 0%, transparent 70%);
    opacity: 0;
    transition: opacity 0.3s ease;
  }

  .cardComponent:hover .card-icon-wrapper::after {
    opacity: 1;
  }

  .card-icon-wrapper i {
    color: white;
    position: relative;
    z-index: 1;
  }

  .card-content {
    flex: 1;
    padding: 0.5rem;
  }

  .card-description {
    font-size: 0.75rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    font-weight: 500;
    margin-bottom: 0.25rem;
  }

  .card-title {
    font-size: 1.1rem;
    font-weight: 600;
    margin: 0;
    transition: color 0.3s ease;
  }

  .card-link {
    text-decoration: none;
  }

  .card-arrow {
    opacity: 0;
    transform: translateX(-10px);
    transition: all 0.3s ease;
    color: #ffffff;
    font-size: 1.2rem;
  }

  .cardComponent:hover .card-arrow {
    opacity: 1;
    transform: translateX(0);
  }

  /* Variaciones de color para los iconos */
  .bg-teriary {
    background: linear-gradient(135deg, #287bf7e0 0%, #0a57ca 100%);
  }

</style>

<a href="{{ $ruta }}" class="card-link" data-bs-toggle="tooltip" data-bs-placement="top" title="{{ $tooltip ?? '' }}">
  <div class="card cardComponent d-flex flex-row align-items-center p-2 shadow-sm">
    <div class="card-icon-wrapper {{ $color }}">
      <i class="fas {{ $icono }} fa-2x"></i>
    </div>

    <div class="card-content ms-3 flex-grow-1">
      <p class="card-description mb-0">{{ $descripcion }}</p>
      <h6 class="card-title">{{ $titulo }}</h6>
    </div>

    <div class="card-arrow">
      <i class="fas fa-arrow-right"></i>
    </div>
  </div>
</a>

<script>
  document.addEventListener('DOMContentLoaded', function() {
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function(tooltipTriggerEl) {
      return new bootstrap.Tooltip(tooltipTriggerEl);
    });
  });
</script>

<style>
  .tooltip-inner {
    background-color: #1e293b;
    color: white;
    font-size: 0.875rem;
    padding: 0.5rem 0.75rem;
    border-radius: 8px;
    box-shadow: 0 4px 6px rgba(0,0,0,0.1);
  }

  .tooltip.bs-tooltip-auto[data-popper-placement^=top] .tooltip-arrow::before,
  .tooltip.bs-tooltip-top .tooltip-arrow::before {
    border-top-color: #1e293b;
  }

  .tooltip.bs-tooltip-auto[data-popper-placement^=bottom] .tooltip-arrow::before,
  .tooltip.bs-tooltip-bottom .tooltip-arrow::before {
    border-bottom-color: #1e293b;
  }

  .tooltip.bs-tooltip-auto[data-popper-placement^=left] .tooltip-arrow::before,
  .tooltip.bs-tooltip-start .tooltip-arrow::before {
    border-left-color: #1e293b;
  }

  .tooltip.bs-tooltip-auto[data-popper-placement^=right] .tooltip-arrow::before,
  .tooltip.bs-tooltip-end .tooltip-arrow::before {
    border-right-color: #1e293b;
  }
</style>
