@extends('layouts.dashboard')

@section('title', 'Prueba camara IP')

@section('breadcrumb')
<x-breadcrumb :items="[
        ['name' => 'Inicio', 'url' => route('home')],
        ['name' => 'Reconocimiento facial', 'url' => route('reconocimientoFacial.index')],
        ['name' => 'Prueba camara IP'],
    ]" />
<br>
@endsection

@section('content')
<div class="container-fluid p-0 border rounded sidebar-dark-primary tableContainer" style="min-height:150px;">
  <x-sectionHeader titulo="Prueba camara IP" rutaVolver="{{ route('reconocimientoFacial.index') }}" :crear="false" />

  <div class="p-4">
    <div class="alert alert-info mb-3">
      Esta vista es de prueba y no utiliza el flujo actual de webcam/reconocimiento facial.
    </div>

    @if(empty($cameras))
      <div class="alert alert-warning mb-0">
        No hay camaras IP habilitadas en <code>CAMERAS_JSON</code>.
      </div>
    @else
      <div id="ipCameraRoutes" class="d-none"
        data-snapshot-url="{{ route('camera.ip.snapshot') }}"
        data-mjpeg-url="{{ route('camera.ip.mjpeg') }}"
        data-diagnostic-url="{{ route('camera.ip.diagnostic') }}"
        data-default-camera="{{ $defaultCameraId }}"></div>

      <div class="card">
        <div class="card-body d-flex flex-column gap-3">
          <div class="row g-3 align-items-end">
            <div class="col-12 col-lg-5">
              <label class="form-label" for="ipCameraSelect">Camara</label>
              <select id="ipCameraSelect" class="form-select">
                @foreach($cameras as $camera)
                  <option value="{{ $camera['id'] }}"
                    data-name="{{ $camera['name'] }}"
                    data-width="{{ $camera['width'] ?? '' }}"
                    data-height="{{ $camera['height'] ?? '' }}"
                    data-distance="{{ $camera['max_face_distance_meters'] ?? '' }}">
                    {{ $camera['name'] }} ({{ $camera['id'] }})
                  </option>
                @endforeach
              </select>
            </div>

            <div class="col-12 col-lg-auto">
              <button id="ipCameraRefreshBtn" class="btn btn-outline-primary w-100" type="button">
                Reconectar stream
              </button>
            </div>

            <div class="col-12 col-lg-auto">
              <button id="ipCameraDiagBtn" class="btn btn-outline-secondary w-100" type="button">
                Diagnosticar
              </button>
            </div>

            <div class="col-12 col-lg">
              <div class="d-flex justify-content-lg-end">
                <span id="ipCameraStatus" class="badge rounded-pill bg-secondary">Sin cargar</span>
              </div>
            </div>
          </div>

          <div class="ratio ratio-16x9 bg-dark rounded overflow-hidden">
            <img id="ipCameraFrame" class="w-100 h-100 object-fit-cover" alt="Vista camara IP" />
          </div>

          <div class="d-flex flex-wrap justify-content-between gap-2">
            <div id="ipCameraMeta" class="small text-muted"></div>
            <div class="small text-muted">Streaming MJPEG en vivo.</div>
          </div>

          <div id="ipCameraError" class="alert alert-danger py-2 px-3 mb-0 d-none"></div>
        </div>
      </div>
    @endif
  </div>
</div>
@endsection

