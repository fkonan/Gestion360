@extends('layouts.dashboard')

@section('title', 'Gestion appmovil')

@section('breadcrumb')
<x-breadcrumb :items="[
        ['name' => 'Inicio', 'url' => route('home')],
        ['name' => 'Gestion appmovil', 'url' => route('gestion-appmovil.index')],
        ['name' => 'Recursos digitales', 'url' => route('recursos-digitales.index')],
        ['name' => $currentType->clean_description],
    ]" />
<br>
@endsection

@push('css')
@vite('resources/css/gestionweb/recursosdigitales-show.css')
@endpush

@section('content')
<div class="container-fluid p-0 border rounded sidebar-dark-primary tableContainer" style="min-height: 150px;">
  <x-sectionHeader
    titulo="{{ $typeConfig['section_title'] }}"
    rutaVolver="{{ route('recursos-digitales.index') }}"
    :crear="false" />

  <div class="px-4 pt-3 pb-4">
    @php
      $isLinkFamily = ($typeConfig['resource_family'] ?? '') === 'link';
      $primaryLink = $isLinkFamily ? $recursos->first() : null;
    @endphp

    <div class="card shadow-sm border-0 mb-4">
      <div class="card-body p-4">
        <div class="d-flex flex-column flex-xl-row justify-content-between gap-3">
          <div>
            <div class="d-flex flex-wrap align-items-center gap-2 mb-2">
              <span class="badge text-bg-{{ $currentType->family_config['accent'] }}">
                <i class="fas {{ $currentType->family_config['icon'] }} me-1"></i>{{ $currentType->family_config['title'] }}
              </span>
            </div>
            <h4 class="mb-1">{{ $currentType->clean_description }}</h4>
            <p class="text-muted mb-0">{{ $typeConfig['list_intro'] }}</p>
          </div>

          @if(!($isLinkFamily && $primaryLink))
            <div class="d-flex align-items-start align-items-xl-center gap-2">
              <a class="btn btn-warning fw-bold" href="{{ route('recursos-digitales.create', ['tipo' => $currentType->IdTipoRecurso]) }}">
                {{ $typeConfig['create_label'] }}
              </a>
            </div>
          @endif
        </div>
      </div>
    </div>

    @if($recursos->isEmpty())
      <div class="alert alert-warning mb-0 d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3">
        <div>
          <strong>No hay {{ $typeConfig['plural_label'] }}</strong> registrados para este tipo.
        </div>
        <a class="btn btn-warning btn-sm fw-bold align-self-start align-self-lg-auto" href="{{ route('recursos-digitales.create', ['tipo' => $currentType->IdTipoRecurso]) }}">
          {{ $typeConfig['create_label'] }}
        </a>
      </div>
    @elseif($typeConfig['detail_layout'] === 'gallery')
      <div class="d-flex flex-column flex-sm-row justify-content-between align-items-sm-center gap-2 px-1 mb-3">
        <div class="small text-muted">
          Vista compacta por miniaturas. Haz clic en la imagen para ampliarla y usa <i class="fas fa-grip-vertical"></i> para reordenar.
        </div>
        <span class="badge rounded-pill text-bg-light border">{{ $recursos->count() }} banners</span>
      </div>

      <div
        class="rd-banner-grid"
        id="rdBannerGrid"
        data-reorder-url="{{ route('recursos-digitales.reordenar', ['tipo' => $currentType->IdTipoRecurso]) }}">
        @foreach($recursos as $recurso)
          @php
            $estadoTexto = $recurso->es_activo ? 'Activo' : 'Inactivo';
            $estadoBadgeClass = $recurso->es_activo ? 'text-bg-success' : 'text-bg-secondary';
          @endphp

          <article
            class="card h-100 shadow-sm rd-banner-card {{ $recurso->es_activo ? '' : 'rd-banner-card-inactive' }}"
            data-recurso-card
            data-recurso-id="{{ $recurso->IdRecurso }}">
            <button
              type="button"
              class="rd-banner-thumb-btn"
              data-bs-toggle="modal"
              data-bs-target="#bannerPreviewModal"
              data-recurso-id="{{ $recurso->IdRecurso }}"
              data-preview-url="{{ $recurso->preview_url }}"
              data-preview-name="{{ $recurso->display_name }}"
              data-preview-order="{{ $recurso->Orden }}"
              data-preview-status="{{ $estadoTexto }}"
              data-preview-source="{{ $recurso->source_label }}"
              data-preview-short-url="{{ $recurso->short_url }}"
              data-edit-url="{{ route('recursos-digitales.edit', ['id' => $recurso->IdRecurso]) }}"
              aria-label="Ampliar {{ $recurso->display_name }}">
              <div class="position-relative ratio ratio-16x9 rd-banner-thumb">
                <img
                  src="{{ $recurso->preview_url }}"
                  data-placeholder="{{ $placeholderPreview }}"
                  data-recurso-thumb
                  onerror="this.onerror=null;this.src=this.dataset.placeholder;"
                  class="w-100 h-100 object-fit-cover"
                  alt="{{ $recurso->display_name }}">
                <div class="rd-banner-chip-group">
                  <span class="rd-banner-chip rd-banner-chip-order" data-order-badge>Orden {{ $recurso->Orden }}</span>
                  <span class="badge rd-banner-chip rd-banner-chip-status {{ $estadoBadgeClass }}" data-estado-badge>{{ $estadoTexto }}</span>
                </div>
                <div class="rd-banner-overlay">
                  <i class="fas fa-expand-alt"></i> Vista grande
                </div>
              </div>
            </button>

            <div class="rd-banner-card-body">
              <div class="rd-banner-meta-name fw-semibold small" title="{{ $recurso->display_name }}">
                {{ $recurso->display_name }}
              </div>

              <div class="rd-banner-footer">
                <div class="d-flex align-items-center gap-2">
                  <button
                    type="button"
                    class="btn btn-sm rd-drag-handle"
                    data-drag-handle
                    title="Arrastrar para reordenar"
                    aria-label="Arrastrar para reordenar"
                    draggable="true">
                    <i class="fas fa-grip-vertical"></i>
                  </button>
                  <span class="rd-banner-state-label">Estado</span>
                  <div class="form-check form-switch m-0">
                    <input
                      class="form-check-input"
                      type="checkbox"
                      role="switch"
                      data-recurso-id="{{ $recurso->IdRecurso }}"
                      {{ $recurso->es_activo ? 'checked' : '' }}
                      onchange="actualizarEstadoRecurso(this, '{{ route('recursos-digitales.cambiarEstado', ['id' => $recurso->IdRecurso]) }}')">
                  </div>
                </div>
                <a class="btn btn-primary btn-sm rd-banner-edit-btn" href="{{ route('recursos-digitales.edit', ['id' => $recurso->IdRecurso]) }}">
                  Editar
                </a>
              </div>
            </div>
          </article>
        @endforeach
      </div>

      <div class="modal fade" id="bannerPreviewModal" tabindex="-1" aria-labelledby="bannerPreviewModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-centered">
          <div class="modal-content border-0 shadow">
            <div class="modal-header">
              <div>
                <h5 class="modal-title" id="bannerPreviewModalLabel">Vista previa del banner</h5>
                <div class="small text-muted" id="bannerPreviewModalSource">Recurso seleccionado</div>
              </div>
              <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
              <div class="border rounded overflow-hidden bg-light-subtle">
                <img
                  id="bannerPreviewModalImage"
                  src="{{ $placeholderPreview }}"
                  data-placeholder="{{ $placeholderPreview }}"
                  alt="Vista previa de banner">
              </div>
              <div class="d-flex flex-wrap align-items-center gap-2 mt-3">
                <span class="badge rounded-pill text-bg-light border" id="bannerPreviewModalOrder">Orden --</span>
                <span class="badge rounded-pill text-bg-light border" id="bannerPreviewModalStatus">Sin estado</span>
                <span class="badge rounded-pill text-bg-light border" id="bannerPreviewModalType">Banner</span>
              </div>
              <div class="small text-muted mt-3 text-break" id="bannerPreviewModalUrl"></div>
            </div>
            <div class="modal-footer">
              <a
                id="bannerPreviewModalOpen"
                href="{{ $placeholderPreview }}"
                target="_blank"
                rel="noopener noreferrer"
                class="btn btn-outline-secondary">
                Abrir imagen
              </a>
              <a id="bannerPreviewModalEdit" href="#" class="btn btn-primary">Editar banner</a>
            </div>
          </div>
        </div>
      </div>
    @else
      @if($isLinkFamily)
        @php
          $primaryLink = $recursos->first();
          $extraLinks = $recursos->slice(1);
          $estadoPrincipalTexto = $primaryLink && $primaryLink->es_activo ? 'Activo' : 'Inactivo';
          $estadoPrincipalBadgeClass = $primaryLink && $primaryLink->es_activo ? 'text-bg-success' : 'text-bg-secondary';
        @endphp

        <div class="d-flex flex-column flex-sm-row justify-content-between align-items-sm-center gap-2 px-1 mb-3">
          <div class="small text-muted">
            Este tipo maneja un acceso unico. Aqui editas el enlace principal que consume la app movil.
          </div>
          <span class="badge rounded-pill text-bg-light border">{{ $recursos->count() }} enlace{{ $recursos->count() === 1 ? '' : 's' }}</span>
        </div>

        @if($primaryLink)
          <article
            class="rd-link-access-card {{ $primaryLink->es_activo ? '' : 'rd-link-access-card-inactive' }}"
            data-recurso-card
            data-recurso-id="{{ $primaryLink->IdRecurso }}">
            <header class="rd-link-access-head">
              <div>
                <div class="d-flex align-items-center gap-2 flex-wrap mb-2">
                  <span class="badge rounded-pill text-bg-light border">Orden {{ $primaryLink->Orden }}</span>
                  <span class="badge rounded-pill text-bg-light border">{{ $primaryLink->preview_label }}</span>
                  <span class="badge rounded-pill text-bg-light border">{{ $primaryLink->source_label }}</span>
                </div>
                <h5 class="rd-link-access-title mb-0" title="{{ $primaryLink->display_name }}">{{ $primaryLink->display_name }}</h5>
              </div>
              <span class="badge {{ $estadoPrincipalBadgeClass }}" data-estado-badge>{{ $estadoPrincipalTexto }}</span>
            </header>

            <div class="rd-link-access-url-wrap">
              <div class="rd-link-access-url-label">URL destino</div>
              <div class="rd-link-access-url text-break" title="{{ $primaryLink->short_url }}">{{ $primaryLink->short_url }}</div>
            </div>

            <footer class="rd-link-access-footer">
              <div class="d-inline-flex align-items-center gap-2">
                <span class="rd-resource-state-label">Estado</span>
                <div class="form-check form-switch m-0">
                  <input
                    class="form-check-input"
                    type="checkbox"
                    role="switch"
                    data-recurso-id="{{ $primaryLink->IdRecurso }}"
                    {{ $primaryLink->es_activo ? 'checked' : '' }}
                    onchange="actualizarEstadoRecurso(this, '{{ route('recursos-digitales.cambiarEstado', ['id' => $primaryLink->IdRecurso]) }}')">
                </div>
              </div>

              <div class="d-flex align-items-center gap-2">
                <a class="btn btn-outline-secondary btn-sm rd-resource-open-btn" href="{{ $primaryLink->preview_url }}" target="_blank" rel="noopener noreferrer">
                  <i class="fas fa-external-link-alt me-1"></i>Abrir
                </a>
                <a class="btn btn-primary btn-sm rd-resource-edit-btn" href="{{ route('recursos-digitales.edit', ['id' => $primaryLink->IdRecurso]) }}">
                  Editar acceso
                </a>
              </div>
            </footer>
          </article>
        @endif

        @if($extraLinks->isNotEmpty())
          <div class="alert alert-warning mt-3 mb-2">
            Se detectaron {{ $extraLinks->count() }} enlace{{ $extraLinks->count() === 1 ? '' : 's' }} adicional{{ $extraLinks->count() === 1 ? '' : 'es' }} para este tipo. El flujo recomendado es manejar un solo acceso.
          </div>
          <div class="rd-link-extra-list">
            @foreach($extraLinks as $extraLink)
              <article class="rd-link-extra-item {{ $extraLink->es_activo ? '' : 'rd-link-extra-item-inactive' }}">
                <div>
                  <div class="fw-semibold">{{ $extraLink->display_name }}</div>
                  <div class="small text-muted text-break">{{ $extraLink->short_url }}</div>
                </div>
                <a class="btn btn-outline-primary btn-sm" href="{{ route('recursos-digitales.edit', ['id' => $extraLink->IdRecurso]) }}">
                  Editar
                </a>
              </article>
            @endforeach
          </div>
        @endif
      @else
        <div class="d-flex flex-column flex-sm-row justify-content-between align-items-sm-center gap-2 px-1 mb-3">
          <div class="small text-muted">
            Vista estructurada para {{ $typeConfig['plural_label'] }} con metadata, estado y acciones agrupadas.
          </div>
          <span class="badge rounded-pill text-bg-light border">{{ $recursos->count() }} {{ $typeConfig['plural_label'] }}</span>
        </div>

        <div class="rd-resource-grid">
          @foreach($recursos as $recurso)
            @php
              $estadoTexto = $recurso->es_activo ? 'Activo' : 'Inactivo';
              $estadoBadgeClass = $recurso->es_activo ? 'text-bg-success' : 'text-bg-secondary';
            @endphp

            <article
              class="rd-resource-card {{ $recurso->es_activo ? '' : 'rd-resource-card-inactive' }}"
              data-recurso-card
              data-recurso-id="{{ $recurso->IdRecurso }}">
              <header class="rd-resource-card-head">
                <div class="d-flex align-items-center gap-2 flex-wrap">
                  <span class="badge rounded-pill text-bg-light border rd-resource-chip-order">Orden {{ $recurso->Orden }}</span>
                  <span class="badge rounded-pill text-bg-light border">{{ $recurso->preview_label }}</span>
                </div>
                <span class="badge {{ $estadoBadgeClass }}" data-estado-badge>{{ $estadoTexto }}</span>
              </header>

              <div class="rd-resource-card-body">
                <div class="rd-resource-icon-wrap">
                  @if(($recurso->preview_icon_type ?? 'font') === 'image' && filled($recurso->preview_icon_asset))
                    <img src="{{ $recurso->preview_icon_asset }}" alt="PDF" class="rd-resource-icon-image">
                  @else
                    <i class="fas {{ $recurso->preview_icon }} rd-resource-icon-font"></i>
                  @endif
                </div>

                <div class="rd-resource-content">
                  <div class="rd-resource-title" title="{{ $recurso->display_name }}">{{ $recurso->display_name }}</div>
                  <div class="rd-resource-url text-break" title="{{ $recurso->short_url }}">{{ $recurso->short_url }}</div>
                  <div class="rd-resource-source">{{ $recurso->source_label }}</div>
                </div>
              </div>

              <footer class="rd-resource-card-footer">
                <div class="d-inline-flex align-items-center gap-2">
                  <span class="rd-resource-state-label">Estado</span>
                  <div class="form-check form-switch m-0">
                    <input
                      class="form-check-input"
                      type="checkbox"
                      role="switch"
                      data-recurso-id="{{ $recurso->IdRecurso }}"
                      {{ $recurso->es_activo ? 'checked' : '' }}
                      onchange="actualizarEstadoRecurso(this, '{{ route('recursos-digitales.cambiarEstado', ['id' => $recurso->IdRecurso]) }}')">
                  </div>
                </div>

                <div class="d-flex align-items-center gap-2">
                  <a class="btn btn-outline-secondary btn-sm rd-resource-open-btn" href="{{ $recurso->preview_url }}" target="_blank" rel="noopener noreferrer">
                    <i class="fas fa-external-link-alt me-1"></i>Abrir
                  </a>
                  <a class="btn btn-primary btn-sm rd-resource-edit-btn" href="{{ route('recursos-digitales.edit', ['id' => $recurso->IdRecurso]) }}">
                    Editar
                  </a>
                </div>
              </footer>
            </article>
          @endforeach
        </div>
      @endif
    @endif
  </div>
