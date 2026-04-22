@extends('layouts.dashboard')

@section('title', 'Huellas - Verify')

@section('breadcrumb')
<x-breadcrumb :items="[
        ['name' => 'Inicio', 'url' => route('home')],
        ['name' => 'Gestion huellero', 'url' => route('fingerprint.gestion')],
        ['name' => 'Verify'],
    ]" />
<br>
@endsection

@section('content')
<div class="container-fluid p-0 border rounded sidebar-dark-primary tableContainer" style="min-height:150px;">
    <x-sectionHeader titulo="Verificación de huellas" rutaVolver="{{ route('fingerprint.gestion') }}" :crear="false" />

    <div class="p-4">
        <div id="alertContainer"></div>

        <div class="row g-3 mt-1">
            <div class="col-12 col-lg-7">
                <div class="card huellero-verify-detail">
                    <div class="card-body">
                        <!-- <h5 class="mb-3">Verify</h5> -->

                        <div class="mb-3">
                            <div class="fw-semibold mb-2">Método de verificación</div>
                            <div class="d-flex flex-wrap gap-2">
                                <input type="radio" class="btn-check" name="verifyMode" id="verifyModeHuella" value="huella" checked>
                                <label class="btn btn-outline-primary" for="verifyModeHuella">Huella</label>
                                <input type="radio" class="btn-check" name="verifyMode" id="verifyModeIdentificacion" value="identificacion">
                                <label class="btn btn-outline-secondary" for="verifyModeIdentificacion">Identificación</label>
                            </div>
                            <div class="form-text mt-2">Coloca el dedo en el lector o ingresa la identificación para verificar.</div>
                        </div>

                        <div class="mb-3" id="identificacionGroup">
                            <label class="form-label" for="identificacionInput">Identificación</label>
                            <input id="identificacionInput" class="form-control" type="text" autocomplete="off">
                            <div class="form-text">Ingresa la identificación si no tienes huella.</div>
                        </div>

                        <div id="sendPayloadWrap" class="d-flex gap-2 flex-wrap d-none mb-3">
                            <button id="sendPayload" class="btn btn-success" type="button" disabled>
                                Finalizar verificación
                            </button>
                        </div>

                        <div class="mb-3 d-none">
                            <label class="form-label">Datos tecnicos (modo desarrollo)</label>
                            <pre id="payloadPreview" class="border rounded p-2 small mb-0">-</pre>
                            <div class="form-text">Solo visible para pruebas.</div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-12 col-lg-5">
                <div class="card h-100">
                    <div class="card-body">
                        <div class="fw-semibold mb-2">Resultado</div>
                        <div id="resultPlaceholder" class="text-muted">Aún sin verificación.</div>
                        <div class="d-none" id="resultPanel">
                            <div class="mb-2">
                                <div id="resultNombre" class="fw-semibold">-</div>
                                <div id="resultIdentificacion" class="text-muted small">-</div>
                            </div>
                            <div class="row g-2">
                                <div class="col-12 col-md-6">
                                    <div class="small text-muted">Cargo</div>
                                    <div id="resultCargo">-</div>
                                </div>
                                <div class="col-12 col-md-6">
                                    <div class="small text-muted">Estado contrato</div>
                                    <div id="resultEstado">-</div>
                                </div>
                                <div class="col-12">
                                    <div class="small text-muted">Dedos registrados</div>
                                    <div id="resultDedos" class="d-flex flex-wrap gap-2"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@pushOnce('script')
<script src="{{ asset('vendor/fingerprint/es6-shim.js') }}"></script>
<script src="{{ asset('vendor/fingerprint/websdk.client.bundle.min.js') }}"></script>
<script src="{{ asset('vendor/fingerprint/fingerprint.sdk.min.js') }}"></script>
<script src="{{ asset('js/fingerprint-client.js') }}?v={{ filemtime(public_path('js/fingerprint-client.js')) }}"></script>
@endpushOnce

@push('css')
  @vite('resources/css/huellero.css')
@endpush

