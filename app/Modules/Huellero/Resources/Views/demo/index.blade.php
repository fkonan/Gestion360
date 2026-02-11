@extends('huellero::layouts.app')

@section('title', 'Demo Integración Original + Laravel')

@section('content')
<div class="container-fluid">
    <div class="row">
        <!-- Panel Original del Huellero -->
        <div class="col-md-6">
            <div class="card shadow-sm">
                <div class="card-header bg-secondary text-white">
                    <h5 class="mb-0">
                        <i class="bi bi-gear me-2"></i>
                        SDK Original - Funcionalidad Completa
                    </h5>
                </div>
                <div class="card-body">
                    <!-- Pestañas originales -->
                    <ul class="nav nav-tabs" id="originalTabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" id="reader-tab" data-bs-toggle="tab" data-bs-target="#reader-content"
                                    onclick="toggle_visibility(['content-reader','content-capture'])">
                                <i class="bi bi-hdd me-1"></i>Reader
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="capture-tab" data-bs-toggle="tab" data-bs-target="#capture-content"
                                    onclick="toggle_visibility(['content-capture','content-reader'])">
                                <i class="bi bi-fingerprint me-1"></i>Capture
                            </button>
                        </li>
                    </ul>

                    <div class="tab-content mt-3">
                        <!-- Pestaña Reader -->
                        <div id="content-reader" class="content-tab">
                            <div class="mb-3">
                                <h6>Dispositivos Disponibles</h6>
                                <div class="d-flex gap-2 mb-3">
                                    <select class="form-select" id="readersDropDown" onchange="selectChangeEvent()">
                                        <option value="">Seleccionar lector...</option>
                                    </select>
                                    <button type="button" class="btn btn-primary" onclick="onGetInfo()">
                                        <i class="bi bi-arrow-clockwise"></i>
                                    </button>
                                </div>
                                <button type="button" class="btn btn-info" id="capabilities" data-bs-toggle="modal"
                                        data-bs-target="#deviceModal" onclick="populatePopUpModal()" disabled>
                                    Ver Capacidades
                                </button>
                            </div>
                            <div id="deviceInfo" class="mt-3"></div>
                        </div>

                        <!-- Pestaña Capture -->
                        <div id="content-capture" class="content-tab d-none">
                            <!-- Formatos de captura -->
                            <form name="myForm" class="mb-3">
                                <h6>Formato de Captura</h6>
                                <div class="row">
                                    <div class="col-6">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="Raw"
                                                   onclick="checkOnly(this)" id="raw">
                                            <label class="form-check-label" for="raw">Raw</label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="Intermediate"
                                                   onclick="checkOnly(this)" id="intermediate">
                                            <label class="form-check-label" for="intermediate">Intermediate</label>
                                        </div>
                                    </div>
                                    <div class="col-6">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="Compressed"
                                                   onclick="checkOnly(this)" id="compressed">
                                            <label class="form-check-label" for="compressed">Compressed</label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="PngImage"
                                                   onclick="checkOnly(this)" id="png" checked>
                                            <label class="form-check-label" for="png">PNG Image</label>
                                        </div>
                                    </div>
                                </div>
                            </form>

                            <!-- Controles de captura -->
                            <div class="d-flex gap-2 mb-3">
                                <button type="button" class="btn btn-success" id="start" onclick="onStart()">
                                    <i class="bi bi-play-fill me-1"></i>Iniciar
                                </button>
                                <button type="button" class="btn btn-danger" id="stop" onclick="onStop()">
                                    <i class="bi bi-stop-fill me-1"></i>Detener
                                </button>
                                <button type="button" class="btn btn-warning" onclick="onClear()">
                                    <i class="bi bi-trash me-1"></i>Limpiar
                                </button>
                            </div>

                            <!-- Calidad -->
                            <div id="Scores" class="mb-3">
                                <label for="qualityInputBox" class="form-label">Calidad:</label>
                                <input type="text" class="form-control" id="qualityInputBox" readonly>
                            </div>

                            <!-- Vista previa -->
                            <div id="imagediv" class="border rounded p-3 text-center" style="min-height: 200px;">
                                <i class="bi bi-fingerprint text-muted" style="font-size: 3rem;"></i>
                                <p class="text-muted mt-2">Coloque el dedo en el lector</p>
                            </div>

                            <!-- Acciones de imagen -->
                            <div class="mt-3 d-flex gap-2">
                                <button type="button" class="btn btn-primary" id="saveImagePng"
                                        onclick="onImageDownload()" disabled>
                                    <i class="bi bi-download me-1"></i>Descargar
                                </button>
                                <button type="button" class="btn btn-info" id="save" disabled>
                                    <i class="bi bi-save me-1"></i>Guardar en Galería
                                </button>
                            </div>

                            <!-- Estado -->
                            <div id="status" class="alert alert-info mt-3" style="display: none;"></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Galería de imágenes -->
            <div class="card shadow-sm mt-3">
                <div class="card-header">
                    <h6 class="mb-0">
                        <i class="bi bi-images me-2"></i>
                        Galería de Huellas
                    </h6>
                </div>
                <div class="card-body">
                    <div id="imageGallery" class="d-flex flex-wrap gap-2">
                        <!-- Las imágenes se añaden dinámicamente -->
                    </div>
                </div>
            </div>
        </div>

        <!-- Panel de Integración Laravel -->
        <div class="col-md-6">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">
                        <i class="bi bi-layers me-2"></i>
                        Integración Laravel - Funcionalidad Extendida
                    </h5>
                </div>
                <div class="card-body">
                    <!-- Estado de integración -->
                    <div class="alert alert-info">
                        <h6><i class="bi bi-info-circle me-2"></i>Estado de Integración</h6>
                        <ul class="mb-0">
                            <li id="sdk-status">SDK Original: <span class="text-muted">Verificando...</span></li>
                            <li id="laravel-status">Laravel SDK: <span class="text-muted">Verificando...</span></li>
                            <li id="bridge-status">Bridge: <span class="text-muted">Verificando...</span></li>
                        </ul>
                    </div>

                    <!-- Modo de operación -->
                    <div class="mb-4">
                        <h6>Modo de Operación</h6>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="operation-mode" id="mode-demo" value="demo" checked>
                            <label class="form-check-label" for="mode-demo">
                                <strong>Modo Demo</strong> - Solo captura y visualización
                            </label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="operation-mode" id="mode-register" value="register">
                            <label class="form-check-label" for="mode-register">
                                <strong>Modo Registro</strong> - Captura y registro en base de datos
                            </label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="operation-mode" id="mode-verify" value="verify">
                            <label class="form-check-label" for="mode-verify">
                                <strong>Modo Verificación</strong> - Verificación automática
                            </label>
                        </div>
                    </div>

                    <!-- Panel de registro -->
                    <div id="register-panel" class="d-none">
                        <h6>Registro de Huella</h6>
                        <div class="mb-3">
                            <label for="persona-codigo" class="form-label">Código de Persona</label>
                            <input type="text" class="form-control" id="persona-codigo" placeholder="Ingrese código...">
                        </div>
                        <div class="mb-3">
                            <label for="dedo-select" class="form-label">Dedo</label>
                            <select class="form-select" id="dedo-select">
                                <option value="">Seleccionar dedo...</option>
                                <option value="01">Pulgar derecho</option>
                                <option value="02">Índice derecho</option>
                                <option value="03">Medio derecho</option>
                                <option value="04">Anular derecho</option>
                                <option value="05">Meñique derecho</option>
                                <option value="06">Pulgar izquierdo</option>
                                <option value="07">Índice izquierdo</option>
                                <option value="08">Medio izquierdo</option>
                                <option value="09">Anular izquierdo</option>
                                <option value="10">Meñique izquierdo</option>
                            </select>
                        </div>
                        <button type="button" class="btn btn-success" id="register-fingerprint" disabled>
                            <i class="bi bi-save me-1"></i>Registrar en Base de Datos
                        </button>
                    </div>

                    <!-- Panel de verificación -->
                    <div id="verify-panel" class="d-none">
                        <h6>Verificación Automática</h6>
                        <div class="alert alert-success" id="verify-result" style="display: none;"></div>
                        <div class="alert alert-warning" id="verify-no-match" style="display: none;"></div>
                        <button type="button" class="btn btn-primary" id="start-auto-verify" disabled>
                            <i class="bi bi-shield-check me-1"></i>Iniciar Verificación Automática
                        </button>
                    </div>

                    <!-- Log de eventos -->
                    <div class="mt-4">
                        <h6>Log de Eventos</h6>
                        <div id="events-log" class="border rounded p-2" style="height: 200px; overflow-y: auto; font-family: monospace; font-size: 0.8rem;">
                            <div class="text-muted">Sistema iniciado...</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal de información del dispositivo -->
