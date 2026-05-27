@extends('layouts.dashboard')

@section('title', 'Mapa de Procesos')

@section('content')
<div class="container-fluid">
    <div class="row mb-3">
        <div class="col-12 d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3">
            <div>
                <h4 class="mb-0">Mapa de Procesos</h4>
                <small class="text-muted">Use los accesos directos o haga clic sobre cada area del mapa para navegar.</small>
            </div>
            <div class="d-flex gap-2 flex-wrap">
                @permite(\App\Constants\Permisos::SIG_MAPA_PROCESOS_LISTADO_MAESTRO)
                    <button
                        type="button"
                        class="btn btn-outline-secondary btn-sm sig-home-download-btn"
                        data-role="descargar-listado-maestro"
                        data-url="{{ route('mapa-procesos.listado-maestro') }}">
                        Descargar listado maestro
                    </button>
                @endpermite
                @permite(\App\Constants\Permisos::SIG_MAPA_PROCESOS_CREAR)
                    <button
                        type="button"
                        class="btn btn-primary btn-sm"
                        data-role="nuevo-documento"
                        data-url="{{ route('mapa-procesos.documento.nuevo') }}">
                        Nuevo documento
                    </button>
                @endpermite
            </div>
        </div>
    </div>

    <div class="card shadow-sm sig-home-map-card">
        <div class="card-body position-relative">
            <div class="sig-home-map-head text-center mb-3">
                <div class="fw-semibold">Navegacion por categoria</div>
                <small class="text-muted">Seleccione una zona del mapa para abrir el listado documental correspondiente.</small>
            </div>

            <div class="mapa-svg-container position-relative">
                {!! file_get_contents(public_path('mapa_procesos/mapa_procesos_original.svg')) !!}
                <div class="sig-map-tooltip" data-role="proc-tooltip" hidden aria-hidden="true">
                    <div class="sig-map-tooltip__header">
                        <div class="sig-map-tooltip__eyebrow" data-field="categoria"></div>
                        <div class="sig-map-tooltip__title" data-field="nombre"></div>
                    </div>
                    <div class="sig-map-tooltip__metrics">
                        <div class="sig-map-tooltip__metric">
                            <span class="sig-map-tooltip__metric-label">Disponibles</span>
                            <strong data-field="activos">0</strong>
                        </div>
                        <div class="sig-map-tooltip__metric" data-role="tooltip-metric-revision">
                            <span class="sig-map-tooltip__metric-label">En actualizacion</span>
                            <strong data-field="revision">0</strong>
                        </div>
                    </div>
                    <div class="sig-map-tooltip__section">
                        <div class="sig-map-tooltip__section-label">Disponibles por tipo</div>
                        <div class="sig-map-tooltip__types" data-role="tooltip-types"></div>
                    </div>
                    <div class="sig-map-tooltip__footer">
                        <div class="sig-map-tooltip__hint">Clic para abrir el listado filtrado</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@pushOnce('css')
