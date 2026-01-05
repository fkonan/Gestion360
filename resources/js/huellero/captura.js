/**
 * Funciones específicas para la página de captura de huellas
 */

// Variables específicas de captura
let selectedPersona = null;
let selectedDedo = null;
let currentStep = 1;
let captureAttempts = 0;
const maxAttempts = 3;

/**
 * Inicializar página de captura
 */
document.addEventListener('DOMContentLoaded', function() {
    if (document.getElementById('captura-form')) {
        initCapturePage();
    }
});

function initCapturePage() {
    setupStepNavigation();
    setupPersonaSearch();
    setupDeviceSelection();
    setupCaptureControls();
    setupFingerprintEvents();

    // Cargar dispositivos al iniciar
    loadDevices();
}

/**
 * Configurar navegación de pasos
 */
function setupStepNavigation() {
    const nextButtons = document.querySelectorAll('[data-next-step]');
    const prevButtons = document.querySelectorAll('[data-prev-step]');

    nextButtons.forEach(button => {
        button.addEventListener('click', () => {
            const nextStep = parseInt(button.dataset.nextStep);
            if (validateCurrentStep()) {
                goToStep(nextStep);
            }
        });
    });

    prevButtons.forEach(button => {
        button.addEventListener('click', () => {
            const prevStep = parseInt(button.dataset.prevStep);
            goToStep(prevStep);
        });
    });
}

/**
 * Configurar búsqueda de personas
 */
function setupPersonaSearch() {
    const searchInput = document.getElementById('persona-search');
    const searchButton = document.getElementById('search-persona-btn');
    const clearButton = document.getElementById('clear-persona-btn');

    if (searchInput && searchButton) {
        searchButton.addEventListener('click', searchPersona);
        searchInput.addEventListener('keypress', (e) => {
            if (e.key === 'Enter') {
                e.preventDefault();
                searchPersona();
            }
        });
    }

    if (clearButton) {
        clearButton.addEventListener('click', clearPersonaSelection);
    }
}

/**
 * Buscar persona
 */
async function searchPersona() {
    const searchInput = document.getElementById('persona-search');
    const query = searchInput.value.trim();

    if (!query) {
        showAlert('Por favor ingrese un término de búsqueda', 'warning');
        return;
    }

    try {
        showLoading('search-persona-btn');

        const response = await fetch('/huellero/personas/buscar', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            },
            body: JSON.stringify({ query: query })
        });

        const data = await response.json();

        if (data.success) {
            displayPersonaResults(data.personas);
        } else {
            showAlert('Error en la búsqueda: ' + data.message, 'error');
        }
    } catch (error) {
        showAlert('Error realizando búsqueda: ' + error.message, 'error');
    } finally {
        hideLoading('search-persona-btn');
    }
}

/**
 * Mostrar resultados de búsqueda de personas
 */
function displayPersonaResults(personas) {
    const resultsContainer = document.getElementById('persona-results');

    if (personas.length === 0) {
        resultsContainer.innerHTML = `
            <div class="alert alert-info">
                <i class="bi bi-info-circle me-2"></i>
                No se encontraron personas con ese criterio de búsqueda.
            </div>
        `;
        return;
    }

    let html = '<div class="row">';
    personas.forEach(persona => {
        html += `
            <div class="col-md-6 mb-3">
                <div class="card persona-card" data-persona-id="${persona.codigo}">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="avatar me-3">
                                <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center" style="width: 50px; height: 50px;">
                                    <i class="bi bi-person-fill"></i>
                                </div>
                            </div>
                            <div class="flex-grow-1">
                                <h6 class="card-title mb-1">${persona.nombre}</h6>
                                <p class="card-text text-muted mb-1">
                                    <small>Código: ${persona.codigo}</small>
                                </p>
                                <p class="card-text text-muted mb-0">
                                    <small>${persona.cargo || 'Sin cargo definido'}</small>
                                </p>
                            </div>
                            <div>
                                <button class="btn btn-primary btn-sm select-persona-btn" data-persona='${JSON.stringify(persona)}'>
                                    Seleccionar
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        `;
    });
    html += '</div>';

    resultsContainer.innerHTML = html;

    // Agregar event listeners a los botones de selección
    document.querySelectorAll('.select-persona-btn').forEach(button => {
        button.addEventListener('click', () => {
            const persona = JSON.parse(button.dataset.persona);
            selectPersona(persona);
        });
    });
}

