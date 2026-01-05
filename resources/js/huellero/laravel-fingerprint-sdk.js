/**
 * Adaptación del JavaScript original del huellero para Laravel
 * Integra el SDK Digital Persona con los endpoints Laravel
 */

// Variables globales del SDK
var fingerprintSdkTest = null;
var currentFormat = Fingerprint.SampleFormat.Intermediate;
var isCapturing = false;
var selectedDevice = null;

// Configuración de dispositivos
var deviceTechnology = {
    0: "Unknown",
    1: "Optical",
    2: "Capacitive",
    3: "Thermal",
    4: "Pressure"
};

var deviceModality = {
    0: "Unknown",
    1: "Swipe",
    2: "Area",
    3: "AreaMultifinger"
};

var deviceUidType = {
    0: "Persistent",
    1: "Volatile"
};

/**
 * Clase principal para integración con Laravel
 */
class LaravelFingerprintSDK {
    constructor() {
        this.sdk = new Fingerprint.WebApi();
        this.setupEventHandlers();
        this.csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    }

    setupEventHandlers() {
        const self = this;

        // Dispositivo conectado
        this.sdk.onDeviceConnected = function(e) {
            self.onDeviceConnected(e);
        };

        // Dispositivo desconectado
        this.sdk.onDeviceDisconnected = function(e) {
            self.onDeviceDisconnected(e);
        };

        // Fallo en comunicación
        this.sdk.onCommunicationFailed = function(e) {
            self.onCommunicationFailed(e);
        };

        // Muestras adquiridas
        this.sdk.onSamplesAcquired = function(s) {
            self.onSamplesAcquired(s);
        };

        // Calidad reportada
        this.sdk.onQualityReported = function(e) {
            self.onQualityReported(e);
        };
    }

    onDeviceConnected(e) {
        console.log('Dispositivo conectado:', e);
        this.notifyLaravel('device_connected', e);
        this.showMessage("Dispositivo conectado - Listo para escanear");
    }

    onDeviceDisconnected(e) {
        console.log('Dispositivo desconectado:', e);
        this.notifyLaravel('device_disconnected', e);
        this.showMessage("Dispositivo desconectado");
    }

    onCommunicationFailed(e) {
        console.log('Fallo de comunicación:', e);
        this.showMessage("Error de comunicación con el SDK", 'error');
    }

    onSamplesAcquired(samples) {
        console.log('Muestras adquiridas:', samples);
        this.processSamples(samples);
    }

    onQualityReported(qualityEvent) {
        const quality = qualityEvent.quality;
        const qualityText = Fingerprint.QualityCode[quality];

        // Actualizar UI
        this.updateQualityDisplay(quality, qualityText);

        // Notificar a Laravel si es necesario
        if (window.updateQualityCallback) {
            window.updateQualityCallback(quality, qualityText);
        }
    }

    /**
     * Iniciar captura de huella
     */
    async startCapture(deviceId, format = 'Intermediate') {
        try {
            selectedDevice = deviceId;
            currentFormat = Fingerprint.SampleFormat[format];

            // Notificar a Laravel que se inicia captura
            const response = await this.makeRequest('/huellero/captura/iniciar', {
                device_id: deviceId,
                format: format
            });

            if (response.success) {
                await this.sdk.startAcquisition(currentFormat, deviceId);
                isCapturing = true;
                this.showMessage("Captura iniciada - Coloque el dedo en el lector");
                return true;
            } else {
                throw new Error(response.message);
            }
        } catch (error) {
            this.showMessage("Error al iniciar captura: " + error.message, 'error');
            return false;
        }
    }

    /**
     * Detener captura de huella
     */
    async stopCapture() {
        try {
            await this.sdk.stopAcquisition();
            isCapturing = false;

            // Notificar a Laravel
            await this.makeRequest('/huellero/captura/detener', {
                device_id: selectedDevice
            });

            this.showMessage("Captura detenida");
            return true;
        } catch (error) {
            this.showMessage("Error al detener captura: " + error.message, 'error');
            return false;
        }
    }

