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
  faceUtilityScore,
  iouBoxes,
} from './shared';
import { createFaceDetector } from './mediapipe';

document.addEventListener('DOMContentLoaded', () => {
  const ui = {
    alertContainer: document.getElementById('alertContainer'),
    serviceStatusTop: document.getElementById('serviceStatusTop'),
    video: document.getElementById('cameraVideo'),
    canvas: document.getElementById('captureCanvas'),
    routeSource: document.getElementById('verifyRoutes'),
    verifyResults: document.getElementById('verifyResults'),
    verifyEmpty: document.getElementById('verifyEmpty'),
  };

  if (!ui.video || !ui.canvas || !ui.routeSource || !ui.verifyResults || !ui.verifyEmpty) return;

  const CONFIG = {
    detection: {
      intervalMs: 200,
      model: 'full',
      minScoreEnter: 0.45,
      minScoreExit: 0.4,
      minFaceRatio: 0.04,
      stableWindowMs: 500,
      recentMs: 700,
    },
    capture: {
      intervalMs: 450,
      maxFaces: 2,
      cropBase: 384,
      cropBig: 448,
      cropQuality: 0.8,
      cropQualitySmall: 0.84,
      cropPaddingBase: 0.25,
      cropPaddingSmall: 0.5,
      smallFaceW: 0.07,
    },
    send: {
      intervalMs: 1200,
      loopTickMs: 220,
      maxBufferImages: 4,
      maxFramesPerSend: 2,
      timeoutMs: 2200,
      batchIouThreshold: 0.88,
      sameFaceSuppressMs: 3500,
      sameFaceIouThreshold: 0.82,
      resumeDelayMs: 400,
    },
    health: { pollMs: 4000, timeoutMs: 2500 },
    resultTtlMs: 10000,
    resultMax: 10,
    debug: false,
  };

  const CAMERA_CONSTRAINTS = [
    { width: { ideal: 1920, min: 640 }, height: { ideal: 1080, min: 480 }, frameRate: { ideal: 30, max: 30 }, facingMode: 'user' },
    { width: { ideal: 1280, min: 640 }, height: { ideal: 720, min: 480 }, frameRate: { ideal: 30, max: 30 }, facingMode: 'user' },
    { width: { ideal: 960, min: 640 }, height: { ideal: 540, min: 480 }, frameRate: { ideal: 30, max: 30 }, facingMode: 'user' },
    { width: { ideal: 640, min: 640 }, height: { ideal: 480, min: 480 }, frameRate: { ideal: 30, max: 30 }, facingMode: 'user' },
  ];

  let stream = null;
  let faceDetector = null;
  let detectionLoopTask = null;
  let captureLoopTask = null;
  let sendLoopTask = null;
  let healthTimer = null;
  let healthInFlight = false;
  let detectionInFlight = false;
  let captureInFlight = false;
  let liveInFlight = false;
  let liveActive = false;
  let serviceOnline = null;
  let lastAlert = '';
  let alertDismissTimer = null;
  let lastFaceDetected = false;
  let lastFaceSeenAt = 0;
  let lastDetections = [];
  let lastNormalizedFaces = [];
  let liveBuffer = [];
  let lastSendAt = 0;
  let verifyAbortController = null;
  let resultPruneTimer = null;
  let verifyResults = [];
  let lastRecognizedBox = null;
  let lastRecognizedAt = 0;

  function endpoint(type) {
    const fallback = new URL(type, window.location.href).toString();
    const map = {
      health: ui.routeSource.dataset.healthUrl,
      verify: ui.routeSource.dataset.verifyUrl,
    };
    try {
      return new URL(map[type] || fallback, window.location.href).toString();
    } catch (error) {
      return fallback;
    }
  }

  function inlineAlert(type, message) {
    lastAlert = showInlineAlert(ui.alertContainer, type, message, lastAlert);
    if (!ui.alertContainer || !message) return;
    if (alertDismissTimer) clearTimeout(alertDismissTimer);
    alertDismissTimer = window.setTimeout(() => {
      ui.alertContainer.innerHTML = '';
      lastAlert = '';
      alertDismissTimer = null;
    }, 4000);
  }

  function debugLog(...args) {
    if (!CONFIG.debug) return;
    console.debug('[verify]', ...args);
  }

  function setBadge(el, text, cls) {
    if (!el) return;
    el.className = `badge rounded-pill ${cls}`;
    el.textContent = text;
  }

  function setServiceStatus(state) {
    const map = { loading: ['Comprobando...', 'bg-secondary'], ok: ['Online', 'bg-success'], error: ['Offline', 'bg-danger'] };
    const [text, cls] = map[state] || map.loading;
    setBadge(ui.serviceStatusTop, text, cls);
  }

  function setServiceOnline(nextState) {
    if (serviceOnline === nextState) return;
    const previous = serviceOnline;
    serviceOnline = nextState;

    if (nextState) {
      setServiceStatus('ok');
      liveBuffer = [];
      if (liveActive) {
        lastSendAt = Date.now() + CONFIG.send.resumeDelayMs;
      }
      if (previous === false && liveActive) {
        inlineAlert('success', 'Servicio restaurado.');
      }
      stopHealthPolling();
      return;
    }

    setServiceStatus('error');
    liveBuffer = [];
    if (previous === true && liveActive) {
      inlineAlert('warning', 'Servicio fuera de linea. Reintentando...');
    }
    if (liveActive) {
      startHealthPolling();
    }
  }

  function syncCanvasSize() {
    const vw = ui.video.videoWidth || 640;
    const vh = ui.video.videoHeight || 480;
    if (ui.canvas.width !== vw) ui.canvas.width = vw;
    if (ui.canvas.height !== vh) ui.canvas.height = vh;
    return { vw, vh };
  }

  function sleep(ms) {
    return new Promise((resolve) => window.setTimeout(resolve, ms));
  }

  async function runDetectionLoop() {
    if (detectionLoopTask || !faceDetector) return;
    detectionLoopTask = (async () => {
      while (liveActive) {
        if (stream && ui.video.readyState >= 2 && !detectionInFlight) {
          detectionInFlight = true;
          try {
            await faceDetector.send({ image: ui.video });
          } catch (error) {
            debugLog('error en deteccion', error);
          } finally {
            detectionInFlight = false;
          }
        }
        await sleep(CONFIG.detection.intervalMs);
      }
      detectionInFlight = false;
      detectionLoopTask = null;
    })();
  }

  function handleFaceResults(results) {
    const detections = results?.detections ?? [];
    const minScore = lastFaceDetected ? CONFIG.detection.minScoreExit : CONFIG.detection.minScoreEnter;
    const foundNow = hasValidFace(detections, { minScore, minFaceRatio: CONFIG.detection.minFaceRatio });
    const now = Date.now();
    if (foundNow) {
      lastFaceSeenAt = now;
    }
    lastFaceDetected = (now - lastFaceSeenAt) <= CONFIG.detection.stableWindowMs;
    lastDetections = detections;
    lastNormalizedFaces = lastFaceDetected
      ? normalizeDetections(detections, ui.video, { minScore, minFaceRatio: CONFIG.detection.minFaceRatio })
      : [];
  }

  function pushResult(person) {
    const key = person.identificacion ? `id:${person.identificacion}` : (person.nombre || '').toLowerCase();
    if (!key) return;
    verifyResults = verifyResults.filter((item) => {
      const itemKey = item.identificacion ? `id:${item.identificacion}` : (item.nombre || '').toLowerCase();
      return itemKey !== key;
    });
    verifyResults.unshift({ ...person, lastSeenAt: Date.now() });
    if (verifyResults.length > CONFIG.resultMax) verifyResults.splice(CONFIG.resultMax);
  }

  function renderResults() {
    const cutoff = Date.now() - CONFIG.resultTtlMs;
    verifyResults = verifyResults.filter((item) => (item.lastSeenAt || 0) >= cutoff);
    ui.verifyResults.innerHTML = '';
    if (!verifyResults.length) {
      ui.verifyEmpty.classList.remove('d-none');
      ui.verifyEmpty.textContent = 'Aun no hay coincidencias.';
      return;
    }
    ui.verifyEmpty.classList.add('d-none');
    const fragment = document.createDocumentFragment();
    verifyResults.forEach((item) => {
      const card = document.createElement('div');
      card.className = 'verify-result-card p-3';
      card.innerHTML = `
        <div class="verify-result-name">${item.nombre || 'Sin nombre'}</div>
        ${item.identificacion ? `<div class="verify-result-doc text-muted mt-1">CC: ${item.identificacion}</div>` : ''}
      `;
      fragment.appendChild(card);
    });
    ui.verifyResults.appendChild(fragment);
  }

  function shouldSuppressSamePerson(candidates) {
    if (!Array.isArray(candidates) || candidates.length === 0) return false;
    if (!lastRecognizedBox || !lastRecognizedAt) return false;
    if ((Date.now() - lastRecognizedAt) > CONFIG.send.sameFaceSuppressMs) return false;
    const current = candidates[0]?.box || null;
    if (!current) return false;
    return iouBoxes(current, lastRecognizedBox) >= CONFIG.send.sameFaceIouThreshold;
  }

  function pickBatchCandidates() {
    if (!liveBuffer.length) return [];
    const sorted = [...liveBuffer].sort((a, b) => {
      if ((b.utility || 0) !== (a.utility || 0)) return (b.utility || 0) - (a.utility || 0);
      return (b.capturedAt || 0) - (a.capturedAt || 0);
    });

    const selected = [];
    for (const candidate of sorted) {
      if (selected.length >= CONFIG.send.maxFramesPerSend) break;
      const hasSimilar = selected.some((item) => iouBoxes(item.box, candidate.box) >= CONFIG.send.batchIouThreshold);
      if (hasSimilar) continue;
      selected.push(candidate);
    }

    if (!selected.length && sorted.length) {
      selected.push(sorted[0]);
    }
    return selected;
  }

  async function captureFrame() {
    if (!liveActive || !stream || ui.video.readyState < 2 || captureInFlight) return;
    if (serviceOnline !== true) {
      liveBuffer = [];
      return;
    }
    if (!lastFaceDetected || (Date.now() - lastFaceSeenAt) > CONFIG.detection.recentMs) {
      liveBuffer = [];
      return;
    }
    const faces = lastNormalizedFaces.length
      ? lastNormalizedFaces
      : normalizeDetections(lastDetections, ui.video, {
        minScore: CONFIG.detection.minScoreEnter,
        minFaceRatio: CONFIG.detection.minFaceRatio,
      });
    if (!faces.length) return;

    captureInFlight = true;
    try {
      syncCanvasSize();
      const selected = pickTopFaces(faces, CONFIG.capture.maxFaces);
      const capturedAt = Date.now();
      for (const face of selected) {
        const isSmall = face.box.width < CONFIG.capture.smallFaceW;
        const blob = await cropFaceToBlob(ui.video, ui.canvas, face, {
          padding: isSmall ? CONFIG.capture.cropPaddingSmall : CONFIG.capture.cropPaddingBase,
          size: isSmall ? CONFIG.capture.cropBig : CONFIG.capture.cropBase,
          quality: isSmall ? CONFIG.capture.cropQualitySmall : CONFIG.capture.cropQuality,
        });
        if (blob) {
          liveBuffer.push({
            blob,
            bytes: blob.size || 0,
            utility: faceUtilityScore(face),
            box: face?.box || null,
            capturedAt,
          });
        }
      }
      if (liveBuffer.length > CONFIG.send.maxBufferImages) {
        liveBuffer = liveBuffer.slice(-CONFIG.send.maxBufferImages);
      }
    } finally {
      captureInFlight = false;
    }
  }

  async function sendVerifyBatch() {
    if (!liveActive || serviceOnline !== true || liveInFlight) return;
    if ((Date.now() - lastSendAt) < CONFIG.send.intervalMs) return;
    if (!lastFaceDetected || (Date.now() - lastFaceSeenAt) > CONFIG.detection.recentMs) {
      liveBuffer = [];
      return;
    }
    const candidates = pickBatchCandidates();
    if (!candidates.length) return;

    if (shouldSuppressSamePerson(candidates)) {
      debugLog('suprimido: misma persona detectada en ventana corta');
      liveBuffer = liveBuffer.slice(-1);
      return;
    }

    liveInFlight = true;
    liveBuffer = [];
    const batchBytes = candidates.reduce((sum, c) => sum + (c.bytes || 0), 0);
    let timeoutId = null;
    const startedAt = performance.now();
    try {
      const controller = new AbortController();
      verifyAbortController = controller;
      lastSendAt = Date.now();
      timeoutId = window.setTimeout(() => controller.abort('request-timeout'), CONFIG.send.timeoutMs);
      const formData = new FormData();
      candidates.forEach((item, index) => {
        formData.append('images[]', item.blob, `verify_${index + 1}.jpg`);
      });
      const response = await fetch(endpoint('verify'), {
        method: 'POST',
        headers: { Accept: 'application/json', 'X-CSRF-TOKEN': getCsrfToken() },
        body: formData,
        credentials: 'same-origin',
        signal: controller.signal,
      });
      const payload = await response.json().catch(() => null);
      if (!response.ok || !payload || payload.ok !== true) {
        debugLog('respuesta invalida', {
          status: response.status,
          bytes: batchBytes,
          ms: Math.round(performance.now() - startedAt),
        });
        if (response.status >= 500) {
          setServiceOnline(false);
        }
        return;
      }

      setServiceOnline(true);
      const persons = Array.isArray(payload.personas) ? payload.personas : [];
      const valid = persons.filter((p) => !(isUnknown(p?.nombre) || isUnknown(p?.identificacion)));
      valid.forEach((person) => pushResult(person));
      if (valid.length) {
        renderResults();
        lastRecognizedBox = candidates[0]?.box || null;
        lastRecognizedAt = Date.now();
      }
      debugLog('batch enviado', {
        sent: candidates.length,
        recognized: valid.length,
        bytes: batchBytes,
        ms: Math.round(performance.now() - startedAt),
      });
    } catch (error) {
      if (error?.name !== 'AbortError') debugLog('error de red en validacion', error);
      setServiceOnline(false);
    } finally {
      if (timeoutId) clearTimeout(timeoutId);
      verifyAbortController = null;
      liveInFlight = false;
    }
  }

  async function checkHealth() {
    if (healthInFlight) return serviceOnline === true;
    if (serviceOnline === null) setServiceStatus('loading');
    healthInFlight = true;
    let timeoutId = null;
    try {
      const controller = new AbortController();
      timeoutId = window.setTimeout(() => controller.abort('health-timeout'), CONFIG.health.timeoutMs);
      const response = await fetch(endpoint('health'), {
        method: 'GET',
        headers: { Accept: 'application/json' },
        credentials: 'same-origin',
        signal: controller.signal,
      });
      const data = await response.json().catch(() => null);
      const ok = response.ok && data?.status && data.status !== 'error';
      setServiceOnline(ok);
      return ok;
    } catch (error) {
      setServiceOnline(false);
      return false;
    } finally {
      if (timeoutId) clearTimeout(timeoutId);
      healthInFlight = false;
    }
  }

  function startHealthPolling() {
    if (healthTimer) return;
    healthTimer = window.setInterval(() => { void checkHealth(); }, CONFIG.health.pollMs);
  }

  function stopHealthPolling() {
    if (!healthTimer) return;
    clearInterval(healthTimer);
    healthTimer = null;
  }

  async function startCamera() {
    if (stream) return true;
    let result = { stream: null, error: null };
    for (const constraints of CAMERA_CONSTRAINTS) {
      result = await requestCamera(ui.video, constraints);
      if (result.stream) break;
    }
    if (!result.stream) {
      inlineAlert('danger', result.error || 'No se pudo acceder a la camara.');
      return false;
    }
    stream = result.stream;
    syncCanvasSize();
    return true;
  }

  async function runCaptureLoop() {
    if (captureLoopTask) return;
    captureLoopTask = (async () => {
      while (liveActive) {
        await captureFrame();
        await sleep(CONFIG.capture.intervalMs);
      }
      captureLoopTask = null;
    })();
  }

  async function runSendLoop() {
    if (sendLoopTask) return;
    sendLoopTask = (async () => {
      while (liveActive) {
        await sendVerifyBatch();
        await sleep(CONFIG.send.loopTickMs);
      }
      sendLoopTask = null;
    })();
  }

  async function startLiveVerify() {
    if (liveActive) return;
    const ok = await startCamera();
    if (!ok) return;
    liveActive = true;
    const healthOk = await checkHealth();
    if (!healthOk) {
      startHealthPolling();
    } else {
      stopHealthPolling();
    }
    await runDetectionLoop();
    await runCaptureLoop();
    await runSendLoop();
  }

  function stopLiveVerify() {
    liveActive = false;
    if (verifyAbortController) {
      verifyAbortController.abort();
      verifyAbortController = null;
    }
    stopHealthPolling();
    liveBuffer = [];
    liveInFlight = false;
    captureInFlight = false;
    detectionInFlight = false;
    stream = stopStream(stream, ui.video);
  }

  window.addEventListener('pagehide', () => {
    stopLiveVerify();
    if (resultPruneTimer) {
      clearInterval(resultPruneTimer);
      resultPruneTimer = null;
    }
    if (alertDismissTimer) {
      clearTimeout(alertDismissTimer);
      alertDismissTimer = null;
    }
  });

  init();

  function init() {
    try {
      faceDetector = createFaceDetector({
        model: CONFIG.detection.model,
        minDetectionConfidence: CONFIG.detection.minScoreEnter,
        onResults: handleFaceResults,
        debug: CONFIG.debug,
      });
    } catch (error) {
      inlineAlert('danger', 'No se pudo inicializar la deteccion facial.');
      return;
    }
    setServiceStatus('loading');
    renderResults();
    resultPruneTimer = window.setInterval(renderResults, 1000);
    void startLiveVerify();
  }
});
