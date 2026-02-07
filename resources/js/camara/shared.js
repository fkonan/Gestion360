export function clampNumber(value, min, max, fallback) {
  const parsed = Number(value);
  if (Number.isNaN(parsed)) {
    return fallback;
  }
  if (typeof min === 'number' && parsed < min) {
    return min;
  }
  if (typeof max === 'number' && parsed > max) {
    return max;
  }
  return parsed;
}

export function getCsrfToken() {
  const meta = document.querySelector('meta[name="csrf-token"]');
  return meta ? meta.getAttribute('content') : '';
}

export function getCameraErrorMessage(err) {
  if (err && err.name === 'NotAllowedError') {
    return 'Permiso denegado. Autoriza el acceso a la camara para continuar.';
  }
  if (err && (err.name === 'NotFoundError' || err.name === 'OverconstrainedError')) {
    return 'No se detecto ninguna camara disponible.';
  }
  if (err && err.name === 'NotReadableError') {
    return 'La camara esta en uso o no es accesible.';
  }
  return 'No se pudo acceder a la camara.';
}

export async function requestCamera(videoEl) {
  if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
    return { stream: null, error: 'Tu navegador no soporta acceso a la camara.' };
  }
  try {
    const stream = await navigator.mediaDevices.getUserMedia({ video: true, audio: false });
    videoEl.srcObject = stream;
    await videoEl.play();
    return { stream, error: null };
  } catch (err) {
    return { stream: null, error: getCameraErrorMessage(err) };
  }
}

export function stopStream(stream, videoEl) {
  if (!stream) {
    return null;
  }
  stream.getTracks().forEach((track) => track.stop());
  if (videoEl) {
    videoEl.srcObject = null;
  }
  return null;
}

export function showInlineAlert(container, type, message, lastMessage = '') {
  if (!container || !message || message === lastMessage) {
    return lastMessage;
  }
  container.innerHTML = '';
  const wrapper = document.createElement('div');
  wrapper.className = `alert alert-${type} alert-dismissible fade show`;
  wrapper.setAttribute('role', 'alert');
  wrapper.innerHTML = `<div>${message}</div>` +
    '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>';
  container.appendChild(wrapper);
  return message;
}

export function showSwal(type, message, title) {
  if (window.Swal) {
    window.Swal.fire({
      title: title || (type === 'success' ? 'Proceso completado' : 'Error'),
      text: message || '',
      icon: type,
      customClass: { popup: 'swalAlert' },
    });
    return true;
  }
  return false;
}

export function captureJpegFromVideo(videoEl, canvasEl, { width, height, quality }) {
  return new Promise((resolve) => {
    canvasEl.width = width;
    canvasEl.height = height;
    const ctx = canvasEl.getContext('2d');
    ctx.drawImage(videoEl, 0, 0, width, height);
    canvasEl.toBlob((blob) => {
      resolve(blob || null);
    }, 'image/jpeg', quality);
  });
}

export function normalizeName(value) {
  if (!value) return '';
  return String(value).trim().toLowerCase().replace(/\s+/g, '');
}

export function isUnknown(value) {
  const normalized = normalizeName(value);
  return normalized === 'unknown' || normalized === 'unknow';
}

export function dedupePush(list, item, max) {
  const index = list.indexOf(item);
  if (index !== -1) {
    list.splice(index, 1);
  }
  list.push(item);
  if (max && list.length > max) {
    list.splice(0, list.length - max);
  }
  return list;
}

export function hasValidFace(detections, { minScore, minFaceRatio }) {
  if (!Array.isArray(detections)) {
    return false;
  }
  return detections.some((d) => {
    const score =
      Array.isArray(d?.score) && d.score.length ? d.score[0] :
        typeof d?.score === 'number' ? d.score :
          1;
    const width = d?.boundingBox?.width ?? 0;
    const widthRatio = width > 0 && width <= 1 ? width : 0;
    return score >= minScore && widthRatio >= minFaceRatio;
  });
}