    /**
     * Procesar muestras capturadas
     */
    async processSamples(samplesData) {
        try {
            // Preparar datos para Laravel
            const processData = {
                samples: samplesData.samples,
                quality: samplesData.quality || 0,
                format: this.getFormatName(currentFormat)
            };

            // Enviar a Laravel para procesamiento
            const response = await this.makeRequest('/huellero/captura/procesar', processData);

            if (response.success) {
                const processedData = response.data;

                // Actualizar UI según el tipo de formato
                this.updateCaptureDisplay(processedData);

                // Callback para la página si existe
                if (window.onFingerprintCaptured) {
                    window.onFingerprintCaptured(processedData);
                }

                return processedData;
            } else {
                throw new Error(response.message);
            }
        } catch (error) {
            this.showMessage("Error procesando muestra: " + error.message, 'error');
            return null;
        }
    }

    /**
     * Verificar huella contra base de datos
     */
    async verifyFingerprint(templateData) {
        try {
            const response = await this.makeRequest('/huellero/verificacion/verificar-huella', {
                template_huella: templateData
            });

            if (response.success) {
                // Registrar evento automático
                this.notifyAccessEvent(response.data);
                return response.data;
            } else {
                this.showMessage("Huella no reconocida", 'warning');
                return null;
            }
        } catch (error) {
            this.showMessage("Error en verificación: " + error.message, 'error');
            return null;
        }
    }

    /**
     * Obtener dispositivos conectados
     */
    async getDevices() {
        try {
            const devices = await this.sdk.enumerateDevices();
            return devices;
        } catch (error) {
            this.showMessage("Error obteniendo dispositivos: " + error.message, 'error');
            return [];
        }
    }

    /**
     * Obtener información de dispositivo
     */
    async getDeviceInfo(deviceId) {
        try {
            const info = await this.sdk.getDeviceInfo(deviceId);
            return {
                deviceId: info.DeviceID,
                uidType: deviceUidType[info.eUidType],
                modality: deviceModality[info.eDeviceModality],
                technology: deviceTechnology[info.eDeviceTech]
            };
        } catch (error) {
            this.showMessage("Error obteniendo info del dispositivo: " + error.message, 'error');
            return null;
        }
    }

    /**
     * Actualizar display de captura según formato
     */
    updateCaptureDisplay(processedData) {
        const previewContainer = document.getElementById('fingerprint-preview');
        if (!previewContainer) return;

        switch (processedData.format) {
            case 'PngImage':
                if (processedData.image_data) {
                    previewContainer.innerHTML = `<img src="${processedData.image_data}" alt="Huella capturada" style="max-width: 100%; max-height: 100%;">`;
                }
                break;

            case 'Raw':
            case 'Intermediate':
            case 'Compressed':
                previewContainer.innerHTML = `
                    <div class="text-center text-success">
                        <i class="bi bi-check-circle" style="font-size: 4rem;"></i>
                        <p class="mt-2">Huella capturada exitosamente</p>
                        <small>Formato: ${processedData.format}</small>
                    </div>
                `;
                break;
        }
    }

    /**
     * Actualizar display de calidad
     */
    updateQualityDisplay(quality, qualityText) {
        // Actualizar barra de progreso si existe
        const qualityBar = document.getElementById('quality-bar');
        const qualityPercentage = document.getElementById('quality-percentage');

        if (qualityBar && qualityPercentage) {
            qualityPercentage.textContent = quality + '%';
            qualityBar.style.width = quality + '%';

            // Cambiar color según calidad
            qualityBar.className = 'quality-bar';
            if (quality >= 80) qualityBar.classList.add('quality-excellent');
            else if (quality >= 60) qualityBar.classList.add('quality-good');
            else if (quality >= 40) qualityBar.classList.add('quality-fair');
            else qualityBar.classList.add('quality-poor');
        }

        // Actualizar input de calidad si existe
        const qualityInput = document.getElementById('qualityInputBox');
        if (qualityInput) {
            qualityInput.value = qualityText;
        }
    }

