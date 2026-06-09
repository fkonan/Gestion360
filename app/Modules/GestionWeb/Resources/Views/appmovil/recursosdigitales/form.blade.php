@extends('layouts.dashboard')

@section('title', 'Gestion appmovil')

@section('breadcrumb')
<x-breadcrumb :items="[
        ['name' => 'Inicio', 'url' => route('home')],
        ['name' => 'Gestion appmovil', 'url' => route('gestion-appmovil.index')],
        ['name' => 'Recursos digitales', 'url' => $rutaIndice],
        ['name' => $currentType->clean_description, 'url' => $rutaDetalleTipo],
        ['name' => $tituloAccion],
    ]" />
<br>
@endsection

@section('content')
<div class="container-fluid p-0 border rounded sidebar-dark-primary tableContainer" style="min-height: 150px;">
  <x-sectionHeader
    titulo="{{ $typeConfig['section_title'] }}"
    rutaVolver="{{ $rutaDetalleTipo }}"
    :clasePosition=false />

  <form
    id="formBanner"
    class="py-5"
    action="{{ $formAction }}"
    method="POST"
    enctype="multipart/form-data"
    onsubmit="deshabilitarSubmit(this)">

    @csrf
    <input type="hidden" name="tipo" value="{{ $selectedTypeId }}">
    @if($httpMethod !== 'POST')
      @method($httpMethod)
    @endif

    <div class="row justify-content-center mx-2 gy-4">
      <div class="col-lg-7">
        <div class="card shadow rounded-3 p-4">
          <div class="mb-3">
            <h5 class="card-title fw-medium text-muted mb-1">{{ $tituloAccion }}</h5>
            <p class="small text-muted mb-0">{{ $descripcionAccion }}</p>
          </div>
          <hr>

          <div class="mb-4">
            <label for="textoAuxiliar" class="form-label">
              {{ $typeConfig['text_label'] }}
              @unless($typeConfig['text_required'])
                <span class="text-muted">(opcional)</span>
              @endunless
            </label>
            <input
              type="text"
              class="form-control form-control-sm"
              id="textoAuxiliar"
              name="textoAuxiliar"
              maxlength="200"
              value="{{ old('textoAuxiliar', $textoAuxiliar) }}">
            <div class="d-flex justify-content-between small">
              <span class="error text-danger fw-bold" id="error-textoAuxiliar"></span>
              <span class="text-muted" id="count-textoAuxiliar">{{ strlen((string) old('textoAuxiliar', $textoAuxiliar)) }} / 200</span>
            </div>
            <div class="form-text">{{ $typeConfig['text_help'] }}</div>
          </div>

          <div class="row g-3">
            @if($typeConfig['allows_file_upload'])
              <div class="col-md-6">
                <label for="archivo" class="form-label">{{ $typeConfig['file_label'] }}</label>
                <input
                  type="file"
                  class="form-control form-control-sm"
                  id="archivo"
                  name="archivo"
                  accept="{{ $acceptTypes }}">
                <span class="error text-danger fw-bold" id="error-archivo"></span>
                <div class="form-text">{{ $typeConfig['file_help'] }}</div>
              </div>
            @endif

            <div class="{{ $typeConfig['allows_file_upload'] ? 'col-md-6' : 'col-12' }}">
              <label for="url_externa" class="form-label">{{ $typeConfig['url_label'] }}</label>
              <input
                type="url"
                class="form-control form-control-sm"
                id="url_externa"
                name="url_externa"
                placeholder="{{ $typeConfig['url_placeholder'] }}"
                value="{{ old('url_externa') }}">
              <span class="error text-danger fw-bold" id="error-url_externa"></span>
              <div class="form-text">{{ $typeConfig['url_help'] }}</div>
            </div>
          </div>

          <div class="alert alert-light border mt-4 mb-4">
            {{ $alertaFormulario }}
          </div>

          <div class="row g-3">
            <div class="col-md-6">
              <label for="orden" class="form-label">Orden</label>
              <input
                type="number"
                class="form-control form-control-sm"
                id="orden"
                name="orden"
                min="1"
                value="{{ old('orden', $ordenDefault) }}"
                required>
              <span class="error text-danger fw-bold" id="error-orden"></span>
              <div class="form-text">Si el orden ya existe, los otros recursos del mismo tipo se ajustan automaticamente.</div>
            </div>

            <div class="col-md-6">
              <label for="estado" class="form-label">Estado</label>
              <select class="form-select form-select-sm" id="estado" name="estado" required>
                <option value="ACTIVO" @selected(old('estado', $estadoDefault) === 'ACTIVO')>ACTIVO</option>
                <option value="INACTIVO" @selected(old('estado', $estadoDefault) === 'INACTIVO')>INACTIVO</option>
              </select>
              <span class="error text-danger fw-bold" id="error-estado"></span>
            </div>
          </div>

          <div class="mt-4">
            <label for="url_actual" class="form-label">URL actual</label>
            <input
              type="text"
              class="form-control form-control-sm"
              id="url_actual"
              value="{{ $urlActualTexto }}"
              disabled>
          </div>

          <div class="mt-4">
            <button type="submit" class="btn btn-primary w-100 fw-bold py-2">
              <i class="fas {{ $iconoBoton }} me-1"></i> {{ $textoBoton }}
            </button>
          </div>
        </div>
      </div>

      <div class="col-lg-5">
        <div class="card shadow-sm p-4 rounded-3">
          <h6 class="text-center fw-bold text-primary mb-2">{{ $typeConfig['preview_title'] }}</h6>
          <hr class="my-2">

          <div id="previewImageWrapper" class="ratio ratio-16x9 bg-light rounded overflow-hidden border {{ $previewKind === 'image' ? '' : 'd-none' }}">
            <img
              id="resourceImagePreview"
              src="{{ $previewUrl }}"
              data-placeholder="{{ $placeholderPreview }}"
              class="w-100 h-100 object-fit-cover"
              alt="{{ $typeConfig['preview_title'] }}">
          </div>

          <div id="resourceDocumentPreview" class="border rounded bg-light-subtle text-center p-4 {{ $previewKind !== 'image' ? '' : 'd-none' }}">
            <div class="rounded-circle bg-primary-subtle d-inline-flex align-items-center justify-content-center mb-3" style="width: 86px; height: 86px;">
              <img
                id="previewDocumentImageIcon"
                src="{{ $previewIconAsset }}"
                alt="Icono del documento"
                class="{{ $previewIconType === 'image' ? '' : 'd-none' }}"
                style="width: 48px; height: 48px; object-fit: contain;">
              <i
                id="previewDocumentFontIcon"
                class="fas {{ $previewIconType === 'font' ? $previewIcon : 'fa-file-alt' }} fa-3x text-primary {{ $previewIconType === 'font' ? '' : 'd-none' }}"></i>
            </div>
            <div id="previewDocumentName" class="fw-semibold">{{ $previewDisplayName }}</div>
            <div id="previewDocumentLabel" class="small text-muted mt-2">{{ $previewLabel }}</div>
          </div>

          <div class="mt-3">
            <div class="small text-muted mb-1">{{ $typeConfig['preview_source_label'] }}</div>
            <div class="fw-semibold" id="previewFuente">{{ $initialSourceText }}</div>
          </div>

          @if($esEdicion)
            <a
              id="openCurrentResource"
              href="{{ $recurso->preview_url }}"
              target="_blank"
              rel="noopener noreferrer"
              class="btn btn-outline-secondary mt-3">
              {{ $typeConfig['open_current_label'] }}
            </a>
          @endif
        </div>

        <div class="card shadow-sm p-4 rounded-3 mt-4">
          <h6 class="fw-bold text-primary mb-2">Notas</h6>
          <ul class="small text-muted mb-0 ps-3">
            @foreach($notas as $nota)
              <li>{{ $nota }}</li>
            @endforeach
          </ul>
        </div>
      </div>
    </div>
  </form>
