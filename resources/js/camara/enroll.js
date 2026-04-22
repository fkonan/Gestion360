import '../../css/camara/enroll.css';
import {
   clampNumber,
   requestCamera,
   stopStream,
   cropFaceToBlob,
   captureFullFrameBlob,
   getCsrfToken,
   showInlineAlert,
   hasValidFace,
} from './shared';
import { createFaceDetector } from './mediapipe';

const CONFIG = {
   detection: {
      intervalMs: 150,
      width: 1920,
      height: 1080,
      modelMinScore: 0.52,
   },
   wizard: {
      stableFrames: 2,
      transitionCooldownMs: 450,
      captureDelayMs: 80,
   },
   stepRules: {
      front: {
         minScoreEnter: 0.60,
         minScoreExit: 0.53,
         minFaceRatio: 0.10,
         enterDist: 1.02,
         exitDist: 1.16,
         sizeEnterMin: 0.66,
         sizeEnterMax: 1.28,
         sizeExitMin: 0.56,
         sizeExitMax: 1.42,
         dxEnterMax: 0.32,
         dyEnterMax: 0.38,
         dxExitMax: 0.40,
         dyExitMax: 0.46,
         holdMs: 320,
         countdownSec: 1,
      },
      left: {
         minScoreEnter: 0.52,
         minScoreExit: 0.46,
         minFaceRatio: 0.095,
         enterDist: 1.20,
         exitDist: 1.38,
         sizeEnterMin: 0.56,
         sizeEnterMax: 1.50,
         sizeExitMin: 0.46,
         sizeExitMax: 1.62,
         dxEnterMax: 0.44,
         dyEnterMax: 0.46,
         dxExitMax: 0.54,
         dyExitMax: 0.54,
         holdMs: 420,
         countdownSec: 1,
         preferredOffsetX: -0.02,
      },
      right: {
         minScoreEnter: 0.52,
         minScoreExit: 0.46,
         minFaceRatio: 0.095,
         enterDist: 1.20,
         exitDist: 1.38,
         sizeEnterMin: 0.56,
         sizeEnterMax: 1.50,
         sizeExitMin: 0.46,
         sizeExitMax: 1.62,
         dxEnterMax: 0.44,
         dyEnterMax: 0.46,
         dxExitMax: 0.54,
         dyExitMax: 0.54,
         holdMs: 420,
         countdownSec: 1,
         preferredOffsetX: 0.02,
      },
   },
   crop: {
      baseSize: 384,
      bigSize: 448,
      padding: 0.30,
      paddingSmall: 0.45,
      smallFaceW: 0.09,
      quality: 0.84,
      qualitySmall: 0.88,
   },
   ovalTarget: { cx: 0.5, cy: 0.52, rx: 0.27, ry: 0.38 },
   emaAlpha: 0.35,
};

