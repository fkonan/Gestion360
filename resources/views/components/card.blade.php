<style>
  .cardComponent {
    position: relative;
    border: 1px solid #e2e8f0;
    border-radius: 16px;
    padding: 0.9rem 1.1rem;
    background: #ffffff;
    gap: 0.85rem;
    transition: transform 0.3s ease, box-shadow 0.3s ease, border-color 0.3s ease;
  }

  .cardComponent:hover {
    transform: translateY(-4px);
    box-shadow: 0 12px 24px rgba(15, 23, 42, 0.18);
    border-color: #0d6efd;
  }

  .cardComponent::before {
    content: '';
    position: absolute;
    top: 0;
    left: 7px;
    right: 7px;
    height: 3px;
    border-radius: 40px 40px 0 0;
    background: linear-gradient(90deg, #0d6efd, #0dcaf0);
    transform: scaleX(0);
    transform-origin: left;
    transition: transform 0.3s ease;
  }

  .cardComponent:hover::before {
    transform: scaleX(1);
  }

  .card-icon-wrapper {
    min-width: 56px;
    width: 56px;
    height: 56px;
    border-radius: 14px;
    display: flex;
    align-items: center;
    justify-content: center;
    position: relative;
    overflow: hidden;
    transition: transform 0.25s ease;
  }

  .cardComponent:hover .card-icon-wrapper {
    transform: scale(1.05);
  }

  .card-icon-wrapper::after {
    content: '';
    position: absolute;
    inset: -40%;
    background: radial-gradient(circle at top right, rgba(255, 255, 255, 0.45), transparent 60%);
    opacity: 0;
    transition: opacity 0.25s ease;
  }

  .cardComponent:hover .card-icon-wrapper::after {
    opacity: 1;
  }

  .card-icon-wrapper i {
    color: #ffffff;
    position: relative;
    z-index: 1;
  }

  .card-content {
    flex: 1;
    min-width: 0;
  }

  .card-description {
    font-size: 0.68rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    font-weight: 600;
    margin-bottom: 0.15rem;
    color: #64748b;
  }

  .card-title {
    font-size: 1rem;
    font-weight: 600;
    margin: 0;
    color: #0f172a;
    transition: color 0.3s ease;
  }

  .cardComponent:hover .card-title {
    color: #0b5ed7;
  }

  .card-link {
    text-decoration: none;
    display: block;
  }

  .card-arrow {
    align-self: stretch;
    display: flex;
    align-items: center;
    color: #94a3b8;
    font-size: 1.05rem;
    opacity: 0;
    transform: translateX(-8px);
    transition: transform 0.3s ease, opacity 0.3s ease, color 0.3s ease;
  }

  .cardComponent:hover .card-arrow {
    opacity: 1;
    transform: translateX(0);
    color: #0b5ed7;
  }

  /* Variaciones de color para los iconos */
  .bg-teriary {
    background: linear-gradient(135deg, #287bf7 0%, #0a57ca 100%);
  }

  [data-bs-theme="dark"] .cardComponent {
    background: rgba(15, 23, 42, 0.7);
    border-color: rgba(148, 163, 184, 0.4);
    box-shadow: 0 10px 24px rgba(2, 6, 23, 0.4);
  }

  [data-bs-theme="dark"] .cardComponent:hover {
    box-shadow: 0 16px 32px rgba(8, 16, 44, 0.6);
    border-color: #38bdf8;
  }

  [data-bs-theme="dark"] .cardComponent::before {
    background: linear-gradient(90deg, #38bdf8, #6366f1);
  }

  [data-bs-theme="dark"] .card-description {
    color: #cbd5f5;
  }

  [data-bs-theme="dark"] .card-title {
    color: #f8fafc;
  }

  [data-bs-theme="dark"] .cardComponent:hover .card-title {
    color: #38bdf8;
  }

  [data-bs-theme="dark"] .card-arrow {
    color: #cbd5f5;
  }

  [data-bs-theme="dark"] .cardComponent:hover .card-arrow {
    color: #38bdf8;
  }

  .tooltip-inner {
    background-color: #1e293b;
    color: white;
    font-size: 0.875rem;
    padding: 0.5rem 0.75rem;
    border-radius: 8px;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
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

<a href="{{ $ruta }}" class="card-link" data-bs-toggle="tooltip" data-bs-placement="top" title="{{ $tooltip ?? '' }}">
  <div class="card cardComponent d-flex flex-row align-items-center shadow-sm">
    <div class="card-icon-wrapper {{ $color }}">
      <i class="fas {{ $icono }} fa-lg"></i>
    </div>

    <div class="card-content">
      <p class="card-description mb-1">{{ $descripcion }}</p>
      <h6 class="card-title mb-0">{{ $titulo }}</h6>
    </div>

    <div class="card-arrow ms-2">
      <i class="fas fa-arrow-right"></i>
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
