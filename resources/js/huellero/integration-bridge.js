/**
 * Bridge para integrar el JavaScript original del huellero con Laravel
 * Mantiene compatibilidad con funciones existentes mientras añade funcionalidad Laravel
 */

// Importar funciones del app.js original
const OriginalHuellero = {
    // Variables globales originales
    test: null,
    state: null,
    myVal: "",
    disabled: true,
    startEnroll: false,
    currentFormat: null,
    deviceTechn: {
        0: "Unknown",
        1: "Optical",
        2: "Capacitive",
        3: "Thermal",
        4: "Pressure"
    },
    deviceModality: {
        0: "Unknown",
        1: "Swipe",
        2: "Area",
        3: "AreaMultifinger"
    },
    deviceUidType: {
        0: "Persistent",
        1: "Volatile"
    }
};

/**
 * Clase híbrida que mantiene funcionalidad original y añade Laravel
 */
class LaravelHuelleroIntegration {
    constructor() {
        this.initializeOriginalSDK();
        this.initializeLaravelIntegration();
    }

    /**
     * Inicializar SDK original con adaptaciones
     */
    initializeOriginalSDK() {
        // Configurar estado inicial como en app.js original
        this.state = document.getElementById('content-capture') || document.body;
        OriginalHuellero.currentFormat = Fingerprint.SampleFormat.PngImage;

        // Crear instancia del SDK original con modificaciones
        this.originalTest = this.createModifiedFingerprintSDK();

        // Exponer globalmente para compatibilidad
        window.test = this.originalTest;
        window.myVal = OriginalHuellero.myVal;
        window.currentFormat = OriginalHuellero.currentFormat;
    }

    /**
     * Crear SDK modificado que integra con Laravel
     */
    createModifiedFingerprintSDK() {
        const self = this;

        function FingerprintSdkTest() {
            const _instance = this;
            this.operationToRestart = null;
            this.acquisitionStarted = false;
            this.sdk = new Fingerprint.WebApi();

            // Eventos originales con integración Laravel
            this.sdk.onDeviceConnected = function (e) {
                self.showMessage("Dispositivo conectado - Listo para escanear");
                self.notifyLaravelDeviceEvent('connected', e);
            };

            this.sdk.onDeviceDisconnected = function (e) {
                self.showMessage("Dispositivo desconectado");
                self.notifyLaravelDeviceEvent('disconnected', e);
            };

            this.sdk.onCommunicationFailed = function (e) {
                self.showMessage("Error de comunicación");
                self.notifyLaravelDeviceEvent('communication_failed', e);
            };

            this.sdk.onSamplesAcquired = function (s) {
                // Procesar con función original
                self.sampleAcquired(s);

                // Procesar con Laravel si está en modo integrado
                if (self.laravelMode) {
                    self.processWithLaravel(s);
                }
            };

            this.sdk.onQualityReported = function (e) {
                // Función original
                const qualityInputBox = document.getElementById("qualityInputBox");
                if (qualityInputBox) {
                    qualityInputBox.value = Fingerprint.QualityCode[(e.quality)];
                }

                // Integración con Laravel
                if (window.updateQualityCallback) {
                    window.updateQualityCallback(e.quality, Fingerprint.QualityCode[(e.quality)]);
                }
            };
        }

        // Métodos originales con mejoras
        FingerprintSdkTest.prototype.startCapture = function () {
            if (this.acquisitionStarted) return;

            const _instance = this;
            self.showMessage("");
            this.operationToRestart = this.startCapture;

            this.sdk.startAcquisition(OriginalHuellero.currentFormat, OriginalHuellero.myVal).then(function () {
                _instance.acquisitionStarted = true;
                self.disableEnableStartStop();

                // Notificar a Laravel
                self.notifyLaravelCaptureEvent('started');
            }, function (error) {
                self.showMessage(error.message);
            });
        };

        FingerprintSdkTest.prototype.stopCapture = function () {
            if (!this.acquisitionStarted) return;

            const _instance = this;
            self.showMessage("");

            this.sdk.stopAcquisition().then(function () {
                _instance.acquisitionStarted = false;
                self.disableEnableStartStop();

                // Notificar a Laravel
                self.notifyLaravelCaptureEvent('stopped');
            }, function (error) {
                self.showMessage(error.message);
            });
        };

        FingerprintSdkTest.prototype.getInfo = function () {
            return this.sdk.enumerateDevices();
        };

        FingerprintSdkTest.prototype.getDeviceInfoWithID = function (uid) {
            return this.sdk.getDeviceInfo(uid);
        };

        return new FingerprintSdkTest();
    }