/**
 * Seleccionar persona
 */
function selectPersona(persona) {
    selectedPersona = persona;

    // Actualizar UI
    document.getElementById('selected-persona-name').textContent = persona.nombre;
    document.getElementById('selected-persona-codigo').textContent = persona.codigo;
    document.getElementById('selected-persona-cargo').textContent = persona.cargo || 'Sin cargo definido';

    // Mostrar información seleccionada
    document.getElementById('persona-selected-info').classList.remove('d-none');

    // Limpiar resultados de búsqueda
    document.getElementById('persona-results').innerHTML = '';
    document.getElementById('persona-search').value = '';

    // Habilitar botón de siguiente paso
    const nextButton = document.querySelector('[data-next-step="2"]');
    if (nextButton) {
        nextButton.disabled = false;
    }

    showAlert('Persona seleccionada correctamente', 'success');
}

/**
 * Limpiar selección de persona
 */
function clearPersonaSelection() {
    selectedPersona = null;

    // Limpiar UI
    document.getElementById('persona-selected-info').classList.add('d-none');
    document.getElementById('persona-results').innerHTML = '';
    document.getElementById('persona-search').value = '';

    // Deshabilitar botón de siguiente paso
    const nextButton = document.querySelector('[data-next-step="2"]');
    if (nextButton) {
        nextButton.disabled = true;
    }
}

/**
 * Configurar selección de dispositivos
 */
function setupDeviceSelection() {
    const refreshButton = document.getElementById('refresh-devices-btn');
    if (refreshButton) {
        refreshButton.addEventListener('click', loadDevices);
    }
}

/**
 * Cargar dispositivos disponibles
 */
async function loadDevices() {
    try {
        showLoading('refresh-devices-btn');

        if (!window.laravelFingerprintSDK) {
            throw new Error('SDK no inicializado');
        }

        const devices = await window.laravelFingerprintSDK.getDevices();
        displayDevices(devices);

    } catch (error) {
        showAlert('Error cargando dispositivos: ' + error.message, 'error');
        displayDevices([]);
    } finally {
        hideLoading('refresh-devices-btn');
    }
}

/**
 * Mostrar dispositivos disponibles
 */
function displayDevices(devices) {
    const container = document.getElementById('devices-list');

    if (devices.length === 0) {
        container.innerHTML = `
            <div class="alert alert-warning">
                <i class="bi bi-exclamation-triangle me-2"></i>
                No se encontraron dispositivos de huella conectados.
                <br><small>Verifique que el dispositivo esté conectado e intente actualizar.</small>
            </div>
        `;
        return;
    }

    let html = '';
    devices.forEach((device, index) => {
        html += `
            <div class="form-check mb-3">
                <input class="form-check-input" type="radio" name="device" id="device-${index}" value="${device.DeviceID}">
                <label class="form-check-label" for="device-${index}">
                    <div class="d-flex align-items-center">
                        <i class="bi bi-fingerprint text-primary me-3" style="font-size: 1.5rem;"></i>
                        <div>
                            <div class="fw-semibold">${device.DeviceID}</div>
                            <small class="text-muted">
                                Tecnología: ${device.eDeviceTech || 'Desconocida'} |
                                Modalidad: ${device.eDeviceModality || 'Desconocida'}
                            </small>
                        </div>
                    </div>
                </label>
            </div>
        `;
    });

    container.innerHTML = html;

    // Agregar event listeners
    document.querySelectorAll('input[name="device"]').forEach(radio => {
        radio.addEventListener('change', () => {
            if (radio.checked) {
                selectedDevice = radio.value;

                // Habilitar botón de siguiente paso
                const nextButton = document.querySelector('[data-next-step="3"]');
                if (nextButton) {
                    nextButton.disabled = false;
                }
            }
        });
    });
}

/**
 * Configurar controles de captura
 */
function setupCaptureControls() {
    // Selección de dedo
    document.querySelectorAll('.finger-option').forEach(option => {
        option.addEventListener('click', () => {
            document.querySelectorAll('.finger-option').forEach(opt => opt.classList.remove('selected'));
            option.classList.add('selected');
            selectedDedo = option.dataset.finger;

            // Habilitar botón de captura
            document.getElementById('start-capture-btn').disabled = false;
        });
    });

    // Botones de captura
    document.getElementById('start-capture-btn')?.addEventListener('click', startFingerCapture);
    document.getElementById('stop-capture-btn')?.addEventListener('click', stopFingerCapture);
    document.getElementById('clear-capture-btn')?.addEventListener('click', clearFingerCapture);
    document.getElementById('save-capture-btn')?.addEventListener('click', saveFingerprint);
}