</div>
@endsection

@pushOnce('script')
<script>
  function actualizarEstadoBadge(badgeElement, isActive) {
    if (!badgeElement) {
      return;
    }

    badgeElement.classList.remove('text-bg-success', 'text-bg-secondary', 'text-bg-light', 'border');
    if (isActive) {
      badgeElement.classList.add('text-bg-success');
    } else {
      badgeElement.classList.add('text-bg-secondary');
    }
    badgeElement.textContent = isActive ? 'Activo' : 'Inactivo';
  }

  function sincronizarEstadoEnModal(recursoId, isActive) {
    const previewModal = document.getElementById('bannerPreviewModal');
    if (!previewModal || !previewModal.classList.contains('show')) {
      return;
    }

    if ((previewModal.dataset.currentRecursoId || '') !== String(recursoId)) {
      return;
    }

    const statusBadge = document.getElementById('bannerPreviewModalStatus');
    actualizarEstadoBadge(statusBadge, isActive);
  }

  function actualizarEstadoRecurso(element, url) {
    const checkedBeforeRequest = element.checked;
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

    $.ajax({
      url: url,
      type: 'POST',
      headers: csrfToken ? {
        'X-CSRF-TOKEN': csrfToken
      } : {},
      data: {
        _token: csrfToken
      },
      success: function(response) {
        if (response.type !== 'success') {
          element.checked = !checkedBeforeRequest;
          mostrarToast(response.message, response.type || 'primary');
          return;
        }

        const nuevoEstado = String(response.estado || (element.checked ? 'ACTIVO' : 'INACTIVO')).toUpperCase();
        const isActive = nuevoEstado === 'ACTIVO';
        const recursoId = element.dataset.recursoId;

        element.checked = isActive;

        const card = element.closest('[data-recurso-card]');
        actualizarEstadoBadge(card?.querySelector('[data-estado-badge]'), isActive);
        card?.classList.toggle('rd-banner-card-inactive', !isActive);
        card?.classList.toggle('rd-resource-card-inactive', !isActive);
        card?.classList.toggle('rd-link-access-card-inactive', !isActive);
        card?.querySelectorAll('[data-preview-status]').forEach((trigger) => {
          trigger.setAttribute('data-preview-status', isActive ? 'Activo' : 'Inactivo');
        });
        sincronizarEstadoEnModal(recursoId, isActive);

        mostrarToast(response.message, response.type || 'primary');
      },
      error: function(xhr) {
        element.checked = !checkedBeforeRequest;
        mostrarToast(xhr.responseJSON?.message || 'Error al cambiar el estado del recurso', 'danger');
      }
    });
  }

  function actualizarOrdenVisualTarjetas(gridElement) {
    if (!gridElement) {
      return;
    }

    Array.from(gridElement.querySelectorAll('[data-recurso-card]')).forEach((card, index) => {
      const orderNumber = index + 1;
      card.querySelectorAll('[data-order-badge]').forEach((badge) => {
        badge.textContent = `Orden ${orderNumber}`;
      });
      card.querySelectorAll('[data-preview-order]').forEach((trigger) => {
        trigger.setAttribute('data-preview-order', String(orderNumber));
      });
    });
  }

  function restaurarOrdenTarjetas(gridElement, orderSnapshot) {
    if (!gridElement || !Array.isArray(orderSnapshot)) {
      return;
    }

    const cardById = new Map(
      Array.from(gridElement.querySelectorAll('[data-recurso-card]')).map((card) => [card.dataset.recursoId, card])
    );

    orderSnapshot.forEach((resourceId) => {
      const card = cardById.get(String(resourceId));
      if (card) {
        gridElement.appendChild(card);
      }
    });

    actualizarOrdenVisualTarjetas(gridElement);
  }

  function inicializarReordenamientoBanners() {
    const gridElement = document.getElementById('rdBannerGrid');
    if (!gridElement) {
      return;
    }

    const reorderUrl = gridElement.dataset.reorderUrl;
    if (!reorderUrl) {
      return;
    }

    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    const placeholderElement = document.createElement('article');
    placeholderElement.className = 'rd-banner-drop-placeholder';
    placeholderElement.setAttribute('aria-hidden', 'true');
    const placeholderPreviewImage = document.createElement('img');
    placeholderPreviewImage.className = 'rd-banner-drop-placeholder-image';
    placeholderPreviewImage.alt = '';
    placeholderPreviewImage.setAttribute('aria-hidden', 'true');
    placeholderPreviewImage.addEventListener('error', function() {
      if (this.dataset.placeholder) {
        this.src = this.dataset.placeholder;
      }
    });
    const placeholderPreviewLabel = document.createElement('div');
    placeholderPreviewLabel.className = 'rd-banner-drop-placeholder-label';
    placeholderPreviewLabel.innerHTML = '<i class="fas fa-arrows-alt"></i> Soltar aqui';
    placeholderElement.appendChild(placeholderPreviewImage);
    placeholderElement.appendChild(placeholderPreviewLabel);
    let draggedCard = null;
    let previousOrder = [];
    let savingOrder = false;
    let dropHandled = false;
    let dragGuardTimer = null;
    let lastPlaceholderPosition = '';
    let lastDragClientY = null;

    const collectCurrentOrder = () => Array.from(gridElement.querySelectorAll('[data-recurso-card]'))
      .map((card) => Number(card.dataset.recursoId));

    const hasOrderChanged = (before, after) => {
      if (before.length !== after.length) {
        return true;
      }

      return after.some((id, index) => id !== before[index]);
    };

    const clearDragGuard = () => {
      if (dragGuardTimer) {
        clearTimeout(dragGuardTimer);
        dragGuardTimer = null;
      }
    };

    const removePlaceholder = () => {
      if (placeholderElement.parentNode) {
        placeholderElement.parentNode.removeChild(placeholderElement);
      }
      lastPlaceholderPosition = '';
    };

    const placePlaceholder = (targetCard, insertAfter) => {
      if (!targetCard || targetCard === draggedCard) {
        removePlaceholder();
        return;
      }

      const targetPositionKey = `${targetCard.dataset.recursoId || ''}:${insertAfter ? 'after' : 'before'}`;
      if (lastPlaceholderPosition === targetPositionKey) {
        return;
      }
      lastPlaceholderPosition = targetPositionKey;

      if (insertAfter) {
        if (targetCard.nextSibling !== placeholderElement) {
          gridElement.insertBefore(placeholderElement, targetCard.nextSibling);
        }
        return;
      }

      if (targetCard !== placeholderElement.nextSibling) {
        gridElement.insertBefore(placeholderElement, targetCard);
      }
    };

    const preparePlaceholder = (card) => {
      const cardRect = card.getBoundingClientRect();
      const targetHeight = Math.max(220, Math.round(cardRect.height));
      placeholderElement.style.height = `${targetHeight}px`;
      const cardImage = card.querySelector('[data-recurso-thumb]');
      const previewSource = cardImage?.getAttribute('src') || '';
      const placeholderSource = cardImage?.dataset?.placeholder || '';
      placeholderPreviewImage.src = previewSource || placeholderSource;
      placeholderPreviewImage.dataset.placeholder = placeholderSource;
    };

    const resetDragState = () => {
      clearDragGuard();
      if (draggedCard) {
        draggedCard.classList.remove('rd-banner-card-dragging');
        draggedCard.classList.remove('rd-banner-card-source-hidden');
      }
      removePlaceholder();
      draggedCard = null;
      previousOrder = [];
      dropHandled = false;
      lastDragClientY = null;
    };

    const persistirNuevoOrden = (beforeOrder) => {
      const newOrder = collectCurrentOrder();

      if (!hasOrderChanged(beforeOrder, newOrder) || savingOrder) {
        actualizarOrdenVisualTarjetas(gridElement);
        return;
      }

      savingOrder = true;
      gridElement.classList.add('rd-banner-grid-saving');
      actualizarOrdenVisualTarjetas(gridElement);

      $.ajax({
        url: reorderUrl,
        type: 'POST',
        timeout: 10000,
        headers: csrfToken ? {
          'X-CSRF-TOKEN': csrfToken
        } : {},
        data: {
          _token: csrfToken,
          orden: newOrder
        },
        success: function(response) {
          mostrarToast(response.message || 'Orden actualizado correctamente', response.type || 'success');
        },
        error: function(xhr) {
          restaurarOrdenTarjetas(gridElement, beforeOrder);
          mostrarToast(xhr.responseJSON?.message || 'No fue posible actualizar el orden de los banners', 'danger');
        },
        complete: function() {
          savingOrder = false;
          gridElement.classList.remove('rd-banner-grid-saving');
        }
      });
    };

    gridElement.addEventListener('dragstart', (event) => {
      const card = event.target.closest('[data-recurso-card]');
      const dragHandle = event.target.closest('[data-drag-handle]');

      if (!card) {
        return;
      }

      if (!dragHandle || savingOrder) {
        event.preventDefault();
        return;
      }

      draggedCard = card;
      previousOrder = collectCurrentOrder();
      lastDragClientY = null;
      preparePlaceholder(card);
      card.classList.add('rd-banner-card-dragging');
      dropHandled = false;
      removePlaceholder();
      gridElement.insertBefore(placeholderElement, card.nextSibling);
      clearDragGuard();
      dragGuardTimer = setTimeout(() => {
        if (draggedCard) {
          resetDragState();
          actualizarOrdenVisualTarjetas(gridElement);
        }
      }, 12000);
      setTimeout(() => {
        if (draggedCard === card) {
          card.classList.add('rd-banner-card-source-hidden');
        }
      }, 0);

      if (event.dataTransfer) {
        event.dataTransfer.effectAllowed = 'move';
        event.dataTransfer.setData('text/plain', card.dataset.recursoId || '');
        if (typeof event.dataTransfer.setDragImage === 'function') {
          try {
            event.dataTransfer.setDragImage(card, Math.round(card.clientWidth / 2), 28);
          } catch (error) {
            // Fallback to native drag preview when setDragImage is not supported by the browser.
          }
        }
      }
    });

    gridElement.addEventListener('dragover', (event) => {
      if (!draggedCard || savingOrder) {
        return;
      }

      event.preventDefault();

      const targetCard = event.target.closest('[data-recurso-card]');
      if (!targetCard || targetCard === draggedCard) {
        return;
      }

      const targetRect = targetCard.getBoundingClientRect();
      const relativeY = Math.min(1, Math.max(0, (event.clientY - targetRect.top) / targetRect.height));
      const movementDeltaY = lastDragClientY === null ? 0 : (event.clientY - lastDragClientY);
      lastDragClientY = event.clientY;

      const movingDown = movementDeltaY > 2;
      const movingUp = movementDeltaY < -2;
      let shouldInsertAfter = relativeY > 0.5;

      // Bias the drop decision by movement direction so position changes faster.
      if (movingDown) {
        shouldInsertAfter = relativeY > 0.22;
      } else if (movingUp) {
        shouldInsertAfter = relativeY > 0.78;
      }

      placePlaceholder(targetCard, shouldInsertAfter);
    });

    gridElement.addEventListener('drop', (event) => {
      if (!draggedCard || savingOrder) {
        return;
      }

      event.preventDefault();
      dropHandled = true;
      const beforeOrder = previousOrder.slice();

      if (placeholderElement.parentNode) {
        gridElement.insertBefore(draggedCard, placeholderElement);
      }

      resetDragState();
      persistirNuevoOrden(beforeOrder);
    });

    gridElement.addEventListener('dragend', () => {
      if (!draggedCard) {
        return;
      }

      if (dropHandled) {
        resetDragState();
        return;
      }

      resetDragState();
      if (!savingOrder) {
        actualizarOrdenVisualTarjetas(gridElement);
      }
    });

    document.addEventListener('drop', (event) => {
      if (!draggedCard) {
        return;
      }

      if (gridElement.contains(event.target)) {
        return;
      }

      resetDragState();
      actualizarOrdenVisualTarjetas(gridElement);
    }, true);

    document.addEventListener('visibilitychange', () => {
      if (!document.hidden || !draggedCard) {
        return;
      }

      resetDragState();
      actualizarOrdenVisualTarjetas(gridElement);
    });

    actualizarOrdenVisualTarjetas(gridElement);
  }

  document.addEventListener('DOMContentLoaded', () => {
    inicializarReordenamientoBanners();

    const previewModal = document.getElementById('bannerPreviewModal');
    if (!previewModal) {
      return;
    }

    const previewImage = document.getElementById('bannerPreviewModalImage');
    const previewSource = document.getElementById('bannerPreviewModalSource');
    const previewOrder = document.getElementById('bannerPreviewModalOrder');
    const previewStatus = document.getElementById('bannerPreviewModalStatus');
    const previewType = document.getElementById('bannerPreviewModalType');
    const previewUrlText = document.getElementById('bannerPreviewModalUrl');
    const previewOpenLink = document.getElementById('bannerPreviewModalOpen');
    const previewEditLink = document.getElementById('bannerPreviewModalEdit');

    previewModal.addEventListener('show.bs.modal', function(event) {
      const trigger = event.relatedTarget;
      if (!trigger) {
        return;
      }

      const recursoId = trigger.getAttribute('data-recurso-id') || '';
      const previewUrl = trigger.getAttribute('data-preview-url') || previewImage.dataset.placeholder;
      const previewName = trigger.getAttribute('data-preview-name') || 'Banner';
      const previewOrderValue = trigger.getAttribute('data-preview-order') || '--';
      const previewStatusValue = (trigger.getAttribute('data-preview-status') || 'Inactivo').toLowerCase() === 'activo';
      const previewSourceValue = trigger.getAttribute('data-preview-source') || 'Recurso configurado';
      const previewShortUrl = trigger.getAttribute('data-preview-short-url') || '';
      const editUrl = trigger.getAttribute('data-edit-url') || '#';

      this.dataset.currentRecursoId = recursoId;

      previewImage.src = previewUrl;
      previewImage.alt = previewName;
      previewSource.textContent = previewName;
      previewOrder.textContent = `Orden ${previewOrderValue}`;
      previewType.textContent = previewSourceValue;
      previewUrlText.textContent = previewShortUrl;
      previewOpenLink.href = previewUrl;
      previewEditLink.href = editUrl;
      actualizarEstadoBadge(previewStatus, previewStatusValue);
    });

    previewImage.addEventListener('error', function() {
      this.src = this.dataset.placeholder;
    });
  });
</script>
@endpushOnce
