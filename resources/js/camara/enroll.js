import '../../css/camara/enroll.css';
import {
   clampNumber,
   requestCamera,
   stopStream,
   captureJpegFromVideo,
   getCsrfToken,
   showInlineAlert,
   showSwal,
   hasValidFace,
} from './shared';
import { createFaceDetector } from './mediapipe';

const DETECTION_INTERVAL_MS = 150;
const DETECTION_W = 640;
const DETECTION_H = 480;
const MIN_DETECTION_SCORE = 0.6;
const ALIGN_ENTER_DIST = 0.90;
const ALIGN_EXIT_DIST = 1.05;
const SCORE_ENTER = 0.6;
const SCORE_EXIT = 0.5;
const SIZE_ENTER_MIN = 0.74;
const SIZE_ENTER_MAX = 1.15;
const SIZE_EXIT_MIN = 0.65;
const SIZE_EXIT_MAX = 1.30;
const DX_ENTER_MAX = 0.24;
const DY_ENTER_MAX = 0.30;
const DX_EXIT_MAX = 0.30;
const DY_EXIT_MAX = 0.38;
const OVAL_TARGET = { cx: 0.5, cy: 0.52, rx: 0.23, ry: 0.32 };
const AUTO_STABLE_FRAMES = 4;
const AUTO_COOLDOWN_MS = 2500;
const HOLD_MS = 750;
const CAPTURE_DELAY_MS = 220;
const EMA_ALPHA = 0.35;
const UI_STABLE_FRAMES = 3;
const UI_EXIT_FRAMES = 4;
const UI_MIN_MS = {
   idle: 500,
   need_id: 700,
   no_face: 700,
   bad: 700,
   near: 600,
   hold: 900,
   capture: 600,
   sending: 0,
   searching: 600,
   adjusting: 600,
};
const UI_PRIORITY = {
   idle: 0,
   searching: 1,
   need_id: 2,
   no_face: 2,
   bad: 2,
   near: 3,
   hold: 4,
   capture: 5,
   sending: 6,
};


function getDetectionScore(detection) {
   if (!detection) return 0;
   if (Array.isArray(detection.score) && detection.score.length) return detection.score[0];
   if (typeof detection.score === 'number') return detection.score;
   if (Array.isArray(detection.V) && detection.V.length) {
      const first = detection.V[0];
      if (first && typeof first.score === 'number') return first.score;
      if (first && typeof first.ga === 'number') return first.ga;
   }
   return 0;
}

function normalizeBBox(detection) {
   if (!detection || !detection.boundingBox) return null;
   const score = getDetectionScore(detection);

   const box = detection.boundingBox;
   let cx = box.xCenter;
   let cy = box.yCenter;
   let w = box.width;
   let h = box.height;

   if (cx == null || cy == null || w == null || h == null) {
      return null;
   }

   if (w > 1.5 || h > 1.5 || cx > 1.5 || cy > 1.5) {
      const widthBase = DETECTION_W || 1;
      const heightBase = DETECTION_H || 1;
      w = w / widthBase;
      h = h / heightBase;
      cx = cx / widthBase;
      cy = cy / heightBase;
   }

   if (!Number.isFinite(w) || !Number.isFinite(h) || !Number.isFinite(cx) || !Number.isFinite(cy)) {
      return null;
   }

   w = Math.min(1, Math.max(0, w));
   h = Math.min(1, Math.max(0, h));
   cx = Math.min(1, Math.max(0, cx));
   cy = Math.min(1, Math.max(0, cy));

   if (w <= 0 || h <= 0) return null;

   return { score, cx, cy, w, h };
}

function ellipseDistance(face, oval) {
   const dx = (face.cx - oval.cx) / oval.rx;
   const dy = (face.cy - oval.cy) / oval.ry;
   return (dx * dx) + (dy * dy);
}

function validateAlignment(face, oval, wasAligned) {
   if (!face || !oval) {
      return {
         aligned: false,
         level: 'bad',
         dist: null,
         sizeRel: null,
         sizeRelX: null,
         sizeRelY: null,
         inside: false,
         sizeOk: false,
         scoreOk: false,
         dx: null,
         dy: null,
         dxN: null,
         dyN: null,
         centerOk: false,
      };
   }

   const dx = face.cx - oval.cx;
   const dy = face.cy - oval.cy;

   // normalizado al tamano del ovalo (clave)
   const dxN = Math.abs(dx / oval.rx);
   const dyN = Math.abs(dy / oval.ry);

   const dist = ellipseDistance(face, oval);

   const sizeRelX = face.w / (oval.rx * 2);
   const sizeRelY = face.h / (oval.ry * 2);
   const sizeRel = Math.min(sizeRelX, sizeRelY);

   const scoreOk = face.score >= SCORE_ENTER;
   const inside = dist <= ALIGN_ENTER_DIST;
   const sizeOk = sizeRel >= SIZE_ENTER_MIN && sizeRel <= SIZE_ENTER_MAX;

   const centerOkEnter = dxN <= DX_ENTER_MAX && dyN <= DY_ENTER_MAX;
   const centerOkExit = dxN <= DX_EXIT_MAX && dyN <= DY_EXIT_MAX;

   const nearOk =
      (face.score >= SCORE_EXIT) &&
      (dist <= ALIGN_EXIT_DIST) &&
      (sizeRel >= SIZE_EXIT_MIN && sizeRel <= SIZE_EXIT_MAX) &&
      centerOkExit;

   let ok = scoreOk && inside && sizeOk && centerOkEnter;

   if (wasAligned && !ok) {
      ok = nearOk; // mantiene histeresis completa
   }

   const level = ok ? 'ok' : (nearOk ? 'near' : 'bad');

   return {
      aligned: ok,
      level,
      dist,
      sizeRel,
      sizeRelX,
      sizeRelY,
      inside,
      sizeOk,
      scoreOk,
      dx,
      dy,
      dxN,
      dyN,
      centerOk: ok ? true : centerOkExit, // informativo
   };
}

