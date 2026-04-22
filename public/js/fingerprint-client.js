(function (global) {
  function normalizeB64(b64url) {
    var s = (b64url || "").trim().replace(/-/g, "+").replace(/_/g, "/");
    while (s.length % 4) s += "=";
    return s;
  }

  function base64ToUtf8(b64) {
    if (!b64) return "";
    try {
      return global.atob(b64);
    } catch (err) {
      return "";
    }
  }

  function b64UrlToUtf8(b64url) {
    return base64ToUtf8(normalizeB64(b64url));
  }

  function looksLikeBase64(value) {
    if (typeof value !== "string") return false;
    if (value.length < 40) return false;
    return /^[A-Za-z0-9+/_-]+={0,2}$/.test(value);
  }

  function looksLikePng(value) {
    if (typeof value !== "string") return false;
    return value.indexOf("iVBOR") === 0;
  }

  function isPngFormat(format) {
    if (format === null || typeof format === "undefined") return false;
    if (global.Fingerprint && global.Fingerprint.SampleFormat) {
      if (format === global.Fingerprint.SampleFormat.PngImage) return true;
    }
    if (typeof format === "string") {
      var lowered = format.toLowerCase();
      if (lowered === "pngimage") return true;
      var numeric = parseInt(format, 10);
      if (!isNaN(numeric) && numeric === 5) return true;
    }
    if (typeof format === "number") {
      return format === 5;
    }
    return false;
  }

  function safeParseSamples(samplesPayload) {
    if (!samplesPayload) return [];
    if (Array.isArray(samplesPayload)) return samplesPayload;
    if (typeof samplesPayload !== "string") return [];

    var trimmed = samplesPayload.trim();
    if (!trimmed) return [];

    try {
      var parsed = JSON.parse(trimmed);
      if (Array.isArray(parsed)) return parsed;
    } catch (err) {}

    var decoded = b64UrlToUtf8(trimmed);
    if (decoded) {
      try {
        var parsedDecoded = JSON.parse(decoded);
        if (Array.isArray(parsedDecoded)) return parsedDecoded;
      } catch (err) {}
    }

    if (looksLikeBase64(trimmed)) {
      return [trimmed];
    }

    return [];
  }

  function extractSampleData(sample) {
    if (!sample) return "";
    if (typeof sample === "string") return sample;
    if (typeof sample.Data === "string") return sample.Data;
    return "";
  }

  class FingerprintClient {
    constructor() {
      this.sdk = null;
      this.acquisitionStarted = false;
      this.currentReader = null;
      this.lastQuality = null;
      this.pendingPngResolve = null;
      this.pendingPngReject = null;
      this.pendingPngTimer = null;
      this.onDeviceConnected = null;
      this.onDeviceDisconnected = null;
      this.onCommunicationFailed = null;
      this.onQualityReported = null;
      this.onSamplesAcquired = null;
      this.onErrorOccurred = null;
    }

    resetWebSdkSessionCache() {
      try {
        global.sessionStorage.removeItem("websdk");
        global.sessionStorage.removeItem("websdk.sessionId");
      } catch (err) {}
    }

    init() {
      if (!global.Fingerprint || !global.Fingerprint.WebApi) {
        throw new Error("Fingerprint SDK not loaded");
      }

      // Avoid stale SRP/session data after local WebSDK service restarts.
      this.resetWebSdkSessionCache();
      this.sdk = new global.Fingerprint.WebApi();

      this.sdk.onDeviceConnected = (e) => {
        if (this.onDeviceConnected) this.onDeviceConnected(e);
      };
      this.sdk.onDeviceDisconnected = (e) => {
        if (this.onDeviceDisconnected) this.onDeviceDisconnected(e);
      };
      this.sdk.onCommunicationFailed = (e) => {
        if (this.onCommunicationFailed) this.onCommunicationFailed(e);
      };
      this.sdk.onErrorOccurred = (e) => {
        if (this.onErrorOccurred) this.onErrorOccurred(e);
      };
      this.sdk.onQualityReported = (e) => {
        this.lastQuality = e ? e.quality : null;
        if (this.onQualityReported) this.onQualityReported(e);
      };
      this.sdk.onSamplesAcquired = (e) => {
        var normalized = this.extractSamples(e);
        var format = e ? (e.sampleFormat ?? e.SampleFormat) : null;
        if (this.pendingPngResolve) {
          var isPng = isPngFormat(format) || looksLikePng(normalized[0]);
          if (isPng) {
            var resolve = this.pendingPngResolve;
            this.clearPendingPng();
            resolve(normalized);
            this.stopCapture().catch(() => {});
            return;
          }
          if (normalized.length > 0) {
            var reject = this.pendingPngReject;
            this.clearPendingPng();
            if (reject) reject(new Error("Sample is not PNG"));
            this.stopCapture().catch(() => {});
            return;
          }
        }
        if (this.onSamplesAcquired) this.onSamplesAcquired(normalized, e);
      };
    }

    extractSamples(samplesEvent) {
      var parsed = safeParseSamples(samplesEvent && samplesEvent.samples);
      var normalized = [];
      for (var i = 0; i < parsed.length; i += 1) {
        var data = extractSampleData(parsed[i]);
        var normalizedData = normalizeB64(data);
        if (normalizedData) normalized.push(normalizedData);
      }
      return normalized;
    }

    enumerateReaders() {
      if (!this.sdk) return Promise.reject(new Error("Fingerprint SDK not initialized"));
      return this.sdk.enumerateDevices();
    }

    startCapture(format, reader) {
      if (!this.sdk) return Promise.reject(new Error("Fingerprint SDK not initialized"));
      if (this.acquisitionStarted) return Promise.resolve(false);
      var sampleFormat = format;
      if (typeof sampleFormat === "undefined" || sampleFormat === null) {
        if (!global.Fingerprint || !global.Fingerprint.SampleFormat) {
          return Promise.reject(new Error("Sample format not available"));
        }
        sampleFormat = global.Fingerprint.SampleFormat.Intermediate;
      }
      this.currentReader = reader || null;
      return this.sdk
        .startAcquisition(sampleFormat, this.currentReader || undefined)
        .then(() => {
          this.acquisitionStarted = true;
          return true;
        });
    }

    startCaptureIntermediate(reader) {
      return this.startCapture(null, reader);
    }

    startCapturePngImage(reader) {
      if (!global.Fingerprint || !global.Fingerprint.SampleFormat) {
        return Promise.reject(new Error("PNG format not available"));
      }
      if (typeof global.Fingerprint.SampleFormat.PngImage === "undefined") {
        return Promise.reject(new Error("PNG format not available"));
      }
      return this.startCapture(global.Fingerprint.SampleFormat.PngImage, reader);
    }

    startCaptureRaw(reader) {
      if (!global.Fingerprint || !global.Fingerprint.SampleFormat) {
        return Promise.reject(new Error("Raw format not available"));
      }
      return this.startCapture(global.Fingerprint.SampleFormat.Raw, reader);
    }

    stopCapture() {
      if (!this.sdk) return Promise.reject(new Error("Fingerprint SDK not initialized"));
      if (!this.acquisitionStarted) return Promise.resolve(false);
      return this.sdk.stopAcquisition(this.currentReader || undefined).then(() => {
        this.acquisitionStarted = false;
        return true;
      });
    }

    clearPendingPng() {
      if (this.pendingPngTimer) {
        clearTimeout(this.pendingPngTimer);
      }
      this.pendingPngTimer = null;
      this.pendingPngResolve = null;
      this.pendingPngReject = null;
    }

    capturePngSample(reader, timeoutMs) {
      if (!this.sdk) return Promise.reject(new Error("Fingerprint SDK not initialized"));
      if (this.acquisitionStarted) {
        return Promise.reject(new Error("Acquisition already started"));
      }

      var waitTime = typeof timeoutMs === "number" ? timeoutMs : 15000;
      var targetReader = reader || this.currentReader || null;

      return new Promise((resolve, reject) => {
        this.pendingPngResolve = resolve;
        this.pendingPngReject = reject;

        this.pendingPngTimer = setTimeout(() => {
          var rejectFn = this.pendingPngReject;
          this.clearPendingPng();
          if (rejectFn) rejectFn(new Error("PNG capture timeout"));
          this.stopCapture().catch(() => {});
        }, waitTime);

        this.startCapturePngImage(targetReader).catch((err) => {
          this.clearPendingPng();
          reject(err);
        });
      }).then((samples) => {
        var data = samples && samples.length ? samples[0] : "";
        if (!data) return "";
        return "data:image/png;base64," + data;
      });
    }
  }

  global.FingerprintClient = FingerprintClient;
  global.FingerprintClient.normalizeB64 = normalizeB64;
})(window);
