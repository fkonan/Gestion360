@extends('layouts.dashboard')

@section('title', 'Revisar solicitud pendiente')

@section('breadcrumb')
<x-breadcrumb :items="[
        ['name' => 'Inicio', 'url' => route('home')],
        ['name' => 'SIG', 'url' => route('mapa-procesos.index')],
        ['name' => 'Gestion de solicitudes', 'url' => route('gestion-solicitudes.index')],
        ['name' => 'Revisar solicitud']
    ]" />
<br>
@endsection

@section('content')
<div class="container-fluid p-0 border rounded sidebar-dark-primary tableContainer" style="min-height:150px;">

  <x-sectionHeader
    titulo="Revisar solicitud pendiente"
    :rutaVolver="route('gestion-solicitudes.index')"
    :clasePosition="false" />

  <div class="p-4 sig-review-screen">
    <section class="sig-review-screen__hero">
      <div class="sig-review-screen__hero-copy">
        <span class="sig-review-screen__eyebrow">Solicitud pendiente</span>
        <h3 class="sig-review-screen__title mb-0">{{ $nombreDocumento }}</h3>
        <div class="sig-review-screen__meta">
          <span class="sig-review-screen__meta-chip">{{ $revision['codigo'] ?? 'Sin código' }}</span>
          <span class="sig-review-screen__meta-chip">{{ $tipoSolicitud }}</span>
          <span class="sig-review-screen__meta-chip">Documento #{{ $revision['documento_id'] ?? 'N/D' }}</span>
          <span class="sig-review-screen__meta-chip">{{ !empty($revision['version']) ? 'Emisión '.$revision['version'] : 'Sin emisión' }}</span>
        </div>
      </div>

      <div class="sig-review-screen__hero-side">
        <span class="badge bg-warning text-dark">{{ $estado }}</span>
        <div class="sig-review-screen__hero-checks">
          <span
            class="sig-review-screen__hero-check"
            data-bs-toggle="tooltip"
            data-bs-placement="top"
            title="Confirma que el archivo corresponda al documento y se pueda visualizar correctamente.">
            Archivo
          </span>
          <span
            class="sig-review-screen__hero-check"
            data-bs-toggle="tooltip"
            data-bs-placement="top"
            title="Verifica que los responsables asignados sean los correctos antes de decidir.">
            Responsables
          </span>
          <span
            class="sig-review-screen__hero-check"
            data-bs-toggle="tooltip"
            data-bs-placement="top"
            title="Registra una observación clara para dejar trazabilidad de la decisión.">
            Decisión
          </span>
        </div>
      </div>
    </section>

    <div class="row g-4 align-items-start">
      <div class="col-12 col-xl-8">
        <div class="sig-review-page">
          <section class="sig-review-screen__metrics">
            <article class="sig-review-screen__metric">
              <span class="sig-review-screen__metric-label">Tipo</span>
              <strong class="sig-review-screen__metric-value">{{ $tipoSolicitud }}</strong>
            </article>
            <article class="sig-review-screen__metric">
              <span class="sig-review-screen__metric-label">Emisión</span>
              <strong class="sig-review-screen__metric-value">{{ $revision['version'] ?: 'Sin asignar' }}</strong>
            </article>
            <article class="sig-review-screen__metric">
              <span class="sig-review-screen__metric-label">Páginas</span>
              <strong class="sig-review-screen__metric-value">{{ $revision['paginas'] ?: 'No registradas' }}</strong>
            </article>
            <article class="sig-review-screen__metric">
              <span class="sig-review-screen__metric-label">Fecha de carga</span>
              <strong class="sig-review-screen__metric-value">{{ $fechaElaboracion }}</strong>
            </article>
          </section>

          <section class="sig-review-section">
            <div class="sig-review-section__header sig-review-section__header--split d-flex flex-column flex-lg-row align-items-lg-start justify-content-lg-between gap-3">
              <div>
                <div class="sig-review-section__title-row">
                  <h6 class="sig-review-section__title">Documento adjunto</h6>
                  <button
                    type="button"
                    class="sig-review-help"
                    data-bs-toggle="tooltip"
                    data-bs-placement="top"
                    title="{{ $previewMensaje }}"
                    aria-label="Información sobre la vista previa">
                    i
                  </button>
                </div>
                <div class="sig-review-section__chips">
                  <span class="sig-review-chip {{ $previewEsDemo ? 'sig-review-chip--warning' : 'sig-review-chip--success' }}">
                    {{ $previewEsDemo ? 'Vista previa temporal' : 'Vista previa disponible' }}
                  </span>
                  <span
                    class="sig-review-chip sig-review-chip--neutral"
                    title="{{ $archivoRuta !== '' ? $archivoRuta : 'Sin archivo asociado' }}">
                    {{ $archivoNombre }}
                  </span>
                </div>
              </div>

              <a
                href="{{ $previewUrl }}"
                target="_blank"
                rel="noopener noreferrer"
                class="btn btn-outline-secondary btn-sm">
                Abrir archivo
              </a>
            </div>

            @if($previewEsDemo)
              <div class="sig-review-inline-note">
                La vista previa es temporal. Antes de aprobar, valida el archivo final que corresponde a esta solicitud.
              </div>
            @endif

            <div class="sig-pdf-viewer sig-review-pdf-viewer">
              <iframe
                class="sig-pdf-frame"
                src="{{ $previewFrameUrl }}"
                title="Vista previa del documento"
                loading="lazy"></iframe>
            </div>
          </section>

          <section class="sig-review-screen__grid">
            <article class="sig-review-section">
              <div class="sig-review-section__header">
                <div class="sig-review-section__title-row">
                  <h6 class="sig-review-section__title">Resumen de la solicitud</h6>
                  <button
                    type="button"
                    class="sig-review-help"
                    data-bs-toggle="tooltip"
                    data-bs-placement="top"
                    title="Usa estos datos para confirmar que estás revisando el documento correcto."
                    aria-label="Ayuda sobre el resumen de la solicitud">
                    i
                  </button>
                </div>
              </div>

              <div class="sig-review-screen__info-cards">
                <article class="sig-review-info-card">
                  <span class="sig-review-info-card__label">Código</span>
                  <strong class="sig-review-info-card__value">{{ $revision['codigo'] ?? 'Sin código asignado' }}</strong>
                </article>
                <article class="sig-review-info-card">
                  <span class="sig-review-info-card__label">Documento</span>
                  <strong class="sig-review-info-card__value">#{{ $revision['documento_id'] ?? 'N/D' }}</strong>
                </article>
                <article class="sig-review-info-card">
                  <span class="sig-review-info-card__label">Archivo</span>
                  <strong class="sig-review-info-card__value" title="{{ $archivoRuta !== '' ? $archivoRuta : 'Sin archivo asociado' }}">{{ $archivoNombre }}</strong>
                </article>
                <article class="sig-review-info-card">
                  <span class="sig-review-info-card__label">Estado</span>
                  <strong class="sig-review-info-card__value">{{ $estado }}</strong>
                </article>
              </div>
            </article>

            <article class="sig-review-section">
              <div class="sig-review-section__header">
                <div class="sig-review-section__title-row">
                  <h6 class="sig-review-section__title">Responsables actuales</h6>
                  <button
                    type="button"
                    class="sig-review-help"
                    data-bs-toggle="tooltip"
                    data-bs-placement="top"
                    title="Estos son los responsables actualmente registrados para la emisión."
                    aria-label="Ayuda sobre responsables actuales">
                    i
                  </button>
                </div>
              </div>

              <div class="sig-review-people">
                <article class="sig-review-field sig-review-field--wide">
                  <span class="sig-review-field__label">Elaboró</span>
                  <strong class="sig-review-field__value">{{ $elaboro }}</strong>
                </article>
                <article class="sig-review-field sig-review-field--wide">
                  <span class="sig-review-field__label">Revisó</span>
                  <strong class="sig-review-field__value">{{ $reviso }}</strong>
                </article>
                <article class="sig-review-field sig-review-field--wide">
                  <span class="sig-review-field__label">Aprueba</span>
                  <strong class="sig-review-field__value">{{ $aprueba }}</strong>
                </article>
              </div>
            </article>
          </section>

          <section class="sig-review-section">
            <div class="sig-review-section__header sig-review-section__header--split">
              <div class="sig-review-section__title-row">
                <h6 class="sig-review-section__title">Comentario del solicitante</h6>
                <button
                  type="button"
                  class="sig-review-help"
                  data-bs-toggle="tooltip"
                  data-bs-placement="top"
                  title="Este comentario explica el contexto o cambio solicitado por la persona que creó la emisión."
                  aria-label="Ayuda sobre el comentario del solicitante">
                  i
                </button>
              </div>
              <span class="sig-review-chip sig-review-chip--neutral">Contexto inicial</span>
            </div>

            <div class="sig-review-note">
              {{ $comentarioSolicitud }}
            </div>
          </section>
        </div>
      </div>

      <div class="col-12 col-xl-4">
        <div class="sig-review-sidebar">
          <section class="sig-review-section sig-review-section--sticky sig-review-decision-card">
            <div class="sig-review-section__header">
              <div class="sig-review-section__title-row">
                <h6 class="sig-review-section__title">Tomar decisión</h6>
                <button
                  type="button"
                  class="sig-review-help"
                  data-bs-toggle="tooltip"
                  data-bs-placement="top"
                  title="Completa responsables y comentario antes de aprobar o devolver esta solicitud."
                  aria-label="Ayuda sobre la toma de decisión">
                  i
                </button>
              </div>
            </div>

            <div class="sig-review-checklist">
              <span class="sig-review-checklist__item">1. Responsables</span>
              <span class="sig-review-checklist__item">2. Comentario</span>
              <span class="sig-review-checklist__item">3. Acción</span>
            </div>

            <div class="sig-review-decision-card__current">
              <article class="sig-review-mini-card">
                <span class="sig-review-mini-card__label">Revisa actual</span>
                <strong class="sig-review-mini-card__value">{{ $reviso }}</strong>
              </article>
              <article class="sig-review-mini-card">
                <span class="sig-review-mini-card__label">Aprueba actual</span>
                <strong class="sig-review-mini-card__value">{{ $aprueba }}</strong>
              </article>
            </div>

            <div class="sig-review-form-group">
              <label for="sigRevisionRevisa" class="sig-review-form-label">
                <span>Revisa</span>
                <button
                  type="button"
                  class="sig-review-help"
                  data-bs-toggle="tooltip"
                  data-bs-placement="top"
                  title="Responsable que valida el contenido antes de la aprobación."
                  aria-label="Ayuda sobre el responsable que revisa">
                  i
                </button>
              </label>
              <select id="sigRevisionRevisa" class="form-select">
                <option value="">Seleccione</option>
                @foreach($ubicacionesRevisa as $ubic)
                  <option value="{{ $ubic->id }}" @selected($revisoSeleccionado === (string) $ubic->id)>{{ $ubic->nombre }}</option>
                @endforeach
              </select>
              <div class="sig-review-form-note">Selecciona quién hará la validación formal de esta emisión.</div>
            </div>

            <div class="sig-review-form-group">
              <label for="sigRevisionAprueba" class="sig-review-form-label">
                <span>Aprueba</span>
                <button
                  type="button"
                  class="sig-review-help"
                  data-bs-toggle="tooltip"
                  data-bs-placement="top"
                  title="Responsable que autoriza que esta emisión quede vigente."
                  aria-label="Ayuda sobre el responsable que aprueba">
                  i
                </button>
              </label>
              <select id="sigRevisionAprueba" class="form-select">
                <option value="">Seleccione</option>
                @foreach($ubicacionesAprueba as $ubic)
                  <option value="{{ $ubic->id }}" @selected($apruebaSeleccionado === (string) $ubic->id)>{{ $ubic->nombre }}</option>
                @endforeach
              </select>
              <div class="sig-review-form-note">Selecciona quién aprobará la emisión si cumple con lo esperado.</div>
            </div>

            <div class="sig-review-form-group">
              <label for="sigRevisionComentario" class="sig-review-form-label">
                <span>Comentario de revisión</span>
                <button
                  type="button"
                  class="sig-review-help"
                  data-bs-toggle="tooltip"
                  data-bs-placement="top"
                  title="Este comentario quedará visible como soporte de la decisión tomada."
                  aria-label="Ayuda sobre el comentario de revisión">
                  i
                </button>
              </label>
              <textarea
                id="sigRevisionComentario"
                class="form-control"
                rows="5"
                maxlength="500"
                placeholder="Escribe la decisión u observación para el solicitante"></textarea>
              <div class="sig-review-form-note">Resume el motivo de la aprobación o el ajuste que debe realizar el solicitante.</div>
              <div id="sigRevisionError" class="text-danger small mt-2 d-none"></div>
            </div>

            <div class="sig-review-actions sig-review-actions--stacked">
              <button type="button" class="btn btn-success sig-review-action" data-estado="APROBADO">
                Aprobar solicitud
              </button>
              <button type="button" class="btn btn-warning text-dark sig-review-action" data-estado="DEVUELTO">
                Devolver con observaciones
              </button>
            </div>
          </section>

          <section class="sig-review-section sig-review-decision-guide">
            <div class="sig-review-section__header">
              <h6 class="sig-review-section__title">Resultado de cada acción</h6>
            </div>
            <div class="sig-review-decision-guide__list">
              <article class="sig-review-decision-guide__item sig-review-decision-guide__item--success">
                <strong>Aprobar</strong>
                <p class="mb-0">La emisión queda aprobada y pasa a ser la versión vigente del documento.</p>
              </article>
              <article class="sig-review-decision-guide__item sig-review-decision-guide__item--warning">
                <strong>Devolver</strong>
                <p class="mb-0">La solicitud regresa al creador con observaciones para que la ajuste y la reenvíe.</p>
              </article>
            </div>
          </section>
        </div>
      </div>
    </div>
  </div>