<div class="modal fade" id="deviceModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Información del Dispositivo</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="ReaderInformationFromDropDown"></div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
    .content-tab {
        min-height: 400px;
    }

    #imageGallery img {
        max-width: 80px;
        max-height: 80px;
        object-fit: cover;
        border: 2px solid #dee2e6;
        border-radius: 5px;
    }

    #events-log {
        background: #f8f9fa;
        border: 1px solid #dee2e6;
    }

    .log-entry {
        margin-bottom: 5px;
        padding: 2px 5px;
    }

    .log-entry.info { color: #0066cc; }
    .log-entry.success { color: #28a745; }
    .log-entry.warning { color: #ffc107; }
    .log-entry.error { color: #dc3545; }
</style>
@endpush

@push('scripts')
<script>
    // Variables globales para el demo
    let currentOperationMode = 'demo';
    let autoVerifyActive = false;
    let lastCapturedData = null;

    // Inicializar demo
    document.addEventListener('DOMContentLoaded', function() {
        initializeDemo();
        setupModeHandlers();
        checkIntegrationStatus();
    });

    // Inicializar demo
    function initializeDemo() {
        logEvent('info', 'Inicializando demo de integración...');

        // Configurar callbacks originales
        setupOriginalCallbacks();

        // Configurar callbacks Laravel
        setupLaravelCallbacks();

        logEvent('success', 'Demo inicializado correctamente');
    }

    // Configurar manejadores de modo
    function setupModeHandlers() {
        document.querySelectorAll('input[name="operation-mode"]').forEach(radio => {
            radio.addEventListener('change', function() {
                currentOperationMode = this.value;
                updateModeDisplay();
                logEvent('info', `Modo cambiado a: ${this.value}`);
            });
        });

        // Botón de registro
        document.getElementById('register-fingerprint').addEventListener('click', registerFingerprint);

        // Botón de verificación automática
        document.getElementById('start-auto-verify').addEventListener('click', toggleAutoVerify);
    }

    // Actualizar display según modo
    function updateModeDisplay() {
        document.getElementById('register-panel').classList.toggle('d-none', currentOperationMode !== 'register');
        document.getElementById('verify-panel').classList.toggle('d-none', currentOperationMode !== 'verify');
    }

    // Configurar callbacks originales
    function setupOriginalCallbacks() {
        // Override de sampleAcquired para demo
        if (window.huelleroIntegration) {
            const originalSampleAcquired = window.huelleroIntegration.sampleAcquired.bind(window.huelleroIntegration);

            window.huelleroIntegration.sampleAcquired = function(s) {
                // Ejecutar función original
                originalSampleAcquired(s);

                // Procesamiento adicional para demo
                processSampleForDemo(s);
            };
        }
    }

    // Configurar callbacks Laravel
    function setupLaravelCallbacks() {
        // Callback cuando se captura huella
        window.onFingerprintCaptured = function(captureData) {
            lastCapturedData = captureData;
            logEvent('success', 'Huella capturada - Datos disponibles para procesamiento');

            // Habilitar botones según modo
            if (currentOperationMode === 'register') {
                document.getElementById('register-fingerprint').disabled = false;
            }

            // Procesar automáticamente si está en modo verificación
            if (currentOperationMode === 'verify' && autoVerifyActive) {
                performAutoVerification(captureData);
            }
        };

        // Callback de calidad
        window.updateQualityCallback = function(quality, qualityText) {
            logEvent('info', `Calidad reportada: ${quality}% (${qualityText})`);
        };
    }

    // Procesar muestra para demo
    function processSampleForDemo(samplesData) {
        logEvent('info', 'Procesando muestra capturada...');

        try {
            const samples = JSON.parse(samplesData.samples);
            const format = getCurrentFormatName();

            logEvent('success', `Muestra procesada - Formato: ${format}, Muestras: ${samples.length}`);

            // Simular procesamiento Laravel
            const mockCaptureData = {
                format: format,
                template: samples[0]?.Data || samples[0],
                quality: Math.floor(Math.random() * 40) + 60, // Simular calidad 60-100%
                timestamp: new Date().toISOString()
            };

            // Disparar callback
            if (window.onFingerprintCaptured) {
                window.onFingerprintCaptured(mockCaptureData);
            }

        } catch (error) {
            logEvent('error', `Error procesando muestra: ${error.message}`);
        }
    }

    // Registrar huella en base de datos
    async function registerFingerprint() {
        if (!lastCapturedData) {
            logEvent('error', 'No hay datos de huella para registrar');
            return;
        }

        const personaCodigo = document.getElementById('persona-codigo').value;
        const dedo = document.getElementById('dedo-select').value;

        if (!personaCodigo || !dedo) {
            logEvent('error', 'Debe especificar código de persona y dedo');
            return;
        }

        try {
            logEvent('info', 'Registrando huella en base de datos...');

            const response = await fetch('/huellero/huellas/registrar', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                },
                body: JSON.stringify({
                    persona_codigo: personaCodigo,
                    dedo: dedo,
                    template_huella: lastCapturedData.template,
                    calidad: lastCapturedData.quality || 0,
                    formato: lastCapturedData.format
                })
            });

            const data = await response.json();

            if (data.success) {
                logEvent('success', `Huella registrada exitosamente para persona ${personaCodigo}`);

                // Limpiar formulario
                document.getElementById('persona-codigo').value = '';
                document.getElementById('dedo-select').value = '';
                document.getElementById('register-fingerprint').disabled = true;
                lastCapturedData = null;
            } else {
                throw new Error(data.message);
            }

        } catch (error) {
            logEvent('error', `Error registrando huella: ${error.message}`);
        }
    }

    // Alternar verificación automática
    function toggleAutoVerify() {
        autoVerifyActive = !autoVerifyActive;

        const button = document.getElementById('start-auto-verify');
        if (autoVerifyActive) {
            button.textContent = 'Detener Verificación';
            button.className = 'btn btn-danger';
            logEvent('info', 'Verificación automática activada');
        } else {
            button.innerHTML = '<i class="bi bi-shield-check me-1"></i>Iniciar Verificación Automática';
            button.className = 'btn btn-primary';
            logEvent('info', 'Verificación automática desactivada');
        }
    }

    // Realizar verificación automática
    async function performAutoVerification(captureData) {
        try {
            logEvent('info', 'Verificando huella contra base de datos...');

            const response = await fetch('/huellero/verificacion/verificar-huella', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                },
                body: JSON.stringify({
                    template_huella: captureData.template,
                    tipo_evento: 'automatico'
                })
            });

            const data = await response.json();

            if (data.success) {
                const persona = data.persona;
                const evento = data.evento;

                logEvent('success', `Persona verificada: ${persona.nombre} - Evento: ${evento.tipo}`);

                document.getElementById('verify-result').innerHTML = `
                    <strong>Verificación Exitosa</strong><br>
                    Persona: ${persona.nombre}<br>
                    Evento: ${evento.tipo}<br>
                    Hora: ${new Date().toLocaleTimeString()}
                `;
                document.getElementById('verify-result').style.display = 'block';
                document.getElementById('verify-no-match').style.display = 'none';

            } else {
                logEvent('warning', 'Huella no reconocida en la base de datos');

                document.getElementById('verify-no-match').innerHTML = `
                    <strong>Huella No Reconocida</strong><br>
                    La huella no coincide con ningún registro en la base de datos.
                `;
                document.getElementById('verify-no-match').style.display = 'block';
                document.getElementById('verify-result').style.display = 'none';
            }

        } catch (error) {
            logEvent('error', `Error en verificación: ${error.message}`);
        }
    }

    // Verificar estado de integración
    function checkIntegrationStatus() {
        // Verificar SDK original
        if (window.test && window.huelleroIntegration) {
            document.getElementById('sdk-status').innerHTML = 'SDK Original: <span class="text-success">Activo</span>';
        } else {
            document.getElementById('sdk-status').innerHTML = 'SDK Original: <span class="text-danger">No disponible</span>';
        }

        // Verificar Laravel SDK
        if (window.laravelFingerprintSDK) {
            document.getElementById('laravel-status').innerHTML = 'Laravel SDK: <span class="text-success">Activo</span>';
        } else {
            document.getElementById('laravel-status').innerHTML = 'Laravel SDK: <span class="text-warning">No disponible</span>';
        }

        // Verificar Bridge
        if (window.LaravelHuelleroIntegration) {
            document.getElementById('bridge-status').innerHTML = 'Bridge: <span class="text-success">Activo</span>';
        } else {
            document.getElementById('bridge-status').innerHTML = 'Bridge: <span class="text-danger">No disponible</span>';
        }
    }

    // Obtener nombre del formato actual
    function getCurrentFormatName() {
        if (window.currentFormat === Fingerprint.SampleFormat.Raw) return 'Raw';
        if (window.currentFormat === Fingerprint.SampleFormat.Intermediate) return 'Intermediate';
        if (window.currentFormat === Fingerprint.SampleFormat.Compressed) return 'Compressed';
        if (window.currentFormat === Fingerprint.SampleFormat.PngImage) return 'PngImage';
        return 'Unknown';
    }

    // Log de eventos
    function logEvent(type, message) {
        const logContainer = document.getElementById('events-log');
        const timestamp = new Date().toLocaleTimeString();

        const logEntry = document.createElement('div');
        logEntry.className = `log-entry ${type}`;
        logEntry.innerHTML = `[${timestamp}] ${message}`;

        logContainer.appendChild(logEntry);
        logContainer.scrollTop = logContainer.scrollHeight;
    }

    // Funciones originales requeridas
    window.toggle_visibility = function(ids) {
        document.getElementById("qualityInputBox").value = "";
        window.onStop();

        for (let i = 0; i < ids.length; i++) {
            const e = document.getElementById(ids[i]);
            if (i == 0) {
                e.style.display = 'block';
                e.classList.remove('d-none');
                if (window.huelleroIntegration) {
                    window.huelleroIntegration.state = e;
                }
            } else {
                e.style.display = 'none';
                e.classList.add('d-none');
            }
        }

        logEvent('info', `Vista cambiada a: ${ids[0]}`);
    };

    window.selectChangeEvent = function() {
        const readersDropDown = document.getElementById("readersDropDown");
        window.myVal = readersDropDown.options[readersDropDown.selectedIndex].value;

        if (window.myVal) {
            logEvent('info', `Dispositivo seleccionado: ${window.myVal}`);
        }

        // Habilitar botones de verificación si hay dispositivo
        if (window.myVal && currentOperationMode === 'verify') {
            document.getElementById('start-auto-verify').disabled = false;
        }
    };

    window.populatePopUpModal = function() {
        const modal = document.getElementById("ReaderInformationFromDropDown");
        if (window.myVal) {
            window.onDeviceInfo(window.myVal, "ReaderInformationFromDropDown");
        } else {
            modal.innerHTML = "Por favor seleccione un lector";
        }
    };

    window.checkOnly = function(stayChecked) {
        const form = document.myForm;
        if (form) {
            for (let i = 0; i < form.elements.length; i++) {
                if (form.elements[i].checked == true && form.elements[i].name != stayChecked.name) {
                    form.elements[i].checked = false;
                }
            }
        }

        logEvent('info', `Formato seleccionado: ${stayChecked.name}`);
    };

    window.onDeviceInfo = function(id, element) {
        if (window.huelleroIntegration && window.huelleroIntegration.originalTest) {
            const deviceInfo = window.huelleroIntegration.originalTest.getDeviceInfoWithID(id);
            deviceInfo.then(function (result) {
                const deviceTech = window.huelleroIntegration.originalTest.deviceTechn || OriginalHuellero.deviceTechn;
                const deviceModality = window.huelleroIntegration.originalTest.deviceModality || OriginalHuellero.deviceModality;
                const deviceUidType = window.huelleroIntegration.originalTest.deviceUidType || OriginalHuellero.deviceUidType;

                const info = "Id: " + result.DeviceID +
                    "<br>Uid Type: " + (deviceUidType[result.eUidType] || 'Unknown') +
                    "<br>Device Tech: " + (deviceTech[result.eDeviceTech] || 'Unknown') +
                    "<br>Device Modality: " + (deviceModality[result.eDeviceModality] || 'Unknown');

                document.getElementById(element).innerHTML = info;
            });
        }
    };
</script>
@endpush
