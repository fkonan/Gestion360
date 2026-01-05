<!-- Toast componente -->
@if(session('toast'))
<div id="toastContainer" class="toast-container position-fixed top-0 end-0 p-3" style="z-index: 1055">
  <div class="toast shadow-sm" role="alert" aria-live="assertive" aria-atomic="true" id="toastAlert">

    <!-- Barra de progreso -->
    <div class="toast-progress bg-{{ session('toast.type', 'primary') }}"></div>

    <div class="d-flex align-items-center px-2 py-1">
      {{-- Icono dinámico --}}
      <div class="p-2">
        @php
        $icons = [
        'success' => 'bi-check-circle-fill text-success',
        'danger' => 'bi-exclamation-triangle-fill text-danger',
        'warning' => 'bi-exclamation-diamond-fill text-warning',
        'info' => 'bi-info-circle-fill text-info',
        'primary' => 'bi-bell-fill text-primary',
        ];
        $icon = $icons[session('toast.type')] ?? 'bi-bell-fill text-primary';
        @endphp
        <i class="bi {{ $icon }} fs-4 me-3 flex-shrink-0"></i>
      </div>

      {{-- Mensaje --}}
      <div class="toast-body fw-semibold">
        {{ session('toast.message') }}
      </div>

      {{-- Botón de cierre --}}
      <button type="button" class="btn-close me-2 m-auto" data-bs-dismiss="toast" aria-label="Cerrar"></button>
    </div>
  </div>
</div>
<script>
  document.addEventListener('DOMContentLoaded', function() {
    const toastEl = document.getElementById('toastAlert');
    if (toastEl) {
      const toast = new bootstrap.Toast(toastEl, {
        delay: 7000
      });
      toast.show();

      setTimeout(() => {
        toastEl.classList.remove('show');
        toastEl.classList.add('toast-hide');
      }, 4800);
    }
  });
</script>
@endif
