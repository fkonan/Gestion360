/**
 * Funciones específicas para verificación de huellas y control de acceso
 */

// Variables de verificación
let verificationMode = 'access'; // 'access' o 'search'
let isVerifying = false;
let verificationTimeout = null;
let accessLogInterval = null;

/**
 * Inicializar página de verificación
 */
document.addEventListener('DOMContentLoaded', function() {
    if (document.getElementById('verification-panel')) {
        initVerificationPage();
    }
});

function initVerificationPage() {
    setupVerificationControls();
    setupModeSelection();
    setupAccessLog();

    // Cargar dispositivos
    loadVerificationDevices();

    // Iniciar monitoreo de acceso
    startAccessMonitoring();
}

/**
 * Configurar controles de verificación
 */
function setupVerificationControls() {
    const startBtn = document.getElementById('start-verification-btn');
    const stopBtn = document.getElementById('stop-verification-btn');
    const refreshBtn = document.getElementById('refresh-verification-devices');

    if (startBtn) {
        startBtn.addEventListener('click', startVerification);
    }

    if (stopBtn) {
        stopBtn.addEventListener('click', stopVerification);
    }

    if (refreshBtn) {
        refreshBtn.addEventListener('click', loadVerificationDevices);
    }
}

/**
 * Configurar selección de modo
 */
function setupModeSelection() {
    const modeRadios = document.querySelectorAll('input[name="verification-mode"]');

    modeRadios.forEach(radio => {
        radio.addEventListener('change', () => {
            verificationMode = radio.value;
            updateModeDisplay();
        });
    });

    updateModeDisplay();
}

/**
 * Actualizar display según modo
 */
function updateModeDisplay() {
    const accessMode = document.getElementById('access-mode-info');
    const searchMode = document.getElementById('search-mode-info');

    if (verificationMode === 'access') {
        accessMode?.classList.remove('d-none');
        searchMode?.classList.add('d-none');
    } else {
        accessMode?.classList.add('d-none');
        searchMode?.classList.remove('d-none');
    }
}

/**
 * Cargar dispositivos para verificación
 */
async function loadVerificationDevices() {
    try {
        showLoading('refresh-verification-devices');

        if (!window.laravelFingerprintSDK) {
            throw new Error('SDK no inicializado');
        }

        const devices = await window.laravelFingerprintSDK.getDevices();
        displayVerificationDevices(devices);

    } catch (error) {
        showAlert('Error cargando dispositivos: ' + error.message, 'error');
        displayVerificationDevices([]);
    } finally {
        hideLoading('refresh-verification-devices');
    }
}

/**
 * Mostrar dispositivos para verificación
 */
function displayVerificationDevices(devices) {
    const container = document.getElementById('verification-devices-list');

    if (devices.length === 0) {
        container.innerHTML = `
            <div class="alert alert-warning">
                <i class="bi bi-exclamation-triangle me-2"></i>
                No se encontraron dispositivos conectados.
            </div>
        `;
        return;
    }

    let html = '';
    devices.forEach((device, index) => {
        html += `
            <div class="form-check mb-2">
                <input class="form-check-input" type="radio" name="verification-device" id="vdevice-${index}" value="${device.DeviceID}">
                <label class="form-check-label" for="vdevice-${index}">
                    <i class="bi bi-fingerprint text-primary me-2"></i>
                    ${device.DeviceID}
                </label>
            </div>
        `;
    });

    container.innerHTML = html;
}

/**
 * Iniciar verificación
 */
async function startVerification() {
    const selectedDevice = document.querySelector('input[name="verification-device"]:checked')?.value;

    if (!selectedDevice) {
        showAlert('Por favor seleccione un dispositivo', 'warning');
        return;
    }

    try {
        isVerifying = true;

        // Actualizar UI
        document.getElementById('start-verification-btn').disabled = true;
        document.getElementById('stop-verification-btn').disabled = false;

        updateVerificationStatus('info', 'Iniciando verificación...');

        // Configurar callback de verificación
        setupVerificationCallback();

        // Iniciar captura
        const success = await window.laravelFingerprintSDK.startCapture(selectedDevice, 'Intermediate');

        if (success) {
            updateVerificationStatus('success', 'Listo para verificar - Coloque el dedo en el lector');

            // Establecer timeout de verificación
            startVerificationTimeout();
        } else {
            throw new Error('No se pudo iniciar la verificación');
        }

    } catch (error) {
        updateVerificationStatus('error', 'Error iniciando verificación: ' + error.message);
        resetVerificationButtons();
        isVerifying = false;
    }
}

/**
 * Configurar callback de verificación
 */
function setupVerificationCallback() {
    window.onFingerprintCaptured = async function(captureData) {
        if (!isVerifying) return;

        try {
            updateVerificationStatus('info', 'Procesando huella...');

            // Verificar huella según modo
            if (verificationMode === 'access') {
                await processAccessVerification(captureData);
            } else {
                await processSearchVerification(captureData);
            }

        } catch (error) {
            updateVerificationStatus('error', 'Error en verificación: ' + error.message);
        }

        // Reiniciar timeout
        restartVerificationTimeout();
    };
}

