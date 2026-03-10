@php
  use Illuminate\Support\Str;

  $tipoSolicitud = ($revision['tipo_solicitud'] ?? '') === 'NUEVO_DOCUMENTO' ? 'Nuevo documento' : 'Emision';
  $estado = ($revision['estado'] ?? '') === 'EN_REVISION' ? 'EN REVISION' : ($revision['estado'] ?? 'N/D');
  $fechaElaboracion = !empty($revision['fecha_elaboracion'])
    ? \Illuminate\Support\Carbon::parse($revision['fecha_elaboracion'])->translatedFormat('d M Y')
    : 'Sin fecha';
  $nombreDocumento = !empty($revision['nombre'])
    ? Str::ucfirst(mb_strtolower((string) $revision['nombre'], 'UTF-8'))
    : 'Solicitud sin nombre';
  $elaboro = !empty($revision['elaboro']) ? Str::ucfirst(mb_strtolower((string) $revision['elaboro'], 'UTF-8')) : 'No asignado';
  $reviso = !empty($revision['reviso']) ? Str::ucfirst(mb_strtolower((string) $revision['reviso'], 'UTF-8')) : 'No asignado';
  $aprueba = !empty($revision['aprueba']) ? Str::ucfirst(mb_strtolower((string) $revision['aprueba'], 'UTF-8')) : 'No asignado';
  $comentarioSolicitud = !empty($revision['comentario_revision'])
    ? $revision['comentario_revision']
    : 'Sin comentario registrado por el solicitante.';
  $previewUrl = $revision['archivo_preview_url'] ?? asset('storage/pdfs/documento_pruebas.pdf');
  $previewMensaje = $revision['archivo_preview_mensaje'] ?? 'Se muestra el PDF de prueba del modulo porque esta solicitud aun no tiene un archivo PDF disponible para visualizar.';
  $previewEsDemo = (bool) ($revision['archivo_preview_demo'] ?? true);
  $previewSeparator = Str::contains($previewUrl, '#') ? '&' : '#';
  $previewFrameUrl = $previewUrl.$previewSeparator.'toolbar=0&navpanes=0&scrollbar=1';
  $revisoSeleccionado = (string) ($revision['id_revisa'] ?? '');
  $apruebaSeleccionado = (string) ($revision['id_aprueba'] ?? '');
@endphp

@extends('layouts.dashboard')

@section('title', 'Revisar solicitud pendiente')

@section('breadcrumb')
<x-breadcrumb :items="[
        ['name' => 'Inicio', 'url' => route('home')],
        ['name' => 'SIG', 'url' => route('home')],
        ['name' => 'Solicitudes pendientes', 'url' => route('mapa-procesos.emisiones.pendientes')],
        ['name' => 'Revisar solicitud']
    ]" />
<br>
@endsection

