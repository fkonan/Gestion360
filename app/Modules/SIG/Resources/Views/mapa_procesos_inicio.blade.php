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

    @if(
        auth()->user()?->can(\App\Constants\Permisos::SIG_MAPA_PROCESOS_VER_EMISION) ||
        auth()->user()?->can(\App\Constants\Permisos::SIG_MAPA_PROCESOS_ACCEDER)
    )
        <div class="row g-3 mb-4">
            @permite(\App\Constants\Permisos::SIG_MAPA_PROCESOS_VER_EMISION)
                <div class="col-12 col-lg-6">
                    <a href="{{ route('mapa-procesos.emisiones.devueltas') }}" class="sig-home-shortcut">
                        <span class="sig-home-shortcut__icon" aria-hidden="true">
                            <i class="fas fa-file-alt"></i>
                        </span>
                        <span class="sig-home-shortcut__body">
                            <span class="sig-home-shortcut__eyebrow">Acceso directo</span>
                            <span class="sig-home-shortcut__title">Mis solicitudes</span>
                            <span class="sig-home-shortcut__text">Revise el estado, el detalle y las respuestas de las solicitudes que ha enviado.</span>
                        </span>
                        <span class="sig-home-shortcut__arrow" aria-hidden="true">
                            <i class="fas fa-angle-right"></i>
                        </span>
                    </a>
                </div>
            @endpermite

            @permite(\App\Constants\Permisos::SIG_MAPA_PROCESOS_ACCEDER)
                <div class="col-12 col-lg-6">
                    <a href="{{ route('mapa-procesos.emisiones.pendientes') }}" class="sig-home-shortcut sig-home-shortcut--warning">
                        <span class="sig-home-shortcut__icon" aria-hidden="true">
                            <i class="fas fa-clipboard-check"></i>
                        </span>
                        <span class="sig-home-shortcut__body">
                            <span class="sig-home-shortcut__eyebrow">Acceso directo</span>
                            <span class="sig-home-shortcut__title">Solicitudes pendientes</span>
                            <span class="sig-home-shortcut__text">Ingrese directamente a la bandeja de revision para evaluar y decidir las solicitudes pendientes.</span>
                        </span>
                        <span class="sig-home-shortcut__arrow" aria-hidden="true">
                            <i class="fas fa-angle-right"></i>
                        </span>
                    </a>
                </div>
            @endpermite
        </div>
    @endif

    <div class="card shadow-sm sig-home-map-card">
        <div class="card-body position-relative">
            <div class="sig-home-map-head text-center mb-3">
                <div class="fw-semibold">Navegacion por categoria</div>
                <small class="text-muted">Seleccione una zona del mapa para abrir el listado documental correspondiente.</small>
            </div>

            <div class="mapa-svg-container position-relative">
                {!! file_get_contents(public_path('mapa_procesos/mapa_procesos_original.svg')) !!}
            </div>

            <button class="hotspot hotspot-gerenciales" data-area="gerenciales" aria-label="Ir a Procesos Gerenciales"></button>
            <button class="hotspot hotspot-misionales" data-area="misionales" aria-label="Ir a Procesos Misionales"></button>
            <button class="hotspot hotspot-apoyo" data-area="apoyo" aria-label="Ir a Procesos de Apoyo"></button>
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

    .mapa-svg-container {
        width: 100%;
        max-width: 700px;
        margin: 0 auto;
    }

    .mapa-svg-container svg {
        width: 100%;
        height: auto;
        display: block;
    }

    .hotspot {
        position: absolute;
        background: rgba(255, 255, 255, 0);
        border: none;
        cursor: pointer;
        z-index: 10;
    }

    .hotspot-gerenciales {
        top: 14%;
        left: 31%;
        width: 38%;
        height: 16%;
    }

    .hotspot-misionales {
        top: 40%;
        left: 21%;
        width: 58%;
        height: 19%;
    }

    .hotspot-apoyo {
        top: 68%;
        left: 32%;
        width: 36%;
        height: 16%;
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

        .hotspot-gerenciales {
            top: 15%;
            left: 22%;
            width: 55%;
            height: 15%;
        }

        .hotspot-misionales {
            top: 41%;
            left: 12%;
            width: 75%;
            height: 20%;
        }

        .hotspot-apoyo {
            top: 70%;
            left: 22%;
            width: 55%;
            height: 15%;
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

    document.querySelectorAll('.hotspot').forEach((boton) => {
      boton.addEventListener('click', () => {
        const area = boton.dataset.area;
        if (area) {
          window.location.href = `${baseRuta}/${area}`;
        }
      });
    });

    if (btnNuevo) {
      btnNuevo.addEventListener('click', () => {
        const url = btnNuevo.getAttribute('data-url');
        if (url && window.cargarModal) {
          window.cargarModal(url, 'Nuevo documento', '#formNuevoDocumento', 'modal-lg');
        }
      });
    }
  });
</script>
@endpushOnce
