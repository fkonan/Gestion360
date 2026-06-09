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

  <div class="p-4 ip-preview-page">
    @if(empty($cameras))
      <div class="alert alert-warning mb-0">
        No hay camaras IP habilitadas en <code>CAMERAS_JSON</code>.
      </div>
    @else
      <div id="ipCameraRoutes" class="d-none"
        data-snapshot-url="{{ route('camera.ip.snapshot') }}"
        data-mjpeg-url="{{ route('camera.ip.mjpeg') }}"
        data-events-stream-url="{{ route('camera.ultimos-eventos-stream') }}"
        data-default-camera="{{ $defaultCameraId }}"></div>

      <div class="row g-3">
        <div class="col-12 col-xl-8">
          <div class="card h-100">
            <div class="card-body d-flex flex-column gap-3">
              <select id="ipCameraSelect" class="d-none">
                @foreach($cameras as $camera)
                  <option value="{{ $camera['id'] }}">
                    {{ $camera['name'] }} ({{ $camera['id'] }})
                  </option>
                @endforeach
              </select>

              <div class="ratio ratio-16x9 bg-dark rounded overflow-hidden">
                <img id="ipCameraFrame" class="w-100 h-100 object-fit-cover" alt="Vista camara IP" />
              </div>
            </div>
          </div>
        </div>

        <div class="col-12 col-xl-4">
          <div class="card h-100 recognize-list-panel">
            <div class="card-body d-flex flex-column gap-2">
              <div class="d-flex align-items-center justify-content-between">
                <div class="fw-semibold">Personas reconocidas</div>
                <span id="ipRecognizedCount" class="badge rounded-pill bg-secondary">0</span>
              </div>
              <div id="ipRecognizedEmpty" class="text-muted small">Sin reconocimientos recientes.</div>
              <ul id="ipRecognizedList" class="list-group list-group-flush overflow-auto camara-recognize-list" style="max-height: 560px;"></ul>
            </div>
          </div>
        </div>
      </div>
    @endif
  </div>
</div>
@endsection

@push('css')
  @vite('resources/css/camara/ip-preview.css')
@endpush