</div>
@endsection

@pushOnce('css')
<style>
  .sig-review-screen {
    display: flex;
    flex-direction: column;
    gap: 1rem;
  }

  .sig-review-screen__hero {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 1rem;
    padding: 1.1rem 1.15rem;
    border: 1px solid var(--app-border);
    border-radius: 1rem;
    background-color: var(--app-surface);
    box-shadow: var(--app-shadow-soft);
  }

  .sig-review-screen__hero-copy {
    display: flex;
    flex-direction: column;
    gap: 0.7rem;
    min-width: 0;
  }

  .sig-review-screen__eyebrow,
  .sig-review-screen__metric-label,
  .sig-review-info-card__label,
  .sig-review-mini-card__label {
    display: inline-block;
    font-size: 0.72rem;
    font-weight: 700;
    letter-spacing: 0.06em;
    text-transform: uppercase;
    color: var(--bs-secondary-color);
  }

  .sig-review-screen__title {
    color: var(--bs-heading-color);
    font-weight: 700;
    line-height: 1.25;
  }

  .sig-review-screen__meta,
  .sig-review-section__chips,
  .sig-review-screen__hero-checks,
  .sig-review-checklist {
    display: flex;
    flex-wrap: wrap;
    gap: 0.45rem;
  }

  .sig-review-screen__meta-chip,
  .sig-review-chip,
  .sig-review-screen__hero-check,
  .sig-review-checklist__item {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-height: 1.9rem;
    padding: 0.3rem 0.7rem;
    border: 1px solid var(--app-border);
    border-radius: 999px;
    background-color: var(--app-surface-alt);
    color: var(--bs-body-color);
    font-size: 0.79rem;
    font-weight: 600;
    line-height: 1.2;
  }

  .sig-review-screen__hero-side {
    display: flex;
    flex-direction: column;
    align-items: flex-end;
    gap: 0.7rem;
    max-width: 340px;
  }

  .sig-review-screen__hero-check {
    cursor: help;
    color: var(--bs-primary);
    background-color: rgba(var(--bs-primary-rgb), 0.08);
    border-color: rgba(var(--bs-primary-rgb), 0.16);
  }

  .sig-review-screen__metrics {
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: 0.75rem;
  }

  .sig-review-screen__metric {
    display: flex;
    flex-direction: column;
    gap: 0.3rem;
    min-height: 76px;
    padding: 0.85rem 0.95rem;
    border: 1px solid var(--app-border);
    border-radius: 0.95rem;
    background-color: var(--app-surface);
    box-shadow: var(--app-shadow-soft);
  }

  .sig-review-screen__metric-value {
    color: var(--bs-heading-color);
    font-size: 1rem;
    font-weight: 700;
    line-height: 1.35;
    word-break: break-word;
  }

  .sig-review-section__header--split {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 1rem;
  }

  .sig-review-section__title-row {
    display: flex;
    align-items: center;
    gap: 0.45rem;
  }

  .sig-review-help {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 1.45rem;
    height: 1.45rem;
    padding: 0;
    border: 1px solid var(--app-border);
    border-radius: 999px;
    background-color: var(--app-surface-alt);
    color: var(--bs-secondary-color);
    font-size: 0.78rem;
    font-weight: 700;
    line-height: 1;
    cursor: help;
  }

  .sig-review-help:hover {
    color: var(--bs-primary);
    border-color: rgba(var(--bs-primary-rgb), 0.25);
  }

  .sig-review-chip--success {
    color: #0f5132;
    background-color: rgba(var(--bs-success-rgb), 0.12);
    border-color: rgba(var(--bs-success-rgb), 0.22);
  }

  .sig-review-chip--warning {
    color: #8a5a00;
    background-color: rgba(var(--bs-warning-rgb), 0.16);
    border-color: rgba(var(--bs-warning-rgb), 0.26);
  }

  .sig-review-chip--neutral {
    color: var(--bs-body-color);
  }

  .sig-review-inline-note {
    margin-bottom: 0.9rem;
    padding: 0.8rem 0.9rem;
    border: 1px solid rgba(var(--bs-warning-rgb), 0.22);
    border-left: 4px solid rgba(var(--bs-warning-rgb), 0.55);
    border-radius: 0.85rem;
    background-color: rgba(var(--bs-warning-rgb), 0.08);
    color: var(--bs-body-color);
    line-height: 1.5;
  }

  .sig-review-screen__grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 1rem;
  }

  .sig-review-screen__info-cards {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 0.75rem;
  }

  .sig-review-info-card,
  .sig-review-mini-card {
    display: flex;
    flex-direction: column;
    gap: 0.35rem;
    padding: 0.9rem 0.95rem;
    border: 1px solid var(--app-border);
    border-radius: 0.85rem;
    background-color: var(--app-surface-alt);
    min-width: 0;
  }

  .sig-review-info-card__value,
  .sig-review-mini-card__value {
    color: var(--bs-heading-color);
    font-weight: 700;
    line-height: 1.45;
    word-break: break-word;
  }

  .sig-review-people {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 0.75rem;
  }

  .sig-review-field {
    min-height: unset;
  }

  .sig-review-note {
    min-height: 92px;
  }

  .sig-review-sidebar {
    display: flex;
    flex-direction: column;
    gap: 1rem;
  }

  .sig-review-decision-card__current {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 0.75rem;
    margin-bottom: 1rem;
  }

  .sig-review-form-group {
    margin-bottom: 1rem;
  }

  .sig-review-form-label {
    display: flex;
    align-items: center;
    gap: 0.45rem;
    margin-bottom: 0.45rem;
    color: var(--bs-heading-color);
    font-weight: 600;
  }

  .sig-review-form-note {
    margin-top: 0.4rem;
    color: var(--bs-secondary-color);
    font-size: 0.79rem;
    line-height: 1.4;
  }

  .sig-review-decision-guide__item {
    padding: 0.9rem 0.95rem;
    border: 1px solid var(--app-border);
    border-radius: 0.85rem;
    background-color: var(--app-surface-alt);
  }

  .sig-review-decision-guide__item--success {
    border-left: 4px solid rgba(var(--bs-success-rgb), 0.5);
  }

  .sig-review-decision-guide__item--warning {
    border-left: 4px solid rgba(var(--bs-warning-rgb), 0.5);
  }

  .sig-review-decision-guide__item strong {
    display: block;
    margin-bottom: 0.35rem;
    color: var(--bs-heading-color);
  }

  .sig-review-decision-guide__item p {
    color: var(--bs-secondary-color);
    line-height: 1.55;
  }

  @media (max-width: 1199.98px) {
    .sig-review-screen__hero {
      flex-direction: column;
    }

    .sig-review-screen__hero-side {
      align-items: flex-start;
      max-width: none;
    }

    .sig-review-screen__metrics {
      grid-template-columns: repeat(2, minmax(0, 1fr));
    }
  }

  @media (max-width: 767.98px) {
    .sig-review-screen__metrics,
    .sig-review-screen__grid,
    .sig-review-screen__info-cards,
    .sig-review-people,
    .sig-review-decision-card__current {
      grid-template-columns: 1fr;
    }

    .sig-review-section__header--split {
      flex-direction: column;
      align-items: stretch;
    }
  }