function pickBestFace(detections, oval) {
   if (!detections.length || !oval) return null;
   const candidates = detections
      .map((d) => normalizeBBox(d))
      .filter((face) => face && face.score >= MIN_DETECTION_SCORE);

   if (!candidates.length) return null;

   candidates.sort((a, b) => {
      const distA = ellipseDistance(a, oval);
      const distB = ellipseDistance(b, oval);
      if (distA !== distB) return distA - distB;
      if (b.score !== a.score) return b.score - a.score;
      const areaA = a.w * a.h;
      const areaB = b.w * b.h;
      return areaB - areaA;
   });

   return candidates[0];
}

document.addEventListener('DOMContentLoaded', () => {
   const ui = {
      alertContainer: document.getElementById('alertContainer'),
      cameraStatus: document.getElementById('cameraStatus'),
      faceStatus: document.getElementById('faceStatus'),
      video: document.getElementById('cameraVideo'),
      canvas: document.getElementById('captureCanvas'),
      detectionCanvas: document.getElementById('detectionCanvas'),
      overlayCanvas: document.getElementById('overlayCanvas'),
      startBtn: document.getElementById('startBtn'),
      stopBtn: document.getElementById('stopBtn'),
      enrollBtn: document.getElementById('enrollBtn'),
      enrollEndpoint: document.getElementById('enrollEndpoint'),
      personSelect: document.getElementById('personSelect'),
      faceDetectionToggle: document.getElementById('faceDetectionToggle'),
      autoCaptureToggle: document.getElementById('autoCaptureToggle'),
      debugToggle: document.getElementById('debugToggle'),
      minFaceSizeInput: document.getElementById('minFaceSizeInput'),
      widthInput: document.getElementById('widthInput'),
      heightInput: document.getElementById('heightInput'),
      qualityInput: document.getElementById('qualityInput'),
      alignStatus: document.getElementById('alignStatus'),
      stabilityCounter: document.getElementById('stabilityCounter'),
      faceOverlay: document.getElementById('faceOverlay'),
      alignBadge: document.getElementById('alignBadge'),
      enrollStateBadge: document.getElementById('enrollStateBadge'),
      enrollStatusTitle: document.getElementById('enrollStatusTitle'),
      enrollStatusMessage: document.getElementById('enrollStatusMessage'),
      enrollProgressBar: document.getElementById('enrollProgressBar'),
   };

   if (!ui.video || !ui.canvas) {
      return;
   }

   if (ui.autoCaptureToggle) {
      ui.autoCaptureToggle.checked = true;
      ui.autoCaptureToggle.disabled = true;
   }

   let stream = null;
   let faceDetector = null;
   let detectionEnabled = true;
   let detectionTimer = null;
   let detectionInFlight = false;
   let lastFaceDetected = false;
   let lastAlert = '';
   let requestInFlight = false;
   let autoCaptureCooldownUntil = 0;
   let isAligned = false;
   let alignedState = false;
   let faceEma = null;
   let ovalTarget = null;
   let lastDetectionLogAt = 0;
   let stableOkFrames = 0;
   let holdStartTs = null;
   let holdProgress = 0;
   let enrollState = 'searching';
   let captureQueued = false;
   let selectedIdentificacion = '';
   let autoCaptureTimer = null;
   let uiState = 'idle';
   let uiLastChangeAt = 0;
   let uiOkFrames = 0;
   let uiNearFrames = 0;
   let uiBadFrames = 0;
   let uiNoFaceFrames = 0;

   function readConfig() {
      const width = clampNumber(ui.widthInput?.value, 80, 1280, 640);
      const height = clampNumber(ui.heightInput?.value, 60, 720, 480);
      const quality = clampNumber(ui.qualityInput?.value, 0.1, 1, 1);
      return {
         width: Math.round(width),
         height: Math.round(height),
         quality: Number(quality.toFixed(2)),
      };
   }

   function readDetectionConfig() {
      const minFacePercent = clampNumber(ui.minFaceSizeInput?.value, 5, 80, 20);
      return {
         minFaceRatio: minFacePercent / 100,
      };
   }

   function initDetectionCanvases() {
      const parent = ui.video ? ui.video.parentElement : null;
      if (!ui.detectionCanvas && parent) {
         const canvas = document.createElement('canvas');
         canvas.id = 'detectionCanvas';
         canvas.className = 'd-none';
         parent.appendChild(canvas);
         ui.detectionCanvas = canvas;
      }
      if (!ui.overlayCanvas && parent) {
         const canvas = document.createElement('canvas');
         canvas.id = 'overlayCanvas';
         canvas.className = 'overlay-canvas';
         canvas.setAttribute('aria-hidden', 'true');
         parent.appendChild(canvas);
         ui.overlayCanvas = canvas;
      }
      if (!ui.detectionCanvas || !ui.overlayCanvas) {
         inlineAlert('warning', 'No se pudo inicializar los canvas de deteccion.');
         return false;
      }
      ui.detectionCanvas.width = DETECTION_W;
      ui.detectionCanvas.height = DETECTION_H;
      ui.overlayCanvas.width = DETECTION_W;
      ui.overlayCanvas.height = DETECTION_H;
      return true;
   }

   if (!initDetectionCanvases()) {
      return;
   }

   function initPersonSelect() {
      if (!ui.personSelect || typeof $ === 'undefined' || !$.fn || !$.fn.select2) {
         return;
      }
      const url = ui.personSelect.dataset.url || '';
      $('#personSelect').select2({
         theme: 'bootstrap-5',
         language: {
            inputTooShort: () => 'Introduce mas caracteres',
            noResults: () => 'No se encontraron resultados',
            searching: () => 'Buscando...',
            loadingMore: () => 'Cargando mas resultados...',
         },
         placeholder: ui.personSelect.dataset.placeholder || 'Selecciona una identificacion...',
         minimumInputLength: 4,
         ajax: {
            url,
            dataType: 'json',
            delay: 250,
            data: function (params) {
               return {
                  query: params.term
               };
            },
            processResults: function (data) {
               return {
                  results: data
               };
            },
            cache: true
         },
         width: '100%',
         allowClear: true,
         dropdownPosition: 'below',
         templateSelection: function (data) {
            if (!data || !data.id) return '';
            if (data.documento) return String(data.documento);
            return String(data.text || data.id);
         }
      });

      $('#personSelect').on('select2:select', function (event) {
         const data = event.params.data || {};
         selectedIdentificacion = String(data.documento || data.id || '').trim();
         stableOkFrames = 0;
         holdStartTs = null;
         holdProgress = 0;
         captureQueued = false;
         setUiState('searching', 'Buscando rostro...', 'Coloca tu cara dentro del ovalo.', 0);
      });

      $('#personSelect').on('select2:clear', function () {
         selectedIdentificacion = '';
         stableOkFrames = 0;
         holdStartTs = null;
         holdProgress = 0;
         captureQueued = false;
         setUiState('need_id', 'Selecciona identificacion', 'Selecciona una identificacion para continuar.', 0);
      });
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
      if (!detectionEnabled) {
         ui.faceStatus.className = 'badge bg-secondary';
         ui.faceStatus.textContent = 'Deteccion desactivada';
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

   function updateAlignUI(state, stableCount) {
      if (ui.alignStatus) {
         const map = {
            off: { text: 'Desactivado', cls: 'bg-secondary' },
            bad: { text: 'No detectado', cls: 'bg-danger' },
            near: { text: 'Casi...', cls: 'bg-warning text-dark' },
            ok: { text: 'Correcto', cls: 'bg-success' },
         };
         const cfg = map[state] || map.bad;
         ui.alignStatus.className = `badge ${cfg.cls}`;
         ui.alignStatus.textContent = cfg.text;
      }
      if (ui.stabilityCounter) {
         ui.stabilityCounter.textContent = `${Math.min(stableCount, AUTO_STABLE_FRAMES)}/${AUTO_STABLE_FRAMES}`;
      }
      if (ui.alignBadge) {
         const map = {
            bad: { text: 'Fuera de posicion', cls: 'bg-danger' },
            near: { text: 'Ajustando...', cls: 'bg-warning text-dark' },
            ok: { text: 'Correcto', cls: 'bg-success' },
            off: { text: 'Desactivado', cls: 'bg-secondary' },
         };
         const cfg = map[state] || map.bad;
         ui.alignBadge.className = `badge ${cfg.cls} align-badge`;
         ui.alignBadge.textContent = cfg.text;
      }
      if (ui.faceOverlay) {
         ui.faceOverlay.classList.remove('is-ok', 'is-near', 'is-bad');
         if (state === 'ok') ui.faceOverlay.classList.add('is-ok');
         if (state === 'near') ui.faceOverlay.classList.add('is-near');
         if (state === 'bad') ui.faceOverlay.classList.add('is-bad');
      }
   }


   function setEnrollStatus(state, title, message, progress) {
      enrollState = state;
      if (ui.enrollStatusTitle) ui.enrollStatusTitle.textContent = title;
      if (ui.enrollStatusMessage) ui.enrollStatusMessage.textContent = message;
      if (ui.enrollStateBadge) {
         ui.enrollStateBadge.classList.remove('bg-success', 'bg-warning', 'bg-info', 'bg-primary', 'bg-danger', 'bg-secondary');
         if (state === 'holding') ui.enrollStateBadge.classList.add('bg-primary');
         else if (state === 'capturing' || state === 'uploading') ui.enrollStateBadge.classList.add('bg-warning');
         else if (state === 'adjusting') ui.enrollStateBadge.classList.add('bg-info');
         else if (state === 'searching') ui.enrollStateBadge.classList.add('bg-secondary');
         else if (state === 'error') ui.enrollStateBadge.classList.add('bg-danger');
         else ui.enrollStateBadge.classList.add('bg-success');
      }
      if (ui.enrollProgressBar) {
         const pct = Math.max(0, Math.min(100, Math.round((progress || 0) * 100)));
         ui.enrollProgressBar.style.width = `${pct}%`;
         ui.enrollProgressBar.classList.remove('bg-success', 'bg-info', 'bg-warning', 'bg-primary');
         if (state === 'holding') ui.enrollProgressBar.classList.add('bg-primary');
         else if (state === 'capturing' || state === 'uploading') ui.enrollProgressBar.classList.add('bg-warning');
         else if (state === 'adjusting') ui.enrollProgressBar.classList.add('bg-info');
         else ui.enrollProgressBar.classList.add('bg-primary');
      }
   }

   function canTransitionUi(nextState, now) {
      if (uiState === nextState) return false;
      const currentPriority = UI_PRIORITY[uiState] ?? 0;
      const nextPriority = UI_PRIORITY[nextState] ?? 0;
      const minMs = UI_MIN_MS[uiState] ?? 0;
      if (nextPriority < currentPriority && (now - uiLastChangeAt) < minMs) {
         return false;
      }
      return true;
   }

   function setUiState(nextState, title, message, progress) {
      const now = Date.now();
      if (!canTransitionUi(nextState, now)) return;
      uiState = nextState;
      uiLastChangeAt = now;
      setEnrollStatus(nextState, title, message, progress);
   }

   function updateUiCounters(level, hasFace) {
      if (!hasFace) {
         uiNoFaceFrames += 1;
         uiOkFrames = 0;
         uiNearFrames = 0;
         uiBadFrames = 0;
         return;
      }
      uiNoFaceFrames = 0;
      if (level === 'ok') {
         uiOkFrames += 1;
         uiNearFrames = 0;
         uiBadFrames = 0;
      } else if (level === 'near') {
         uiNearFrames += 1;
         uiOkFrames = 0;
         uiBadFrames = 0;
      } else {
         uiBadFrames += 1;
         uiOkFrames = 0;
         uiNearFrames = 0;
      }
   }

   function updateUiFromDetection(level, hasFace) {
      const now = Date.now();
      updateUiCounters(level, hasFace);

      if (!selectedIdentificacion) {
         setUiState('need_id', 'Selecciona identificacion', 'Selecciona una identificacion para continuar.', 0);
         return;
      }

      if (requestInFlight) {
         setUiState('sending', 'Enrolando...', 'Validando registro...', 1);
         return;
      }

      if (captureQueued) {
         setUiState('capture', 'Capturando...', 'Un momento...', 1);
         return;
      }

      if (!hasFace) {
         if (uiNoFaceFrames >= UI_EXIT_FRAMES || uiState === 'idle') {
            setUiState('no_face', 'Buscando rostro...', 'Coloca tu cara dentro del ovalo.', 0);
         }
         return;
      }

      if (level === 'bad') {
         if (uiBadFrames >= UI_EXIT_FRAMES) {
            setUiState('bad', 'Ajusta tu posicion', 'Centra tu rostro y mira a la camara.', 0);
         }
         return;
      }

      if (level === 'near') {
         if (uiNearFrames >= UI_STABLE_FRAMES) {
            setUiState('near', 'Casi listo', 'Centrate y mira a la camara.', holdProgress);
         }
         return;
      }

      if (level === 'ok') {
         if (holdProgress > 0 || uiOkFrames >= UI_STABLE_FRAMES) {
            const pct = Math.round(holdProgress * 100);
            const remaining = Math.max(0, (HOLD_MS - (holdProgress * HOLD_MS)) / 1000).toFixed(1);
            setUiState('hold', 'Perfecto, no te muevas', `Manten la posicion... ${remaining}s (${pct}%)`, holdProgress);
         }
      }
   }

   function setButtons(isRunning) {
      if (ui.startBtn) ui.startBtn.disabled = isRunning;
      if (ui.stopBtn) ui.stopBtn.disabled = !isRunning;
   }

   function setApiBusy(isBusy) {
      requestInFlight = isBusy;
      if (ui.enrollBtn) ui.enrollBtn.disabled = isBusy;
   }

   function getEndpointUrl() {
      const fallback = new URL('enroll', window.location.href).toString();
      const raw = ui.enrollEndpoint?.value || '';
      if (!raw) return fallback;
      try {
         return new URL(raw, window.location.href).toString();
      } catch (error) {
         return fallback;
      }
   }

   function inlineAlert(type, message) {
      lastAlert = showInlineAlert(ui.alertContainer, type, message, lastAlert);
   }

   function showSuccess(message) {
      const shown = showSwal('success', message, 'Enrolado');
      if (!shown) {
         inlineAlert('success', message);
      }
   }

   function showError(message) {
      const shown = showSwal('error', message, 'Error');
      if (!shown) {
         inlineAlert('danger', message);
      }
   }

   function finishSuccess(message) {
      stopDetectionLoop();
      stream = stopStream(stream, ui.video);
      setCameraStatus('off');
      setButtons(false);
      stableOkFrames = 0;
      holdStartTs = null;
      holdProgress = 0;
      captureQueued = false;
      autoCaptureCooldownUntil = 0;
      if (autoCaptureTimer) {
         clearTimeout(autoCaptureTimer);
         autoCaptureTimer = null;
      }
      setEnrollStatus('searching', 'Proceso finalizado', 'Recargando...', 0);
      const shown = showSwal('success', message, 'Enrolado');
      if (shown && typeof shown.then === 'function') {
         shown.then((result) => {
            if (result && result.isConfirmed) {
               window.location.reload();
            }
         });
      }
   }

   function initFaceDetection() {
      try {
         faceDetector = createFaceDetector({
            model: 'full',
            minDetectionConfidence: MIN_DETECTION_SCORE,
            onResults: handleFaceResults,
            debug: !!(ui.debugToggle && ui.debugToggle.checked),
         });
         return true;
      } catch (error) {
         inlineAlert('danger', 'No se pudo inicializar la deteccion facial.');
         detectionEnabled = false;
         if (ui.faceDetectionToggle) ui.faceDetectionToggle.checked = false;
         setFaceStatus(false);
         return false;
      }
   }

   function getOvalTargetFromDOM() {
      return OVAL_TARGET;
   }

   function smoothFace(nextFace) {
      if (!nextFace) return null;
      if (!faceEma) {
         faceEma = { ...nextFace };
         return faceEma;
      }
      faceEma = {
         score: (faceEma.score * (1 - EMA_ALPHA)) + (nextFace.score * EMA_ALPHA),
         cx: (faceEma.cx * (1 - EMA_ALPHA)) + (nextFace.cx * EMA_ALPHA),
         cy: (faceEma.cy * (1 - EMA_ALPHA)) + (nextFace.cy * EMA_ALPHA),
         w: (faceEma.w * (1 - EMA_ALPHA)) + (nextFace.w * EMA_ALPHA),
         h: (faceEma.h * (1 - EMA_ALPHA)) + (nextFace.h * EMA_ALPHA),
      };
      return faceEma;
   }


   function syncOverlayCanvas() {
      if (!ui.overlayCanvas) return null;
      if (ui.overlayCanvas.width !== DETECTION_W) ui.overlayCanvas.width = DETECTION_W;
      if (ui.overlayCanvas.height !== DETECTION_H) ui.overlayCanvas.height = DETECTION_H;
      return { cw: DETECTION_W, ch: DETECTION_H };
   }

   function renderDebug(face, oval, debugInfo = null, totalDetections = 0) {
      if (!ui.overlayCanvas) return;
      const enabled = !!(ui.debugToggle && ui.debugToggle.checked);
      const dims = syncOverlayCanvas();
      if (!dims) {
         return;
      }

      const ctx = ui.overlayCanvas.getContext('2d');
      const vw = dims.cw;
      const vh = dims.ch;
      ctx.clearRect(0, 0, vw, vh);

      if (oval) {
         const color = debugInfo?.level === 'ok'
            ? 'rgba(25, 135, 84, 0.95)'
            : debugInfo?.level === 'near'
               ? 'rgba(255, 193, 7, 0.95)'
               : 'rgba(220, 53, 69, 0.95)';
         const guideCx = oval.cx * vw;
         const guideCy = oval.cy * vh;
         let guideRy = vh * 0.45;
         let guideRx = guideRy * 0.62;
         const margin = 10;
         guideRy = Math.min(guideRy, (vh / 2) - margin);
         guideRx = Math.min(guideRx, (vw / 2) - margin);

         ctx.save();
         ctx.fillStyle = 'rgba(0, 0, 0, 0.80)';
         ctx.fillRect(0, 0, vw, vh);
         ctx.globalCompositeOperation = 'destination-out';
         ctx.beginPath();
         ctx.ellipse(guideCx, guideCy, guideRx, guideRy, 0, 0, Math.PI * 2);
         ctx.fill();
         ctx.restore();

         ctx.save();
         ctx.strokeStyle = color;
         ctx.lineWidth = 4;
         ctx.shadowColor = color;
         ctx.shadowBlur = 14;
         ctx.beginPath();
         ctx.ellipse(guideCx, guideCy, guideRx, guideRy, 0, 0, Math.PI * 2);
         ctx.stroke();
         ctx.shadowBlur = 0;
         ctx.restore();

      }

      if (face && enabled) {
         ctx.strokeStyle = 'rgba(255, 0, 0, 0.9)';
         ctx.lineWidth = 2;
         const x = (face.cx - face.w / 2) * vw;
         const y = (face.cy - face.h / 2) * vh;
         const w = face.w * vw;
         const h = face.h * vh;
         ctx.strokeRect(x, y, w, h);

         ctx.fillStyle = 'rgba(255, 0, 0, 0.9)';
         ctx.beginPath();
         ctx.arc(face.cx * vw, face.cy * vh, 4, 0, Math.PI * 2);
         ctx.fill();
      }

      if (debugInfo && enabled) {
         ctx.fillStyle = 'rgba(0, 0, 0, 0.6)';
         ctx.fillRect(8, vh - 28, 640, 20);
         ctx.fillStyle = '#fff';
         ctx.font = '12px sans-serif';
         ctx.fillText(
            `lvl:${debugInfo.level || 'n/a'} score:${debugInfo.score.toFixed(2)} dist:${debugInfo.dist.toFixed(2)} dx:${debugInfo.dx.toFixed(3)} dy:${debugInfo.dy.toFixed(3)} sizeX:${debugInfo.sizeRelX.toFixed(2)} sizeY:${debugInfo.sizeRelY.toFixed(2)} size:${debugInfo.sizeRel.toFixed(2)} inside:${debugInfo.inside ? '1' : '0'} ok:${debugInfo.sizeOk ? '1' : '0'} hold:${(debugInfo.progress * 100).toFixed(0)}% det:${totalDetections}`,
            12,
            vh - 14
         );
      }
   }

   async function updateEnrollState(level, hasDetection) {
      const autoEnabled = true;
      if (ui.enrollBtn) {
         ui.enrollBtn.disabled = !ui.debugToggle?.checked || requestInFlight;
         ui.enrollBtn.classList.toggle('d-none', !ui.debugToggle?.checked);
      }
      if (!selectedIdentificacion) {
         stableOkFrames = 0;
         holdStartTs = null;
         holdProgress = 0;
         captureQueued = false;
         updateUiFromDetection('bad', false);
         return;
      }
      if (!autoEnabled || !detectionEnabled) {
         stableOkFrames = 0;
         holdStartTs = null;
         holdProgress = 0;
         updateAlignUI(detectionEnabled ? (hasDetection ? 'near' : 'bad') : 'off', 0);
         updateUiFromDetection(level, hasDetection);
         return;
      }

      if (!hasDetection) {
         stableOkFrames = 0;
         holdStartTs = null;
         holdProgress = 0;
         captureQueued = false;
         updateAlignUI('bad', 0);
         updateUiFromDetection(level, false);
         return;
      }

      if (level === 'bad') {
         stableOkFrames = 0;
         holdStartTs = null;
         holdProgress = 0;
         captureQueued = false;
         updateAlignUI('bad', 0);
         updateUiFromDetection(level, true);
         return;
      }

      if (level === 'near') {
         stableOkFrames = Math.max(0, stableOkFrames - 1);
         holdProgress = Math.max(0, holdProgress - 0.08);
         if (holdProgress === 0) {
            holdStartTs = null;
         }
         captureQueued = false;
         updateAlignUI('near', 0);
         updateUiFromDetection(level, true);
         return;
      }

      stableOkFrames += 1;
      if (stableOkFrames < AUTO_STABLE_FRAMES) {
         updateAlignUI('near', stableOkFrames);
         updateUiFromDetection('near', true);
         return;
      }

      if (!holdStartTs) holdStartTs = performance.now();
      const elapsed = performance.now() - holdStartTs;
      holdProgress = Math.min(1, elapsed / HOLD_MS);
      updateUiFromDetection('ok', true);

      if (holdProgress >= 1 && !requestInFlight && Date.now() >= autoCaptureCooldownUntil && !captureQueued) {
         captureQueued = true;
         updateUiFromDetection('ok', true);
         autoCaptureCooldownUntil = Date.now() + AUTO_COOLDOWN_MS;
         holdStartTs = null;
         stableOkFrames = 0;
         if (autoCaptureTimer) {
            clearTimeout(autoCaptureTimer);
            autoCaptureTimer = null;
         }
         autoCaptureTimer = window.setTimeout(async () => {
            await enrollFace();
            autoCaptureTimer = null;
         }, CAPTURE_DELAY_MS);
      }
   }

   function handleFaceResults(results) {
      if (!detectionEnabled) return;
      const detections = results?.detections ?? [];
      const { minFaceRatio } = readDetectionConfig();
      const foundNow = hasValidFace(detections, {
         minScore: MIN_DETECTION_SCORE,
         minFaceRatio,
      });

      if (!window.__mpLastSeenAt) window.__mpLastSeenAt = 0;
      if (foundNow) window.__mpLastSeenAt = Date.now();
      const stable = (Date.now() - window.__mpLastSeenAt) <= 400;

      lastFaceDetected = stable;
      setFaceStatus(stable);

      if (ui.debugToggle && ui.debugToggle.checked && detections.length) {
         const now = Date.now();
         if (now - lastDetectionLogAt > 1000) {
            console.log('[MP raw detection sample]', detections[0]);
            lastDetectionLogAt = now;
         }
      }

      if (!detections.length) {
         isAligned = false;
         alignedState = false;
         updateEnrollState('bad', false);
         holdProgress = 0;
         renderDebug(null, ovalTarget, { level: 'bad', score: 0, dist: 0, sizeRel: 0, sizeRelX: 0, sizeRelY: 0, inside: false, sizeOk: false, scoreOk: false, dx: 0, dy: 0, progress: 0 }, 0);
         return;
      }

      const bestFace = pickBestFace(detections, ovalTarget);
      if (!bestFace) {
         isAligned = false;
         alignedState = false;
         updateAlignUI('bad', 0);
         holdProgress = 0;
         updateEnrollState('bad', true);
         renderDebug(null, ovalTarget, { level: 'bad', score: 0, dist: 0, sizeRel: 0, sizeRelX: 0, sizeRelY: 0, inside: false, sizeOk: false, scoreOk: false, dx: 0, dy: 0, progress: 0 }, detections.length);
         return;
      }

      const smoothedFace = smoothFace(bestFace);
      const validation = validateAlignment(smoothedFace, ovalTarget, alignedState);
      alignedState = validation.aligned;
      isAligned = alignedState;
      updateAlignUI(validation.level, validation.level === 'ok' ? AUTO_STABLE_FRAMES : 0);
      updateEnrollState(validation.level, true);
      renderDebug(
         smoothedFace,
         ovalTarget,
         {
            score: bestFace.score,
            dist: validation.dist ?? 0,
            sizeRel: validation.sizeRel ?? 0,
            sizeRelX: validation.sizeRelX ?? 0,
            sizeRelY: validation.sizeRelY ?? 0,
            inside: validation.inside,
            sizeOk: validation.sizeOk,
            scoreOk: validation.scoreOk,
            dx: validation.dx,
            dy: validation.dy,
            progress: holdProgress,
            level: validation.level,
         },
         detections.length
      );

      if (ui.debugToggle && ui.debugToggle.checked) {
         console.log('[debug] face', {
            score: smoothedFace.score,
            cx: smoothedFace.cx,
            cy: smoothedFace.cy,
            w: smoothedFace.w,
            h: smoothedFace.h,
            dist: validation.dist,
            sizeRel: validation.sizeRel,
            inside: validation.inside,
            sizeOk: validation.sizeOk,
            scoreOk: validation.scoreOk,
            dx: validation.dx,
            dy: validation.dy,
            level: validation.level,
            detections: detections.length,
         });
      }
   }

   function startDetectionLoop() {
      if (!detectionEnabled || !faceDetector || detectionTimer) {
         return;
      }
      detectionTimer = window.setInterval(() => {
         if (!stream || ui.video.readyState < 2 || !ui.detectionCanvas) {
            return;
         }
         if (detectionInFlight) {
            return;
         }
         detectionInFlight = true;
         const ctx = ui.detectionCanvas.getContext('2d');
         ctx.drawImage(ui.video, 0, 0, DETECTION_W, DETECTION_H);
         faceDetector.send({ image: ui.detectionCanvas })
            .catch(() => {
               /* inlineAlert('warning', 'No se pudo ejecutar la deteccion facial.'); */
            })
            .finally(() => {
               detectionInFlight = false;
            });
      }, DETECTION_INTERVAL_MS);
   }

   function stopDetectionLoop() {
      if (detectionTimer) {
         clearInterval(detectionTimer);
         detectionTimer = null;
      }
      detectionInFlight = false;
      stableOkFrames = 0;
      holdStartTs = null;
      holdProgress = 0;
      captureQueued = false;
      isAligned = false;
      updateAlignUI('off', 0);
      setUiState('idle', 'Camara detenida', 'Inicia la camara para continuar.', 0);
      renderDebug(null, ovalTarget);
   }

   async function startCamera() {
      if (stream) {
         return true;
      }
      const result = await requestCamera(ui.video);
      if (!result.stream) {
         inlineAlert('danger', result.error || 'No se pudo acceder a la camara.');
         setCameraStatus('error');
         return false;
      }
      stream = result.stream;
      await new Promise((resolve) => {
         if (ui.video.videoWidth) return resolve();
         const onMeta = () => {
            ui.video.removeEventListener('loadedmetadata', onMeta);
            resolve();
         };
         ui.video.addEventListener('loadedmetadata', onMeta);
      });
      ovalTarget = getOvalTargetFromDOM();
      setCameraStatus('on');
      setButtons(true);
      setUiState('searching', 'Buscando rostro...', 'Coloca tu cara dentro del ovalo.', 0);
      startDetectionLoop();
      return true;
   }

   function stopCamera() {
      stream = stopStream(stream, ui.video);
      setCameraStatus('off');
      setButtons(false);
      stopDetectionLoop();
   }

   async function captureBlobForApi() {
      if (detectionEnabled && !lastFaceDetected) {
         inlineAlert('warning', 'No hay cara para capturar.');
         return null;
      }
      const autoEnabled = !!(ui.autoCaptureToggle && ui.autoCaptureToggle.checked);
      if (!autoEnabled && detectionEnabled && !isAligned) {
         const shown = showSwal('warning', 'Coloca tu rostro dentro del ovalo para enrolar.', 'Atencion');
         if (!shown) {
            inlineAlert('warning', 'Coloca tu rostro dentro del ovalo para enrolar.');
         }
         return null;
      }
      const ok = await startCamera();
      if (!ok) {
         return null;
      }
      if (ui.video.readyState < 2) {
         inlineAlert('warning', 'La camara no esta lista para capturar.');
         return null;
      }
      const config = readConfig();
      const blob = await captureJpegFromVideo(ui.video, ui.canvas, config);
      if (!blob) {
         inlineAlert('warning', 'No se pudo capturar la imagen.');
         return null;
      }
      return blob;
   }

   async function enrollFace() {
      if (requestInFlight) return;
      const identificacion = selectedIdentificacion || '';
      if (!identificacion) {
         setUiState('need_id', 'Selecciona identificacion', 'Selecciona una identificacion para continuar.', 0);
         inlineAlert('warning', 'Selecciona una identificacion para continuar.');
         captureQueued = false;
         return;
      }
      const blob = await captureBlobForApi();
      if (!blob) {
         captureQueued = false;
         return;
      }

      setApiBusy(true);
      setUiState('sending', 'Enrolando...', 'Validando registro...', 1);
      try {
         const formData = new FormData();
         formData.append('image', blob, 'frame.jpg');
         formData.append('identificacion', identificacion);

         const response = await fetch(getEndpointUrl(), {
            method: 'POST',
            headers: {
               'Accept': 'application/json',
               'X-CSRF-TOKEN': getCsrfToken(),
            },
            body: formData,
            credentials: 'same-origin',
         });
         const data = await response.json().catch(() => null);
         if (!response.ok || !data) {
            showError('No se pudo enrolar. Intentalo de nuevo.');
            return;
         }
         const ok = data.status === 'ok' || data.ok === true;
         if (!ok) {
            showError(data.message || 'No se pudo enrolar. Intentalo de nuevo.');
            setUiState('error', 'Error al enrolar', 'Intenta de nuevo.', 0);
            return;
         }
         finishSuccess(`Enrolado correctamente. Identificacion: ${identificacion}`);
      } catch (error) {
         showError('No se pudo enrolar. Intentalo de nuevo.');
         setUiState('error', 'Error al enrolar', 'Intenta de nuevo.', 0);
      } finally {
         setApiBusy(false);
         captureQueued = false;
      }
   }

   if (ui.startBtn) ui.startBtn.addEventListener('click', startCamera);
   if (ui.stopBtn) ui.stopBtn.addEventListener('click', stopCamera);
   if (ui.enrollBtn) ui.enrollBtn.addEventListener('click', enrollFace);

   if (ui.faceDetectionToggle) {
      ui.faceDetectionToggle.addEventListener('change', () => {
         detectionEnabled = ui.faceDetectionToggle.checked;
         lastFaceDetected = false;
         setFaceStatus(false);
         stableOkFrames = 0;
         updateAlignUI(detectionEnabled ? 'bad' : 'off', 0);
         setUiState('searching', 'Buscando rostro...', 'Coloca tu cara dentro del ovalo.', 0);
         if (detectionEnabled) {
            startDetectionLoop();
         } else {
            stopDetectionLoop();
         }
      });
   }

   if (ui.debugToggle) {
      ui.debugToggle.addEventListener('change', () => {
         renderDebug(null, ovalTarget);
         if (ui.enrollBtn) {
            ui.enrollBtn.disabled = !ui.debugToggle.checked || requestInFlight;
            ui.enrollBtn.classList.toggle('d-none', !ui.debugToggle.checked);
         }
      });
   }

   if (ui.minFaceSizeInput) {
      ui.minFaceSizeInput.addEventListener('input', () => {
         setFaceStatus(lastFaceDetected);
      });
   }

   window.addEventListener('pagehide', () => {
      stopCamera();
   });

   window.addEventListener('resize', () => {
      ovalTarget = getOvalTargetFromDOM();
   });

   detectionEnabled = !!(ui.faceDetectionToggle && ui.faceDetectionToggle.checked);
   initFaceDetection();
   initPersonSelect();
   setCameraStatus('off');
   setFaceStatus(false);
   updateAlignUI(detectionEnabled ? 'bad' : 'off', 0);
   setUiState('need_id', 'Selecciona identificacion', 'Selecciona una identificacion para continuar.', 0);
   setButtons(false);
   if (ui.enrollBtn) {
      ui.enrollBtn.disabled = !ui.debugToggle?.checked;
      ui.enrollBtn.classList.toggle('d-none', !ui.debugToggle?.checked);
   }
   ovalTarget = getOvalTargetFromDOM();
});