</div>
@endsection

@pushOnce('script')
@vite(['resources/js/cargarModal.js'])
<script>
  document.addEventListener('DOMContentLoaded', () => {
    const fileInput = document.getElementById('archivo');
    const urlInput = document.getElementById('url_externa');
    const textoAuxiliarInput = document.getElementById('textoAuxiliar');
    const previewMode = @json($typeConfig['preview_mode']);
    const previewImageWrapper = document.getElementById('previewImageWrapper');
    const previewImage = document.getElementById('resourceImagePreview');
    const previewDocument = document.getElementById('resourceDocumentPreview');
    const previewDocumentImageIcon = document.getElementById('previewDocumentImageIcon');
    const previewDocumentFontIcon = document.getElementById('previewDocumentFontIcon');
    const previewDocumentName = document.getElementById('previewDocumentName');
    const previewDocumentLabel = document.getElementById('previewDocumentLabel');
    const previewFuente = document.getElementById('previewFuente');
    const urlActual = document.getElementById('url_actual');
    const initialPreview = @json($previewUrl);
    const placeholderPreview = @json($placeholderPreview);
    const initialPreviewKind = @json($previewKind);
    const initialPreviewIcon = @json($previewIcon);
    const initialPreviewIconType = @json($previewIconType);
    const initialPreviewIconAsset = @json($previewIconAsset);
    const initialPreviewLabel = @json($previewLabel);
    const initialSourceText = @json($initialSourceText);
    const initialUrlText = @json($urlActualTexto);
    const defaultDocumentName = @json($defaultDocumentName);
    const pdfIconUrl = @json(asset('img/verPDF.png'));

    const updateCounter = (input, counterEl, max) => {
      if (!input || !counterEl) {
        return;
      }

      input.addEventListener('input', () => {
        counterEl.textContent = `${input.value.length} / ${max}`;
      });
    };

    const imageExtensions = ['png', 'jpg', 'jpeg', 'gif', 'webp'];

    const revokeObjectUrl = () => {
      if (previewImage.dataset.objectUrl) {
        URL.revokeObjectURL(previewImage.dataset.objectUrl);
        delete previewImage.dataset.objectUrl;
      }
    };

    const resolveResourceName = (fallback = '') => {
      const typedName = textoAuxiliarInput.value.trim();

      if (typedName !== '') {
        return typedName;
      }

      if (fallback !== '') {
        return fallback;
      }

      return defaultDocumentName;
    };

    const setPreviewMode = ({ kind, src = '', icon = 'fa-file-alt', iconType = 'font', iconAsset = '', label = 'Archivo', name = '', sourceText = initialSourceText, currentValue = initialUrlText }) => {
      previewFuente.textContent = sourceText;
      urlActual.value = currentValue;

      if (kind === 'image') {
        previewImageWrapper.classList.remove('d-none');
        previewDocument.classList.add('d-none');
        previewImage.src = src || placeholderPreview;

        return;
      }

      previewImageWrapper.classList.add('d-none');
      previewDocument.classList.remove('d-none');
      if (iconType === 'image' && iconAsset) {
        previewDocumentImageIcon.classList.remove('d-none');
        previewDocumentImageIcon.src = iconAsset;
        previewDocumentFontIcon.classList.add('d-none');
      } else {
        previewDocumentImageIcon.classList.add('d-none');
        previewDocumentFontIcon.classList.remove('d-none');
        previewDocumentFontIcon.className = `fas ${icon} fa-3x text-primary`;
      }
      previewDocumentName.textContent = resolveResourceName(name);
      previewDocumentLabel.textContent = label;
    };

    const buildPreviewMeta = (value) => {
      const normalizedValue = (value || '').trim().split('#')[0].split('?')[0];
      const filename = normalizedValue.split('/').pop() || normalizedValue;
      const extension = filename.includes('.') ? filename.split('.').pop().toLowerCase() : '';

      if (previewMode === 'image' || imageExtensions.includes(extension)) {
        return {
          kind: 'image',
          icon: 'fa-image',
          label: 'Imagen',
        };
      }

      if (previewMode === 'link') {
        try {
          const parsedUrl = new URL(value);
          return {
            kind: 'link',
            icon: 'fa-link',
            iconType: 'font',
            iconAsset: '',
            label: parsedUrl.hostname.replace(/^www\./i, '') || 'Enlace',
          };
        } catch (error) {
          return {
            kind: 'link',
            icon: 'fa-link',
            iconType: 'font',
            iconAsset: '',
            label: 'Enlace',
          };
        }
      }

      if (extension === 'pdf') {
        return {
          kind: 'pdf',
          icon: 'fa-file-pdf',
          iconType: 'image',
          iconAsset: pdfIconUrl,
          label: 'PDF',
        };
      }

      return {
        kind: 'file',
        icon: 'fa-file-alt',
        iconType: 'font',
        iconAsset: '',
        label: extension ? extension.toUpperCase() : 'Archivo',
      };
    };

    const resetPreview = () => {
      revokeObjectUrl();

      setPreviewMode({
        kind: initialPreviewKind,
        src: initialPreview || placeholderPreview,
        icon: initialPreviewIcon,
        iconType: initialPreviewIconType,
        iconAsset: initialPreviewIconAsset,
        label: initialPreviewLabel,
        name: resolveResourceName(),
        sourceText: initialSourceText,
        currentValue: initialUrlText
      });
    };

    const updatePreviewWithUrl = (url) => {
      if (!url) {
        resetPreview();
        return;
      }

      revokeObjectUrl();

      const previewMeta = buildPreviewMeta(url);

      setPreviewMode({
        kind: previewMeta.kind,
        src: previewMeta.kind === 'image' ? url : '',
        icon: previewMeta.icon,
        iconType: previewMeta.iconType || 'font',
        iconAsset: previewMeta.iconAsset || '',
        label: previewMeta.label,
        name: resolveResourceName(),
        sourceText: 'URL externa ingresada',
        currentValue: url
      });
    };

    if (fileInput) {
      fileInput.addEventListener('change', () => {
        const file = fileInput.files && fileInput.files[0];

        if (!file) {
          if (urlInput.value.trim()) {
            updatePreviewWithUrl(urlInput.value.trim());
            return;
          }

          resetPreview();
          return;
        }

        revokeObjectUrl();
        urlInput.value = '';
        const previewMeta = buildPreviewMeta(file.name);

        if (previewMeta.kind === 'image') {
          const objectUrl = URL.createObjectURL(file);
          previewImage.dataset.objectUrl = objectUrl;

          setPreviewMode({
            kind: 'image',
            src: objectUrl,
            icon: previewMeta.icon,
            iconType: previewMeta.iconType || 'font',
            iconAsset: previewMeta.iconAsset || '',
            label: previewMeta.label,
            name: resolveResourceName(file.name),
            sourceText: `Archivo seleccionado: ${file.name}`,
            currentValue: file.name
          });

          return;
        }

        setPreviewMode({
          kind: previewMeta.kind,
          icon: previewMeta.icon,
          iconType: previewMeta.iconType || 'font',
          iconAsset: previewMeta.iconAsset || '',
          label: previewMeta.label,
          name: resolveResourceName(file.name),
          sourceText: `Archivo seleccionado: ${file.name}`,
          currentValue: file.name
        });
      });
    }

    urlInput.addEventListener('input', () => {
      const url = urlInput.value.trim();

      if (url !== '') {
        if (fileInput) {
          fileInput.value = '';
        }
        updatePreviewWithUrl(url);
        return;
      }

      resetPreview();
    });

    textoAuxiliarInput.addEventListener('input', () => {
      if (!previewDocument.classList.contains('d-none')) {
        previewDocumentName.textContent = resolveResourceName();
      }
    });

    previewImage.addEventListener('error', () => {
      if (previewImage.dataset.objectUrl) {
        return;
      }

      previewImage.src = placeholderPreview;
      previewFuente.textContent = 'No fue posible cargar la vista previa con la URL indicada';
      urlActual.value = initialUrlText;
    });

    updateCounter(textoAuxiliarInput, document.getElementById('count-textoAuxiliar'), 200);
    resetPreview();
  });

  $(document).ready(function() {
    validarFormulario('#formBanner');
  });
</script>
@endpushOnce