/**
 * Configurar eventos del SDK de huellas
 */
function setupFingerprintEvents() {
    // Callback cuando se captura una huella
    window.onFingerprintCaptured = function(captureData) {
        console.log('Huella capturada:', captureData);

        // Actualizar UI
        updateCaptureStatus('success', 'Huella capturada exitosamente');

        // Habilitar botón de guardado
        document.getElementById('save-capture-btn').disabled = false;

        // Almacenar datos para guardado
        window.currentCaptureData = captureData;
    };

    // Callback para actualizaciones de calidad
    window.updateQualityCallback = function(quality, qualityText) {
        updateQualityDisplay(quality, qualityText);

        // Mostrar sugerencias según calidad
        if (quality < 40) {
            updateCaptureStatus('warning', 'Calidad baja - Limpie el dedo y vuelva a intentar');
        } else if (quality < 60) {
            updateCaptureStatus('info', 'Calidad regular - Presione firmemente el dedo');
        } else if (quality >= 80) {
            updateCaptureStatus('success', 'Excelente calidad de captura');
        }
    };
}

/**
 * Iniciar captura de huella
 */
async function startFingerCapture() {
    if (!selectedDevice) {
        showAlert('Por favor seleccione un dispositivo', 'warning');
        return;
    }

    if (!selectedDedo) {
        showAlert('Por favor seleccione un dedo', 'warning');
        return;
    }

    try {
        captureAttempts = 0;

        // Actualizar UI
        document.getElementById('start-capture-btn').disabled = true;
        document.getElementById('stop-capture-btn').disabled = false;
        document.getElementById('save-capture-btn').disabled = true;

        updateCaptureStatus('info', 'Iniciando captura...');

        // Iniciar captura
        const success = await window.laravelFingerprintSDK.startCapture(selectedDevice, 'Intermediate');

        if (success) {
            updateCaptureStatus('info', 'Coloque el dedo en el lector');
        } else {
            throw new Error('No se pudo iniciar la captura');
        }

    } catch (error) {
        updateCaptureStatus('error', 'Error iniciando captura: ' + error.message);
        resetCaptureButtons();
    }
}

/**
 * Detener captura de huella
 */
async function stopFingerCapture() {
    try {
        await window.laravelFingerprintSDK.stopCapture();
        updateCaptureStatus('info', 'Captura detenida');
        resetCaptureButtons();
    } catch (error) {
        updateCaptureStatus('error', 'Error deteniendo captura: ' + error.message);
    }
}

/**
 * Limpiar captura
 */
function clearFingerCapture() {
    window.laravelFingerprintSDK.clearCapture();
    updateCaptureStatus('', '');
    document.getElementById('save-capture-btn').disabled = true;
    window.currentCaptureData = null;
}

/**
 * Guardar huella en base de datos
 */
async function saveFingerprint() {
    if (!window.currentCaptureData) {
        showAlert('No hay datos de huella para guardar', 'warning');
        return;
    }

    if (!selectedPersona) {
        showAlert('No hay persona seleccionada', 'warning');
        return;
    }

    try {
        showLoading('save-capture-btn');
        updateCaptureStatus('info', 'Guardando huella...');

        const response = await fetch('/huellero/huellas/registrar', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            },
            body: JSON.stringify({
                persona_codigo: selectedPersona.codigo,
                dedo: selectedDedo,
                template_huella: window.currentCaptureData.template,
                calidad: window.currentCaptureData.quality || 0,
                formato: window.currentCaptureData.format
            })
        });

        const data = await response.json();

        if (data.success) {
            updateCaptureStatus('success', 'Huella guardada exitosamente');
            showAlert('Huella registrada correctamente para ' + selectedPersona.nombre, 'success');

            // Limpiar formulario para nueva captura
            setTimeout(() => {
                resetCaptureForm();
            }, 2000);
        } else {
            throw new Error(data.message);
        }

    } catch (error) {
        updateCaptureStatus('error', 'Error guardando huella: ' + error.message);
        showAlert('Error al guardar huella: ' + error.message, 'error');
    } finally {
        hideLoading('save-capture-btn');
    }
}