@push('script')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        var ui = {
            alertContainer: document.getElementById('alertContainer'),
            statusBadge: document.getElementById('statusBadge'),
            startCapture: document.getElementById('startCapture'),
            stopCapture: document.getElementById('stopCapture'),
            resetCapture: document.getElementById('resetCapture'),
            sendPayload: document.getElementById('sendPayload'),
            verifyModes: Array.prototype.slice.call(document.querySelectorAll('input[name="verifyMode"]')),
            identificacionGroup: document.getElementById('identificacionGroup'),
            identificacionInput: document.getElementById('identificacionInput'),
            resultPanel: document.getElementById('resultPanel'),
            resultPlaceholder: document.getElementById('resultPlaceholder'),
            resultIdentificacion: document.getElementById('resultIdentificacion'),
            resultNombre: document.getElementById('resultNombre'),
            resultCargo: document.getElementById('resultCargo'),
            resultEstado: document.getElementById('resultEstado'),
            resultDedos: document.getElementById('resultDedos'),
            sendPayloadWrap: document.getElementById('sendPayloadWrap'),
            payloadPreview: document.getElementById('payloadPreview')
        };
        if (ui.startCapture) ui.startCapture.textContent = 'Iniciar';
        if (ui.stopCapture) ui.stopCapture.textContent = 'Detener';
        if (ui.resetCapture) ui.resetCapture.textContent = 'Reiniciar';
        if (ui.sendPayload) ui.sendPayload.textContent = 'Verificar';

        var client = new FingerprintClient();
        var register = false;
        var probe = '';
        var lastQuality = null;
        var targetCount = 1;
        var activeReader = '';
        var autoCaptureEnabled = true;
        var restartTimer = null;

        var fingerLabels = {
            '01': 'Pulgar derecho',
            '02': 'Indice derecho',
            '03': 'Medio derecho',
            '04': 'Anular derecho',
            '05': 'Menique derecho',
            '06': 'Pulgar izquierdo',
            '07': 'Indice izquierdo',
            '08': 'Medio izquierdo',
            '09': 'Anular izquierdo',
            '10': 'Menique izquierdo'
        };

        function normalizeFingerCode(value) {
            if (value === null || typeof value === 'undefined') return '';
            var text = String(value).trim();
            if (!text) return '';
            if (text.length === 1) return '0' + text;
            return text;
        }

        function renderDedosRegistrados(dedos) {
            if (!ui.resultDedos) return;
            ui.resultDedos.innerHTML = '';
            var normalized = Array.isArray(dedos) ? dedos.map(normalizeFingerCode).filter(Boolean) : [];
            if (!normalized.length) {
                var empty = document.createElement('span');
                empty.className = 'text-muted small';
                empty.textContent = 'Sin registros';
                ui.resultDedos.appendChild(empty);
                return;
            }
            normalized.forEach(function(code) {
                var label = fingerLabels[code] || code;
                var badge = document.createElement('span');
                badge.className = 'badge text-bg-success';
                badge.textContent = label;
                ui.resultDedos.appendChild(badge);
            });
        }

        function currentMode() {
            var checked = ui.verifyModes.find(function(input) {
                return input.checked;
            });
            return checked ? checked.value : 'huella';
        }

        function identValue() {
            return ui.identificacionInput ? ui.identificacionInput.value.trim() : '';
        }

        function applyModeUI() {
            var mode = currentMode();
            var isHuella = mode === 'huella';
            if (ui.identificacionGroup) {
                ui.identificacionGroup.classList.remove('opacity-50');
            }
            if (!isHuella) {
                if (client.acquisitionStarted) {
                    stopCapture();
                }
                setButtons(false);
                setStatus('connected');
                resetProbe();
                if (ui.startCapture) ui.startCapture.disabled = true;
                if (ui.stopCapture) ui.stopCapture.disabled = true;
                if (ui.sendPayloadWrap) {
                    ui.sendPayloadWrap.classList.remove('d-none');
                }
                autoCaptureEnabled = false;
            } else {
                if (ui.startCapture) ui.startCapture.disabled = false;
                if (ui.sendPayloadWrap) {
                    ui.sendPayloadWrap.classList.add('d-none');
                }
                autoCaptureEnabled = true;
                scheduleAutoCapture();
            }
            renderResult(null);
            updateProgress();
        }

        function setStatus(state) {
            var map = {
                connected: { text: 'Conectado', cls: 'bg-success' },
                disconnected: { text: 'Desconectado', cls: 'bg-secondary' },
                error: { text: 'Error', cls: 'bg-danger' },
                waiting: { text: 'Esperando dedo', cls: 'bg-warning text-dark' },
                capturing: { text: 'Capturando', cls: 'bg-info text-dark' },
                completed: { text: 'Completado', cls: 'bg-success' }
            };
            var cfg = map[state] || map.disconnected;
            if (!ui.statusBadge) return;
            ui.statusBadge.className = 'badge ' + cfg.cls;
            ui.statusBadge.textContent = cfg.text;
        }

        function showAlert(type, message) {
            if (!message) return;
            var isError = type === 'danger' || type === 'warning' || type === 'error';
            if (isError && window.Swal && typeof window.Swal.fire === 'function') {
                var iconType = type === 'warning' ? 'warning' : 'error';
                window.Swal.fire({
                    icon: iconType,
                    title: 'Verificacion',
                    text: message,
                    confirmButtonColor: '#3366CC',
                    customClass: {
                        popup: 'swalAlert'
                    }
                });
                return;
            }
            var wrapper = document.createElement('div');
            wrapper.className = 'alert alert-' + type + ' alert-dismissible fade show';
            wrapper.setAttribute('role', 'alert');
            wrapper.innerHTML = '<div>' + message + '</div>' +
                '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>';
            ui.alertContainer.appendChild(wrapper);
            setTimeout(function() {
                if (wrapper && wrapper.parentNode) {
                    wrapper.classList.remove('show');
                    wrapper.addEventListener('transitionend', function() {
                        if (wrapper.parentNode) {
                            wrapper.parentNode.removeChild(wrapper);
                        }
                    }, { once: true });
                }
            }, 4000);
        }

        function setButtons(isCapturing) {
            if (ui.startCapture) ui.startCapture.disabled = isCapturing;
            if (ui.stopCapture) ui.stopCapture.disabled = !isCapturing;
        }

        function updatePayloadPreview() {
            if (!ui.payloadPreview) {
                return;
            }
            var mode = currentMode();
            if (mode === 'identificacion') {
                var ident = identValue();
                ui.payloadPreview.textContent = ident ? JSON.stringify({ identificacion: ident }, null, 2) : '-';
                return;
            }
            if (!probe) {
                ui.payloadPreview.textContent = '-';
                return;
            }
            var payload = {
                huella: probe,
            };
            ui.payloadPreview.textContent = JSON.stringify(payload, null, 2);
        }

        function updateProgress() {
            var mode = currentMode();
            ui.sendPayload.disabled = mode === 'huella' ? !probe : !identValue();
            updatePayloadPreview();
        }

        function resetProbe() {
            probe = '';
            updateProgress();
        }

        function scheduleAutoCapture() {
            if (!autoCaptureEnabled) return;
            if (currentMode() !== 'huella') return;
            if (client.acquisitionStarted) return;
            if (probe) {
                resetProbe();
            }
            if (restartTimer) {
                clearTimeout(restartTimer);
            }
            restartTimer = setTimeout(function() {
                if (autoCaptureEnabled && currentMode() === 'huella' && !client.acquisitionStarted) {
                    startCapture();
                }
            }, 500);
        }

        function renderResult(data) {
            var hasData = data && (data.identificacion || data.cargo || data.estadoContrato);
            if (ui.resultPanel) {
                ui.resultPanel.classList.toggle('d-none', !hasData);
            }
            if (ui.resultPlaceholder) {
                ui.resultPlaceholder.classList.toggle('d-none', hasData);
            }
            if (ui.resultIdentificacion) {
                ui.resultIdentificacion.textContent = hasData && data.identificacion ? 'C.C. ' + data.identificacion : '-';
            }
            if (ui.resultNombre) {
                ui.resultNombre.textContent = hasData && data.nombre ? data.nombre : '-';
            }
            if (ui.resultCargo) {
                ui.resultCargo.textContent = hasData && data.cargo ? data.cargo : '-';
            }
            if (ui.resultEstado) {
                var estado = hasData ? data.estadoContrato : null;
                var estadoTexto = '-';
                if (estado === 1 || estado === '1') {
                    estadoTexto = 'Activo';
                } else if (estado === 0 || estado === '0') {
                    estadoTexto = 'Inactivo';
                } else if (estado !== null && typeof estado !== 'undefined') {
                    estadoTexto = estado;
                }
                ui.resultEstado.textContent = estadoTexto;
            }
            if (ui.resultDedos) {
                renderDedosRegistrados(hasData ? data.dedosRegistrados : []);
            }
        }

        async function refreshReaders(autoSelect) {
            try {
                var readers = await client.enumerateReaders();
                if (!readers || readers.length === 0) {
                    setStatus('disconnected');
                    showAlert('warning', 'No se detectaron lectores.');
                    activeReader = '';
                    return;
                }
                if (autoSelect || !activeReader) {
                    activeReader = readers[0];
                }
            } catch (err) {
                setStatus('error');
                showAlert('danger', 'No se pudo enumerar lectores.');
            }
        }

        function handleSample(sampleData) {
            if (currentMode() !== 'huella') return;
            if (!register || probe) return;
            if (lastQuality !== 0) {
                setStatus('waiting');
                showAlert('warning', 'Muestra rechazada por calidad.');
                return;
            }
            if (!sampleData) {
                showAlert('warning', 'Muestra vacia.');
                return;
            }
            probe = sampleData;
            updateProgress();

            register = false;
            client.stopCapture().finally(function() {
                setButtons(false);
                setStatus('completed');
                scheduleAutoCapture();
            });
            if (currentMode() === 'huella') {
                sendPayload();
            }
        }

        async function startCapture() {
            if (currentMode() !== 'huella') {
                return;
            }
            if (!activeReader) {
                await refreshReaders(true);
            }
            if (!activeReader) {
                showAlert('warning', 'No se detecto un lector disponible.');
                return;
            }
            if (client.acquisitionStarted) return;
            lastQuality = null;
            register = true;
            setStatus('capturing');
            setButtons(true);
            try {
                await client.startCaptureIntermediate(activeReader);
            } catch (err) {
                register = false;
                setButtons(false);
                setStatus('error');
                var detail = err && err.message ? err.message : '';
                if (detail) {
                    showAlert('danger', 'No se pudo iniciar la captura: ' + detail);
                } else {
                    showAlert('danger', 'No se pudo iniciar la captura.');
                }
            }
        }

        async function stopCapture() {
            register = false;
            try {
                await client.stopCapture();
            } finally {
                setButtons(false);
                setStatus('connected');
                scheduleAutoCapture();
            }
        }

        function resetCapture() {
            register = false;
            client.stopCapture().finally(function() {
                setButtons(false);
                setStatus('connected');
            });
            probe = '';
            updateProgress();
            renderResult(null);
            scheduleAutoCapture();
        }

        async function sendPayload() {
            var mode = currentMode();
            var payload = {};
            if (mode === 'huella') {
                if (!probe) {
                    showAlert('warning', 'No hay muestra para verificar.');
                    return;
                }
                payload.huella = probe;
            } else {
                var ident = identValue();
                if (!ident) {
                    showAlert('warning', 'Ingresa una identificación.');
                    return;
                }
                payload.identificacion = ident;
            }

            var csrfToken = document.querySelector('meta[name="csrf-token"]');
            var token = csrfToken ? csrfToken.getAttribute('content') : '';

            try {
                var response = await fetch("{{ route('api.fingerprint.verifyDetailed') }}", {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': token
                    },
                    credentials: 'same-origin',
                    body: JSON.stringify(payload)
                });

                var data = null;
                try {
                    data = await response.json();
                } catch (err) {
                    data = null;
                }

                if (!response.ok || !data) {
                    showAlert('warning', (data && data.error) ? data.error : 'Servicio no disponible.');
                    scheduleAutoCapture();
                    return;
                }

                if (data.status !== 'ok') {
                    showAlert('warning', 'No fue posible encontrar la persona.');
                    renderResult(null);
                    scheduleAutoCapture();
                    return;
                }

                renderResult(data.data || null);
                showAlert('success', 'Verificación completada.');
                scheduleAutoCapture();
            } catch (err) {
                showAlert('warning', 'No se pudo contactar el servicio. Payload guardado.');
                scheduleAutoCapture();
            }
        }

        try {
            client.init();
        } catch (err) {
            setStatus('error');
            showAlert('danger', 'Fingerprint SDK no disponible.');
            return;
        }

        client.onDeviceConnected = function() {
            setStatus('connected');
            refreshReaders(true);
            scheduleAutoCapture();
        };
        client.onDeviceDisconnected = function() {
            setStatus('disconnected');
            setButtons(false);
            activeReader = '';
        };
        client.onCommunicationFailed = function() {
            setStatus('error');
            showAlert('danger', 'Fallo la comunicación con el servicio WebAPI.');
            scheduleAutoCapture();
        };
        client.onErrorOccurred = function() {
            setStatus('error');
            showAlert('danger', 'Error del lector.');
            scheduleAutoCapture();
        };
        client.onQualityReported = function(e) {
            lastQuality = e ? e.quality : null;
        };
        client.onSamplesAcquired = function(normalized) {
            if (!normalized || normalized.length === 0) return;
            handleSample(normalized[0]);
        };

        if (ui.startCapture) ui.startCapture.addEventListener('click', startCapture);
        if (ui.stopCapture) ui.stopCapture.addEventListener('click', stopCapture);
        if (ui.resetCapture) ui.resetCapture.addEventListener('click', resetCapture);
        if (ui.sendPayload) ui.sendPayload.addEventListener('click', sendPayload);
        ui.verifyModes.forEach(function(input) {
            input.addEventListener('change', applyModeUI);
        });
        if (ui.identificacionInput) {
            ui.identificacionInput.addEventListener('input', function() {
                var ident = identValue();
                if (ident) {
                    var identMode = document.getElementById('verifyModeIdentificacion');
                    if (identMode && !identMode.checked) {
                        identMode.checked = true;
                        applyModeUI();
                    }
                }
                updateProgress();
            });
        }

        setStatus('disconnected');
        setButtons(false);
        refreshReaders(true);
        updateProgress();
        renderResult(null);
        applyModeUI();
        scheduleAutoCapture();
    });
</script>
@endpush