@push('script')
  @if(!empty($cameras))
    <script>
      (() => {
        const routes = document.getElementById('ipCameraRoutes');
        const select = document.getElementById('ipCameraSelect');
        const frame = document.getElementById('ipCameraFrame');
        const recognizedListEl = document.getElementById('ipRecognizedList');
        const recognizedEmptyEl = document.getElementById('ipRecognizedEmpty');
        const recognizedCountEl = document.getElementById('ipRecognizedCount');
        const mjpegUrl = routes?.dataset?.mjpegUrl || '';
        const eventsStreamUrl = routes?.dataset?.eventsStreamUrl || '';
        const defaultCamera = routes?.dataset?.defaultCamera || '';
        const LIST_TTL_MS = 5000;
        const SEEN_KEYS_RETENTION_MS = 10 * 60 * 1000;
        const MAX_RENDERED_EVENTS = 40;
        const STREAM_RECONNECT_MS = 1500;

        let currentStreamUrl = '';
        let eventsPruneTimer = null;
        let eventSource = null;
        let eventStreamEnabled = false;
        let reconnectStreamTimer = null;
        let lastStreamId = 0;
        let renderedEvents = [];
        const seenEventKeys = new Map();

        const escapeHtml = (value) => {
          return String(value ?? '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;');
        };

        const getEventVisual = (eventCode) => {
          const isSalida = Number(eventCode) === 1;
          return {
            label: isSalida ? 'Salida' : 'Ingreso',
            badgeClass: isSalida ? 'bg-danger' : 'bg-success',
          };
        };

        const getEventKey = (item) => {
          const streamId = item?.stream_id !== undefined && item?.stream_id !== null
            ? String(item.stream_id).trim()
            : '';
          if (streamId !== '') {
            return `stream:${streamId}`;
          }

          const eventId = item?.evento_id !== undefined && item?.evento_id !== null
            ? String(item.evento_id).trim()
            : '';
          if (eventId !== '') {
            return `id:${eventId}`;
          }

          const identification = String(item?.identificacion ?? '').trim();
          const eventCode = String(item?.evento ?? '').trim();
          const timestamp = String(item?.fecha_evento ?? item?.hora_evento ?? '').trim();
          const description = String(item?.descripcion ?? '').trim();
          const fallback = `${identification}|${eventCode}|${timestamp}|${description}`;
          return fallback === '|||' ? '' : `fallback:${fallback}`;
        };

        const normalizeEventItem = (item) => {
          if (!item || typeof item !== 'object') {
            return null;
          }
          const key = getEventKey(item);
          if (!key) {
            return null;
          }
          let formattedDate = '';
          const rawDate = String(item.fecha_evento ?? '').trim();
          if (rawDate) {
            const dateObj = new Date(rawDate);
            if (!Number.isNaN(dateObj.getTime())) {
              formattedDate = new Intl.DateTimeFormat('es-CO', {
                year: 'numeric',
                month: '2-digit',
                day: '2-digit',
              }).format(dateObj);
            }
          }

          let formattedTime = String(item.hora_evento ?? '').trim();
          if (!formattedTime && rawDate) {
            const dateObj = new Date(rawDate);
            if (!Number.isNaN(dateObj.getTime())) {
              formattedTime = new Intl.DateTimeFormat('es-CO', {
                hour: 'numeric',
                minute: '2-digit',
                hour12: true,
              }).format(dateObj);
            }
          }

          return {
            key,
            identificacion: String(item.identificacion ?? '').trim(),
            nombre: String(item.nombre ?? '').trim() || 'Sin nombre',
            fechaEvento: formattedDate,
            horaEvento: formattedTime,
            evento: Number(item.evento ?? 0),
          };
        };

        const renderRecognizedEvents = () => {
          if (!recognizedListEl || !recognizedEmptyEl) {
            return;
          }

          recognizedListEl.innerHTML = '';
          if (recognizedCountEl) {
            recognizedCountEl.textContent = String(renderedEvents.length);
          }

          if (renderedEvents.length === 0) {
            recognizedEmptyEl.classList.remove('d-none');
            return;
          }

          recognizedEmptyEl.classList.add('d-none');
          const fragment = document.createDocumentFragment();

          renderedEvents.forEach((item) => {
            const eventVisual = getEventVisual(item.evento);
            const docText = item.identificacion ? `CC ${escapeHtml(item.identificacion)}` : '';
            const dateText = escapeHtml(item.fechaEvento || '--');
            const timeText = escapeHtml(item.horaEvento || '--');
            const li = document.createElement('li');
            li.className = 'list-group-item';
            li.innerHTML = `
              <div class="recognize-card-head">
                <div class="recognize-card-ident">
                  <div class="recognize-card-name">${escapeHtml(item.nombre)}</div>
                  ${docText ? `<div class="recognize-card-doc">${docText}</div>` : ''}
                </div>
                <span class="badge ${eventVisual.badgeClass} recognize-event-badge">${eventVisual.label}</span>
              </div>
              <div class="recognize-meta-grid">
                <div class="recognize-meta-pill recognize-meta-pill-date">
                  <span class="recognize-meta-label">Fecha:</span>
                  <span class="recognize-meta-value">${dateText}</span>
                </div>
                <div class="recognize-meta-pill recognize-meta-pill-time">
                  <span class="recognize-meta-label"><i class="far fa-clock"></i> Hora:</span>
                  <span class="recognize-meta-value">${timeText}</span>
                </div>
              </div>
            `;
            fragment.appendChild(li);
          });

          recognizedListEl.appendChild(fragment);
        };

        const pruneRenderedEvents = () => {
          const cutoff = Date.now() - LIST_TTL_MS;
          const next = renderedEvents.filter((item) => (item.seenAt || 0) >= cutoff);
          if (next.length === renderedEvents.length) {
            return;
          }
          renderedEvents = next;
          renderRecognizedEvents();
        };

        const pruneSeenKeys = () => {
          const cutoff = Date.now() - SEEN_KEYS_RETENTION_MS;
          for (const [key, seenAt] of seenEventKeys.entries()) {
            if (seenAt < cutoff) {
              seenEventKeys.delete(key);
            }
          }
        };

        const clearRecognizedEvents = () => {
          lastStreamId = 0;
          renderedEvents = [];
          seenEventKeys.clear();
          renderRecognizedEvents();
        };

        const addIncomingEvent = (payload, eventId = '') => {
          const normalized = normalizeEventItem(payload);
          if (!normalized) {
            return;
          }

          const now = Date.now();
          if (eventId && /^\d+$/.test(eventId)) {
            lastStreamId = Math.max(lastStreamId, Number(eventId));
          }
          if (seenEventKeys.has(normalized.key)) {
            return;
          }

          seenEventKeys.set(normalized.key, now);
          renderedEvents.unshift({
            ...normalized,
            seenAt: now,
          });

          if (renderedEvents.length > MAX_RENDERED_EVENTS) {
            renderedEvents = renderedEvents.slice(0, MAX_RENDERED_EVENTS);
          }

          renderRecognizedEvents();
          pruneRenderedEvents();
          pruneSeenKeys();
        };

        const handleStreamMessage = (event) => {
          if (!event?.data) {
            return;
          }
          let payload = null;
          try {
            payload = JSON.parse(event.data);
          } catch (error) {
            payload = null;
          }
          if (!payload || typeof payload !== 'object') {
            return;
          }
          const origin = String(payload.origen || '').trim().toLowerCase();
          if (origin && origin !== 'api' && origin !== 'camara') {
            return;
          }
          addIncomingEvent(payload, String(event.lastEventId || ''));
        };

        const scheduleStreamReconnect = () => {
          if (!eventStreamEnabled) {
            return;
          }
          if (reconnectStreamTimer) {
            return;
          }
          reconnectStreamTimer = window.setTimeout(() => {
            reconnectStreamTimer = null;
            connectRecognizedStream();
          }, STREAM_RECONNECT_MS);
        };

        const connectRecognizedStream = () => {
          if (!eventStreamEnabled) {
            return;
          }
          if (!eventsStreamUrl || typeof window.EventSource !== 'function') {
            return;
          }

          if (eventSource) {
            eventSource.close();
            eventSource = null;
          }

          const streamUrl = new URL(eventsStreamUrl, window.location.origin);
          if (lastStreamId > 0) {
            streamUrl.searchParams.set('last_event_id', String(lastStreamId));
          }
          streamUrl.searchParams.set('_', Date.now().toString());
          eventSource = new EventSource(streamUrl.toString(), { withCredentials: true });

          eventSource.addEventListener('recognized', handleStreamMessage);
          eventSource.onmessage = handleStreamMessage;
          eventSource.onerror = () => {
            if (eventSource) {
              eventSource.close();
              eventSource = null;
            }
            scheduleStreamReconnect();
          };
        };

        const startRecognizedStream = () => {
          eventStreamEnabled = true;
          if (reconnectStreamTimer) {
            clearTimeout(reconnectStreamTimer);
            reconnectStreamTimer = null;
          }
          connectRecognizedStream();
          if (!eventsPruneTimer) {
            eventsPruneTimer = window.setInterval(() => {
              pruneRenderedEvents();
            }, 300);
          }
        };

        const stopRecognizedStream = ({ clear = false } = {}) => {
          eventStreamEnabled = false;
          if (reconnectStreamTimer) {
            clearTimeout(reconnectStreamTimer);
            reconnectStreamTimer = null;
          }
          if (eventSource) {
            eventSource.close();
            eventSource = null;
          }
          if (eventsPruneTimer) {
            clearInterval(eventsPruneTimer);
            eventsPruneTimer = null;
          }
          if (clear) {
            clearRecognizedEvents();
          }
        };

        const stopStream = () => {
          if (!frame) {
            return;
          }
          frame.onload = null;
          frame.onerror = null;
          frame.src = '';
          currentStreamUrl = '';
          stopRecognizedStream({ clear: true });
        };

        const startStream = () => {
          if (!mjpegUrl) {
            stopRecognizedStream({ clear: true });
            return;
          }

          const cameraId = select?.value || '';
          if (!cameraId) {
            stopRecognizedStream({ clear: true });
            return;
          }

          stopStream();

          const requestUrl = new URL(mjpegUrl, window.location.origin);
          requestUrl.searchParams.set('camera_id', cameraId);
          requestUrl.searchParams.set('_', Date.now().toString());

          currentStreamUrl = requestUrl.toString();
          startRecognizedStream();

          frame.onerror = () => {
            console.warn('[camera-ip] No se pudo abrir el stream en vivo.');
          };

          frame.src = currentStreamUrl;
        };

        if (defaultCamera && select) {
          select.value = defaultCamera;
        }

        if (select) {
          select.addEventListener('change', () => {
            startStream();
          });
        }

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

        renderRecognizedEvents();
        startStream();
      })();
    </script>
  @endif
@endpush