@section('content')
<div class="container-fluid p-0 border rounded sidebar-dark-primary tableContainer" style="min-height:150px;">

  <x-sectionHeader
    titulo="Revisar solicitud pendiente"
    :rutaVolver="route('mapa-procesos.emisiones.pendientes')"
    :clasePosition="false" />

  <div class="p-4">
    <div class="row g-4 align-items-start">
      <div class="col-12 col-xl-8">
        <div class="sig-review-page">
          <section class="sig-review-header">
            <div>
              <span class="sig-review-header__eyebrow">Solicitud en revision</span>
              <h4 class="sig-review-header__title mb-1">{{ $nombreDocumento }}</h4>
              <p class="sig-review-header__subtitle mb-0">{{ $revision['codigo'] ?? 'SIN CODIGO' }}</p>
            </div>
            <div class="sig-review-header__status">
              <span class="badge bg-warning text-dark">{{ $estado }}</span>
            </div>
          </section>

          <section class="sig-review-section">
            <div class="sig-review-section__header d-flex flex-column flex-lg-row align-items-lg-start justify-content-lg-between gap-3">
              <div>
                <h6 class="sig-review-section__title">Documento</h6>
                <p class="sig-review-section__hint mb-0">{{ $previewMensaje }}</p>
              </div>
              <a
                href="{{ $previewUrl }}"
                target="_blank"
                rel="noopener noreferrer"
                class="btn btn-outline-secondary btn-sm">
                Abrir archivo
              </a>
            </div>

            <div class="sig-pdf-viewer sig-review-pdf-viewer">
              <iframe
                class="sig-pdf-frame"
                src="{{ $previewFrameUrl }}"
                title="Vista previa del documento"
                loading="lazy"></iframe>
            </div>

            @if($previewEsDemo)
              <p class="sig-pdf-note mt-2 mb-0">Se esta mostrando el PDF de prueba configurado para el modulo SIG.</p>
            @endif
          </section>

          <section class="sig-review-section">
            <div class="sig-review-section__header">
              <h6 class="sig-review-section__title">Resumen</h6>
            </div>
            <div class="sig-review-grid">
              <article class="sig-review-field">
                <span class="sig-review-field__label">Tipo</span>
                <strong class="sig-review-field__value">{{ $tipoSolicitud }}</strong>
              </article>
              <article class="sig-review-field">
                <span class="sig-review-field__label">Emision</span>
                <strong class="sig-review-field__value">{{ $revision['version'] ?: 'Sin asignar' }}</strong>
              </article>
              <article class="sig-review-field">
                <span class="sig-review-field__label">Archivo</span>
                <strong class="sig-review-field__value">{{ $revision['archivo_url'] ?: 'Sin archivo asociado' }}</strong>
              </article>
              <article class="sig-review-field">
                <span class="sig-review-field__label">Paginas</span>
                <strong class="sig-review-field__value">{{ $revision['paginas'] ?: 'No registradas' }}</strong>
              </article>
              <article class="sig-review-field">
                <span class="sig-review-field__label">Fecha de elaboracion</span>
                <strong class="sig-review-field__value">{{ $fechaElaboracion }}</strong>
              </article>
              <article class="sig-review-field">
                <span class="sig-review-field__label">Documento</span>
                <strong class="sig-review-field__value">#{{ $revision['documento_id'] ?? 'N/D' }}</strong>
              </article>
            </div>
          </section>

          <section class="sig-review-section">
            <div class="sig-review-section__header">
              <h6 class="sig-review-section__title">Responsables</h6>
            </div>
            <div class="sig-review-people">
              <article class="sig-review-field sig-review-field--wide">
                <span class="sig-review-field__label">Elaboro</span>
                <strong class="sig-review-field__value">{{ $elaboro }}</strong>
              </article>
              <article class="sig-review-field sig-review-field--wide">
                <span class="sig-review-field__label">Reviso</span>
                <strong class="sig-review-field__value">{{ $reviso }}</strong>
              </article>
              <article class="sig-review-field sig-review-field--wide">
                <span class="sig-review-field__label">Aprueba</span>
                <strong class="sig-review-field__value">{{ $aprueba }}</strong>
              </article>
            </div>
          </section>

          <section class="sig-review-section">
            <div class="sig-review-section__header">
              <h6 class="sig-review-section__title">Comentario del solicitante</h6>
            </div>
            <div class="sig-review-note">
              {{ $comentarioSolicitud }}
            </div>
          </section>
        </div>
      </div>

      <div class="col-12 col-xl-4">
        <div class="sig-review-sidebar">
          <section class="sig-review-section sig-review-section--sticky">
            <div class="sig-review-section__header">
              <h6 class="sig-review-section__title">Decision</h6>
              <p class="sig-review-section__hint mb-0">Registra un comentario y luego toma una decision sobre esta solicitud.</p>
            </div>

            <div class="mb-3">
              <label for="sigRevisionRevisa" class="form-label fw-semibold">Revisa</label>
              <select id="sigRevisionRevisa" class="form-select">
                <option value="">Seleccione</option>
                @foreach($ubicacionesRevisa as $ubic)
                  <option value="{{ $ubic->id }}" @selected($revisoSeleccionado === (string) $ubic->id)>{{ $ubic->nombre }}</option>
                @endforeach
              </select>
            </div>

            <div class="mb-3">
              <label for="sigRevisionAprueba" class="form-label fw-semibold">Aprueba</label>
              <select id="sigRevisionAprueba" class="form-select">
                <option value="">Seleccione</option>
                @foreach($ubicacionesAprueba as $ubic)
                  <option value="{{ $ubic->id }}" @selected($apruebaSeleccionado === (string) $ubic->id)>{{ $ubic->nombre }}</option>
                @endforeach
              </select>
            </div>

            <div class="mb-3">
              <label for="sigRevisionComentario" class="form-label fw-semibold">Comentario de revision</label>
              <textarea
                id="sigRevisionComentario"
                class="form-control"
                rows="6"
                maxlength="500"
                placeholder="Escribe la decision u observacion para el solicitante"></textarea>
              <div id="sigRevisionError" class="text-danger small mt-2 d-none"></div>
            </div>

            <div class="sig-review-actions sig-review-actions--stacked">
              <button type="button" class="btn btn-success sig-review-action" data-estado="APROBADO">
                Aprobar solicitud
              </button>
              <button type="button" class="btn btn-warning sig-review-action" data-estado="DEVUELTO">
                Devolver con observaciones
              </button>
              <button type="button" class="btn btn-danger sig-review-action" data-estado="RECHAZADO">
                Rechazar solicitud
              </button>
            </div>
          </section>
        </div>
      </div>
    </div>
  </div>
</div>
@endsection

@pushOnce('script')
<script>
  document.addEventListener('DOMContentLoaded', () => {
    const rutas = {
      APROBADO: "{{ route('mapa-procesos.emisiones.aprobar', ['id' => $revision['id']]) }}",
      RECHAZADO: "{{ route('mapa-procesos.emisiones.rechazar', ['id' => $revision['id']]) }}",
      DEVUELTO: "{{ route('mapa-procesos.emisiones.devolver', ['id' => $revision['id']]) }}",
      pendientes: "{{ route('mapa-procesos.emisiones.pendientes') }}",
    };

    let processing = false;

    const revisaSelect = document.getElementById('sigRevisionRevisa');
    const apruebaSelect = document.getElementById('sigRevisionAprueba');
    const textarea = document.getElementById('sigRevisionComentario');
    const errorEl = document.getElementById('sigRevisionError');
    const buttons = Array.from(document.querySelectorAll('.sig-review-action'));

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
      if (estado === 'DEVUELTO') return 'devolver';
      return 'rechazar';
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
        showError('Debes seleccionar quien revisa la solicitud.');
        revisaSelect?.focus();
        return;
      }
      if (!idAprueba) {
        showError('Debes seleccionar quien aprueba la solicitud.');
        apruebaSelect?.focus();
        return;
      }

      clearError();

      Swal.fire({
        title: `Deseas ${obtenerEtiquetaAccion(estado)} esta solicitud?`,
        text: 'La decision quedara registrada para el solicitante.',
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Confirmar',
        cancelButtonText: 'Cancelar',
        confirmButtonColor: estado === 'APROBADO' ? '#198754' : (estado === 'DEVUELTO' ? '#ffc107' : '#dc3545'),
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