/**
 * Procesar verificación de acceso
 */
async function processAccessVerification(captureData) {
    try {
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

            // Mostrar resultado exitoso
            displayAccessResult(persona, evento, 'success');

            // Actualizar log en tiempo real
            if (accessLogInterval) {
                loadRecentAccess();
            }

            updateVerificationStatus('success', `Acceso autorizado - ${persona.nombre}`);

        } else {
            displayAccessResult(null, null, 'denied');
            updateVerificationStatus('warning', 'Huella no reconocida - Intente nuevamente');
        }

    } catch (error) {
        displayAccessResult(null, null, 'error');
        updateVerificationStatus('error', 'Error en verificación: ' + error.message);
    }
}

/**
 * Procesar búsqueda por huella
 */
async function processSearchVerification(captureData) {
    try {
        const response = await fetch('/huellero/verificacion/buscar-persona', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            },
            body: JSON.stringify({
                template_huella: captureData.template
            })
        });

        const data = await response.json();

        if (data.success) {
            displaySearchResult(data.persona, data.huellas_coincidentes || []);
            updateVerificationStatus('success', `Persona encontrada - ${data.persona.nombre}`);
        } else {
            displaySearchResult(null, []);
            updateVerificationStatus('warning', 'No se encontró ninguna persona con esa huella');
        }

    } catch (error) {
        displaySearchResult(null, []);
        updateVerificationStatus('error', 'Error en búsqueda: ' + error.message);
    }
}

/**
 * Mostrar resultado de acceso
 */
function displayAccessResult(persona, evento, status) {
    const container = document.getElementById('access-result');

    let html = '';
    let alertClass = '';
    let icon = '';

    switch (status) {
        case 'success':
            alertClass = 'alert-success';
            icon = 'bi-check-circle-fill';
            html = `
                <div class="d-flex align-items-center">
                    <i class="bi ${icon} me-3" style="font-size: 2rem;"></i>
                    <div>
                        <h5 class="mb-1">Acceso Autorizado</h5>
                        <p class="mb-1"><strong>${persona.nombre}</strong></p>
                        <small class="text-muted">
                            ${persona.cargo || 'Sin cargo'} |
                            ${evento.tipo.toUpperCase()} |
                            ${new Date().toLocaleString()}
                        </small>
                    </div>
                </div>
            `;
            break;

        case 'denied':
            alertClass = 'alert-warning';
            icon = 'bi-x-circle-fill';
            html = `
                <div class="d-flex align-items-center">
                    <i class="bi ${icon} me-3" style="font-size: 2rem;"></i>
                    <div>
                        <h5 class="mb-1">Acceso Denegado</h5>
                        <p class="mb-0">Huella no reconocida</p>
                        <small class="text-muted">${new Date().toLocaleString()}</small>
                    </div>
                </div>
            `;
            break;

        case 'error':
            alertClass = 'alert-danger';
            icon = 'bi-exclamation-triangle-fill';
            html = `
                <div class="d-flex align-items-center">
                    <i class="bi ${icon} me-3" style="font-size: 2rem;"></i>
                    <div>
                        <h5 class="mb-1">Error de Sistema</h5>
                        <p class="mb-0">No se pudo procesar la verificación</p>
                        <small class="text-muted">${new Date().toLocaleString()}</small>
                    </div>
                </div>
            `;
            break;
    }

    container.innerHTML = `<div class="alert ${alertClass}">${html}</div>`;

    // Auto-limpiar después de 5 segundos
    setTimeout(() => {
        container.innerHTML = '';
    }, 5000);
}

/**
 * Mostrar resultado de búsqueda
 */
function displaySearchResult(persona, huellas) {
    const container = document.getElementById('search-result');

    if (!persona) {
        container.innerHTML = `
            <div class="alert alert-warning">
                <i class="bi bi-search me-2"></i>
                No se encontró ninguna persona con esa huella.
            </div>
        `;
        return;
    }

    let huellasInfo = '';
    if (huellas.length > 0) {
        huellasInfo = huellas.map(h => `${h.dedo} (${h.calidad}%)`).join(', ');
    }

    container.innerHTML = `
        <div class="alert alert-success">
            <div class="row">
                <div class="col-md-8">
                    <h5 class="mb-2">
                        <i class="bi bi-person-check me-2"></i>
                        Persona Encontrada
                    </h5>
                    <p class="mb-1"><strong>${persona.nombre}</strong></p>
                    <p class="mb-1">Código: ${persona.codigo}</p>
                    <p class="mb-1">Cargo: ${persona.cargo || 'Sin cargo definido'}</p>
                    ${huellasInfo ? `<p class="mb-0"><small>Huellas: ${huellasInfo}</small></p>` : ''}
                </div>
                <div class="col-md-4 text-end">
                    <button class="btn btn-primary btn-sm" onclick="viewPersonDetails('${persona.codigo}')">
                        Ver Detalles
                    </button>
                </div>
            </div>
        </div>
    `;
}