<style>
    .sig-home-shortcut {
        display: flex;
        align-items: flex-start;
        gap: 1rem;
        height: 100%;
        padding: 1rem 1.1rem;
        border: 1px solid var(--app-border);
        border-radius: 0.95rem;
        background-color: var(--app-surface);
        color: inherit;
        text-decoration: none;
        box-shadow: var(--app-shadow-soft);
        transition: transform 0.14s ease, border-color 0.14s ease, box-shadow 0.14s ease;
    }

    .sig-home-shortcut:hover,
    .sig-home-shortcut:focus-visible {
        color: inherit;
        text-decoration: none;
        border-color: rgba(var(--bs-primary-rgb), 0.28);
        box-shadow: 0 10px 24px rgba(15, 23, 42, 0.12);
        transform: translateY(-1px);
        outline: none;
    }

    .sig-home-shortcut--warning:hover,
    .sig-home-shortcut--warning:focus-visible {
        border-color: rgba(var(--bs-warning-rgb), 0.38);
    }

    .sig-home-shortcut__icon {
        width: 2.85rem;
        height: 2.85rem;
        flex: 0 0 2.85rem;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: 0.85rem;
        background-color: rgba(var(--bs-primary-rgb), 0.1);
        color: var(--bs-primary);
        font-size: 1.05rem;
    }

    .sig-home-shortcut--warning .sig-home-shortcut__icon {
        background-color: rgba(var(--bs-warning-rgb), 0.14);
        color: #b7791f;
    }

    .sig-home-shortcut__body {
        display: flex;
        flex-direction: column;
        gap: 0.2rem;
        min-width: 0;
        flex: 1;
    }

    .sig-home-shortcut__eyebrow {
        font-size: 0.72rem;
        font-weight: 700;
        letter-spacing: 0.05em;
        text-transform: uppercase;
        color: var(--bs-secondary-color);
    }

    .sig-home-shortcut__title {
        font-size: 1rem;
        font-weight: 700;
        color: var(--bs-heading-color);
        line-height: 1.25;
    }

    .sig-home-shortcut__text {
        font-size: 0.9rem;
        line-height: 1.45;
        color: var(--bs-secondary-color);
    }

    .sig-home-shortcut__arrow {
        align-self: center;
        color: var(--bs-secondary-color);
        font-size: 1rem;
    }

    .sig-home-map-card .card-body {
        padding: 1.25rem 1.25rem 1.5rem;
    }

    .sig-home-map-head {
        margin-inline: auto;
        max-width: 520px;
    }

    .sig-home-download-btn.is-loading,
    .sig-home-download-btn:disabled {
        cursor: wait;
        pointer-events: none;
        opacity: 0.85;
    }

    .mapa-svg-container {
        width: 100%;
        max-width: 700px;
        margin: 0 auto;
        overflow: visible;
    }

    .mapa-svg-container svg {
        width: 100%;
        height: auto;
        display: block;
    }

    .mapa-svg-container [proc],
    .mapa-svg-container [cat] {
        cursor: pointer;
        transition: opacity 0.18s ease, filter 0.18s ease, transform 0.18s ease;
    }

    .mapa-svg-container [data-map-layer] > :not([proc]):not([cat]) {
        transition: opacity 0.18s ease, filter 0.18s ease;
    }

    .mapa-svg-container [proc] {
        transform-box: fill-box;
        transform-origin: center;
    }

    .mapa-svg-container.is-proc-hovering [data-map-layer] > :not([proc]):not([cat]) {
        opacity: 0.34;
    }

    .mapa-svg-container.is-proc-hovering [proc] {
        opacity: 0.26;
        filter: saturate(0.74);
    }

    .mapa-svg-container.is-proc-hovering [cat] {
        opacity: 0.56;
    }

    .mapa-svg-container [proc].is-proc-active {
        opacity: 1 !important;
        filter: drop-shadow(0 10px 18px rgba(15, 23, 42, 0.16));
        transform: translateY(-1px) scale(1.018);
    }

    .mapa-svg-container [cat].is-cat-active {
        opacity: 1 !important;
    }

    .mapa-svg-container [proc]:focus-visible,
    .mapa-svg-container [cat]:focus-visible {
        outline: none;
    }

    .mapa-svg-container [proc]:focus-visible polygon,
    .mapa-svg-container [cat]:focus-visible path,
    .mapa-svg-container [cat]:focus-visible polygon {
        opacity: 0.82;
    }

    .mapa-svg-container [cat] {
        transition: filter 0.18s ease, opacity 0.18s ease, transform 0.18s ease;
    }

    [data-bs-theme="dark"] .mapa-svg-container [cat] {
        filter: drop-shadow(0 8px 18px rgba(0, 0, 0, 0.42));
    }

    [data-bs-theme="dark"] .mapa-svg-container.is-proc-hovering [data-map-layer] > :not([proc]):not([cat]) {
        opacity: 0.18;
    }

    [data-bs-theme="dark"] .mapa-svg-container.is-proc-hovering [proc] {
        opacity: 0.18;
        filter: saturate(0.58) brightness(0.82);
    }

    [data-bs-theme="dark"] .mapa-svg-container.is-proc-hovering [cat] {
        opacity: 0.68;
    }

    [data-bs-theme="dark"] .mapa-svg-container [proc].is-proc-active {
        filter: drop-shadow(0 10px 22px rgba(0, 0, 0, 0.5))
                drop-shadow(0 0 10px rgba(var(--bs-primary-rgb), 0.2));
    }

    [data-bs-theme="dark"] .mapa-svg-container [cat] .fil15,
    [data-bs-theme="dark"] .mapa-svg-container [cat] .fil17 {
        fill: #f4f7ff !important;
        stroke: rgba(9, 16, 31, 0.72);
        stroke-width: 22px;
        stroke-linejoin: round;
        paint-order: stroke fill;
    }

    [data-bs-theme="dark"] .mapa-svg-container [cat] .fil14 {
        fill: #8f3a36 !important;
        stroke: rgba(255, 255, 255, 0.14);
        stroke-width: 18px;
        stroke-linejoin: round;
    }

    [data-bs-theme="dark"] .mapa-svg-container [cat] .fil9 {
        fill: #d9534f !important;
        stroke: rgba(255, 255, 255, 0.14);
        stroke-width: 18px;
        stroke-linejoin: round;
    }

    [data-bs-theme="dark"] .mapa-svg-container [cat]:hover,
    [data-bs-theme="dark"] .mapa-svg-container [cat]:focus-visible {
        filter: drop-shadow(0 10px 22px rgba(0, 0, 0, 0.52))
                drop-shadow(0 0 10px rgba(var(--bs-primary-rgb), 0.18));
    }

    .sig-map-tooltip {
        --sig-tooltip-accent: #0c54a0;
        --sig-tooltip-bg-top: rgba(255, 255, 255, 0.99);
        --sig-tooltip-bg-bottom: rgba(248, 250, 252, 0.97);
        --sig-tooltip-border: rgba(15, 23, 42, 0.18);
        --sig-tooltip-title: #0f172a;
        --sig-tooltip-muted: rgba(51, 65, 85, 0.92);
        --sig-tooltip-metric-bg: rgba(226, 232, 240, 0.78);
        --sig-tooltip-metric-border: rgba(148, 163, 184, 0.22);
        --sig-tooltip-badge-bg: rgba(12, 84, 160, 0.1);
        --sig-tooltip-badge-text: #0c54a0;
        position: absolute;
        z-index: 5;
        width: min(280px, calc(100vw - 2rem));
        max-width: 280px;
        padding: 0.95rem 1rem 1rem;
        border-radius: 1rem;
        border: 1px solid var(--sig-tooltip-border);
        border-top: 3px solid var(--sig-tooltip-accent);
        background:
            linear-gradient(180deg, var(--sig-tooltip-bg-top), var(--sig-tooltip-bg-bottom)),
            var(--app-surface);
        box-shadow:
            0 24px 60px rgba(15, 23, 42, 0.28),
            0 10px 24px rgba(15, 23, 42, 0.14),
            inset 0 1px 0 rgba(255, 255, 255, 0.92);
        backdrop-filter: blur(14px) saturate(145%);
        color: var(--bs-body-color);
        pointer-events: none;
        opacity: 0;
        transform: translate3d(0, 8px, 0) scale(0.98);
        transition: opacity 0.16s ease, transform 0.16s ease;
    }

    .sig-map-tooltip.is-visible {
        opacity: 1;
        transform: translate3d(0, 0, 0) scale(1);
    }

    .sig-map-tooltip.is-right {
        transform-origin: left center;
    }

    .sig-map-tooltip.is-left {
        transform-origin: right center;
    }

    .sig-map-tooltip__header {
        display: flex;
        flex-direction: column;
        gap: 0.45rem;
    }

    .sig-map-tooltip__eyebrow {
        display: inline-flex;
        align-self: flex-start;
        padding: 0.26rem 0.55rem;
        border-radius: 999px;
        background-color: var(--sig-tooltip-badge-bg);
        color: var(--sig-tooltip-badge-text);
        font-size: 0.67rem;
        font-weight: 700;
        letter-spacing: 0.06em;
        text-transform: uppercase;
    }

    .sig-map-tooltip__title {
        font-size: 0.96rem;
        font-weight: 700;
        line-height: 1.3;
        color: var(--sig-tooltip-title);
    }

    .sig-map-tooltip__metrics {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 0.55rem;
        margin-top: 0.82rem;
        margin-bottom: 0.82rem;
    }

    .sig-map-tooltip__metric {
        display: flex;
        flex-direction: column;
        gap: 0.24rem;
        padding: 0.6rem 0.65rem;
        border-radius: 0.8rem;
        background-color: var(--sig-tooltip-metric-bg);
        border: 1px solid var(--sig-tooltip-metric-border);
    }

    .sig-map-tooltip__metric-label {
        font-size: 0.71rem;
        font-weight: 600;
        line-height: 1.2;
        color: var(--sig-tooltip-muted);
    }

    .sig-map-tooltip__metric strong {
        font-size: 1.05rem;
        font-weight: 700;
        line-height: 1.1;
        color: var(--sig-tooltip-title);
    }

    .sig-map-tooltip__metric--attention {
        background-color: rgba(var(--bs-warning-rgb), 0.22);
        border-color: rgba(var(--bs-warning-rgb), 0.32);
    }

    .sig-map-tooltip__metric--attention strong {
        color: #b45309;
    }

    .sig-map-tooltip__section {
        display: flex;
        flex-direction: column;
        gap: 0.45rem;
        margin-top: 0.15rem;
        margin-bottom: 0.8rem;
    }

    .sig-map-tooltip__section-label {
        font-size: 0.7rem;
        font-weight: 700;
        letter-spacing: 0.05em;
        text-transform: uppercase;
        color: var(--sig-tooltip-muted);
    }

    .sig-map-tooltip__types {
        display: grid;
        gap: 0.4rem;
    }

    .sig-map-tooltip__type {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 0.75rem;
        padding: 0.45rem 0.55rem;
        border-radius: 0.75rem;
        background-color: rgba(226, 232, 240, 0.42);
        border: 1px solid rgba(148, 163, 184, 0.18);
    }

    .sig-map-tooltip__type-name {
        min-width: 0;
        font-size: 0.78rem;
        line-height: 1.35;
        color: var(--sig-tooltip-title);
    }

    .sig-map-tooltip__type-count {
        flex: 0 0 auto;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-width: 1.9rem;
        padding: 0.18rem 0.48rem;
        border-radius: 999px;
        background-color: var(--sig-tooltip-badge-bg);
        color: var(--sig-tooltip-badge-text);
        font-size: 0.72rem;
        font-weight: 700;
        line-height: 1;
    }

    .sig-map-tooltip__type-empty {
        padding: 0.2rem 0;
        font-size: 0.78rem;
        line-height: 1.45;
        color: var(--sig-tooltip-muted);
    }

    .sig-map-tooltip__footer {
        display: flex;
        flex-direction: column;
        gap: 0.55rem;
        padding-top: 0.72rem;
        border-top: 1px solid rgba(148, 163, 184, 0.2);
    }

    .sig-map-tooltip__hint {
        font-size: 0.73rem;
        font-weight: 600;
        color: var(--sig-tooltip-accent);
    }

    [data-bs-theme="dark"] .sig-map-tooltip {
        --sig-tooltip-bg-top: rgba(17, 24, 39, 0.98);
        --sig-tooltip-bg-bottom: rgba(9, 14, 24, 0.96);
        --sig-tooltip-border: rgba(255, 255, 255, 0.12);
        --sig-tooltip-title: #f8fafc;
        --sig-tooltip-muted: rgba(226, 232, 240, 0.86);
        --sig-tooltip-metric-bg: rgba(30, 41, 59, 0.84);
        --sig-tooltip-metric-border: rgba(148, 163, 184, 0.16);
        --sig-tooltip-badge-bg: rgba(96, 165, 250, 0.14);
        --sig-tooltip-badge-text: #bfdbfe;
        background:
            linear-gradient(180deg, var(--sig-tooltip-bg-top), var(--sig-tooltip-bg-bottom)),
            rgba(15, 23, 42, 0.96);
        box-shadow:
            0 24px 58px rgba(2, 6, 23, 0.58),
            0 10px 24px rgba(2, 6, 23, 0.28),
            inset 0 1px 0 rgba(255, 255, 255, 0.06);
    }

    [data-bs-theme="dark"] .sig-map-tooltip__metric {
        background-color: var(--sig-tooltip-metric-bg);
        border-color: var(--sig-tooltip-metric-border);
    }

    [data-bs-theme="dark"] .sig-map-tooltip__type {
        background-color: rgba(30, 41, 59, 0.72);
        border-color: rgba(148, 163, 184, 0.16);
    }

    [data-bs-theme="dark"] .sig-map-tooltip__metric--attention {
        background-color: rgba(var(--bs-warning-rgb), 0.2);
    }

    [data-bs-theme="dark"] .sig-map-tooltip__metric--attention strong {
        color: #f4c66d;
    }

    [data-bs-theme="dark"] .sig-map-tooltip__footer {
        border-top-color: rgba(148, 163, 184, 0.18);
    }

    @media (max-width: 768px) {
        .sig-home-shortcut {
            padding: 0.95rem 1rem;
        }

        .sig-home-shortcut__icon {
            width: 2.65rem;
            height: 2.65rem;
            flex-basis: 2.65rem;
        }
    }

    [data-bs-theme="dark"] .sig-home-shortcut--warning .sig-home-shortcut__icon {
        color: #f4c66d;
    }