    /**
     * Mostrar mensaje en UI
     */
    showMessage(message, type = 'info') {
        console.log(`${type.toUpperCase()}: ${message}`);

        // Actualizar elemento de estado si existe
        const statusElement = document.getElementById('capture-status');
        if (statusElement) {
            statusElement.textContent = message;
            statusElement.className = `capture-status status-${type === 'error' ? 'error' : type === 'warning' ? 'warning' : type === 'success' ? 'success' : 'info'}`;
            statusElement.classList.remove('d-none');
        }

        // Callback global si existe
        if (window.showStatusMessage) {
            window.showStatusMessage(message, type);
        }
    }

    /**
     * Notificar evento de acceso
     */
    notifyAccessEvent(accessData) {
        if (window.onAccessEvent) {
            window.onAccessEvent(accessData);
        }

        // Mostrar notificación de acceso
        const persona = accessData.persona;
        const evento = accessData.evento;

        this.showMessage(`Acceso registrado: ${evento.tipo} - ${persona.nombre}`, 'success');
    }

    /**
     * Notificar eventos a Laravel (opcional)
     */
    async notifyLaravel(eventType, data) {
        try {
            await this.makeRequest('/huellero/api/evento-hardware', {
                event_type: eventType,
                data: data,
                timestamp: new Date().toISOString()
            });
        } catch (error) {
            // Error silencioso - no crítico
            console.warn('Error notificando evento a Laravel:', error);
        }
    }

    /**
     * Realizar petición HTTP a Laravel
     */
    async makeRequest(url, data = {}) {
        const response = await fetch(url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': this.csrfToken,
                'Accept': 'application/json'
            },
            body: JSON.stringify(data)
        });

        if (!response.ok) {
            throw new Error(`HTTP ${response.status}: ${response.statusText}`);
        }

        return await response.json();
    }

    /**
     * Obtener nombre del formato
     */
    getFormatName(format) {
        const formats = {
            [Fingerprint.SampleFormat.Raw]: 'Raw',
            [Fingerprint.SampleFormat.Intermediate]: 'Intermediate',
            [Fingerprint.SampleFormat.Compressed]: 'Compressed',
            [Fingerprint.SampleFormat.PngImage]: 'PngImage'
        };
        return formats[format] || 'Unknown';
    }

    /**
     * Limpiar captura
     */
    clearCapture() {
        const previewContainer = document.getElementById('fingerprint-preview');
        if (previewContainer) {
            previewContainer.innerHTML = `
                <div class="text-center text-muted">
                    <i class="bi bi-fingerprint" style="font-size: 4rem;"></i>
                    <p class="mt-2">Coloque el dedo en el lector</p>
                </div>
            `;
        }

        this.updateQualityDisplay(0, 'N/A');

        const statusElement = document.getElementById('capture-status');
        if (statusElement) {
            statusElement.classList.add('d-none');
        }
    }
}

// Inicializar SDK cuando se carga la página
document.addEventListener('DOMContentLoaded', function() {
    // Solo inicializar si estamos en páginas del huellero
    if (window.location.pathname.includes('/huellero/')) {
        window.laravelFingerprintSDK = new LaravelFingerprintSDK();

        console.log('Laravel Fingerprint SDK inicializado');

        // Hacer disponible globalmente para compatibilidad
        window.fingerprintSDK = window.laravelFingerprintSDK;
    }
});

// Funciones de compatibilidad con el código original
function onStart() {
    if (window.laravelFingerprintSDK && selectedDevice) {
        const format = document.getElementById('formato-captura')?.value || 'Intermediate';
        window.laravelFingerprintSDK.startCapture(selectedDevice, format);
    }
}

function onStop() {
    if (window.laravelFingerprintSDK) {
        window.laravelFingerprintSDK.stopCapture();
    }
}

function onClear() {
    if (window.laravelFingerprintSDK) {
        window.laravelFingerprintSDK.clearCapture();
    }
}

function onGetInfo() {
    if (window.laravelFingerprintSDK) {
        window.laravelFingerprintSDK.getDevices().then(devices => {
            console.log('Dispositivos encontrados:', devices);
            if (window.populateDevices) {
                window.populateDevices(devices);
            }
        });
    }
}

// Exportar para uso global
window.LaravelFingerprintSDK = LaravelFingerprintSDK;