@push('script')
  @if(!empty($cameras))
    <script>
      (() => {
        const routes = document.getElementById('ipCameraRoutes');
        const select = document.getElementById('ipCameraSelect');
        const refreshBtn = document.getElementById('ipCameraRefreshBtn');
        const diagBtn = document.getElementById('ipCameraDiagBtn');
        const status = document.getElementById('ipCameraStatus');
        const frame = document.getElementById('ipCameraFrame');
        const meta = document.getElementById('ipCameraMeta');
        const errorBox = document.getElementById('ipCameraError');
        const mjpegUrl = routes?.dataset?.mjpegUrl || '';
        const diagnosticUrl = routes?.dataset?.diagnosticUrl || '';
        const defaultCamera = routes?.dataset?.defaultCamera || '';

        let currentStreamUrl = '';

        const setStatus = (label, cssClass) => {
          status.className = `badge rounded-pill ${cssClass}`;
          status.textContent = label;
        };

        const showError = (message) => {
          if (!message) {
            errorBox.classList.add('d-none');
            errorBox.textContent = '';
            return;
          }

          errorBox.textContent = message;
          errorBox.classList.remove('d-none');
        };

        const updateMeta = () => {
          const option = select.options[select.selectedIndex];
          if (!option) {
            meta.textContent = '';
            return;
          }

          const width = option.dataset.width || '-';
          const height = option.dataset.height || '-';
          const maxDistance = option.dataset.distance || '-';
          meta.textContent = `${option.dataset.name || option.text} | ${width}x${height} | Distancia max: ${maxDistance}m`;
        };

        const stopStream = () => {
          if (!frame) {
            return;
          }
          frame.onload = null;
          frame.onerror = null;
          frame.src = '';
          currentStreamUrl = '';
        };

        const startStream = () => {
          if (!mjpegUrl) {
            showError('No se encontro la URL de stream MJPEG.');
            return;
          }

          const cameraId = select.value || '';
          if (!cameraId) {
            setStatus('Sin camara', 'bg-warning');
            showError('Selecciona una camara para iniciar la prueba.');
            return;
          }

          stopStream();
          showError('');
          updateMeta();
          setStatus('Conectando...', 'bg-warning');

          const requestUrl = new URL(mjpegUrl, window.location.origin);
          requestUrl.searchParams.set('camera_id', cameraId);
          requestUrl.searchParams.set('_', Date.now().toString());

          currentStreamUrl = requestUrl.toString();

          frame.onload = () => {
            setStatus('En vivo', 'bg-success');
          };

          frame.onerror = () => {
            setStatus('Sin senal', 'bg-danger');
            showError('No se pudo abrir el stream en vivo. Usa "Diagnosticar" para ver el detalle.');
          };

          frame.src = currentStreamUrl;
        };

        if (defaultCamera) {
          select.value = defaultCamera;
        }

        select.addEventListener('change', () => {
          startStream();
        });

        refreshBtn.addEventListener('click', () => {
          startStream();
        });

        diagBtn.addEventListener('click', async () => {
          if (!diagnosticUrl) {
            return;
          }

          const cameraId = select.value || '';
          if (!cameraId) {
            showError('Selecciona una camara para ejecutar diagnostico.');
            return;
          }

          const requestUrl = new URL(diagnosticUrl, window.location.origin);
          requestUrl.searchParams.set('camera_id', cameraId);
          requestUrl.searchParams.set('_', Date.now().toString());

          try {
            const response = await fetch(requestUrl.toString(), {
              method: 'GET',
              cache: 'no-store',
              headers: {
                'X-Requested-With': 'XMLHttpRequest',
              },
            });

            const payload = await response.json().catch(() => null);
            if (!response.ok || !payload) {
              throw new Error('No se pudo ejecutar el diagnostico.');
            }

            console.info('[camera-ip][diagnostic]', payload);
            window.alert(
              `Diagnostico ejecutado.\\n\\n` +
              `TCP socket: ${payload?.tcp_check?.ok ? 'OK' : 'FALLO'}\\n` +
              `FFMPEG TCP: ${payload?.ffmpeg_check_tcp?.ok ? 'OK' : 'FALLO'}\\n` +
              `FFMPEG UDP: ${payload?.ffmpeg_check_udp?.ok ? 'OK' : 'FALLO'}\\n\\n` +
              `Revisa la consola (F12) para el detalle completo.`
            );
          } catch (error) {
            window.alert(error instanceof Error ? error.message : 'No se pudo ejecutar el diagnostico.');
          }
        });

        document.addEventListener('visibilitychange', () => {
          if (document.hidden) {
            stopStream();
            return;
          }

          startStream();
        });

        window.addEventListener('beforeunload', () => {
          stopStream();
        });

        startStream();
      })();
    </script>
  @endif
@endpush
