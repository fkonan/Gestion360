import {
  requestCamera,
  stopStream,
  getCsrfToken,
  showInlineAlert,
  dedupePush,
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
    lastPayloadSize: document.getElementById('lastPayloadSize'),
    sendPreview: document.getElementById('sendPreview'),
    video: document.getElementById('cameraVideo'),
    canvas: document.getElementById('captureCanvas'),
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
      cropQuality: 0.8,
      cropQualitySmall: 0.85,
      cropPaddingBase: 0.25,
      cropPaddingSmall: 0.45,
      smallFaceW: 0.06,
      fullWidth: 640,
      fullHeight: 480,
      fullQuality: 0.8,
    },
    send: {
      intervalMs: 1200,
      batchSize: 4,
      globalCooldownMs: 200,
      identityCooldownMs: 15000,
      timeoutMs: 1400,
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
  let liveBuffer = [];
  let liveInFlight = false;
  let lastAlert = '';
  let recognizedList = [];
  let lastFaceSeenAt = 0;
  let lastDetections = [];
  let lastDetectionsAt = 0;
  let lastSendAt = 0;
  let previewUrls = [];
  const lastSeenByName = new Map();
  let captureInFlight = false;
  let faceRotationIndex = 0;
  let lastCaptureMode = 'precision';

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

  function setControls(active, failed = false) {
    if (ui.startBtn) ui.startBtn.disabled = active;
    if (ui.stopBtn) ui.stopBtn.disabled = !active;
    if (ui.retryBtn) {
      ui.retryBtn.classList.toggle('d-none', !failed);
    }
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
    return now >= (lastSendAt + CONFIG.send.globalCooldownMs);
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

  function extractIdentificaciones(payload) {
    if (!payload) return [];
    if (Array.isArray(payload.data)) {
      return payload.data
        .map((item) => item.nombre)
        .filter((value) => value && !isUnknown(value));
    }
    if (Array.isArray(payload.results)) {
      const ids = [];
      payload.results.forEach((result) => {
        if (!result || !Array.isArray(result.faces)) return;
        result.faces.forEach((face) => {
          if (face && face.identificacion && !isUnknown(face.identificacion)) {
            ids.push(String(face.identificacion).trim());
          }
        });
      });
      return ids;
    }
    return [];
  }

  function addRecognized(id) {
    if (!id) return;
    recognizedList = dedupePush(recognizedList, id, CONFIG.listMax);
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
      ? recognizedList.filter((item) => item.toLowerCase().includes(term))
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
      li.className = 'list-group-item py-2 fs-6';
      li.innerHTML = highlightMatch(item, term);
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

    try {
      const controller = new AbortController();
      const startedAt = performance.now();
      const timeoutId = window.setTimeout(() => controller.abort(), CONFIG.send.timeoutMs);
      const formData = new FormData();
      batch.forEach((blob, index) => {
        formData.append('images[]', blob, `frame_${index + 1}.jpg`);
      });
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
      clearTimeout(timeoutId);
      const data = await response.json().catch(() => null);
      if (!response.ok || !data) {
        inlineAlert('warning', 'No se pudo reconocer en vivo.');
        return;
      }
      const ids = extractIdentificaciones(data);
      if (ids.length) {
        ids.forEach((id) => {
          const lastSeen = lastSeenByName.get(id) || 0;
          if ((Date.now() - lastSeen) >= CONFIG.send.identityCooldownMs) {
            addRecognized(id);
            lastSeenByName.set(id, Date.now());
          }
        });
      }
      renderRecognized();
      if (CONFIG.debug) {
        const elapsed = Math.round(performance.now() - startedAt);
        console.debug('[camara] recognize ms', elapsed);
      }
    } catch (error) {
      if (error && error.name === 'AbortError') {
        if (CONFIG.debug) {
          console.debug('[camara] recognize abort timeout');
        }
      } else {
        inlineAlert('warning', 'Error de red en reconocimiento en vivo.');
      }
    } finally {
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
    liveCaptureTimer = setInterval(async () => {
      if (!lastFaceDetected) {
        return;
      }
      if ((Date.now() - lastFaceSeenAt) > CONFIG.detection.recentMs) {
        liveBuffer = [];
        return;
      }
      await captureFrame();
    }, CONFIG.capture.intervalMs);
    liveSendTimer = setInterval(sendLiveBatch, CONFIG.send.intervalMs);
    setControls(true, false);
  }

  function stopLiveRecognize() {
    liveActive = false;
    if (liveCaptureTimer) {
      clearInterval(liveCaptureTimer);
      liveCaptureTimer = null;
    }
    if (liveSendTimer) {
      clearInterval(liveSendTimer);
      liveSendTimer = null;
    }
    liveBuffer = [];
    liveInFlight = false;
    clearPreview();
    setControls(false, false);
    stopCamera();
  }

  async function checkHealth() {
    setServiceStatus('loading');
    try {
      const response = await fetch(getEndpointUrl('health'), {
        method: 'GET',
        headers: {
          'Accept': 'application/json',
        },
        credentials: 'same-origin',
      });
      if (!response.ok) {
        setServiceStatus('error');
        return;
      }
      const data = await response.json().catch(() => null);
      if (data && data.status && data.status !== 'error') {
        setServiceStatus('ok');
      } else {
        setServiceStatus('error');
      }
    } catch (error) {
      setServiceStatus('error');
    }
  }

  if (ui.searchInput) {
    ui.searchInput.addEventListener('input', renderRecognized);
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
  renderRecognized();
  checkHealth();
  startLiveRecognize();
});