/**
 * Detener verificación
 */
async function stopVerification() {
    try {
        isVerifying = false;

        if (verificationTimeout) {
            clearTimeout(verificationTimeout);
            verificationTimeout = null;
        }

        await window.laravelFingerprintSDK.stopCapture();
        updateVerificationStatus('info', 'Verificación detenida');
        resetVerificationButtons();

        // Limpiar resultados
        document.getElementById('access-result').innerHTML = '';
        document.getElementById('search-result').innerHTML = '';

    } catch (error) {
        updateVerificationStatus('error', 'Error deteniendo verificación: ' + error.message);
    }
}

/**
 * Configurar log de acceso en tiempo real
 */
function setupAccessLog() {
    loadRecentAccess();
}

/**
 * Iniciar monitoreo de acceso
 */
function startAccessMonitoring() {
    // Actualizar cada 30 segundos
    accessLogInterval = setInterval(loadRecentAccess, 30000);
}

/**
 * Cargar accesos recientes
 */
async function loadRecentAccess() {
    try {
        const response = await fetch('/huellero/eventos/recientes?limit=10', {
            headers: {
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            }
        });

        const data = await response.json();

        if (data.success) {
            displayRecentAccess(data.eventos);
        }

    } catch (error) {
        console.error('Error cargando accesos recientes:', error);
    }
}

/**
 * Mostrar accesos recientes
 */
function displayRecentAccess(eventos) {
    const container = document.getElementById('recent-access-list');

    if (eventos.length === 0) {
        container.innerHTML = `
            <div class="text-center text-muted py-4">
                <i class="bi bi-clock-history" style="font-size: 2rem;"></i>
                <p class="mt-2">No hay eventos recientes</p>
            </div>
        `;
        return;
    }

    let html = '';
    eventos.forEach(evento => {
        const persona = evento.persona;
        const tipoIcon = evento.tipo === 'entrada' ? 'bi-box-arrow-in-right text-success' : 'bi-box-arrow-right text-primary';
        const tiempoFormateado = new Date(evento.fecha_hora).toLocaleString();

        html += `
            <div class="d-flex align-items-center py-2 border-bottom">
                <div class="me-3">
                    <i class="bi ${tipoIcon}" style="font-size: 1.2rem;"></i>
                </div>
                <div class="flex-grow-1">
                    <div class="fw-semibold">${persona.nombre}</div>
                    <small class="text-muted">${persona.codigo} | ${persona.cargo || 'Sin cargo'}</small>
                </div>
                <div class="text-end">
                    <div class="badge bg-${evento.tipo === 'entrada' ? 'success' : 'primary'} mb-1">
                        ${evento.tipo.toUpperCase()}
                    </div>
                    <div><small class="text-muted">${tiempoFormateado}</small></div>
                </div>
            </div>
        `;
    });

    container.innerHTML = html;
}

/**
 * Gestión de timeouts de verificación
 */
function startVerificationTimeout() {
    verificationTimeout = setTimeout(() => {
        if (isVerifying) {
            updateVerificationStatus('info', 'Esperando huella - Coloque el dedo en el lector');
        }
    }, 10000); // 10 segundos sin actividad
}

function restartVerificationTimeout() {
    if (verificationTimeout) {
        clearTimeout(verificationTimeout);
    }
    startVerificationTimeout();
}

/**
 * Actualizar estado de verificación
 */
function updateVerificationStatus(type, message) {
    const statusElement = document.getElementById('verification-status');
    if (statusElement && message) {
        statusElement.textContent = message;
        statusElement.className = `verification-status alert alert-${type === 'error' ? 'danger' : type === 'warning' ? 'warning' : type === 'success' ? 'success' : 'info'}`;
        statusElement.classList.remove('d-none');
    } else if (statusElement) {
        statusElement.classList.add('d-none');
    }
}

/**
 * Resetear botones de verificación
 */
function resetVerificationButtons() {
    document.getElementById('start-verification-btn').disabled = false;
    document.getElementById('stop-verification-btn').disabled = true;
}

/**
 * Ver detalles de persona
 */
function viewPersonDetails(codigoPersona) {
    // Implementar modal o redirección a detalles
    window.open(`/huellero/personas/${codigoPersona}`, '_blank');
}

/**
 * Limpiar al salir de la página
 */
window.addEventListener('beforeunload', () => {
    if (accessLogInterval) {
        clearInterval(accessLogInterval);
    }

    if (verificationTimeout) {
        clearTimeout(verificationTimeout);
    }

    if (isVerifying) {
        stopVerification();
    }
});
