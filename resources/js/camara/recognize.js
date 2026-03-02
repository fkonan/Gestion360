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
  iouBoxes,
  faceUtilityScore,
} from './shared';
import { createFaceDetector } from './mediapipe';

document.addEventListener('DOMContentLoaded', () => {
  const ui = {
    alertContainer: document.getElementById('alertContainer'),
    cameraStatus: document.getElementById('cameraStatus'),
    faceStatus: document.getElementById('faceStatus'),
    serviceStatus: document.getElementById('serviceStatus'),
    serviceStatusTop: document.getElementById('serviceStatusTop'),
    serviceMessage: document.getElementById('serviceMessage'),
    lastPayloadSize: document.getElementById('lastPayloadSize'),
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
    latestEventsBtn: document.getElementById('latestEventsBtn'),
    latestEventsModal: document.getElementById('latestEventsModal'),
    latestEventsLoading: document.getElementById('latestEventsLoading'),
    latestEventsEmpty: document.getElementById('latestEventsEmpty'),
    latestEventsList: document.getElementById('latestEventsList'),
    todayEventsDocInput: document.getElementById('todayEventsDocInput'),
    todayEventsSearchBtn: document.getElementById('todayEventsSearchBtn'),
    todayEventsResetBtn: document.getElementById('todayEventsResetBtn'),
  };

  if (!ui.video || !ui.canvas) {
    return;
  }

  const CONFIG = {
    // Balanced for entry: faster response (~1s) without saturating network.
    detection: {
      intervalMs: 180,
      slowIntervalMs: 260,
      slowAfterMs: 3000,
      // Procesa deteccion en menor resolucion para bajar CPU sin afectar la captura real.
      processWidth: 960,
      processHeight: 540,
      model: 'full',
      minScoreEnter: 0.45,
      minScoreExit: 0.40,
      // Evita reconocer rostros lejanos; ajustado para ~1.5m en camaras tipo C920.
      minFaceRatio: 0.075,
      stableWindowMs: 500,
      recentMs: 700,
    },
    capture: {
      // Captura moderada: suficiente variedad sin sobrecargar CPU/red.
      intervalMs: 450,
      crowdThreshold: 5,
      maxFaces: 4,
      cropBase: 384,
      cropBig: 448,
      cropQuality: 0.8,
      cropQualitySmall: 0.84,
      cropPaddingBase: 0.25,
      cropPaddingSmall: 0.50,
      smallFaceW: 0.07,
      fullWidth: 1920,
      fullHeight: 1080,
      fullQuality: 0.78,
    },
    send: {
      // Produccion balanceada: respuesta rapida (~1-1.5s) con carga controlada.
      intervalMs: 1200,
      loopTickMs: 160,
      adaptiveInterval: true,
      minIntervalMs: 1000,
      maxIntervalMs: 1500,
      adaptStepUpMs: 120,
      adaptStepDownMs: 80,
      // Selecciona y envia pocos frames utiles por ciclo para evitar duplicados.
      maxImagesPerRequest: 5,
      maxBufferImages: 5,
      maxPayloadBytes: 700 * 1024,
      maxFramesPerSend: 2,
      preferBestFrames: true,
      enableFrameDedupe: true,
      dedupeWindowMs: 2200,
      bboxIouThreshold: 0.9,
      bboxCenterThreshold: 0.035,
      minFrameGapMs: 220,
      globalCooldownMs: 180,
      identityCooldownMs: 15000,
      maxConsecutiveFailures: 3,
      // Timeout de seguridad para evitar peticiones colgadas indefinidamente.
      timeoutMs: 4500,
    },
    health: {
      pollMs: 4000,
      resumeDelayMs: 400,
      timeoutMs: 2500,
    },
    session: {
      keepaliveMs: 60000,
      timeoutMs: 2500,
    },
    listMax: 50,
    listTtlMs: 7000,
    debug: false,
  };
  const CAMERA_CONSTRAINTS_FALLBACK = [
    {
      width: { ideal: 1920, min: 640 },
      height: { ideal: 1080, min: 480 },
      frameRate: { ideal: 30, max: 30 },
      facingMode: 'user',
    },
    {
      width: { ideal: 1280, min: 640 },
      height: { ideal: 720, min: 480 },
      frameRate: { ideal: 30, max: 30 },
      facingMode: 'user',
    },
    {
      width: { ideal: 960, min: 640 },
      height: { ideal: 540, min: 480 },
      frameRate: { ideal: 30, max: 30 },
      facingMode: 'user',
    },
    {
      width: { ideal: 640, min: 640 },
      height: { ideal: 480, min: 480 },
      frameRate: { ideal: 30, max: 30 },
      facingMode: 'user',
    },
  ];
  // Ajustes rapidos:
  // - Mas lejos: bajar minFaceRatio/minScore, subir width/quality.
  // - Menos CPU/red: subir cooldowns o bajar maxFramesPerSend/quality.

  let stream = null;
  let detectionCanvas = null;
  let detectionCtx = null;
  let faceDetector = null;
  let detectionTimer = null;
  let detectionInFlight = false;
  let detectionNextAt = 0;
  let noFaceSinceAt = 0;
  let currentDetectionIntervalMs = CONFIG.detection.intervalMs;
  let lastFaceDetected = false;
  let liveActive = false;
  let liveCaptureLoopActive = false;
  let liveSendLoopActive = false;
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
  let captureInFlight = false;
  let faceRotationIndex = 0;
  let recognizeAbortController = null;
  let alertDismissTimer = null;
  let recognizedPruneTimer = null;
  let keepaliveTimer = null;
  let keepaliveInFlight = false;
  let sendFailureStreak = 0;
  let dynamicSendIntervalMs = CONFIG.send.intervalMs;
  let latestEventsModalInstance = null;
  let latestEventsInFlight = false;
  const recentCapturedFaces = [];
  let cycleStats = {
    captured: 0,
    discardedDedupe: 0,
    discardedSelection: 0,
    sent: 0,
    sentBytes: 0,
  };

  function inlineAlert(type, message) {
    lastAlert = showInlineAlert(ui.alertContainer, type, message, lastAlert);
    if (!ui.alertContainer || !message) {
      return;
    }
    if (alertDismissTimer) {
      clearTimeout(alertDismissTimer);
    }
    alertDismissTimer = window.setTimeout(() => {
      ui.alertContainer.innerHTML = '';
      lastAlert = '';
      alertDismissTimer = null;
    }, 4000);
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
    if (!ui.serviceStatus && !ui.serviceStatusTop) {
      return;
    }
    const map = {
      loading: { text: 'Comprobando...', cls: 'bg-secondary' },
      ok: { text: 'Online', cls: 'bg-success' },
      error: { text: 'Offline', cls: 'bg-danger' },
    };
    const cfg = map[state] || map.loading;
    if (ui.serviceStatus) {
      ui.serviceStatus.className = `badge ${cfg.cls}`;
      ui.serviceStatus.textContent = cfg.text;
    }
    if (ui.serviceStatusTop) {
      ui.serviceStatusTop.className = `badge rounded-pill ${cfg.cls}`;
      ui.serviceStatusTop.textContent = cfg.text;
    }
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

  function getRequestedCameraSummary() {
    return '1920x1080@30';
  }

  async function waitForVideoMetadata(videoEl) {
    if (videoEl.videoWidth && videoEl.videoHeight) {
      return;
    }
    await new Promise((resolve) => {
      const onLoaded = () => {
        clearTimeout(timeoutId);
        videoEl.removeEventListener('loadedmetadata', onLoaded);
        resolve();
      };
      const timeoutId = window.setTimeout(() => {
        videoEl.removeEventListener('loadedmetadata', onLoaded);
        resolve();
      }, 1500);
      videoEl.addEventListener('loadedmetadata', onLoaded, { once: true });
    });
  }

  function syncProcessingCanvases() {
    const vw = ui.video.videoWidth || CONFIG.capture.fullWidth;
    const vh = ui.video.videoHeight || CONFIG.capture.fullHeight;
    if (!vw || !vh) {
      return { width: CONFIG.capture.fullWidth, height: CONFIG.capture.fullHeight };
    }
    if (ui.canvas.width !== vw) ui.canvas.width = vw;
    if (ui.canvas.height !== vh) ui.canvas.height = vh;
    if (!detectionCanvas) {
      detectionCanvas = document.createElement('canvas');
      detectionCtx = detectionCanvas.getContext('2d');
    }

    const maxDw = Math.max(320, Number(CONFIG.detection.processWidth) || vw);
    const maxDh = Math.max(240, Number(CONFIG.detection.processHeight) || vh);
    const scale = Math.min(1, maxDw / vw, maxDh / vh);
    const dw = Math.max(320, Math.round(vw * scale));
    const dh = Math.max(240, Math.round(vh * scale));
    if (detectionCanvas.width !== dw) detectionCanvas.width = dw;
    if (detectionCanvas.height !== dh) detectionCanvas.height = dh;
    return { width: vw, height: vh };
  }

  function sleep(ms) {
    return new Promise((resolve) => {
      window.setTimeout(resolve, ms);
    });
  }

  async function fetchJsonWithTimeout(url, options = {}, timeoutMs = 0) {
    const controller = new AbortController();
    const timeoutId = timeoutMs > 0
      ? window.setTimeout(() => controller.abort('request-timeout'), timeoutMs)
      : null;
    try {
      const response = await fetch(url, {
        ...options,
        signal: controller.signal,
      });
      const data = await response.json().catch(() => null);
      return { response, data, aborted: false };
    } catch (error) {
      const aborted = !!(error && error.name === 'AbortError');
      return { response: null, data: null, error, aborted };
    } finally {
      if (timeoutId) {
        clearTimeout(timeoutId);
      }
    }
  }

  function startSendLoop() {
    if (liveSendLoopActive || !liveActive) return;
    liveSendLoopActive = true;
    const run = async () => {
      while (liveSendLoopActive && liveActive) {
        await sendLiveBatch();
        if (!liveSendLoopActive || !liveActive) {
          break;
        }
        await sleep(CONFIG.send.loopTickMs);
      }
    };
    void run();
  }

  function stopSendLoop() {
    liveSendLoopActive = false;
  }

  function getCurrentSendIntervalMs() {
    if (!CONFIG.send.adaptiveInterval) {
      return CONFIG.send.intervalMs;
    }
    return dynamicSendIntervalMs;
  }

  function adjustSendIntervalMs(lastCycleMs) {
    if (!CONFIG.send.adaptiveInterval || !Number.isFinite(lastCycleMs)) {
      return;
    }
    const base = CONFIG.send.intervalMs;
    if (lastCycleMs > (base + 250)) {
      dynamicSendIntervalMs = Math.min(
        CONFIG.send.maxIntervalMs,
        dynamicSendIntervalMs + CONFIG.send.adaptStepUpMs
      );
      return;
    }
    if (lastCycleMs < (base - 250)) {
      dynamicSendIntervalMs = Math.max(
        CONFIG.send.minIntervalMs,
        dynamicSendIntervalMs - CONFIG.send.adaptStepDownMs
      );
    }
  }

  function startCaptureLoop() {
    if (liveCaptureLoopActive || !liveActive) return;
    liveCaptureLoopActive = true;
    const run = async () => {
      while (liveCaptureLoopActive && liveActive) {
        if (serviceOnline === false) {
          liveBuffer = [];
        } else if (liveInFlight && liveBuffer.length >= Math.max(1, CONFIG.send.maxFramesPerSend)) {
          // Mientras hay una request activa, no seguir acumulando frames similares.
        } else if (lastFaceDetected && (Date.now() - lastFaceSeenAt) <= CONFIG.detection.recentMs) {
          await captureFrame();
        } else {
          liveBuffer = [];
        }

        if (!liveCaptureLoopActive || !liveActive) {
          break;
        }
        await sleep(CONFIG.capture.intervalMs);
      }
    };
    void run();
  }

  function stopCaptureLoop() {
    liveCaptureLoopActive = false;
  }

  function markServiceOffline() {
    if (serviceOnline === false) {
      setServiceStatus('error');
      return;
    }
    serviceOnline = false;
    setServiceStatus('error');
    setServiceMessage('Servicio fuera de linea. Esperando reconexion...', 'text-danger');
    liveBuffer = [];
    resetCycleStats();
    stopSendLoop();
    startHealthPolling();
  }

  function registerSendFailure() {
    sendFailureStreak += 1;
    if (sendFailureStreak >= CONFIG.send.maxConsecutiveFailures) {
      markServiceOffline();
      return true;
    }
    return false;
  }

  function markServiceOnline() {
    if (serviceOnline === true) {
      setServiceStatus('ok');
      return;
    }
    serviceOnline = true;
    sendFailureStreak = 0;
    setServiceStatus('ok');
    setServiceMessage('Servicio restaurado.', 'text-success');
    window.setTimeout(() => setServiceMessage(''), 2500);
    liveBuffer = [];
    resetCycleStats();
    lastSendAt = 0;
    dynamicSendIntervalMs = CONFIG.send.intervalMs;
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

  function resetCycleStats() {
    cycleStats = {
      captured: 0,
      discardedDedupe: 0,
      discardedSelection: 0,
      sent: 0,
      sentBytes: 0,
    };
  }

  function makeCandidate(face, blob, mode, capturedAt) {
    const score = face?.score || 0;
    const faceRatio = face?.box?.width || 0;
    const centerDist = face?.centerDist || 1;
    const utility = faceUtilityScore(face);
    return {
      blob,
      bytes: blob?.size || 0,
      capturedAt,
      mode,
      score,
      faceRatio,
      centerDist,
      utility,
      box: face?.box || null,
    };
  }

  function isNearDuplicate(candidate) {
    if (!CONFIG.send.enableFrameDedupe || !candidate?.box) {
      return false;
    }
    const now = candidate.capturedAt || Date.now();
    const cutoff = now - CONFIG.send.dedupeWindowMs;
    for (let i = recentCapturedFaces.length - 1; i >= 0; i -= 1) {
      const prev = recentCapturedFaces[i];
      if ((prev.capturedAt || 0) < cutoff) {
        break;
      }
      const iou = iouBoxes(candidate.box, prev.box);
      const dx = Math.abs((candidate.box.xCenter || 0) - (prev.box.xCenter || 0));
      const dy = Math.abs((candidate.box.yCenter || 0) - (prev.box.yCenter || 0));
      if (iou >= CONFIG.send.bboxIouThreshold &&
        dx <= CONFIG.send.bboxCenterThreshold &&
        dy <= CONFIG.send.bboxCenterThreshold) {
        return true;
      }
    }
    return false;
  }

  function registerCapturedFace(candidate) {
    if (!candidate?.box) return;
    recentCapturedFaces.push({
      capturedAt: candidate.capturedAt,
      box: candidate.box,
    });
    const cutoff = (candidate.capturedAt || Date.now()) - (CONFIG.send.dedupeWindowMs * 2);
    while (recentCapturedFaces.length && (recentCapturedFaces[0].capturedAt || 0) < cutoff) {
      recentCapturedFaces.shift();
    }
  }

  function appendToLiveBuffer(candidates) {
    if (!Array.isArray(candidates) || candidates.length === 0) return;
    liveBuffer.push(...candidates);
    if (liveBuffer.length > CONFIG.send.maxBufferImages) {
      liveBuffer.splice(0, liveBuffer.length - CONFIG.send.maxBufferImages);
    }
  }

  function buildBatchWithinLimits() {
    const maxCount = Math.max(1, CONFIG.send.maxImagesPerRequest);
    const maxFrames = Math.max(1, CONFIG.send.maxFramesPerSend || maxCount);
    const maxBytes = Math.max(64 * 1024, CONFIG.send.maxPayloadBytes || (900 * 1024));
    if (!liveBuffer.length) {
      return [];
    }

    const source = liveBuffer.slice(-maxCount);
    const candidates = CONFIG.send.preferBestFrames
      ? [...source].sort((a, b) => {
        if ((b.utility || 0) !== (a.utility || 0)) return (b.utility || 0) - (a.utility || 0);
        return (b.capturedAt || 0) - (a.capturedAt || 0);
      })
      : [...source].reverse();

    const selected = [];
    let totalBytes = 0;
    for (const candidate of candidates) {
      const nextSize = candidate?.bytes || 0;
      // Siempre permitir al menos una imagen por request.
      if (selected.length > 0 && (totalBytes + nextSize) > maxBytes) {
        cycleStats.discardedSelection += 1;
        continue;
      }
      if (selected.length >= maxFrames) {
        cycleStats.discardedSelection += 1;
        continue;
      }
      const tooClose = selected.some((item) => {
        const dt = Math.abs((candidate.capturedAt || 0) - (item.capturedAt || 0));
        if (dt < CONFIG.send.minFrameGapMs) return true;
        if (!candidate?.box || !item?.box) return false;
        if (dt > CONFIG.send.dedupeWindowMs) return false;
        return iouBoxes(candidate.box, item.box) >= CONFIG.send.bboxIouThreshold;
      });
      if (tooClose) {
        cycleStats.discardedSelection += 1;
        continue;
      }
      selected.push(candidate);
      totalBytes += nextSize;
    }

    if (CONFIG.send.preferBestFrames) {
      selected.sort((a, b) => (a.capturedAt || 0) - (b.capturedAt || 0));
    } else {
      selected.reverse();
    }

    return selected;
  }

  function getEndpointUrl(type) {
    const fallback = new URL(type, window.location.href).toString();
    if (!ui.startBtn) {
      return fallback;
    }
    const routes = {
      health: ui.startBtn.dataset.healthUrl,
      keepalive: ui.startBtn.dataset.keepaliveUrl,
      recognize: ui.startBtn.dataset.recognizeUrl,
      latestEvents: ui.startBtn.dataset.latestEventsUrl,
      todayEvents: ui.startBtn.dataset.todayEventsUrl,
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

  async function pingSessionKeepalive(force = false) {
    if (keepaliveInFlight && !force) {
      return;
    }
    keepaliveInFlight = true;
    try {
      const { response, aborted } = await fetchJsonWithTimeout(getEndpointUrl('keepalive'), {
        method: 'GET',
        headers: {
          'Accept': 'application/json',
        },
        credentials: 'same-origin',
      }, CONFIG.session.timeoutMs);
      if (aborted) {
        if (CONFIG.debug) {
          console.debug('[camara][keepalive] timeout');
        }
        return;
      }
      if (!response) {
        return;
      }
      if (response.status === 401 || response.status === 419) {
        inlineAlert('warning', 'La sesion expiro. Recargando...');
        window.setTimeout(() => window.location.reload(), 1200);
      }
    } catch (error) {
      if (CONFIG.debug) {
        console.debug('[camara][keepalive] fallo', error);
      }
    } finally {
      keepaliveInFlight = false;
    }
  }

  function startSessionKeepalive() {
    if (keepaliveTimer) {
      return;
    }
    pingSessionKeepalive(true);
    keepaliveTimer = window.setInterval(() => {
      void pingSessionKeepalive();
    }, CONFIG.session.keepaliveMs);
  }

  function stopSessionKeepalive() {
    if (!keepaliveTimer) {
      return;
    }
    clearInterval(keepaliveTimer);
    keepaliveTimer = null;
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
    const now = Date.now();
    const detections = results?.detections ?? [];
    const minScore = lastFaceDetected ? CONFIG.detection.minScoreExit : CONFIG.detection.minScoreEnter;
    const foundNow = hasValidFace(detections, {
      minScore,
      minFaceRatio: CONFIG.detection.minFaceRatio,
    });

    if (foundNow) {
      lastFaceSeenAt = now;
    }
    lastDetections = detections;
    lastDetectionsAt = now;

    if (!window.__mpLastSeenAt) window.__mpLastSeenAt = 0;
    if (foundNow) window.__mpLastSeenAt = now;
    const stable = (now - window.__mpLastSeenAt) <= CONFIG.detection.stableWindowMs;

    lastFaceDetected = stable;
    setFaceStatus(stable);
    if (stable) {
      noFaceSinceAt = 0;
      currentDetectionIntervalMs = CONFIG.detection.intervalMs;
    } else {
      noFaceSinceAt = noFaceSinceAt || now;
      if ((now - noFaceSinceAt) >= CONFIG.detection.slowAfterMs) {
        currentDetectionIntervalMs = CONFIG.detection.slowIntervalMs;
      }
    }
    if (!stable) {
      liveBuffer = [];
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
      const now = Date.now();
      if (now < detectionNextAt) {
        return;
      }
      detectionNextAt = now + currentDetectionIntervalMs;
      detectionInFlight = true;
      syncProcessingCanvases();
      if (!detectionCanvas || !detectionCtx) {
        detectionInFlight = false;
        return;
      }
      detectionCtx.drawImage(ui.video, 0, 0, detectionCanvas.width, detectionCanvas.height);
      faceDetector.send({ image: detectionCanvas })
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
    detectionNextAt = 0;
    noFaceSinceAt = 0;
    currentDetectionIntervalMs = CONFIG.detection.intervalMs;
  }

  async function startCamera() {
    if (stream) {
      return true;
    }
    if (CONFIG.debug) {
      console.info(`[camera] requested ${getRequestedCameraSummary()}`);
    }
    let result = { stream: null, error: null };
    for (const constraints of CAMERA_CONSTRAINTS_FALLBACK) {
      result = await requestCamera(ui.video, constraints);
      if (result.stream) {
        break;
      }
    }
    if (!result.stream) {
      inlineAlert('danger', result.error || 'No se pudo acceder a la camara.');
      setCameraStatus('error');
      setControls(false, true);
      return false;
    }
    stream = result.stream;
    await waitForVideoMetadata(ui.video);
    const dims = syncProcessingCanvases();
    const track = stream.getVideoTracks?.()[0];
    const settings = track?.getSettings ? track.getSettings() : {};
    const gotWidth = ui.video.videoWidth || dims.width;
    const gotHeight = ui.video.videoHeight || dims.height;
    const gotFps = settings.frameRate ? Number(settings.frameRate).toFixed(1) : '~30';
    if (CONFIG.debug) {
      console.info(`[camera] got ${gotWidth}x${gotHeight}@${gotFps}`);
    }
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
        ? 'fas fa-sign-in-alt fa-2x'
        : 'fas fa-sign-out-alt fa-2x';
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

  function handleEventKeyboardShortcut(event) {
    const target = event.target;
    const tag = target?.tagName;
    const isEditable = target?.isContentEditable
      || tag === 'INPUT'
      || tag === 'TEXTAREA'
      || tag === 'SELECT';
    if (isEditable) {
      return;
    }

    // Atajo solo visual/UI: tecla 1 => Ingreso, tecla 2 => Salida.
    // Los valores reales enviados al backend se mantienen (Ingreso=2, Salida=1).
    if (event.key === '1' || event.code === 'Numpad1') {
      setSelectedEvent('2');
    } else if (event.key === '2' || event.code === 'Numpad2') {
      setSelectedEvent('1');
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

    const detectionRef = detectionCanvas || ui.video;
    const faces = normalizeDetections(lastDetections, detectionRef, {
      minScore: CONFIG.detection.minScoreEnter,
      minFaceRatio: CONFIG.detection.minFaceRatio,
    });
    if (!faces.length) {
      liveBuffer = [];
      return;
    }

    captureInFlight = true;
    try {
      const dims = syncProcessingCanvases();
      if (faces.length >= CONFIG.capture.crowdThreshold) {
        // Modo CROWD: enviar 1 frame completo cuando hay muchas caras.
        const refFace = pickTopFaces(faces, 1)[0] || null;
        const blob = await captureFullFrameBlob(ui.video, ui.canvas, {
          width: dims.width,
          height: dims.height,
          quality: CONFIG.capture.fullQuality,
        });
        if (blob && refFace) {
          const candidate = makeCandidate(refFace, blob, 'crowd', Date.now());
          if (isNearDuplicate(candidate)) {
            cycleStats.discardedDedupe += 1;
          } else {
            cycleStats.captured += 1;
            registerCapturedFace(candidate);
            appendToLiveBuffer([candidate]);
          }
        }
        return;
      }

      // Modo PRECISION: crops por cara (1..4).
      if (faces.length > CONFIG.capture.maxFaces) {
        faceRotationIndex = (faceRotationIndex + 1) % faces.length;
      }
      const selected = pickTopFaces(faces, CONFIG.capture.maxFaces, faceRotationIndex);
      if (!selected.length) {
        liveBuffer = [];
        return;
      }

      const candidates = [];
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
        if (!blob) continue;
        const candidate = makeCandidate(face, blob, 'crop', Date.now());
        if (isNearDuplicate(candidate)) {
          cycleStats.discardedDedupe += 1;
          continue;
        }
        cycleStats.captured += 1;
        registerCapturedFace(candidate);
        candidates.push(candidate);
      }
      appendToLiveBuffer(candidates);
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
        fechaRegistro: person?.fecha_registro ? String(person.fecha_registro).trim() : '',
        horaRegistro: person?.hora_registro ? String(person.hora_registro).trim() : '',
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
    if (!key) return false;
    recognizedList = recognizedList.filter((item) => getPersonKey(item) !== key);
    recognizedList.unshift({
      ...person,
      lastSeenAt: Date.now(),
    });
    if (CONFIG.listMax && recognizedList.length > CONFIG.listMax) {
      recognizedList.splice(CONFIG.listMax);
    }
    return true;
  }

  function pruneRecognizedList() {
    const cutoff = Date.now() - CONFIG.listTtlMs;
    const next = recognizedList.filter((item) => (item.lastSeenAt || 0) >= cutoff);
    const changed = next.length !== recognizedList.length;
    recognizedList = next;
    return changed;
  }

  function escapeHtml(value) {
    const text = String(value ?? '');
    return text
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;')
      .replace(/'/g, '&#39;');
  }

  function highlightMatch(text, term) {
    const source = String(text ?? '');
    if (!term) return escapeHtml(source);
    const lower = source.toLowerCase();
    const idx = lower.indexOf(term);
    if (idx === -1) return escapeHtml(source);
    const before = source.slice(0, idx);
    const match = source.slice(idx, idx + term.length);
    const after = source.slice(idx + term.length);
    return `${escapeHtml(before)}<mark class="px-1">${escapeHtml(match)}</mark>${escapeHtml(after)}`;
  }

  function sanitizeIdentificationInput(value) {
    return String(value || '').replace(/\D+/g, '').slice(0, 20);
  }

  function getEventVisual(eventCode) {
    const isSalida = Number(eventCode) === 1;
    return {
      isSalida,
      label: isSalida ? 'Salida' : 'Ingreso',
      badgeClass: isSalida ? 'bg-danger' : 'bg-success',
      borderColor: isSalida ? '#dc3545' : '#198754',
    };
  }

  function setSearchStatus(term, listLength) {
    if (!ui.searchStatus) return;
    if (!term) {
      ui.searchStatus.className = 'badge bg-secondary d-none';
      ui.searchStatus.textContent = 'Encontrado';
      return;
    }
    if (listLength > 0) {
      ui.searchStatus.className = 'badge bg-success';
      ui.searchStatus.textContent = 'Encontrado';
      return;
    }
    ui.searchStatus.className = 'badge bg-danger';
    ui.searchStatus.textContent = 'No aparece';
  }

  function buildRecognizedItemElement(item, term) {
    const li = document.createElement('li');
    li.className = 'list-group-item';
    const name = item.nombre || item.identificacion || 'Sin nombre';
    const idText = item.identificacion ? `CC: ${item.identificacion}` : '';
    const registeredTime = escapeHtml(item.horaRegistro || '--');
    const registeredDate = escapeHtml(item.fechaRegistro || '--');
    const eventVisual = getEventVisual(item.evento);

    li.innerHTML = `
      <div class="recognize-card-head">
        <div class="recognize-card-ident">
          <div class="recognize-card-name">${highlightMatch(name, term)}</div>
          ${idText ? `<div class="recognize-card-doc">${highlightMatch(idText, term)}</div>` : ''}
        </div>
        <span class="badge ${eventVisual.badgeClass} recognize-event-badge">${eventVisual.label}</span>
      </div>
      <div class="recognize-meta-grid">
        <div class="recognize-meta-pill recognize-meta-pill-date">
          <span class="recognize-meta-label">Fecha:</span>
          <span class="recognize-meta-value">${registeredDate}</span>
        </div>
        <div class="recognize-meta-pill recognize-meta-pill-time">
          <span class="recognize-meta-label"><i class="far fa-clock"></i> Hora:</span>
          <span class="recognize-meta-value">${registeredTime}</span>
        </div>
      </div>
    `;
    return li;
  }

  function buildLatestEventItemElement(item) {
    const li = document.createElement('li');
    const eventVisual = getEventVisual(item?.evento || 0);
    const nombre = escapeHtml(item?.nombre || 'Sin nombre');
    const identificacion = item?.identificacion ? `CC ${escapeHtml(item.identificacion)}` : '';
    const descripcion = item?.descripcion ? escapeHtml(String(item.descripcion).trim()) : '';
    const hora = escapeHtml(item?.hora_evento || '--');

    li.className = 'list-group-item d-flex align-items-center justify-content-between gap-3';
    li.style.borderLeft = `4px solid ${eventVisual.borderColor}`;
    li.innerHTML = `
      <div style="min-width:0; flex:1 1 auto;">
        <div class="fw-semibold text-truncate">${nombre}</div>
        ${identificacion ? `<div class="text-muted small">${identificacion}</div>` : ''}
        ${descripcion ? `<div class="small">${descripcion}</div>` : ''}
      </div>
      <div class="d-flex flex-column align-items-end gap-1" style="white-space:nowrap;">
        <span class="badge ${eventVisual.badgeClass}">${eventVisual.label}</span>
        <div class="fw-semibold" style="font-size:1rem;">${hora}</div>
      </div>
    `;
    return li;
  }

  function setLatestEventsFetchError(message = 'No se pudieron consultar los registros.') {
    renderLatestEvents([], message);
    if (!ui.latestEventsEmpty) return;
    ui.latestEventsEmpty.classList.remove('d-none');
    ui.latestEventsEmpty.textContent = message;
  }

  function setLatestEventsLoading(isLoading) {
    if (ui.latestEventsLoading) {
      ui.latestEventsLoading.classList.toggle('d-none', !isLoading);
    }
    if (ui.latestEventsBtn) {
      ui.latestEventsBtn.disabled = isLoading;
    }
  }

  function renderRecognized() {
    if (!ui.recognizeList || !ui.recognizeEmpty) {
      return;
    }
    pruneRecognizedList();
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

    const fragment = document.createDocumentFragment();
    list.forEach((item) => {
      fragment.appendChild(buildRecognizedItemElement(item, term));
    });
    ui.recognizeList.appendChild(fragment);

    setSearchStatus(term, list.length);
  }

  function renderLatestEvents(items, emptyText = 'Sin registros recientes.') {
    if (!ui.latestEventsList || !ui.latestEventsEmpty) {
      return;
    }
    ui.latestEventsList.innerHTML = '';
    if (!Array.isArray(items) || items.length === 0) {
      ui.latestEventsEmpty.classList.remove('d-none');
      ui.latestEventsEmpty.textContent = emptyText;
      return;
    }

    ui.latestEventsEmpty.classList.add('d-none');
    const fragment = document.createDocumentFragment();
    items.forEach((item) => {
      fragment.appendChild(buildLatestEventItemElement(item));
    });
    ui.latestEventsList.appendChild(fragment);
  }

  function getTodayEventsDocQuery() {
    if (!ui.todayEventsDocInput) return '';
    return sanitizeIdentificationInput(ui.todayEventsDocInput.value);
  }

  async function loadLatestEvents() {
    if (latestEventsInFlight || !ui.latestEventsList) {
      return;
    }
    latestEventsInFlight = true;
    setLatestEventsLoading(true);
    try {
      const { response, data, aborted } = await fetchJsonWithTimeout(getEndpointUrl('latestEvents'), {
        method: 'GET',
        headers: {
          'Accept': 'application/json',
        },
        credentials: 'same-origin',
      }, 3500);

      if (aborted || !response || !response.ok || !data?.ok) {
        setLatestEventsFetchError();
        return;
      }
      renderLatestEvents(Array.isArray(data.data) ? data.data : []);
    } catch (error) {
      setLatestEventsFetchError();
    } finally {
      latestEventsInFlight = false;
      setLatestEventsLoading(false);
    }
  }

  async function loadTodayEventsByDoc() {
    const identificacion = getTodayEventsDocQuery();
    if (!identificacion) {
      setLatestEventsFetchError('Ingresa una identificacion valida.');
      return;
    }
    if (latestEventsInFlight || !ui.latestEventsList) {
      return;
    }
    latestEventsInFlight = true;
    setLatestEventsLoading(true);
    try {
      const url = new URL(getEndpointUrl('todayEvents'));
      url.searchParams.set('identificacion', identificacion);
      const { response, data, aborted } = await fetchJsonWithTimeout(url.toString(), {
        method: 'GET',
        headers: {
          'Accept': 'application/json',
        },
        credentials: 'same-origin',
      }, 3500);

      if (aborted || !response || !response.ok || !data?.ok) {
        setLatestEventsFetchError('No se pudo consultar los registros de hoy.');
        return;
      }
      const items = Array.isArray(data.data) ? data.data : [];
      renderLatestEvents(items, 'No hay registros hoy para esta identificacion.');
    } catch (error) {
      setLatestEventsFetchError('No se pudo consultar los registros de hoy.');
    } finally {
      latestEventsInFlight = false;
      setLatestEventsLoading(false);
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
    if ((now - lastSendAt) < getCurrentSendIntervalMs()) {
      return;
    }
    if (!lastFaceDetected || (now - lastFaceSeenAt) > CONFIG.detection.recentMs) {
      liveBuffer = [];
      return;
    }
    if (liveBuffer.length === 0) {
      return;
    }
    if (!canSendNow()) return;

    liveInFlight = true;
    const batchCandidates = buildBatchWithinLimits();
    if (batchCandidates.length === 0) {
      liveBuffer = [];
      liveInFlight = false;
      return;
    }
    // Reinicia ventana de captura tras cada envio: no mantener backlog antiguo.
    liveBuffer = [];
    const batch = batchCandidates.map((item) => item.blob).filter(Boolean);
    if (batch.length === 0) {
      liveInFlight = false;
      return;
    }
    const batchSizeBytes = batchCandidates.reduce((sum, item) => sum + (item?.bytes || 0), 0);
    setLastPayloadSize(batchSizeBytes);

    let timeoutId = null;
    try {
      const controller = new AbortController();
      recognizeAbortController = controller;
      const startedAt = performance.now();
      lastSendAt = Date.now();
      if (CONFIG.send.timeoutMs > 0) {
        timeoutId = window.setTimeout(() => controller.abort('request-timeout'), CONFIG.send.timeoutMs);
      }
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
        const offlineMarked = registerSendFailure();
        if (offlineMarked) {
          inlineAlert('warning', 'Servicio de reconocimiento fuera de linea.');
        } else if (CONFIG.debug) {
          console.debug('[camara][recognize-live] fallo transitorio', response.status);
        }
        return;
      }
      sendFailureStreak = 0;
      const persons = extractPersons(data);
      let listChanged = false;
      if (persons.length) {
        persons.forEach((person) => {
          const key = getPersonKey(person);
          if (!key) return;
          listChanged = upsertRecognized(person) || listChanged;
        });
      }
      const updatedAt = performance.now();
      let renderedAt = updatedAt;
      if (listChanged) {
        renderRecognized();
        renderedAt = performance.now();
      }
      adjustSendIntervalMs(parsedAt - startedAt);
      if (CONFIG.debug) {
        console.debug('[camara][live] ok', {
          sentFrames: batch.length,
          sendIntervalMs: getCurrentSendIntervalMs(),
          payloadKb: Number((batchSizeBytes / 1024).toFixed(1)),
          tHeadersMs: Number((headersAt - startedAt).toFixed(1)),
          tParseMs: Number((parsedAt - headersAt).toFixed(1)),
          tRenderMs: Number((renderedAt - updatedAt).toFixed(1)),
          tTotalMs: Number((renderedAt - startedAt).toFixed(1)),
          listChanged,
          captured: cycleStats.captured,
          discardedDedupe: cycleStats.discardedDedupe,
          discardedSelection: cycleStats.discardedSelection,
        });
      }
      cycleStats.sent = batch.length;
      cycleStats.sentBytes = batchSizeBytes;
      resetCycleStats();
    } catch (error) {
      const isAbort = !!(error && error.name === 'AbortError');
      if (!isAbort) {
        const offlineMarked = registerSendFailure();
        if (offlineMarked) {
          inlineAlert('warning', 'Error de red en reconocimiento en vivo.');
        } else if (CONFIG.debug) {
          console.debug('[camara][recognize-live] error transitorio de red', {
            message: error?.message || 'network-error',
            failureStreak: sendFailureStreak,
          });
        }
      } else if (CONFIG.debug) {
        console.debug('[camara][recognize-live] request aborted', {
          reason: error?.message || 'abort',
        });
      }
      resetCycleStats();
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
    resetCycleStats();
    sendFailureStreak = 0;
    dynamicSendIntervalMs = CONFIG.send.intervalMs;
    const healthOk = await checkHealth();
    if (!healthOk) {
      startHealthPolling();
    }
    startCaptureLoop();
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
    stopCaptureLoop();
    stopSendLoop();
    stopHealthPolling();
    liveBuffer = [];
    resetCycleStats();
    sendFailureStreak = 0;
    dynamicSendIntervalMs = CONFIG.send.intervalMs;
    liveInFlight = false;
    setControls(false, false);
    stopCamera();
  }

  async function checkHealth() {
    if (healthInFlight) {
      return serviceOnline === true;
    }
    if (serviceOnline === null) {
      setServiceStatus('loading');
    }
    healthInFlight = true;
    try {
      const { response, data, aborted } = await fetchJsonWithTimeout(getEndpointUrl('health'), {
        method: 'GET',
        headers: {
          'Accept': 'application/json',
        },
        credentials: 'same-origin',
      }, CONFIG.health.timeoutMs);
      if (aborted || !response) {
        markServiceOffline();
        return false;
      }
      if (!response.ok) {
        markServiceOffline();
        return false;
      }
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
    document.addEventListener('keydown', handleEventKeyboardShortcut);
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

  if (ui.latestEventsBtn) {
    ui.latestEventsBtn.addEventListener('click', () => {
      if (ui.latestEventsModal && window.bootstrap?.Modal) {
        if (!latestEventsModalInstance) {
          latestEventsModalInstance = new window.bootstrap.Modal(ui.latestEventsModal);
        }
        latestEventsModalInstance.show();
      }
      void loadLatestEvents();
    });
  }

  if (ui.todayEventsSearchBtn) {
    ui.todayEventsSearchBtn.addEventListener('click', () => {
      void loadTodayEventsByDoc();
    });
  }

  if (ui.todayEventsDocInput) {
    ui.todayEventsDocInput.addEventListener('input', () => {
      const sanitized = sanitizeIdentificationInput(ui.todayEventsDocInput.value);
      if (ui.todayEventsDocInput.value !== sanitized) {
        ui.todayEventsDocInput.value = sanitized;
      }
    });
    ui.todayEventsDocInput.addEventListener('keydown', (event) => {
      if (event.key !== 'Enter') return;
      event.preventDefault();
      void loadTodayEventsByDoc();
    });
  }

  if (ui.todayEventsResetBtn) {
    ui.todayEventsResetBtn.addEventListener('click', () => {
      if (ui.todayEventsDocInput) {
        ui.todayEventsDocInput.value = '';
      }
      void loadLatestEvents();
    });
  }

  window.addEventListener('pagehide', () => {
    stopLiveRecognize();
    stopSessionKeepalive();
    document.removeEventListener('keydown', handleEventKeyboardShortcut);
    if (recognizedPruneTimer) {
      clearInterval(recognizedPruneTimer);
      recognizedPruneTimer = null;
    }
  });

  document.addEventListener('visibilitychange', () => {
    if (document.visibilityState === 'visible') {
      void pingSessionKeepalive(true);
    }
  });

  initFaceDetection();
  setCameraStatus('off');
  setFaceStatus(false);
  setControls(false, false);
  setServiceMessage('');
  renderRecognized();
  startSessionKeepalive();
  recognizedPruneTimer = window.setInterval(() => {
    if (pruneRecognizedList()) {
      renderRecognized();
    }
  }, 1000);
  startLiveRecognize();
});