const DETECTION_W = CONFIG.detection.width;
const DETECTION_H = CONFIG.detection.height;
const OVAL_TARGET = CONFIG.ovalTarget;
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
const ENROLL_STEPS = ['front', 'left', 'right'];
const STEP_LABELS = {
   front: 'Frente',
   left: 'Perfil izquierdo',
   right: 'Perfil derecho',
};
const STEP_HINTS = {
   front: 'Mira al frente y centra tu rostro en el ovalo.',
   left: 'Gira ligeramente tu rostro hacia la izquierda.',
   right: 'Gira ligeramente tu rostro hacia la derecha.',
};
const UI_STABLE_FRAMES = 4;
const UI_EXIT_FRAMES = 5;
const UI_MIN_MS = {
   idle: 500,
   need_id: 700,
   no_face: 800,
   bad: 800,
   near: 700,
   hold: 1000,
   capture: 600,
   sending: 0,
   searching: 800,
   adjusting: 700,
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

function validateAlignment(face, oval, wasAligned, rule, stepKey) {
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
         profileHintOk: false,
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

   const scoreOk = face.score >= rule.minScoreEnter;
   const inside = dist <= rule.enterDist;
   const sizeOk = sizeRel >= rule.sizeEnterMin && sizeRel <= rule.sizeEnterMax;

   const centerOkEnter = dxN <= rule.dxEnterMax && dyN <= rule.dyEnterMax;
   const centerOkExit = dxN <= rule.dxExitMax && dyN <= rule.dyExitMax;

   const profileHintOk = stepKey === 'left'
      ? (dx <= (rule.preferredOffsetX ?? -0.01))
      : stepKey === 'right'
         ? (dx >= (rule.preferredOffsetX ?? 0.01))
         : true;

   const nearOk =
      (face.score >= rule.minScoreExit) &&
      (dist <= rule.exitDist) &&
      (sizeRel >= rule.sizeExitMin && sizeRel <= rule.sizeExitMax) &&
      centerOkExit;

   let ok = scoreOk && inside && sizeOk && centerOkEnter;
   if (wasAligned && !ok) {
      ok = nearOk;
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
      profileHintOk,
   };
}

function pickBestFace(detections, oval, rule) {
   if (!detections.length || !oval) return null;
   const candidates = detections
      .map((d) => normalizeBBox(d))
      .filter((face) => face && face.score >= rule.minScoreExit && face.w >= rule.minFaceRatio);

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
      serviceStatus: document.getElementById('serviceStatus'),
      video: document.getElementById('cameraVideo'),
      canvas: document.getElementById('captureCanvas'),
      detectionCanvas: document.getElementById('detectionCanvas'),
      overlayCanvas: document.getElementById('overlayCanvas'),
      startBtn: document.getElementById('startBtn'),
      stopBtn: document.getElementById('stopBtn'),
      enrollBtn: document.getElementById('enrollBtn'),
      enrollEndpoint: document.getElementById('enrollEndpoint'),
      healthEndpoint: document.getElementById('healthEndpoint'),
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
      enrollStepTitle: document.getElementById('enrollStepTitle'),
      enrollStepInstruction: document.getElementById('enrollStepInstruction'),
      enrollStepMeta: document.getElementById('enrollStepMeta'),
      enrollCountdown: document.getElementById('enrollCountdown'),
      stepFrontBadge: document.getElementById('stepFrontBadge'),
      stepLeftBadge: document.getElementById('stepLeftBadge'),
      stepRightBadge: document.getElementById('stepRightBadge'),
      checkPose: document.getElementById('checkPose'),
      checkDistance: document.getElementById('checkDistance'),
      checkLight: document.getElementById('checkLight'),
      thumbFront: document.getElementById('thumbFront'),
      thumbLeft: document.getElementById('thumbLeft'),
      thumbRight: document.getElementById('thumbRight'),
      continueStepBtn: document.getElementById('continueStepBtn'),
      sendEnrollBtn: document.getElementById('sendEnrollBtn'),
      repeatStepBtn: document.getElementById('repeatStepBtn'),
   };

   if (!ui.video || !ui.canvas) {
      return;
   }

   // Corrige textos fijos del panel para evitar ruido visual por codificacion.
   const identificationTitle = ui.personSelect?.closest('.border')?.querySelector('.fw-semibold');
   if (identificationTitle) {
      identificationTitle.textContent = 'Identificacion';
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
   let lastFaceForCapture = null;
   let ovalTarget = null;
   let stableOkFrames = 0;
   let holdStartTs = null;
   let holdProgress = 0;
   let enrollState = 'searching';
   let captureQueued = false;
   let selectedIdentificacion = '';
   let autoCaptureTimer = null;
   let serviceOnline = null;
   let healthInFlight = false;
   let healthTimer = null;
   let currentStepIndex = 0;
   let stepState = 'idle';
   let transitionBlockedUntil = 0;
   let stepCaptureInFlight = false;
   let countdownStartTs = null;
   let countdownNumber = null;
   let stepThumbUrls = { front: '', left: '', right: '' };
   const enrollShots = { front: null, left: null, right: null };
   let uiState = 'idle';
   let uiLastChangeAt = 0;
   let uiOkFrames = 0;
   let uiNearFrames = 0;
   let uiBadFrames = 0;
   let uiNoFaceFrames = 0;

   function readConfig() {
      const width = clampNumber(ui.widthInput?.value, 80, 1920, 1920);
      const height = clampNumber(ui.heightInput?.value, 60, 1080, 1080);
      const quality = clampNumber(ui.qualityInput?.value, 0.1, 1, 1);
      return {
         width: Math.round(width),
         height: Math.round(height),
         quality: Number(quality.toFixed(2)),
      };
   }

   function readDetectionConfig() {
      const rule = getCurrentRule();
      const minFacePercent = clampNumber(ui.minFaceSizeInput?.value, 5, 80, 20);
      const debugOverride = !!(ui.debugToggle?.checked && ui.minFaceSizeInput);
      return {
         minFaceRatio: debugOverride ? (minFacePercent / 100) : rule.minFaceRatio,
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
         minimumInputLength: 3,
         ajax: {
            url,
            dataType: 'json',
            delay: 120,
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
         resetWizardState();
         setUiState('searching', 'Buscando rostro...', 'Coloca tu cara dentro del ovalo.', 0);
      });

      $('#personSelect').on('select2:clear', function () {
         selectedIdentificacion = '';
         resetWizardState();
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

   function setServiceStatus(state) {
      if (!ui.serviceStatus) return;
      const map = {
         loading: { text: 'Comprobando...', cls: 'bg-secondary' },
         ok: { text: 'Online', cls: 'bg-success' },
         error: { text: 'Offline', cls: 'bg-danger' },
      };
      const cfg = map[state] || map.loading;
      ui.serviceStatus.className = `badge ${cfg.cls}`;
      ui.serviceStatus.textContent = cfg.text;
   }

   function getHealthUrl() {
      const fallback = new URL('camera/health', window.location.href).toString();
      const raw = ui.healthEndpoint?.value || '';
      if (!raw) return fallback;
      try {
         return new URL(raw, window.location.href).toString();
      } catch (error) {
         return fallback;
      }
   }

   async function checkHealth() {
      if (healthInFlight) return serviceOnline === true;
      healthInFlight = true;
      setServiceStatus(serviceOnline === null ? 'loading' : (serviceOnline ? 'ok' : 'error'));
      try {
         const response = await fetch(getHealthUrl(), {
            method: 'GET',
            headers: { Accept: 'application/json' },
            credentials: 'same-origin',
         });
         serviceOnline = response.ok;
      } catch (error) {
         serviceOnline = false;
      } finally {
         healthInFlight = false;
      }
      setServiceStatus(serviceOnline ? 'ok' : 'error');
      return serviceOnline;
   }

   function startHealthPolling() {
      if (healthTimer) return;
      healthTimer = window.setInterval(async () => {
         const ok = await checkHealth();
         if (ok && healthTimer) {
            clearInterval(healthTimer);
            healthTimer = null;
         }
      }, 4000);
   }

   function stopHealthPolling() {
      if (healthTimer) {
         clearInterval(healthTimer);
         healthTimer = null;
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
         ui.stabilityCounter.textContent = `${Math.min(stableCount, CONFIG.wizard.stableFrames)}/${CONFIG.wizard.stableFrames}`;
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
         else if (state === 'capture' || state === 'capturing' || state === 'uploading' || state === 'sending') ui.enrollStateBadge.classList.add('bg-warning');
         else if (state === 'adjusting') ui.enrollStateBadge.classList.add('bg-info');
         else if (state === 'searching' || state === 'need_id' || state === 'no_face' || state === 'idle') ui.enrollStateBadge.classList.add('bg-secondary');
         else if (state === 'error') ui.enrollStateBadge.classList.add('bg-danger');
         else ui.enrollStateBadge.classList.add('bg-success');
      }
      if (ui.enrollProgressBar) {
         const pct = Math.max(0, Math.min(100, Math.round((progress || 0) * 100)));
         ui.enrollProgressBar.style.width = `${pct}%`;
         ui.enrollProgressBar.classList.remove('bg-success', 'bg-info', 'bg-warning', 'bg-primary');
         if (state === 'holding') ui.enrollProgressBar.classList.add('bg-primary');
         else if (state === 'capture' || state === 'capturing' || state === 'uploading' || state === 'sending') ui.enrollProgressBar.classList.add('bg-warning');
         else if (state === 'adjusting') ui.enrollProgressBar.classList.add('bg-info');
         else ui.enrollProgressBar.classList.add('bg-primary');
      }
   }

   function getCurrentStepKey() {
      return ENROLL_STEPS[Math.max(0, Math.min(currentStepIndex, ENROLL_STEPS.length - 1))];
   }

   function getCurrentRule() {
      const stepKey = getCurrentStepKey();
      return CONFIG.stepRules[stepKey] || CONFIG.stepRules.front;
   }

   function setStepState(nextState) {
      stepState = nextState;
      const stepKey = getCurrentStepKey();
      if (ui.enrollStepInstruction) {
         if (nextState === 'awaitContinue') {
            ui.enrollStepInstruction.textContent = 'Paso capturado. Revisa la miniatura y pulsa \"Continuar al siguiente paso\".';
         } else if (nextState === 'readyToSend') {
            ui.enrollStepInstruction.textContent = 'Ya tienes 3/3 capturas. Revisa y pulsa \"Enviar enrolamiento\".';
         } else if (nextState === 'aligning' || nextState === 'holding' || nextState === 'countdown') {
            ui.enrollStepInstruction.textContent = STEP_HINTS[stepKey];
         }
      }
      updateWizardActions();
   }

   function setStepTransitionCooldown() {
      setStepState('awaitContinue');
   }

   function updateChecklist(level = 'bad') {
      const stepKey = getCurrentStepKey();
      if (ui.checkPose) {
         const poseText = stepKey === 'front'
            ? 'Mira al frente y mantiene la cabeza estable.'
            : `Gira la cabeza ~45 grados hacia ${stepKey === 'left' ? 'la izquierda' : 'la derecha'}.`;
         ui.checkPose.textContent = poseText;
      }
      if (ui.checkDistance) {
         ui.checkDistance.textContent = 'Acerca el rostro al ovalo sin salirte del marco.';
      }
      if (ui.checkLight) {
         ui.checkLight.textContent = 'Evita contraluz y sombras fuertes en la cara.';
      }

      [ui.checkPose, ui.checkDistance, ui.checkLight].forEach((el) => {
         if (!el) return;
         el.classList.remove('is-ok');
      });
      if (level === 'ok') {
         ui.checkPose?.classList.add('is-ok');
         ui.checkDistance?.classList.add('is-ok');
      } else if (level === 'near') {
         ui.checkDistance?.classList.add('is-ok');
      }
   }

   function renderShotPreview(stepKey) {
      const map = {
         front: ui.thumbFront,
         left: ui.thumbLeft,
         right: ui.thumbRight,
      };
      const target = map[stepKey];
      if (!target) return;
      if (stepThumbUrls[stepKey]) {
         URL.revokeObjectURL(stepThumbUrls[stepKey]);
         stepThumbUrls[stepKey] = '';
      }
      const blob = enrollShots[stepKey];
      if (!blob) {
         target.classList.add('d-none');
         target.removeAttribute('src');
         return;
      }
      const previewUrl = URL.createObjectURL(blob);
      stepThumbUrls[stepKey] = previewUrl;
      target.src = previewUrl;
      target.classList.remove('d-none');
   }

   function releasePreviewUrls() {
      Object.keys(stepThumbUrls).forEach((key) => {
         if (stepThumbUrls[key]) {
            URL.revokeObjectURL(stepThumbUrls[key]);
            stepThumbUrls[key] = '';
         }
      });
   }

   function updateWizardActions() {
      const hasAllShots = ENROLL_STEPS.every((key) => !!enrollShots[key]);
      if (ui.continueStepBtn) {
         const showContinue = stepState === 'awaitContinue' && currentStepIndex < ENROLL_STEPS.length - 1;
         ui.continueStepBtn.classList.toggle('d-none', !showContinue);
         ui.continueStepBtn.disabled = requestInFlight || !selectedIdentificacion;
      }
      if (ui.sendEnrollBtn) {
         const showSend = hasAllShots && (stepState === 'readyToSend' || stepState === 'awaitContinue');
         ui.sendEnrollBtn.classList.toggle('d-none', !showSend);
         ui.sendEnrollBtn.disabled = requestInFlight || !selectedIdentificacion || serviceOnline === false;
      }
      if (ui.repeatStepBtn) {
         ui.repeatStepBtn.disabled = requestInFlight || !selectedIdentificacion;
      }
   }

   function updateWizardBadges() {
      const badges = [
         { key: 'front', el: ui.stepFrontBadge },
         { key: 'left', el: ui.stepLeftBadge },
         { key: 'right', el: ui.stepRightBadge },
      ];
      badges.forEach(({ key, el }, index) => {
         if (!el) return;
         const done = !!enrollShots[key];
         const isCurrent = index === currentStepIndex;
         el.className = 'badge';
         if (done) {
            el.classList.add('bg-success-subtle', 'text-success-emphasis');
            el.textContent = `${index + 1}. ${STEP_LABELS[key]} \u2713`;
         } else if (isCurrent) {
            el.classList.add('bg-primary-subtle', 'text-primary-emphasis');
            el.textContent = `${index + 1}. ${STEP_LABELS[key]}`;
         } else {
            el.classList.add('bg-secondary-subtle', 'text-secondary-emphasis');
            el.textContent = `${index + 1}. ${STEP_LABELS[key]}`;
         }
         if (isCurrent) {
            el.classList.add('enroll-step-active');
         }
      });
   }

   function updateWizardUI() {
      const stepKey = getCurrentStepKey();
      const completed = Object.values(enrollShots).filter(Boolean).length;
      if (ui.enrollStepTitle) {
         ui.enrollStepTitle.textContent = `Paso ${currentStepIndex + 1}/3: ${STEP_LABELS[stepKey]}`;
      }
      if (ui.enrollStepInstruction) {
         ui.enrollStepInstruction.textContent = STEP_HINTS[stepKey];
      }
      if (ui.enrollStepMeta) {
         ui.enrollStepMeta.textContent = `Completado: ${completed}/3`;
      }
      updateWizardBadges();
      updateChecklist();
      updateWizardActions();
   }

   function clearCountdown() {
      countdownNumber = null;
      countdownStartTs = null;
      if (ui.enrollCountdown) {
         ui.enrollCountdown.textContent = '';
      }
   }

   function updateCountdown(remainingMs, countdownMs) {
      if (!ui.enrollCountdown) return;
      const maxSec = Math.max(1, Math.round(countdownMs / 1000));
      const next = Math.max(1, Math.min(maxSec, Math.ceil(remainingMs / 1000)));
      if (countdownNumber !== next) {
         countdownNumber = next;
      }
      ui.enrollCountdown.textContent = `Capturando en ${next}...`;
   }

   function clearStepCapture(stepKey) {
      if (!stepKey || !Object.prototype.hasOwnProperty.call(enrollShots, stepKey)) return;
      enrollShots[stepKey] = null;
      holdStartTs = null;
      holdProgress = 0;
      stableOkFrames = 0;
      captureQueued = false;
      clearCountdown();
      renderShotPreview(stepKey);
      updateWizardUI();
      updateWizardActions();
   }

   function resetWizardState() {
      ENROLL_STEPS.forEach((stepKey) => {
         enrollShots[stepKey] = null;
         renderShotPreview(stepKey);
      });
      currentStepIndex = 0;
      transitionBlockedUntil = 0;
      resetAlignmentForNextStep();
      setStepState('aligning');
      updateWizardUI();
      if (selectedIdentificacion) {
         const stepKey = getCurrentStepKey();
         setUiState('searching', `Paso ${currentStepIndex + 1}/3: ${STEP_LABELS[stepKey]}`, STEP_HINTS[stepKey], 0);
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
      updateUiCounters(level, hasFace);
      const stepKey = getCurrentStepKey();
      const stepLabel = STEP_LABELS[stepKey];
      updateChecklist(level);

      if (!selectedIdentificacion) {
         setUiState('need_id', 'Selecciona identificacion', 'Selecciona una identificacion para continuar.', 0);
         return;
      }

      if (serviceOnline === false) {
         setUiState('error', 'Servicio fuera de linea', 'Esperando reconexion del servicio para enrolar.', 0);
         return;
      }

      if (requestInFlight) {
         setUiState('sending', 'Enrolando...', 'Validando registro...', 1);
         return;
      }

      if (stepState === 'awaitContinue') {
         setUiState('capture', `Paso ${currentStepIndex + 1}/3: ${stepLabel}`, 'Capturado ✓. Pulsa continuar para seguir.', 1);
         return;
      }
      if (stepState === 'readyToSend') {
         setUiState('capture', 'Capturas listas', 'Pulsa "Enviar enrolamiento" para finalizar.', 1);
         return;
      }

      if (stepCaptureInFlight) {
         setUiState('capture', `Paso ${currentStepIndex + 1}/3: ${STEP_LABELS[stepKey]}`, 'Capturando...', 1);
         return;
      }

      if (captureQueued) {
         setUiState('capture', 'Capturando...', 'Un momento...', 1);
         return;
      }

      if (!hasFace) {
         if (uiNoFaceFrames >= UI_EXIT_FRAMES || uiState === 'idle') {
            setUiState('no_face', `Paso ${currentStepIndex + 1}/3: ${stepLabel}`, 'Coloca tu rostro dentro del ovalo.', 0);
         }
         return;
      }

      if (level === 'bad') {
         if (uiBadFrames >= UI_EXIT_FRAMES) {
            setUiState('bad', `Paso ${currentStepIndex + 1}/3: ${stepLabel}`, 'Ajusta tu posicion y centra el rostro.', 0);
         }
         return;
      }

      if (level === 'near') {
         if (uiNearFrames >= UI_STABLE_FRAMES) {
            setUiState('near', `Paso ${currentStepIndex + 1}/3: ${stepLabel}`, 'Casi listo, mantén la posición.', holdProgress);
         }
         return;
      }

      if (level === 'ok') {
         if (holdProgress > 0 || uiOkFrames >= UI_STABLE_FRAMES) {
            const pct = Math.round(holdProgress * 100);
            setUiState('hold', `Paso ${currentStepIndex + 1}/3: ${stepLabel}`, `Perfecto, manten la posicion... (${pct}%)`, holdProgress);
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
      if (ui.repeatStepBtn) ui.repeatStepBtn.disabled = isBusy || !selectedIdentificacion;
      updateWizardActions();
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

   function showError(message) {
      if (window.Swal) {
         window.Swal.fire({
            title: 'Error',
            text: message || '',
            icon: 'error',
            customClass: { popup: 'swalAlert' },
            confirmButtonText: 'Aceptar',
         });
      } else {
         inlineAlert('danger', message);
      }
   }

   function finishSuccess(message) {
      const finalize = () => {
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
         stopHealthPolling();
         releasePreviewUrls();
      };
      setEnrollStatus('searching', 'Proceso finalizado', 'Presiona aceptar para cerrar.', 0);
      if (window.Swal) {
         window.Swal.fire({
            title: 'Enrolado',
            text: message || '',
            icon: 'success',
            customClass: { popup: 'swalAlert' },
            confirmButtonText: 'Aceptar',
            allowOutsideClick: false,
            allowEscapeKey: false,
         }).then((result) => {
            if (result?.isConfirmed) {
               finalize();
               window.location.reload();
            }
         });
      } else {
         inlineAlert('success', message);
         finalize();
      }
   }

   function initFaceDetection() {
      try {
         faceDetector = createFaceDetector({
            model: 'full',
            minDetectionConfidence: CONFIG.detection.modelMinScore,
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
         score: (faceEma.score * (1 - CONFIG.emaAlpha)) + (nextFace.score * CONFIG.emaAlpha),
         cx: (faceEma.cx * (1 - CONFIG.emaAlpha)) + (nextFace.cx * CONFIG.emaAlpha),
         cy: (faceEma.cy * (1 - CONFIG.emaAlpha)) + (nextFace.cy * CONFIG.emaAlpha),
         w: (faceEma.w * (1 - CONFIG.emaAlpha)) + (nextFace.w * CONFIG.emaAlpha),
         h: (faceEma.h * (1 - CONFIG.emaAlpha)) + (nextFace.h * CONFIG.emaAlpha),
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
         let guideRy = vh * 0.48;
         let guideRx = guideRy * 0.72;
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
      const stepKey = getCurrentStepKey();
      const stepLabel = STEP_LABELS[stepKey];
      const stepRule = getCurrentRule();
      if (ui.enrollBtn) {
         ui.enrollBtn.disabled = !ui.debugToggle?.checked || requestInFlight;
         ui.enrollBtn.classList.toggle('d-none', !ui.debugToggle?.checked);
      }
      if (!selectedIdentificacion) {
         stableOkFrames = 0;
         holdStartTs = null;
         holdProgress = 0;
         captureQueued = false;
         clearCountdown();
         setStepState('idle');
         updateUiFromDetection('bad', false);
         return;
      }
      if (serviceOnline === false) {
         stableOkFrames = 0;
         holdStartTs = null;
         holdProgress = 0;
         captureQueued = false;
         clearCountdown();
         setStepState('idle');
         setUiState('error', 'Servicio fuera de linea', 'Esperando reconexion del servicio para enrolar.', 0);
         startHealthPolling();
         return;
      }
      if (!detectionEnabled) {
         stableOkFrames = 0;
         holdStartTs = null;
         holdProgress = 0;
         clearCountdown();
         setStepState('idle');
         updateAlignUI(detectionEnabled ? (hasDetection ? 'near' : 'bad') : 'off', 0);
         updateUiFromDetection(level, hasDetection);
         return;
      }

      if (stepState === 'awaitContinue') {
         setUiState('capture', `Paso ${currentStepIndex + 1}/3: ${stepLabel}`, 'Capturado ✓. Pulsa continuar para seguir.', 1);
         return;
      }
      if (stepState === 'readyToSend') {
         setUiState('capture', 'Capturas listas', 'Revisa las 3 miniaturas y pulsa "Enviar enrolamiento".', 1);
         return;
      }
      if (stepState === 'sending') {
         setUiState('sending', 'Enrolando...', 'Enviando capturas para validar...', 1);
         return;
      }
      if (Date.now() < transitionBlockedUntil) {
         setUiState('adjusting', `Prep\u00e1rate para ${stepLabel.toLowerCase()}`, STEP_HINTS[stepKey], 0);
         return;
      }

      if (!hasDetection) {
         stableOkFrames = 0;
         holdStartTs = null;
         holdProgress = 0;
         captureQueued = false;
         clearCountdown();
         setStepState('aligning');
         updateAlignUI('bad', 0);
         setUiState('no_face', `Paso ${currentStepIndex + 1}/3: ${stepLabel}`, 'Coloca tu rostro dentro del ovalo.', 0);
         return;
      }

      const isAcceptedLevel = level === 'ok' || level === 'near';
      if (!isAcceptedLevel) {
         stableOkFrames = 0;
         holdStartTs = null;
         holdProgress = 0;
         captureQueued = false;
         clearCountdown();
          setStepState('aligning');
         updateAlignUI('bad', 0);
         setUiState('bad', `Paso ${currentStepIndex + 1}/3: ${stepLabel}`, STEP_HINTS[stepKey], 0);
         return;
      }

      stableOkFrames += 1;
      if (stableOkFrames < CONFIG.wizard.stableFrames) {
         setStepState('aligning');
         updateAlignUI(level, stableOkFrames);
         setUiState('near', `Paso ${currentStepIndex + 1}/3: ${stepLabel}`, 'Casi listo. Mant\u00e9n la posici\u00f3n.', holdProgress);
         return;
      }

      if (stepState !== 'holding' && stepState !== 'countdown') {
         setStepState('holding');
         holdStartTs = performance.now();
         countdownStartTs = null;
      }
      if (!holdStartTs) holdStartTs = performance.now();
      const elapsed = performance.now() - holdStartTs;
      holdProgress = Math.min(1, elapsed / stepRule.holdMs);
      if (stepState === 'holding') {
         if (ui.enrollCountdown) ui.enrollCountdown.textContent = 'Alineado \u2713 Mant\u00e9n la posici\u00f3n';
         setUiState('hold', `Paso ${currentStepIndex + 1}/3: ${stepLabel}`, 'Perfecto, no te muevas.', holdProgress);
      }

      if (
         stepState === 'holding' &&
         holdProgress >= 1 &&
         !requestInFlight &&
         !stepCaptureInFlight &&
         Date.now() >= autoCaptureCooldownUntil &&
         !captureQueued
      ) {
         autoCaptureCooldownUntil = Date.now() + CONFIG.wizard.transitionCooldownMs;
         countdownStartTs = performance.now();
         setStepState('countdown');
      }

      if (stepState === 'countdown' && !requestInFlight && !stepCaptureInFlight && !captureQueued) {
         const countdownMs = stepRule.countdownSec * 1000;
         const countdownElapsed = performance.now() - (countdownStartTs || performance.now());
         const remainingMs = Math.max(0, countdownMs - countdownElapsed);
         updateCountdown(remainingMs, countdownMs);
         setUiState('hold', `Paso ${currentStepIndex + 1}/3: ${stepLabel}`, 'Listo para capturar...', holdProgress);
         if (remainingMs > 0) {
            return;
         }
         captureQueued = true;
         autoCaptureCooldownUntil = Date.now() + CONFIG.wizard.transitionCooldownMs;
         holdStartTs = null;
         countdownStartTs = null;
         stableOkFrames = 0;
         clearCountdown();
         if (autoCaptureTimer) {
            clearTimeout(autoCaptureTimer);
            autoCaptureTimer = null;
         }
         autoCaptureTimer = window.setTimeout(async () => {
            await captureCurrentStep();
            autoCaptureTimer = null;
         }, CONFIG.wizard.captureDelayMs);
      }
   }

   function handleFaceResults(results) {
      if (!detectionEnabled) return;
      if (stepState === 'awaitContinue' || stepState === 'readyToSend' || stepState === 'sending') {
         return;
      }
      const detections = results?.detections ?? [];
      const rule = getCurrentRule();
      const { minFaceRatio } = readDetectionConfig();
      const foundNow = hasValidFace(detections, {
         minScore: rule.minScoreExit,
         minFaceRatio,
      });

      if (!window.__mpLastSeenAt) window.__mpLastSeenAt = 0;
      if (foundNow) window.__mpLastSeenAt = Date.now();
      const stable = (Date.now() - window.__mpLastSeenAt) <= 400;

      lastFaceDetected = stable;
      setFaceStatus(stable);

      if (!detections.length) {
         isAligned = false;
         alignedState = false;
         lastFaceForCapture = null;
         setStepState('aligning');
         updateEnrollState('bad', false);
         holdProgress = 0;
         renderDebug(null, ovalTarget, { level: 'bad', score: 0, dist: 0, sizeRel: 0, sizeRelX: 0, sizeRelY: 0, inside: false, sizeOk: false, scoreOk: false, dx: 0, dy: 0, progress: 0 }, 0);
         return;
      }

      const bestFace = pickBestFace(detections, ovalTarget, rule);
      if (!bestFace) {
         isAligned = false;
         alignedState = false;
         lastFaceForCapture = null;
         setStepState('aligning');
         updateAlignUI('bad', 0);
         holdProgress = 0;
         updateEnrollState('bad', true);
         renderDebug(null, ovalTarget, { level: 'bad', score: 0, dist: 0, sizeRel: 0, sizeRelX: 0, sizeRelY: 0, inside: false, sizeOk: false, scoreOk: false, dx: 0, dy: 0, progress: 0 }, detections.length);
         return;
      }

      const smoothedFace = smoothFace(bestFace);
      lastFaceForCapture = {
         score: bestFace.score,
         box: {
            xCenter: smoothedFace.cx,
            yCenter: smoothedFace.cy,
            width: smoothedFace.w,
            height: smoothedFace.h,
         },
      };
      const validation = validateAlignment(smoothedFace, ovalTarget, alignedState, rule, getCurrentStepKey());
      const isAcceptedLevel = validation.level === 'ok' || validation.level === 'near';
      alignedState = validation.aligned || isAcceptedLevel;
      isAligned = alignedState;
      if (isAcceptedLevel && stepState === 'aligning') {
         setStepState('holding');
      }
      updateAlignUI(validation.level, isAcceptedLevel ? CONFIG.wizard.stableFrames : 0);
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
            profileHintOk: validation.profileHintOk,
         },
         detections.length
      );

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
      }, CONFIG.detection.intervalMs);
   }

   function stopDetectionLoop() {
      if (detectionTimer) {
         clearInterval(detectionTimer);
         detectionTimer = null;
      }
      detectionInFlight = false;
      stableOkFrames = 0;
      holdStartTs = null;
      countdownStartTs = null;
      holdProgress = 0;
      captureQueued = false;
      isAligned = false;
      clearCountdown();
      setStepState('idle');
      updateAlignUI('off', 0);
      setUiState('idle', 'Camara detenida', 'Inicia la camara para continuar.', 0);
      renderDebug(null, ovalTarget);
   }

   async function startCamera() {
      if (stream) {
         return true;
      }
      if (!selectedIdentificacion) {
         setUiState('need_id', 'Selecciona identificacion', 'Selecciona una identificacion para continuar.', 0);
         inlineAlert('warning', 'Selecciona una identificacion antes de iniciar la camara.');
         return false;
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
      transitionBlockedUntil = 0;
      setStepState('aligning');
      updateWizardUI();
      const stepKey = getCurrentStepKey();
      setUiState('searching', `Paso ${currentStepIndex + 1}/3: ${STEP_LABELS[stepKey]}`, STEP_HINTS[stepKey], 0);
      startDetectionLoop();
      const ok = await checkHealth();
      if (!ok) {
         inlineAlert('warning', 'Servicio fuera de linea. Esperando reconexion...');
         startHealthPolling();
      }
      return true;
   }

   function stopCamera() {
      stream = stopStream(stream, ui.video);
      setCameraStatus('off');
      setButtons(false);
      stopDetectionLoop();
      stopHealthPolling();
   }

   async function captureBlobForStep() {
      if (detectionEnabled && (!lastFaceDetected || !isAligned)) {
         inlineAlert('warning', 'Alinea tu rostro antes de capturar este paso.');
         return null;
      }
      const ok = await startCamera();
      if (!ok || ui.video.readyState < 2) {
         inlineAlert('warning', 'La camara no esta lista para capturar.');
         return null;
      }

      if (lastFaceForCapture?.box) {
         const isSmallFace = (lastFaceForCapture.box.width || 0) < CONFIG.crop.smallFaceW;
         const blob = await cropFaceToBlob(ui.video, ui.canvas, lastFaceForCapture, {
            size: isSmallFace ? CONFIG.crop.bigSize : CONFIG.crop.baseSize,
            padding: isSmallFace ? CONFIG.crop.paddingSmall : CONFIG.crop.padding,
            quality: isSmallFace ? CONFIG.crop.qualitySmall : CONFIG.crop.quality,
         });
         if (blob) return blob;
      }

      const config = readConfig();
      return captureFullFrameBlob(ui.video, ui.canvas, {
         width: config.width,
         height: config.height,
         quality: config.quality,
      });
   }

   function resetAlignmentForNextStep() {
      stableOkFrames = 0;
      holdStartTs = null;
      countdownStartTs = null;
      holdProgress = 0;
      captureQueued = false;
      autoCaptureCooldownUntil = 0;
      alignedState = false;
      isAligned = false;
      clearCountdown();
   }

   async function captureCurrentStep() {
      if (requestInFlight || stepCaptureInFlight) return;
      const identificacion = selectedIdentificacion || '';
      if (!identificacion) {
         setUiState('need_id', 'Selecciona identificacion', 'Selecciona una identificacion para continuar.', 0);
         inlineAlert('warning', 'Selecciona una identificacion para continuar.');
         captureQueued = false;
         return;
      }
      const stepKey = getCurrentStepKey();
      stepCaptureInFlight = true;
      setUiState('capture', `Paso ${currentStepIndex + 1}/3: ${STEP_LABELS[stepKey]}`, 'Capturando...', 1);

      try {
         const blob = await captureBlobForStep();
         if (!blob) {
            setUiState('error', 'Captura no valida', 'No se pudo capturar este paso. Intenta de nuevo.', 0);
            setStepState('aligning');
            return;
         }
         enrollShots[stepKey] = blob;
         renderShotPreview(stepKey);
         updateWizardUI();
         setUiState('capture', `Paso ${currentStepIndex + 1}/3: ${STEP_LABELS[stepKey]}`, 'Capturado \u2713', 1);
         resetAlignmentForNextStep();
         if (currentStepIndex < ENROLL_STEPS.length - 1) {
            setStepTransitionCooldown();
            setUiState('capture', `Paso ${currentStepIndex + 1}/3: ${STEP_LABELS[stepKey]}`, 'Capturado ✓. Pulsa continuar para seguir.', 1);
         } else {
            setStepState('readyToSend');
            setUiState('capture', 'Capturas listas', 'Revisa las 3 fotos y pulsa "Enviar enrolamiento".', 1);
         }
      } finally {
         stepCaptureInFlight = false;
         captureQueued = false;
      }
   }

   function goToNextStep() {
      if (currentStepIndex >= ENROLL_STEPS.length - 1) {
         setStepState('readyToSend');
         updateWizardActions();
         return;
      }
      currentStepIndex += 1;
      resetAlignmentForNextStep();
      setStepState('aligning');
      transitionBlockedUntil = Date.now() + CONFIG.wizard.transitionCooldownMs;
      updateWizardUI();
      const stepKey = getCurrentStepKey();
      setUiState('searching', `Prep\u00e1rate para paso ${currentStepIndex + 1}/3`, STEP_HINTS[stepKey], 0);
   }

   function buildEnrollFormData(identificacion) {
      const formData = new FormData();
      ENROLL_STEPS.forEach((stepKey, index) => {
         const blob = enrollShots[stepKey];
         if (blob) {
            formData.append('images[]', blob, `${stepKey}.jpg`);
            formData.append(stepKey, blob, `${stepKey}.jpg`);
         }
      });
      formData.append('identificacion', identificacion);
      return formData;
   }

   async function submitEnroll() {
      if (requestInFlight) return;
      const identificacion = selectedIdentificacion || '';
      if (!identificacion) {
         inlineAlert('warning', 'Selecciona una identificacion para continuar.');
         return;
      }
      const missing = ENROLL_STEPS.filter((key) => !enrollShots[key]);
      if (missing.length) {
         setUiState('error', 'Captura incompleta', 'Debes completar los 3 pasos antes de enviar.', 0);
         return;
      }
      const healthOk = await checkHealth();
      if (!healthOk) {
         setUiState('error', 'Servicio fuera de linea', 'No es posible enrolar en este momento.', 0);
         inlineAlert('warning', 'Servicio fuera de linea. Esperando reconexion...');
         startHealthPolling();
         return;
      }

      setApiBusy(true);
      setStepState('sending');
      setUiState('sending', 'Enrolando...', 'Enviando capturas para validar...', 1);
      try {
         const response = await fetch(getEndpointUrl(), {
            method: 'POST',
            headers: {
               Accept: 'application/json',
               'X-CSRF-TOKEN': getCsrfToken(),
            },
            body: buildEnrollFormData(identificacion),
            credentials: 'same-origin',
         });
         const data = await response.json().catch(() => null);
         if (!response.ok || !data) {
            showError('No se pudo enrolar. Intentalo de nuevo.');
            setUiState('error', 'Error al enrolar', 'Reintenta el envio o repite el paso.', 0);
            setStepState('readyToSend');
            return;
         }
         const ok = data.status === 'ok' || data.ok === true;
         if (!ok) {
            showError(data.message || 'No se pudo enrolar. Intentalo de nuevo.');
            setUiState('error', 'Error al enrolar', 'Reintenta el envio o repite el paso.', 0);
            setStepState('readyToSend');
            return;
         }
         finishSuccess(`Enrolado correctamente. Identificacion: ${identificacion}`);
      } catch (error) {
         serviceOnline = false;
         setServiceStatus('error');
         startHealthPolling();
         showError('No se pudo enrolar. Intentalo de nuevo.');
         setUiState('error', 'Error al enrolar', 'Reintenta el envio o repite el paso.', 0);
         setStepState('readyToSend');
      } finally {
         setApiBusy(false);
      }
   }

   if (ui.startBtn) ui.startBtn.addEventListener('click', startCamera);
   if (ui.stopBtn) ui.stopBtn.addEventListener('click', stopCamera);
   if (ui.enrollBtn) ui.enrollBtn.addEventListener('click', submitEnroll);
   if (ui.continueStepBtn) {
      ui.continueStepBtn.addEventListener('click', () => {
         if (stepState !== 'awaitContinue') return;
         goToNextStep();
      });
   }
   if (ui.sendEnrollBtn) {
      ui.sendEnrollBtn.addEventListener('click', submitEnroll);
   }
   if (ui.repeatStepBtn) {
      ui.repeatStepBtn.addEventListener('click', () => {
         const stepKey = getCurrentStepKey();
         clearStepCapture(stepKey);
         setStepState('aligning');
         setUiState('adjusting', `Paso ${currentStepIndex + 1}/3: ${STEP_LABELS[stepKey]}`, STEP_HINTS[stepKey], 0);
      });
   }

   if (ui.faceDetectionToggle) {
      ui.faceDetectionToggle.addEventListener('change', () => {
         detectionEnabled = ui.faceDetectionToggle.checked;
         lastFaceDetected = false;
         setFaceStatus(false);
         stableOkFrames = 0;
         updateAlignUI(detectionEnabled ? 'bad' : 'off', 0);
         const stepKey = getCurrentStepKey();
         setUiState('searching', `Paso ${currentStepIndex + 1}/3: ${STEP_LABELS[stepKey]}`, STEP_HINTS[stepKey], 0);
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
      stopHealthPolling();
      releasePreviewUrls();
   });

   window.addEventListener('resize', () => {
      ovalTarget = getOvalTargetFromDOM();
   });

   detectionEnabled = !!(ui.faceDetectionToggle && ui.faceDetectionToggle.checked);
   initFaceDetection();
   initPersonSelect();
   setCameraStatus('off');
   setFaceStatus(false);
   setServiceStatus('loading');
   updateAlignUI(detectionEnabled ? 'bad' : 'off', 0);
   setUiState('need_id', 'Selecciona identificacion', 'Selecciona una identificacion para continuar.', 0);
   setButtons(false);
   resetWizardState();
   void checkHealth().then((ok) => {
      if (!ok) {
         startHealthPolling();
      }
   });
   if (ui.enrollBtn) {
      ui.enrollBtn.disabled = !ui.debugToggle?.checked;
      ui.enrollBtn.classList.toggle('d-none', !ui.debugToggle?.checked);
   }
   ovalTarget = getOvalTargetFromDOM();
});