    /**
     * Inicializar integración Laravel
     */
    initializeLaravelIntegration() {
        this.laravelMode = false; // Modo híbrido por defecto
        this.csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

        // Detectar si estamos en páginas Laravel del huellero
        if (window.location.pathname.includes('/huellero/')) {
            this.laravelMode = true;
            this.initializeLaravelFeatures();
        }
    }

    /**
     * Inicializar características específicas de Laravel
     */
    initializeLaravelFeatures() {
        // Integrar con Laravel Fingerprint SDK si existe
        if (window.laravelFingerprintSDK) {
            this.laravelSDK = window.laravelFingerprintSDK;
        }

        // Configurar callbacks para integración
        this.setupLaravelCallbacks();
    }

    /**
     * Configurar callbacks para Laravel
     */
    setupLaravelCallbacks() {
        const self = this;

        // Callback cuando se captura muestra (compatible con original)
        window.onFingerprintCapturedOriginal = window.onFingerprintCaptured;
        window.onFingerprintCaptured = function(captureData) {
            // Ejecutar callback original si existe
            if (window.onFingerprintCapturedOriginal) {
                window.onFingerprintCapturedOriginal(captureData);
            }

            // Procesamiento Laravel adicional
            if (self.laravelMode) {
                self.handleLaravelCapture(captureData);
            }
        };
    }

    /**
     * Procesar muestra con Laravel
     */
    async processWithLaravel(samplesData) {
        if (!this.laravelMode || !this.laravelSDK) return;

        try {
            await this.laravelSDK.processSamples(samplesData);
        } catch (error) {
            console.warn('Error procesando con Laravel:', error);
        }
    }

    /**
     * Manejar captura para Laravel
     */
    handleLaravelCapture(captureData) {
        // Si estamos en modo verificación automática
        if (this.shouldAutoVerify()) {
            this.performAutoVerification(captureData);
        }
    }

    /**
     * Determinar si debe verificar automáticamente
     */
    shouldAutoVerify() {
        return window.location.pathname.includes('/huellero/verificacion') &&
               document.getElementById('verification-panel');
    }

    /**
     * Realizar verificación automática
     */
    async performAutoVerification(captureData) {
        if (!this.laravelSDK) return;

        try {
            const result = await this.laravelSDK.verifyFingerprint(captureData.template);
            if (result && window.onAccessEvent) {
                window.onAccessEvent(result);
            }
        } catch (error) {
            console.warn('Error en verificación automática:', error);
        }
    }