</style>
@endpushOnce

@pushOnce('script')
<script>
  document.addEventListener('DOMContentLoaded', () => {
    const rutas = {
      APROBADO: "{{ route('gestion-solicitudes.aprobar', ['id' => $revision['id']]) }}",
      DEVUELTO: "{{ route('gestion-solicitudes.devolver', ['id' => $revision['id']]) }}",
      pendientes: "{{ route('gestion-solicitudes.index') }}",
    };

    let processing = false;

    const revisaSelect = document.getElementById('sigRevisionRevisa');
    const apruebaSelect = document.getElementById('sigRevisionAprueba');
    const textarea = document.getElementById('sigRevisionComentario');
    const errorEl = document.getElementById('sigRevisionError');
    const buttons = Array.from(document.querySelectorAll('.sig-review-action'));

    if (window.bootstrap?.Tooltip) {
      document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach((element) => {
        new window.bootstrap.Tooltip(element);
      });
    }

    const setProcessing = (value) => {
      processing = value;
      if (revisaSelect) {
        revisaSelect.disabled = value;
      }
      if (apruebaSelect) {
        apruebaSelect.disabled = value;
      }
      if (textarea) {
        textarea.disabled = value;
      }
      buttons.forEach((button) => {
        button.disabled = value;
      });
    };

    const showError = (message) => {
      if (!errorEl) return;
      errorEl.textContent = message;
      errorEl.classList.remove('d-none');
    };

    const clearError = () => {
      if (!errorEl) return;
      errorEl.textContent = '';
      errorEl.classList.add('d-none');
    };

    const obtenerEtiquetaAccion = (estado) => {
      if (estado === 'APROBADO') return 'aprobar';
      return 'devolver';
    };

    const enviarDecision = (estado) => {
      if (processing) {
        return;
      }

      const comentario = textarea?.value?.trim() || '';
      const idRevisa = revisaSelect?.value?.trim() || '';
      const idAprueba = apruebaSelect?.value?.trim() || '';

      if (!comentario) {
        showError('El comentario es obligatorio para continuar.');
        textarea?.focus();
        return;
      }

      if (!idRevisa) {
        showError('Debes seleccionar quién revisa la solicitud.');
        revisaSelect?.focus();
        return;
      }

      if (!idAprueba) {
        showError('Debes seleccionar quién aprueba la solicitud.');
        apruebaSelect?.focus();
        return;
      }

      clearError();

      Swal.fire({
        title: `¿Deseas ${obtenerEtiquetaAccion(estado)} esta solicitud?`,
        text: 'La decisión quedará registrada para el solicitante.',
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Confirmar',
        cancelButtonText: 'Cancelar',
        confirmButtonColor: estado === 'APROBADO' ? '#198754' : '#ffc107',
        cancelButtonColor: '#6c757d',
        customClass: { popup: 'swalAlert' }
      }).then((result) => {
        if (!result.isConfirmed) {
          return;
        }

        setProcessing(true);

        fetch(rutas[estado], {
          method: 'POST',
          headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            'Accept': 'application/json',
            'Content-Type': 'application/json',
          },
          body: JSON.stringify({
            comentario,
            id_revisa: idRevisa,
            id_aprueba: idAprueba
          })
        })
          .then(r => r.ok ? r.json() : Promise.reject(r))
          .then((resp) => {
            sessionStorage.setItem('toastTitle', resp.title || 'Actualizado');
            sessionStorage.setItem('toastType', resp.type || 'success');
            window.location.href = rutas.pendientes;
          })
          .catch(async (err) => {
            let mensaje = 'No se pudo actualizar la solicitud.';
            try {
              const json = await err.json();
              mensaje = json.message || mensaje;
            } catch (_) {}
            showError(mensaje);
          })
          .finally(() => {
            setProcessing(false);
          });
      });
    };

    buttons.forEach((button) => {
      button.addEventListener('click', () => enviarDecision(button.dataset.estado));
    });
  });
</script>
@endpushOnce