/**
 * Validar paso actual
 */
function validateCurrentStep() {
    switch (currentStep) {
        case 1:
            if (!selectedPersona) {
                showAlert('Por favor seleccione una persona', 'warning');
                return false;
            }
            break;
        case 2:
            if (!selectedDevice) {
                showAlert('Por favor seleccione un dispositivo', 'warning');
                return false;
            }
            break;
        case 3:
            if (!selectedDedo) {
                showAlert('Por favor seleccione un dedo', 'warning');
                return false;
            }
            break;
    }
    return true;
}

/**
 * Ir a paso específico
 */
function goToStep(step) {
    // Ocultar todos los pasos
    document.querySelectorAll('.step-content').forEach(content => {
        content.classList.add('d-none');
    });

    // Mostrar paso actual
    document.getElementById(`step-${step}`).classList.remove('d-none');

    // Actualizar indicadores de paso
    document.querySelectorAll('.step-indicator').forEach((indicator, index) => {
        indicator.classList.remove('active', 'completed');
        if (index + 1 < step) {
            indicator.classList.add('completed');
        } else if (index + 1 === step) {
            indicator.classList.add('active');
        }
    });

    currentStep = step;
}

/**
 * Actualizar estado de captura
 */
function updateCaptureStatus(type, message) {
    const statusElement = document.getElementById('capture-status');
    if (statusElement && message) {
        statusElement.textContent = message;
        statusElement.className = `capture-status alert alert-${type === 'error' ? 'danger' : type === 'warning' ? 'warning' : type === 'success' ? 'success' : 'info'}`;
        statusElement.classList.remove('d-none');
    } else if (statusElement) {
        statusElement.classList.add('d-none');
    }
}

/**
 * Actualizar display de calidad
 */
function updateQualityDisplay(quality, qualityText) {
    const qualityBar = document.getElementById('quality-progress');
    const qualityLabel = document.getElementById('quality-label');

    if (qualityBar) {
        qualityBar.style.width = quality + '%';
        qualityBar.className = 'progress-bar';

        if (quality >= 80) qualityBar.classList.add('bg-success');
        else if (quality >= 60) qualityBar.classList.add('bg-warning');
        else qualityBar.classList.add('bg-danger');
    }

    if (qualityLabel) {
        qualityLabel.textContent = `${quality}% - ${qualityText}`;
    }
}

/**
 * Resetear botones de captura
 */
function resetCaptureButtons() {
    document.getElementById('start-capture-btn').disabled = false;
    document.getElementById('stop-capture-btn').disabled = true;
}

/**
 * Resetear formulario de captura
 */
function resetCaptureForm() {
    // Limpiar selecciones
    selectedPersona = null;
    selectedDedo = null;
    selectedDevice = null;
    window.currentCaptureData = null;

    // Resetear UI
    clearPersonaSelection();
    document.querySelectorAll('.finger-option').forEach(opt => opt.classList.remove('selected'));
    document.querySelectorAll('input[name="device"]').forEach(radio => radio.checked = false);

    // Limpiar captura
    if (window.laravelFingerprintSDK) {
        window.laravelFingerprintSDK.clearCapture();
    }

    // Volver al primer paso
    goToStep(1);

    // Resetear botones
    resetCaptureButtons();
    document.getElementById('save-capture-btn').disabled = true;
}

/**
 * Funciones de utilidad
 */
function showLoading(buttonId) {
    const button = document.getElementById(buttonId);
    if (button) {
        button.disabled = true;
        const originalText = button.textContent;
        button.dataset.originalText = originalText;
        button.innerHTML = '<i class="bi bi-hourglass-split me-2"></i>Procesando...';
    }
}

function hideLoading(buttonId) {
    const button = document.getElementById(buttonId);
    if (button && button.dataset.originalText) {
        button.disabled = false;
        button.textContent = button.dataset.originalText;
        delete button.dataset.originalText;
    }
}

function showAlert(message, type) {
    // Crear alerta temporal
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${type === 'error' ? 'danger' : type} alert-dismissible fade show`;
    alertDiv.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;

    // Insertar al inicio del contenido
    const container = document.querySelector('.container') || document.body;
    container.insertBefore(alertDiv, container.firstChild);

    // Auto-remover después de 5 segundos
    setTimeout(() => {
        if (alertDiv.parentNode) {
            alertDiv.remove();
        }
    }, 5000);
}