    /**
     * Función original sampleAcquired con mejoras
     */
    sampleAcquired(s) {
        const currentFormat = OriginalHuellero.currentFormat;

        if (currentFormat == Fingerprint.SampleFormat.PngImage) {
            localStorage.setItem("imageSrc", "");
            const samples = JSON.parse(s.samples);
            const imageData = "data:image/png;base64," + Fingerprint.b64UrlTo64(samples[0]);
            localStorage.setItem("imageSrc", imageData);

            if (this.state == document.getElementById("content-capture")) {
                const vDiv = document.getElementById('imagediv');
                if (vDiv) {
                    vDiv.innerHTML = "";
                    const image = document.createElement("img");
                    image.id = "image";
                    image.src = imageData;
                    vDiv.appendChild(image);
                }
            }

            this.disableEnableExport(false);
        }
        else if (currentFormat == Fingerprint.SampleFormat.Raw) {
            localStorage.setItem("raw", "");
            const samples = JSON.parse(s.samples);
            const sampleData = Fingerprint.b64UrlTo64(samples[0].Data);
            const decodedData = JSON.parse(Fingerprint.b64UrlToUtf8(sampleData));
            localStorage.setItem("raw", Fingerprint.b64UrlTo64(decodedData.Data));

            const vDiv = document.getElementById('imagediv');
            if (vDiv) {
                vDiv.innerHTML = '<div id="animateText" style="display:none">RAW Sample Acquired <br>'+Date()+'</div>';
                setTimeout(() => this.delayAnimate("animateText","table-cell"), 100);
            }

            this.disableEnableExport(false);
        }
        else if (currentFormat == Fingerprint.SampleFormat.Compressed) {
            localStorage.setItem("wsq", "");
            const samples = JSON.parse(s.samples);
            const sampleData = Fingerprint.b64UrlTo64(samples[0].Data);
            const decodedData = JSON.parse(Fingerprint.b64UrlToUtf8(sampleData));
            localStorage.setItem("wsq","data:application/octet-stream;base64," + Fingerprint.b64UrlTo64(decodedData.Data));

            const vDiv = document.getElementById('imagediv');
            if (vDiv) {
                vDiv.innerHTML = '<div id="animateText" style="display:none">WSQ Sample Acquired <br>'+Date()+'</div>';
                setTimeout(() => this.delayAnimate("animateText","table-cell"), 100);
            }

            this.disableEnableExport(false);
        }
        else if (currentFormat == Fingerprint.SampleFormat.Intermediate) {
            localStorage.setItem("intermediate", "");
            const samples = JSON.parse(s.samples);
            const sampleData = Fingerprint.b64UrlTo64(samples[0].Data);
            localStorage.setItem("intermediate", sampleData);

            const vDiv = document.getElementById('imagediv');
            if (vDiv) {
                vDiv.innerHTML = '<div id="animateText" style="display:none">Intermediate Sample Acquired <br>'+Date()+'</div>';
                setTimeout(() => this.delayAnimate("animateText","table-cell"), 100);
            }

            this.disableEnableExport(false);
        }
        else {
            alert("Format Error");
        }
    }

    /**
     * Funciones de utilidad originales
     */
    showMessage(message) {
        // Función original
        const x = this.state.querySelectorAll("#status");
        if (x.length != 0) {
            x[0].innerHTML = message;
        }

        // Integración Laravel
        if (this.laravelMode && window.showStatusMessage) {
            window.showStatusMessage(message, 'info');
        }
    }

    disableEnableStartStop() {
        if (OriginalHuellero.myVal != "") {
            const startBtn = document.getElementById('start');
            const stopBtn = document.getElementById('stop');

            if (startBtn && stopBtn) {
                if (this.originalTest.acquisitionStarted) {
                    startBtn.disabled = true;
                    stopBtn.disabled = false;
                } else {
                    startBtn.disabled = false;
                    stopBtn.disabled = true;
                }
            }
        }
    }

    disableEnableExport(val) {
        const exportBtn = document.getElementById('saveImagePng');
        if (exportBtn) {
            exportBtn.disabled = val;
        }
    }

    delayAnimate(id, visibility) {
        const element = document.getElementById(id);
        if (element) {
            element.style.display = visibility;
        }
    }

    /**
     * Notificar eventos a Laravel
     */
    async notifyLaravelDeviceEvent(eventType, data) {
        if (!this.laravelMode || !this.csrfToken) return;

        try {
            await fetch('/huellero/api/evento-hardware', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': this.csrfToken
                },
                body: JSON.stringify({
                    event_type: `device_${eventType}`,
                    data: data,
                    timestamp: new Date().toISOString()
                })
            });
        } catch (error) {
            console.warn('Error notificando evento de dispositivo:', error);
        }
    }

    async notifyLaravelCaptureEvent(eventType) {
        if (!this.laravelMode || !this.csrfToken) return;

        try {
            await fetch('/huellero/api/evento-captura', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': this.csrfToken
                },
                body: JSON.stringify({
                    event_type: `capture_${eventType}`,
                    device_id: OriginalHuellero.myVal,
                    format: this.getFormatName(OriginalHuellero.currentFormat),
                    timestamp: new Date().toISOString()
                })
            });
        } catch (error) {
            console.warn('Error notificando evento de captura:', error);
        }
    }

    getFormatName(format) {
        const formats = {
            [Fingerprint.SampleFormat.Raw]: 'Raw',
            [Fingerprint.SampleFormat.Intermediate]: 'Intermediate',
            [Fingerprint.SampleFormat.Compressed]: 'Compressed',
            [Fingerprint.SampleFormat.PngImage]: 'PngImage'
        };
        return formats[format] || 'Unknown';
    }
}