</style>
@endpushOnce

@pushOnce('script')
@vite(['resources/js/cargarModal.js'])
<script>
  document.addEventListener('DOMContentLoaded', () => {
    const baseRuta = "{{ url('/sig/mapa-procesos') }}";
    const btnNuevo = document.querySelector('[data-role="nuevo-documento"]');
    const btnListadoMaestro = document.querySelector('[data-role="descargar-listado-maestro"]');
    const mapa = document.querySelector('.mapa-svg-container');
    const resumenProcesos = @json($resumenProcesosMapa ?? []);
    const categorias = {
      G: { slug: 'gerenciales', titulo: 'Procesos Gerenciales', accent: '#0c54a0' },
      M: { slug: 'misionales', titulo: 'Procesos Misionales', accent: '#0f766e' },
      A: { slug: 'apoyo', titulo: 'Procesos de Apoyo', accent: '#b45309' },
      O: { slug: 'otros', titulo: 'Otros Procesos', accent: '#475569' }
    };

    const construirUrlCategoria = (codigoCategoria, procesoId = null) => {
      const categoria = categorias[String(codigoCategoria || '').trim().toUpperCase()];
      if (!categoria) {
        return null;
      }

      const url = new URL(`${baseRuta}/${categoria.slug}`);
      if (procesoId) {
        url.searchParams.set('proceso', procesoId);
      }

      return url.toString();
    };

    const registrarNavegacion = (elemento, destino) => {
      if (!elemento || !destino) {
        return;
      }

      elemento.setAttribute('tabindex', '0');
      elemento.setAttribute('role', 'link');
      elemento.addEventListener('click', () => {
        window.location.href = destino;
      });
      elemento.addEventListener('keydown', (event) => {
        if (event.key === 'Enter' || event.key === ' ') {
          event.preventDefault();
          window.location.href = destino;
        }
      });
    };

    if (mapa) {
      const capaMapa = mapa.querySelector('svg > g[id="Capa_x0020_1"]');
      const tooltip = mapa.querySelector('[data-role="proc-tooltip"]');
      const tooltipNombre = tooltip?.querySelector('[data-field="nombre"]');
      const tooltipCategoria = tooltip?.querySelector('[data-field="categoria"]');
      const tooltipActivos = tooltip?.querySelector('[data-field="activos"]');
      const tooltipRevision = tooltip?.querySelector('[data-field="revision"]');
      const tooltipDescripcion = null;
      const tooltipTipos = tooltip?.querySelector('[data-role="tooltip-types"]');
      const tooltipRevisionBox = tooltip?.querySelector('[data-role="tooltip-metric-revision"]');
      let procesoResaltado = null;
      let categoriasResaltadas = [];
      let tooltipHideTimer = null;

      const escaparHtml = (valor = '') => String(valor)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#39;');

      if (capaMapa) {
        capaMapa.setAttribute('data-map-layer', 'main');
      }

      const ocultarTooltip = () => {
        if (!tooltip) {
          return;
        }

        clearTimeout(tooltipHideTimer);
        tooltip.classList.remove('is-visible');
        tooltip.setAttribute('aria-hidden', 'true');
        tooltipHideTimer = window.setTimeout(() => {
          tooltip.hidden = true;
        }, 140);
      };

      const posicionarTooltip = (grupoProceso) => {
        if (!tooltip || !grupoProceso) {
          return;
        }

        const containerRect = mapa.getBoundingClientRect();
        const procesoRect = grupoProceso.getBoundingClientRect();
        const tooltipRect = tooltip.getBoundingClientRect();
        const viewportPadding = 12;
        const gap = 18;
        const tooltipWidth = tooltipRect.width || 280;
        const tooltipHeight = tooltipRect.height || 180;
        const rightSpace = window.innerWidth - procesoRect.right;
        const leftSpace = procesoRect.left;
        const canShowRight = rightSpace >= (tooltipWidth + viewportPadding + gap);
        const canShowLeft = leftSpace >= (tooltipWidth + viewportPadding + gap);
        let side = 'right';
        let left = (procesoRect.right - containerRect.left) + gap;
        let top = (procesoRect.top - containerRect.top) + (procesoRect.height / 2) - (tooltipHeight / 2);

        if (!canShowRight && canShowLeft) {
          side = 'left';
          left = (procesoRect.left - containerRect.left) - tooltipWidth - gap;
        } else if (!canShowRight && !canShowLeft && leftSpace > rightSpace) {
          side = 'left';
          left = (procesoRect.left - containerRect.left) - tooltipWidth - gap;
        }

        const minLeft = viewportPadding - containerRect.left;
        const maxLeft = window.innerWidth - containerRect.left - tooltipWidth - viewportPadding;
        const minTop = viewportPadding - containerRect.top;
        const maxTop = window.innerHeight - containerRect.top - tooltipHeight - viewportPadding;

        left = Math.max(minLeft, Math.min(left, maxLeft));
        top = Math.max(minTop, Math.min(top, maxTop));

        tooltip.classList.toggle('is-right', side === 'right');
        tooltip.classList.toggle('is-left', side === 'left');

        tooltip.style.left = `${left}px`;
        tooltip.style.top = `${top}px`;
      };

      const obtenerDatosProceso = (grupoProceso) => {
        const procesoId = String(grupoProceso.getAttribute('proc') || '').trim();
        const resumen = resumenProcesos[procesoId] || {};
        const codigoCategoria = String(resumen.categoria || grupoProceso.getAttribute('id-cat') || '').trim().toUpperCase();
        const categoria = categorias[codigoCategoria] || categorias.O;

        return {
          nombre: resumen.nombre || String(grupoProceso.getAttribute('text') || 'Proceso').trim(),
          categoriaTitulo: resumen.categoria_titulo || categoria.titulo,
          activos: Number(resumen.activos || 0),
          enRevision: Number(resumen.en_revision || 0),
          tiposDocumento: Array.isArray(resumen.tipos_documento) ? resumen.tipos_documento : [],
          accent: categoria.accent,
        };
      };

      const mostrarTooltip = (grupoProceso) => {
        if (!tooltip) {
          return;
        }

        clearTimeout(tooltipHideTimer);

        const datosProceso = obtenerDatosProceso(grupoProceso);
        tooltip.style.setProperty('--sig-tooltip-accent', datosProceso.accent);

        if (tooltipNombre) {
          tooltipNombre.textContent = datosProceso.nombre;
        }

        if (tooltipCategoria) {
          tooltipCategoria.textContent = datosProceso.categoriaTitulo;
        }

        if (tooltipActivos) {
          tooltipActivos.textContent = String(datosProceso.activos);
        }

        if (tooltipRevision) {
          tooltipRevision.textContent = String(datosProceso.enRevision);
        }

        if (tooltipRevisionBox) {
          tooltipRevisionBox.classList.toggle('sig-map-tooltip__metric--attention', datosProceso.enRevision > 0);
        }

        if (tooltipTipos) {
          tooltipTipos.innerHTML = datosProceso.tiposDocumento.length
            ? datosProceso.tiposDocumento.map((tipo) => `
                <div class="sig-map-tooltip__type">
                  <span class="sig-map-tooltip__type-name">${escaparHtml(tipo.nombre ?? 'Tipo sin nombre')}</span>
                  <span class="sig-map-tooltip__type-count">${Number(tipo.cantidad || 0)}</span>
                </div>
              `).join('')
            : '<div class="sig-map-tooltip__type-empty">No hay documentos activos visibles para clasificar en este subproceso.</div>';
        }

        if (tooltipDescripcion) {
          tooltipDescripcion.textContent = datosProceso.enRevision > 0
            ? 'La vista mostrará los documentos activos visibles de este subproceso y su estado de actualización.'
            : 'La vista mostrará los documentos activos visibles de este subproceso.';
        }

        tooltip.hidden = false;
        tooltip.setAttribute('aria-hidden', 'false');

        requestAnimationFrame(() => {
          posicionarTooltip(grupoProceso);
          tooltip.classList.add('is-visible');
        });
      };

      const limpiarResaltadoProceso = () => {
        mapa.classList.remove('is-proc-hovering');
        ocultarTooltip();

        if (procesoResaltado) {
          procesoResaltado.classList.remove('is-proc-active');
          procesoResaltado = null;
        }

        categoriasResaltadas.forEach((grupoCategoria) => {
          grupoCategoria.classList.remove('is-cat-active');
        });
        categoriasResaltadas = [];
      };

      const resaltarProceso = (grupoProceso) => {
        if (!grupoProceso || procesoResaltado === grupoProceso) {
          return;
        }

        limpiarResaltadoProceso();

        const codigoCategoria = String(grupoProceso.getAttribute('id-cat') || '').trim().toUpperCase();
        mapa.classList.add('is-proc-hovering');
        grupoProceso.classList.add('is-proc-active');
        procesoResaltado = grupoProceso;
        mostrarTooltip(grupoProceso);

        if (codigoCategoria) {
          categoriasResaltadas = Array.from(mapa.querySelectorAll(`[cat="${codigoCategoria}"]`));
          categoriasResaltadas.forEach((grupoCategoria) => {
            grupoCategoria.classList.add('is-cat-active');
          });
        }
      };

      mapa.querySelectorAll('[proc][id-cat]').forEach((grupoProceso) => {
        const procesoId = grupoProceso.getAttribute('proc');
        const codigoCategoria = grupoProceso.getAttribute('id-cat');
        const destino = construirUrlCategoria(codigoCategoria, procesoId);
        grupoProceso.setAttribute('map', 'proc');
        grupoProceso.addEventListener('mouseenter', () => resaltarProceso(grupoProceso));
        grupoProceso.addEventListener('mouseleave', limpiarResaltadoProceso);
        grupoProceso.addEventListener('focus', () => resaltarProceso(grupoProceso));
        grupoProceso.addEventListener('blur', limpiarResaltadoProceso);
        registrarNavegacion(grupoProceso, destino);
      });

      mapa.querySelectorAll('[cat]').forEach((grupoCategoria) => {
        const codigoCategoria = grupoCategoria.getAttribute('cat');
        const destino = construirUrlCategoria(codigoCategoria);
        registrarNavegacion(grupoCategoria, destino);
      });

      window.addEventListener('resize', () => {
        if (procesoResaltado && tooltip && !tooltip.hidden) {
          posicionarTooltip(procesoResaltado);
        }
      });
    }

    if (btnNuevo) {
      btnNuevo.addEventListener('click', () => {
        const url = btnNuevo.getAttribute('data-url');
        if (url && window.cargarModal) {
          window.cargarModal(url, 'Nuevo documento', '#formNuevoDocumento', 'modal-lg');
        }
      });
    }

    if (btnListadoMaestro) {
      const urlDescarga = btnListadoMaestro.getAttribute('data-url');
      const textoOriginal = btnListadoMaestro.innerHTML;
      let descargaEnCurso = false;
      const nombreArchivoPorDefecto = `listado-maestro-sig-${new Date().toISOString().slice(0, 19).replace(/[:T]/g, '-').replace(/-$/, '')}.pdf`;

      const obtenerNombreArchivo = (contentDisposition) => {
        if (!contentDisposition) {
          return nombreArchivoPorDefecto;
        }

        const coincidenciaUtf = contentDisposition.match(/filename\*\s*=\s*UTF-8''([^;]+)/i);
        if (coincidenciaUtf?.[1]) {
          try {
            return decodeURIComponent(coincidenciaUtf[1]).replace(/["']/g, '');
          } catch (_) {
          }
        }

        const coincidenciaSimple = contentDisposition.match(/filename\s*=\s*"([^"]+)"/i)
          || contentDisposition.match(/filename\s*=\s*([^;]+)/i);

        return coincidenciaSimple?.[1]?.trim().replace(/["']/g, '') || nombreArchivoPorDefecto;
      };

      const descargarBlob = (blob, nombreArchivo) => {
        const objectUrl = window.URL.createObjectURL(blob);
        const enlace = document.createElement('a');
        enlace.href = objectUrl;
        enlace.download = nombreArchivo;
        enlace.style.display = 'none';
        document.body.appendChild(enlace);
        enlace.click();
        enlace.remove();

        window.setTimeout(() => {
          window.URL.revokeObjectURL(objectUrl);
        }, 1500);
      };

      const actualizarEstadoDescargaListado = (texto) => {
        btnListadoMaestro.innerHTML = `<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>${texto}`;
      };
      const esperar = (ms) => new Promise((resolve) => window.setTimeout(resolve, ms));

      const restaurarDescargaListado = () => {
        descargaEnCurso = false;
        btnListadoMaestro.disabled = false;
        btnListadoMaestro.classList.remove('is-loading');
        btnListadoMaestro.innerHTML = textoOriginal;
      };

      btnListadoMaestro.addEventListener('click', async () => {
        if (descargaEnCurso || !urlDescarga) {
          return;
        }

        descargaEnCurso = true;
        btnListadoMaestro.disabled = true;
        btnListadoMaestro.classList.add('is-loading');
        actualizarEstadoDescargaListado('Generando PDF...');

        try {
          const separador = urlDescarga.includes('?') ? '&' : '?';
          const respuesta = await fetch(`${urlDescarga}${separador}t=${Date.now()}`, {
            method: 'GET',
            credentials: 'same-origin',
            cache: 'no-store',
            headers: {
              'X-Requested-With': 'XMLHttpRequest',
            },
          });

          if (!respuesta.ok) {
            throw new Error(`No se pudo generar el listado maestro (${respuesta.status}).`);
          }

          const contentType = String(respuesta.headers.get('Content-Type') || '').toLowerCase();
          if (!contentType.includes('application/pdf')) {
            throw new Error('La respuesta no corresponde a un archivo PDF.');
          }

          actualizarEstadoDescargaListado('Descargando PDF...');
          const blob = await respuesta.blob();

          if (!blob || blob.size === 0) {
            throw new Error('El archivo PDF se generó vacío.');
          }

          const nombreArchivo = obtenerNombreArchivo(respuesta.headers.get('Content-Disposition'));
          descargarBlob(blob, nombreArchivo);
          actualizarEstadoDescargaListado('Finalizando descarga...');
          await esperar(1800);
        } catch (error) {
          if (window.mostrarToast) {
            mostrarToast(error?.message || 'No fue posible descargar el listado maestro.', 'danger');
          } else if (window.Swal?.fire) {
            Swal.fire({
              icon: 'error',
              title: 'Error',
              text: error?.message || 'No fue posible descargar el listado maestro.',
            });
          }
        } finally {
          restaurarDescargaListado();
        }
      });
    }
  });
</script>
@endpushOnce
