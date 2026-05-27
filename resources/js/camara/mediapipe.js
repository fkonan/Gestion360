import { FaceDetection } from '@mediapipe/face_detection';

// Rutas fijas por ambiente (como estaba antes: pruebas vs produccion).
const MEDIAPIPE_BASES = {
   prod: '/gestion360/public/',
   dev: '/vendor/mediapipe/face_detection/',
};

export function getMediapipeBaseUrl() {
   const hostname = window.location.hostname || '';
   const isLocal = hostname === 'localhost' || hostname === '127.0.0.1';
   const basePath = isLocal ? MEDIAPIPE_BASES.dev : MEDIAPIPE_BASES.prod;
   return new URL(basePath, window.location.origin).toString();
}

export function checkMediapipeAssets(baseUrl, files = null) {
   if (typeof window === 'undefined' || typeof fetch === 'undefined') {
      return;
   }
   const targets = files || [
      'face_detection_full_range.tflite',
      'face_detection_short_range.tflite',
      'face_detection_solution_simd_wasm_bin.wasm',
   ];
   targets.forEach(async (file) => {
      const url = `${baseUrl}${file}`;
      try {
         const head = await fetch(url, { method: 'HEAD', cache: 'no-store' });
         if (!head.ok) {
            console.warn('[mediapipe] asset faltante', url, head.status);
         }
      } catch (error) {
         try {
            const res = await fetch(url, { method: 'GET', cache: 'no-store' });
            if (!res.ok) {
               console.warn('[mediapipe] asset faltante', url, res.status);
            }
         } catch (innerError) {
            console.warn('[mediapipe] asset no accesible', url);
         }
      }
   });
}

export function createFaceDetector({
   model = 'full',
   minDetectionConfidence = 0.55,
   onResults,
   debug = false,
} = {}) {
   const baseUrl = getMediapipeBaseUrl();
   const detector = new FaceDetection({
      locateFile: (file) => `${baseUrl}${file}`,
   });
   detector.setOptions({ model, minDetectionConfidence });
   if (typeof onResults === 'function') {
      detector.onResults(onResults);
   }
   if (debug) {
      checkMediapipeAssets(baseUrl);
   }
   return detector;
}