// Funciones globales originales mantenidas para compatibilidad
window.onStart = function() {
    if (window.huelleroIntegration && window.huelleroIntegration.originalTest) {
        window.assignFormat();
        if (OriginalHuellero.currentFormat == "") {
            alert("Please select a format.");
        } else {
            window.huelleroIntegration.originalTest.startCapture();
        }
    }
};

window.onStop = function() {
    if (window.huelleroIntegration && window.huelleroIntegration.originalTest) {
        window.huelleroIntegration.originalTest.stopCapture();
    }
};

window.onGetInfo = function() {
    if (window.huelleroIntegration && window.huelleroIntegration.originalTest) {
        const allReaders = window.huelleroIntegration.originalTest.getInfo();
        allReaders.then(function (sucessObj) {
            window.populateReaders(sucessObj);
        }, function (error) {
            window.huelleroIntegration.showMessage(error.message);
        });
    }
};

window.onClear = function() {
    const vDiv = document.getElementById('imagediv');
    if (vDiv) {
        vDiv.innerHTML = "";
    }
    localStorage.setItem("imageSrc", "");
    localStorage.setItem("wsq", "");
    localStorage.setItem("raw", "");
    localStorage.setItem("intermediate", "");

    if (window.huelleroIntegration) {
        window.huelleroIntegration.disableEnableExport(true);
    }
};

// Función para asignar formato (original)
window.assignFormat = function() {
    OriginalHuellero.currentFormat = "";
    const form = document.myForm;
    if (form) {
        for (let i = 0; i < form.elements.length; i++) {
            if (form.elements[i].checked == true) {
                if (form.elements[i].name == "Raw") {
                    OriginalHuellero.currentFormat = Fingerprint.SampleFormat.Raw;
                }
                if (form.elements[i].name == "Intermediate") {
                    OriginalHuellero.currentFormat = Fingerprint.SampleFormat.Intermediate;
                }
                if (form.elements[i].name == "Compressed") {
                    OriginalHuellero.currentFormat = Fingerprint.SampleFormat.Compressed;
                }
                if (form.elements[i].name == "PngImage") {
                    OriginalHuellero.currentFormat = Fingerprint.SampleFormat.PngImage;
                }
            }
        }
    }

    // Sincronizar con variable global
    window.currentFormat = OriginalHuellero.currentFormat;
};

// Otras funciones originales necesarias
window.populateReaders = function(readersArray) {
    const _deviceInfoTable = document.getElementById("deviceInfo");
    if (_deviceInfoTable) {
        _deviceInfoTable.innerHTML = "";
        if (readersArray.length != 0) {
            _deviceInfoTable.innerHTML += "<h4>Available Readers</h4>";
            for (let i = 0; i < readersArray.length; i++) {
                _deviceInfoTable.innerHTML +=
                "<div id='dynamicInfoDivs' align='left'>" +
                    "<div data-toggle='collapse' data-target='#" + readersArray[i] + "'>" +
                        "<img src='/img/huellero/info.png' alt='Info' height='20' width='20'> &nbsp; &nbsp;" + readersArray[i] + "</div>" +
                        "<p class='collapse' id='" + readersArray[i] + "'>" + window.onDeviceInfo(readersArray[i], readersArray[i]) + "</p>" +
                    "</div>";
            }
        }
    }
};

// Inicializar cuando se carga la página
document.addEventListener('DOMContentLoaded', function() {
    // Crear instancia global de integración
    window.huelleroIntegration = new LaravelHuelleroIntegration();

    console.log('Integración Laravel-Huellero inicializada');

    // Ejecutar inicialización original si estamos en página del huellero original
    if (document.getElementById('content-capture') || window.location.pathname.includes('/huellero/demo')) {
        localStorage.clear();
        window.readersDropDownPopulate?.(true);
        window.disableEnable?.();
        window.enableDisableScanQualityDiv?.("content-reader");
        window.disableEnableExport?.(true);
    }
});

// Exportar para uso global
window.LaravelHuelleroIntegration = LaravelHuelleroIntegration;