export function getDetectionScore(detection) {
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

function normalizeBox(box, videoEl) {
  if (!box) return null;
  let cx = box.xCenter;
  let cy = box.yCenter;
  let w = box.width;
  let h = box.height;
  if (cx == null || cy == null || w == null || h == null) {
    if (box.originX != null && box.originY != null && box.width != null && box.height != null) {
      cx = box.originX + (box.width / 2);
      cy = box.originY + (box.height / 2);
      w = box.width;
      h = box.height;
    }
  }
  if (cx == null || cy == null || w == null || h == null) {
    return null;
  }
  if (w > 1.5 || h > 1.5 || cx > 1.5 || cy > 1.5) {
    const vw = videoEl?.videoWidth || videoEl?.clientWidth || 1;
    const vh = videoEl?.videoHeight || videoEl?.clientHeight || 1;
    cx = cx / vw;
    cy = cy / vh;
    w = w / vw;
    h = h / vh;
  }
  w = Math.min(1, Math.max(0, w));
  h = Math.min(1, Math.max(0, h));
  cx = Math.min(1, Math.max(0, cx));
  cy = Math.min(1, Math.max(0, cy));
  if (!Number.isFinite(cx) || !Number.isFinite(cy) || !Number.isFinite(w) || !Number.isFinite(h)) {
    return null;
  }
  if (w <= 0 || h <= 0) {
    return null;
  }
  return { xCenter: cx, yCenter: cy, width: w, height: h };
}

export function normalizeDetections(detections, videoEl, { minScore = 0, minFaceRatio = 0 } = {}) {
  if (!Array.isArray(detections)) return [];
  const faces = [];
  detections.forEach((d) => {
    const score = getDetectionScore(d);
    const box = normalizeBox(d?.boundingBox, videoEl);
    if (!box) return;
    if (score < minScore || box.width < minFaceRatio) return;
    const area = box.width * box.height;
    const dx = box.xCenter - 0.5;
    const dy = box.yCenter - 0.5;
    const centerDist = (dx * dx) + (dy * dy);
    faces.push({ score, box, area, centerDist });
  });
  return faces;
}

export function pickTopFaces(faces, maxFaces, rotationIndex = 0) {
  if (!Array.isArray(faces) || faces.length === 0) return [];
  const sorted = [...faces].sort((a, b) => {
    if (b.area !== a.area) return b.area - a.area;
    if (b.score !== a.score) return b.score - a.score;
    return a.centerDist - b.centerDist;
  });
  if (sorted.length <= maxFaces) {
    return sorted;
  }
  const offset = rotationIndex % sorted.length;
  const rotated = sorted.slice(offset).concat(sorted.slice(0, offset));
  return rotated.slice(0, maxFaces);
}

export async function cropFacesToBlobs(videoEl, canvasEl, faces, {
  size = 320,
  padding = 0.25,
  quality = 0.8,
} = {}) {
  if (!videoEl || !canvasEl || !faces.length) return [];
  const vw = videoEl.videoWidth || videoEl.clientWidth;
  const vh = videoEl.videoHeight || videoEl.clientHeight;
  if (!vw || !vh) return [];
  const ctx = canvasEl.getContext('2d');
  const results = [];
  for (const face of faces) {
    const { xCenter, yCenter, width, height } = face.box;
    const pad = padding;
    const w = Math.min(1, width * (1 + pad * 2));
    const h = Math.min(1, height * (1 + pad * 2));
    const sx = Math.max(0, (xCenter - w / 2) * vw);
    const sy = Math.max(0, (yCenter - h / 2) * vh);
    const sw = Math.min(vw, w * vw);
    const sh = Math.min(vh, h * vh);
    canvasEl.width = size;
    canvasEl.height = size;
    ctx.drawImage(videoEl, sx, sy, sw, sh, 0, 0, size, size);
    const blob = await new Promise((resolve) => {
      canvasEl.toBlob((b) => resolve(b || null), 'image/jpeg', quality);
    });
    if (blob) results.push(blob);
  }
  return results;
}

export async function cropFaceToBlob(videoEl, canvasEl, face, {
  size = 320,
  padding = 0.25,
  quality = 0.8,
} = {}) {
  if (!videoEl || !canvasEl || !face?.box) return null;
  const vw = videoEl.videoWidth || videoEl.clientWidth;
  const vh = videoEl.videoHeight || videoEl.clientHeight;
  if (!vw || !vh) return null;
  const { xCenter, yCenter, width, height } = face.box;
  const pad = padding;
  const w = Math.min(1, width * (1 + pad * 2));
  const h = Math.min(1, height * (1 + pad * 2));
  const sx = Math.max(0, (xCenter - w / 2) * vw);
  const sy = Math.max(0, (yCenter - h / 2) * vh);
  const sw = Math.min(vw, w * vw);
  const sh = Math.min(vh, h * vh);
  const ctx = canvasEl.getContext('2d');
  canvasEl.width = size;
  canvasEl.height = size;
  ctx.drawImage(videoEl, sx, sy, sw, sh, 0, 0, size, size);
  return new Promise((resolve) => {
    canvasEl.toBlob((b) => resolve(b || null), 'image/jpeg', quality);
  });
}

export async function captureFullFrameBlob(videoEl, canvasEl, {
  width = 640,
  height = 480,
  quality = 0.75,
} = {}) {
  if (!videoEl || !canvasEl) return null;
  const vw = videoEl.videoWidth || videoEl.clientWidth;
  const vh = videoEl.videoHeight || videoEl.clientHeight;
  if (!vw || !vh) return null;
  const ctx = canvasEl.getContext('2d');
  canvasEl.width = width;
  canvasEl.height = height;
  ctx.drawImage(videoEl, 0, 0, width, height);
  return new Promise((resolve) => {
    canvasEl.toBlob((b) => resolve(b || null), 'image/jpeg', quality);
  });
}
