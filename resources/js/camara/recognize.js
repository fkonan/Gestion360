import {
  requestCamera,
  stopStream,
  getCsrfToken,
  showInlineAlert,
  isUnknown,
  hasValidFace,
  normalizeDetections,
  pickTopFaces,
  cropFaceToBlob,
  captureFullFrameBlob,
} from './shared';
import { createFaceDetector } from './mediapipe';

document.addEventListener('DOMContentLoaded', () => {
  const ui = {
    alertContainer: document.getElementById('alertContainer'),
    cameraStatus: document.getElementById('cameraStatus'),
    faceStatus: document.getElementById('faceStatus'),
    serviceStatus: document.getElementById('serviceStatus'),
    serviceMessage: document.getElementById('serviceMessage'),
    lastPayloadSize: document.getElementById('lastPayloadSize'),
    sendPreview: document.getElementById('sendPreview'),
    video: document.getElementById('cameraVideo'),
    canvas: document.getElementById('captureCanvas'),
    eventIngreso: document.getElementById('eventIngreso'),
    eventSalida: document.getElementById('eventSalida'),
    eventIngresoLabel: document.getElementById('eventIngresoLabel'),
    eventSalidaLabel: document.getElementById('eventSalidaLabel'),
    eventModeBanner: document.getElementById('eventModeBanner'),
    eventModeIcon: document.getElementById('eventModeIcon'),
    eventModeText: document.getElementById('eventModeText'),
    eventModeDot: document.getElementById('eventModeDot'),
    leftPanel: document.getElementById('leftPanel'),
    startBtn: document.getElementById('startBtn'),
    stopBtn: document.getElementById('stopBtn'),
    retryBtn: document.getElementById('retryBtn'),
    recognizeList: document.getElementById('recognizeList'),
    recognizeEmpty: document.getElementById('recognizeEmpty'),
    recognizedCount: document.getElementById('recognizedCount'),
    searchInput: document.getElementById('searchInput'),
    searchStatus: document.getElementById('searchStatus'),
  };

  if (!ui.video || !ui.canvas) {
    return;
  }

  const CONFIG = {
    // Balanced for entry: faster response (~1s) without saturating network.
    detection: {
      intervalMs: 150,
      model: 'full',
      minScoreEnter: 0.45,
      minScoreExit: 0.40,
      minFaceRatio: 0.03,
      stableWindowMs: 500,
      recentMs: 700,
    },
    capture: {
      intervalMs: 450,
      crowdThreshold: 5,
      maxFaces: 4,
      cropBase: 384,
      cropBig: 448,
      cropQuality: 1,
      cropQualitySmall: 0.85,
      cropPaddingBase: 0.25,
      cropPaddingSmall: 0.45,
      smallFaceW: 0.06,
      fullWidth: 640,
      fullHeight: 480,
      fullQuality: 1,
    },
    send: {
      intervalMs: 1200,
      batchSize: 4,
      globalCooldownMs: 200,
      identityCooldownMs: 15000,
      timeoutMs: 5000,
    },
    health: {
      pollMs: 4000,
      resumeDelayMs: 400,
    },
    listMax: 50,
    debug: false,
  };
  // Ajustes rapidos:
  // - Mas lejos: bajar minFaceRatio/minScore, subir width/quality.
  // - Menos CPU/red: subir cooldowns o bajar batchSize/quality.

  let stream = null;
  let faceDetector = null;
  let detectionTimer = null;
  let detectionInFlight = false;
  let lastFaceDetected = false;
  let liveActive = false;
  let liveCaptureTimer = null;
  let liveSendTimer = null;
  let healthTimer = null;
  let healthInFlight = false;
  let liveBuffer = [];
  let liveInFlight = false;
  let serviceOnline = null;
  let resumeSendAt = 0;
  let lastAlert = '';
  let recognizedList = [];
  let lastFaceSeenAt = 0;
  let lastDetections = [];
  let lastDetectionsAt = 0;
  let lastSendAt = 0;
  let previewUrls = [];
  const lastSeenByKey = new Map();
  let captureInFlight = false;
  let faceRotationIndex = 0;
  let lastCaptureMode = 'precision';
  let recognizeAbortController = null;

  function inlineAlert(type, message) {
    lastAlert = showInlineAlert(ui.alertContainer, type, message, lastAlert);
  }

  function setCameraStatus(state) {
    const map = {
      off: { text: 'Camara detenida', cls: 'bg-secondary' },
      on: { text: 'Camara activa', cls: 'bg-success' },
      error: { text: 'Error de camara', cls: 'bg-danger' },
    };
    const cfg = map[state] || map.off;
    if (ui.cameraStatus) {
      ui.cameraStatus.className = `badge ${cfg.cls}`;
      ui.cameraStatus.textContent = cfg.text;
    }
  }

  function setFaceStatus(found) {
    if (!ui.faceStatus) {
      return;
    }
    if (found) {
      ui.faceStatus.className = 'badge bg-success';
      ui.faceStatus.textContent = 'Cara detectada';
    } else {
      ui.faceStatus.className = 'badge bg-danger';
      ui.faceStatus.textContent = 'No hay cara';
    }
  }

  function setServiceStatus(state) {
    if (!ui.serviceStatus) {
      return;
    }
    const map = {
      loading: { text: 'Comprobando...', cls: 'bg-secondary' },
      ok: { text: 'Online', cls: 'bg-success' },
      error: { text: 'Offline', cls: 'bg-danger' },
    };
    const cfg = map[state] || map.loading;
    ui.serviceStatus.className = `badge ${cfg.cls}`;
    ui.serviceStatus.textContent = cfg.text;
  }

  function setServiceMessage(message, cls = 'text-muted') {
    if (!ui.serviceMessage) {
      return;
    }
    ui.serviceMessage.className = `small ${cls}`;
    ui.serviceMessage.textContent = message || '';
  }

  function setControls(active, failed = false) {
    if (ui.startBtn) ui.startBtn.disabled = active;
    if (ui.stopBtn) ui.stopBtn.disabled = !active;
    if (ui.retryBtn) {
      ui.retryBtn.classList.toggle('d-none', !failed);
    }
  }

  function startSendLoop() {
    if (liveSendTimer || !liveActive) return;
    liveSendTimer = setInterval(sendLiveBatch, CONFIG.send.intervalMs);
  }

  function stopSendLoop() {
    if (!liveSendTimer) return;
    clearInterval(liveSendTimer);
    liveSendTimer = null;
  }

  function markServiceOffline() {
    if (serviceOnline === false) return;
    serviceOnline = false;
    setServiceStatus('error');
    setServiceMessage('Servicio fuera de linea. Esperando reconexion...', 'text-danger');
    liveBuffer = [];
    stopSendLoop();
    startHealthPolling();
  }

  function markServiceOnline() {
    if (serviceOnline === true) return;
    serviceOnline = true;
    setServiceStatus('ok');
    setServiceMessage('Servicio restaurado.', 'text-success');
    window.setTimeout(() => setServiceMessage(''), 2500);
    liveBuffer = [];
    lastSendAt = 0;
    resumeSendAt = Date.now() + CONFIG.health.resumeDelayMs;
    if (liveActive) {
      startSendLoop();
    }
    stopHealthPolling();
  }

  function startHealthPolling() {
    if (healthTimer) return;
    healthTimer = setInterval(checkHealth, CONFIG.health.pollMs);
  }

  function stopHealthPolling() {
    if (!healthTimer) return;
    clearInterval(healthTimer);
    healthTimer = null;
  }

  function formatKb(bytes) {
    if (!Number.isFinite(bytes)) return '--';
    return `${(bytes / 1024).toFixed(1)} KB`;
  }

  function setLastPayloadSize(bytes) {
    if (!ui.lastPayloadSize) return;
    ui.lastPayloadSize.textContent = formatKb(bytes);
  }

  function clearPreview() {
    if (!previewUrls.length) return;
    previewUrls.forEach((url) => URL.revokeObjectURL(url));
    previewUrls = [];
    if (ui.sendPreview) {
      ui.sendPreview.innerHTML = '';
    }
  }

  function updateSendPreview(blobs) {
    if (!ui.sendPreview || !Array.isArray(blobs)) return;
    // Reuse existing <img> nodes to avoid layout thrash/flicker
    const existing = Array.from(ui.sendPreview.children);
    // Revoke old URLs
    if (previewUrls.length) {
      previewUrls.forEach((url) => URL.revokeObjectURL(url));
      previewUrls = [];
    }
    blobs.forEach((blob, index) => {
      if (!blob) return;
      const url = URL.createObjectURL(blob);
      previewUrls.push(url);
      let img = existing[index];
      if (!img) {
        img = document.createElement('img');
        img.className = 'img-fluid rounded border';
        img.style.maxHeight = '140px';
        img.style.objectFit = 'cover';
        img.style.flex = '0 0 auto';
        ui.sendPreview.appendChild(img);
      }
      img.src = url;
      img.alt = 'Preview envio';
    });
    // Remove extra nodes
    for (let i = blobs.length; i < existing.length; i += 1) {
      existing[i].remove();
    }
  }

  function getEndpointUrl(type) {
    const fallback = new URL(type, window.location.href).toString();
    if (!ui.startBtn) {
      return fallback;
    }
    const routes = {
      health: ui.startBtn.dataset.healthUrl,
      recognize: ui.startBtn.dataset.recognizeUrl,
    };
    const candidate = routes[type];
    if (!candidate) {
      return fallback;
    }
    try {
      return new URL(candidate, window.location.href).toString();
    } catch (error) {
      return fallback;
    }
  }

  function initFaceDetection() {
    try {
      faceDetector = createFaceDetector({
        model: CONFIG.detection.model,
        minDetectionConfidence: CONFIG.detection.minScoreEnter,
        onResults: handleFaceResults,
        debug: CONFIG.debug,
      });
      return true;
    } catch (error) {
      inlineAlert('danger', 'No se pudo inicializar la deteccion facial.');
      return false;
    }
  }

  function handleFaceResults(results) {
    const detections = results?.detections ?? [];
    const minScore = lastFaceDetected ? CONFIG.detection.minScoreExit : CONFIG.detection.minScoreEnter;
    const foundNow = hasValidFace(detections, {
      minScore,
      minFaceRatio: CONFIG.detection.minFaceRatio,
    });

    if (foundNow) {
      lastFaceSeenAt = Date.now();
    }
    lastDetections = detections;
    lastDetectionsAt = Date.now();

    if (!window.__mpLastSeenAt) window.__mpLastSeenAt = 0;
    if (foundNow) window.__mpLastSeenAt = Date.now();
    const stable = (Date.now() - window.__mpLastSeenAt) <= CONFIG.detection.stableWindowMs;

    lastFaceDetected = stable;
    setFaceStatus(stable);
    if (!stable) {
      liveBuffer = [];
    }

    if (CONFIG.debug && detections.length) {
      console.debug('[camara] detect', {
        stable,
        score: Array.isArray(detections[0]?.score) ? detections[0].score[0] : detections[0]?.score,
        width: detections[0]?.boundingBox?.width ?? 0,
      });
    }
  }

  function startDetectionLoop() {
    if (!faceDetector || detectionTimer) {
      return;
    }
    detectionTimer = window.setInterval(() => {
      if (!stream || ui.video.readyState < 2) {
        return;
      }
      if (detectionInFlight) {
        return;
      }
      detectionInFlight = true;
      faceDetector.send({ image: ui.video })
        .catch(() => {
          /* inlineAlert('warning', 'No se pudo ejecutar la deteccion facial.'); */
        })
        .finally(() => {
          detectionInFlight = false;
        });
    }, CONFIG.detection.intervalMs);
  }

  function stopDetectionLoop() {
    if (detectionTimer) {
      clearInterval(detectionTimer);
      detectionTimer = null;
    }
    detectionInFlight = false;
  }

  async function startCamera() {
    if (stream) {
      return true;
    }
    const result = await requestCamera(ui.video);
    if (!result.stream) {
      inlineAlert('danger', result.error || 'No se pudo acceder a la camara.');
      setCameraStatus('error');
      setControls(false, true);
      return false;
    }
    stream = result.stream;
    setCameraStatus('on');
    setControls(true, false);
    startDetectionLoop();
    return true;
  }

  function stopCamera() {
    stream = stopStream(stream, ui.video);
    setCameraStatus('off');
    setControls(false, false);
    stopDetectionLoop();
  }

  function canSendNow() {
    const now = Date.now();
    return now >= (lastSendAt + CONFIG.send.globalCooldownMs) && now >= resumeSendAt;
  }

  function setSelectedEvent(value) {
    const next = value === '1' ? '1' : '2';
    if (ui.eventIngreso) ui.eventIngreso.checked = next === '2';
    if (ui.eventSalida) ui.eventSalida.checked = next === '1';
    localStorage.setItem('camara_evento', next);
    updateEventUI(next);
  }

  function getSelectedEvent() {
    if (ui.eventSalida?.checked) return '1';
    return '2';
  }

  function updateEventUI(eventValue) {
    const isIngreso = eventValue === '2';
    if (ui.eventModeBanner) {
      ui.eventModeBanner.classList.toggle('bg-success', isIngreso);
      ui.eventModeBanner.classList.toggle('bg-danger', !isIngreso);
    }
    if (ui.eventModeIcon) {
      ui.eventModeIcon.className = isIngreso
        ? 'fa-solid fa-arrow-right-to-bracket fa-2x'
        : 'fa-solid fa-arrow-right-from-bracket fa-2x';
    }
    if (ui.eventModeText) {
      ui.eventModeText.textContent = isIngreso ? 'MODO: INGRESO' : 'MODO: SALIDA';
    }
    if (ui.eventModeDot) {
      ui.eventModeDot.classList.toggle('bg-white', true);
    }
    if (ui.leftPanel) {
      ui.leftPanel.classList.toggle('border-success', isIngreso);
      ui.leftPanel.classList.toggle('border-danger', !isIngreso);
    }
    if (ui.eventIngresoLabel) {
      ui.eventIngresoLabel.classList.toggle('btn-success', isIngreso);
      ui.eventIngresoLabel.classList.toggle('btn-outline-success', !isIngreso);
    }
    if (ui.eventSalidaLabel) {
      ui.eventSalidaLabel.classList.toggle('btn-danger', !isIngreso);
      ui.eventSalidaLabel.classList.toggle('btn-outline-danger', isIngreso);
    }
  }

  async function captureFrame() {
    if (!stream || ui.video.readyState < 2) {
      return;
    }
    if (captureInFlight) {
      return;
    }
    const now = Date.now();
    const hasRecentFace = lastFaceDetected && ((now - lastFaceSeenAt) <= CONFIG.detection.recentMs);
    if (!hasRecentFace || (now - lastDetectionsAt) > CONFIG.detection.recentMs) {
      liveBuffer = [];
      return;
    }

    const faces = normalizeDetections(lastDetections, ui.video, {
      minScore: CONFIG.detection.minScoreEnter,
      minFaceRatio: CONFIG.detection.minFaceRatio,
    });
    if (!faces.length) {
      liveBuffer = [];
      return;
    }

    captureInFlight = true;
    try {
      if (faces.length >= CONFIG.capture.crowdThreshold) {
        // Modo CROWD: enviar 1 frame completo cuando hay muchas caras.
        lastCaptureMode = 'crowd';
        const blob = await captureFullFrameBlob(ui.video, ui.canvas, {
          width: CONFIG.capture.fullWidth,
          height: CONFIG.capture.fullHeight,
          quality: CONFIG.capture.fullQuality,
        });
        liveBuffer = blob ? [blob] : [];
        return;
      }

      // Modo PRECISION: crops por cara (1..4).
      lastCaptureMode = 'precision';
      if (faces.length > CONFIG.capture.maxFaces) {
        faceRotationIndex = (faceRotationIndex + 1) % faces.length;
      }
      const selected = pickTopFaces(faces, CONFIG.capture.maxFaces, faceRotationIndex);
      if (!selected.length) {
        liveBuffer = [];
        return;
      }

      const blobs = [];
      for (const face of selected) {
        const isSmall = face.box.width < CONFIG.capture.smallFaceW;
        const padding = isSmall ? CONFIG.capture.cropPaddingSmall : CONFIG.capture.cropPaddingBase;
        const size = isSmall ? CONFIG.capture.cropBig : CONFIG.capture.cropBase;
        const quality = isSmall ? CONFIG.capture.cropQualitySmall : CONFIG.capture.cropQuality;
        const blob = await cropFaceToBlob(ui.video, ui.canvas, face, {
          padding,
          size,
          quality,
        });
        if (blob) blobs.push(blob);
      }
      liveBuffer = blobs;
    } finally {
      captureInFlight = false;
    }
  }

  function extractPersons(payload) {
    if (!payload || payload.ok !== true || !Array.isArray(payload.personas)) {
      return [];
    }
    return payload.personas
      .map((person) => ({
        identificacion: person?.identificacion ? String(person.identificacion).trim() : '',
        nombre: person?.nombre ? String(person.nombre).trim() : '',
        distance: typeof person?.distance === 'number' ? person.distance : null,
        filename: person?.filename ? String(person.filename).trim() : '',
        evento: person?.evento === 1 ? 1 : 2,
      }))
      .filter((person) => person.nombre || person.identificacion)
      .filter((person) => !isUnknown(person.nombre) && !isUnknown(person.identificacion));
  }

  function getPersonKey(person) {
    if (!person) return '';
    if (person.identificacion) return `id:${person.identificacion}`;
    if (person.nombre) return `nm:${person.nombre.toLowerCase()}`;
    return '';
  }

  function upsertRecognized(person) {
    const key = getPersonKey(person);
    if (!key) return;
    recognizedList = recognizedList.filter((item) => getPersonKey(item) !== key);
    recognizedList.push({
      ...person,
      lastSeenAt: Date.now(),
    });
    if (CONFIG.listMax && recognizedList.length > CONFIG.listMax) {
      recognizedList.splice(0, recognizedList.length - CONFIG.listMax);
    }
  }

  function highlightMatch(text, term) {
    if (!term) return text;
    const lower = text.toLowerCase();
    const idx = lower.indexOf(term);
    if (idx === -1) return text;
    const before = text.slice(0, idx);
    const match = text.slice(idx, idx + term.length);
    const after = text.slice(idx + term.length);
    return `${before}<mark class="px-1">${match}</mark>${after}`;
  }

  function renderRecognized() {
    if (!ui.recognizeList || !ui.recognizeEmpty) {
      return;
    }
    const term = ui.searchInput ? ui.searchInput.value.trim().toLowerCase() : '';
    const list = term
      ? recognizedList.filter((item) => {
        const haystack = `${item.nombre || ''} ${item.identificacion || ''}`.toLowerCase();
        return haystack.includes(term);
      })
      : recognizedList;

    ui.recognizeList.innerHTML = '';
    if (ui.recognizedCount) {
      ui.recognizedCount.textContent = String(recognizedList.length);
    }

    if (!recognizedList.length) {
      ui.recognizeEmpty.classList.remove('d-none');
      ui.recognizeEmpty.textContent = 'Sin coincidencias todavia.';
    } else {
      ui.recognizeEmpty.classList.add('d-none');
    }

    list.forEach((item) => {
      const li = document.createElement('li');
      li.className = 'list-group-item py-2';
      const name = item.nombre || item.identificacion || 'Sin nombre';
      const idText = item.identificacion ? `CC: ${item.identificacion}` : '';
      const eventBadge = item.evento === 1
        ? '<span class="badge bg-danger">Salida</span>'
        : '<span class="badge bg-success">Ingreso</span>';
      const metaParts = [idText].filter(Boolean).join(' · ');
      li.innerHTML = `
        <div class="d-flex align-items-center justify-content-between gap-2">
          <div class="fw-semibold">${highlightMatch(name, term)}</div>
          ${eventBadge}
        </div>
        ${metaParts ? `<div class="small text-muted">${highlightMatch(metaParts, term)}</div>` : ''}
      `;
      ui.recognizeList.appendChild(li);
    });

    if (ui.searchStatus) {
      if (!term) {
        ui.searchStatus.className = 'badge bg-secondary d-none';
        ui.searchStatus.textContent = 'Encontrado';
      } else if (list.length > 0) {
        ui.searchStatus.className = 'badge bg-success';
        ui.searchStatus.textContent = 'Encontrado';
      } else {
        ui.searchStatus.className = 'badge bg-danger';
        ui.searchStatus.textContent = 'No aparece';
      }
    }
  }

  async function sendLiveBatch() {
    if (!liveActive || liveInFlight) {
      return;
    }
    if (serviceOnline === false) {
      return;
    }
    const now = Date.now();
    if (!lastFaceDetected || (now - lastFaceSeenAt) > CONFIG.detection.recentMs) {
      liveBuffer = [];
      return;
    }
    if (liveBuffer.length === 0) {
      return;
    }
    if (!canSendNow()) return;

    liveInFlight = true;
    lastSendAt = Date.now();
    const maxBatch = lastCaptureMode === 'crowd' ? 1 : CONFIG.send.batchSize;
    const batch = liveBuffer.slice(0, maxBatch);
    liveBuffer = [];
    const batchSizeBytes = batch.reduce((sum, blob) => sum + (blob?.size || 0), 0);
    setLastPayloadSize(batchSizeBytes);
    if (batch.length) {
      updateSendPreview(batch);
    }

    let timeoutId = null;
    try {
      const controller = new AbortController();
      recognizeAbortController = controller;
      const startedAt = performance.now();
      timeoutId = window.setTimeout(() => controller.abort('request-timeout'), CONFIG.send.timeoutMs);
      const formData = new FormData();
      batch.forEach((blob, index) => {
        formData.append('images[]', blob, `frame_${index + 1}.jpg`);
      });
      const selectedEvent = getSelectedEvent();
      formData.append('evento', selectedEvent);
      const response = await fetch(getEndpointUrl('recognize'), {
        method: 'POST',
        headers: {
          'Accept': 'application/json',
          'X-CSRF-TOKEN': getCsrfToken(),
        },
        body: formData,
        credentials: 'same-origin',
        signal: controller.signal,
      });
      const headersAt = performance.now();
      clearTimeout(timeoutId);
      timeoutId = null;
      const data = await response.json().catch(() => null);
      const parsedAt = performance.now();
      if (!response.ok || !data) {
        markServiceOffline();
        inlineAlert('warning', 'No se pudo reconocer en vivo.');
        return;
      }
      const persons = extractPersons(data);
      if (persons.length) {
        persons.forEach((person) => {
          const key = getPersonKey(person);
          if (!key) return;
          upsertRecognized(person);
          lastSeenByKey.set(key, Date.now());
        });
      }
      const updatedAt = performance.now();
      renderRecognized();
      const renderedAt = performance.now();
      if (CONFIG.debug) {
        console.debug('[camara] recognize ms', {
          total: Math.round(renderedAt - startedAt),
          tHeaders: Math.round(headersAt - startedAt),
          tJson: Math.round(parsedAt - headersAt),
          tUpdate: Math.round(updatedAt - parsedAt),
          tRender: Math.round(renderedAt - updatedAt),
          evento: selectedEvent,
          ok: data?.ok,
          count: persons.length,
        });
      }
    } catch (error) {
      if (error && error.name === 'AbortError') {
        if (CONFIG.debug) {
          console.debug('[camara] recognize abort (timeout or stop/pagehide)');
        }
      } else {
        markServiceOffline();
        inlineAlert('warning', 'Error de red en reconocimiento en vivo.');
      }
    } finally {
      if (timeoutId) {
        clearTimeout(timeoutId);
      }
      recognizeAbortController = null;
      liveInFlight = false;
    }
  }

  async function startLiveRecognize() {
    if (liveActive) return;
    const ok = await startCamera();
    if (!ok) {
      return;
    }
    liveActive = true;
    liveBuffer = [];
    const healthOk = await checkHealth();
    if (!healthOk) {
      startHealthPolling();
    }
    liveCaptureTimer = setInterval(async () => {
      if (serviceOnline === false) {
        liveBuffer = [];
        return;
      }
      if (!lastFaceDetected) {
        return;
      }
      if ((Date.now() - lastFaceSeenAt) > CONFIG.detection.recentMs) {
        liveBuffer = [];
        return;
      }
      await captureFrame();
    }, CONFIG.capture.intervalMs);
    if (serviceOnline !== false) {
      startSendLoop();
    }
    setControls(true, false);
  }

  function stopLiveRecognize() {
    liveActive = false;
    if (recognizeAbortController) {
      recognizeAbortController.abort();
      recognizeAbortController = null;
    }
    if (liveCaptureTimer) {
      clearInterval(liveCaptureTimer);
      liveCaptureTimer = null;
    }
    stopSendLoop();
    stopHealthPolling();
    liveBuffer = [];
    liveInFlight = false;
    clearPreview();
    setControls(false, false);
    stopCamera();
  }

  async function checkHealth() {
    if (healthInFlight) {
      return serviceOnline === true;
    }
    setServiceStatus('loading');
    healthInFlight = true;
    try {
      const response = await fetch(getEndpointUrl('health'), {
        method: 'GET',
        headers: {
          'Accept': 'application/json',
        },
        credentials: 'same-origin',
      });
      if (!response.ok) {
        markServiceOffline();
        return false;
      }
      const data = await response.json().catch(() => null);
      if (data && data.status && data.status !== 'error') {
        markServiceOnline();
        return true;
      }
      markServiceOffline();
      return false;
    } catch (error) {
      markServiceOffline();
      return false;
    } finally {
      healthInFlight = false;
    }
  }

  if (ui.searchInput) {
    ui.searchInput.addEventListener('input', renderRecognized);
  }

  if (ui.eventIngreso || ui.eventSalida) {
    const saved = localStorage.getItem('camara_evento');
    setSelectedEvent(saved === '1' ? '1' : '2');
    ui.eventIngreso?.addEventListener('change', () => setSelectedEvent('2'));
    ui.eventSalida?.addEventListener('change', () => setSelectedEvent('1'));
  }

  if (ui.startBtn) {
    ui.startBtn.addEventListener('click', startLiveRecognize);
  }
  if (ui.stopBtn) {
    ui.stopBtn.addEventListener('click', stopLiveRecognize);
  }
  if (ui.retryBtn) {
    ui.retryBtn.addEventListener('click', startLiveRecognize);
  }

  window.addEventListener('pagehide', () => {
    stopLiveRecognize();
  });

  initFaceDetection();
  setCameraStatus('off');
  setFaceStatus(false);
  setControls(false, false);
  setServiceMessage('');
  renderRecognized();
  checkHealth();
  startLiveRecognize();
});
