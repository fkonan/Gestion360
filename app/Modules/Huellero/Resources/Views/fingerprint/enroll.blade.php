@extends('layouts.dashboard')

@section('title', 'Huellas - Enroll')

@section('breadcrumb')
<x-breadcrumb :items="[
        ['name' => 'Inicio', 'url' => route('home')],
        ['name' => 'Gestion huellero', 'url' => route('fingerprint.gestion')],
        ['name' => 'Enroll'],
    ]" />
<br>
@endsection

@section('content')
<div class="container-fluid p-0 border rounded sidebar-dark-primary tableContainer" style="min-height:150px;">
    <x-sectionHeader titulo="Enroll de huellas" rutaVolver="{{ route('fingerprint.gestion') }}" :crear="false" />

    <div class="p-4 mt-5 ">

        <div class="row g-4">
            <div class="col-lg-7">
                <div class="card">
                    <div class="card-body">
                        <!-- <h5 class="mb-3">Enroll</h5> -->

                        <div class="mb-3">
                            <label class="form-label" for="personSelect">Seleccionar persona</label>
                            <select id="personSelect" class="form-select form-select-sm">
                                <option value=""></option>
                            </select>
                            <!-- <div class="form-text">Busca por documento.</div> -->
                        </div>

                        <div class="mb-3">
                            <div id="selectedPerson" class="border rounded p-2 small d-none">-</div>
                            <input type="hidden" id="fingerSelect" value="">
                            <div class="fingerprint-hands rounded border p-3" data-fingers='@json($dedos ?? [])'>
                                <div class="d-flex justify-content-center mb-3">
                                    <span class="selected-finger-display is-empty" id="selectedFingerLabel">
                                        Sin dedo seleccionado
                                    </span>
                                </div>
                                <div class="d-flex flex-wrap flex-lg-nowrap justify-content-center gap-3">
                                    <div class="hand-block">
                                        <div class="small text-muted text-center mb-2">Mano izquierda</div>
                                        <div class="hand-canvas is-mirrored">
                                            <img src="{{ asset('svg/mano.svg') }}" class="hand-image" alt="Mano izquierda">
                                            <button type="button" class="finger-hotspot finger-thumb" data-dedo="06" aria-label="Pulgar izquierdo"></button>
                                            <button type="button" class="finger-hotspot finger-index" data-dedo="07" aria-label="Indice izquierdo"></button>
                                            <button type="button" class="finger-hotspot finger-middle" data-dedo="08" aria-label="Medio izquierdo"></button>
                                            <button type="button" class="finger-hotspot finger-ring" data-dedo="09" aria-label="Anular izquierdo"></button>
                                            <button type="button" class="finger-hotspot finger-pinky" data-dedo="10" aria-label="Menique izquierdo"></button>
                                        </div>
                                    </div>
                                    <div class="hand-block">
                                        <div class="small text-muted text-center mb-2">Mano derecha</div>
                                        <div class="hand-canvas">
                                            <img src="{{ asset('svg/mano.svg') }}" class="hand-image" alt="Mano derecha">
                                            <button type="button" class="finger-hotspot finger-thumb" data-dedo="01" aria-label="Pulgar derecho"></button>
                                            <button type="button" class="finger-hotspot finger-index" data-dedo="02" aria-label="Indice derecho"></button>
                                            <button type="button" class="finger-hotspot finger-middle" data-dedo="03" aria-label="Medio derecho"></button>
                                            <button type="button" class="finger-hotspot finger-ring" data-dedo="04" aria-label="Anular derecho"></button>
                                            <button type="button" class="finger-hotspot finger-pinky" data-dedo="05" aria-label="Menique derecho"></button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="mb-3 d-none">
                            <label class="form-label">Datos tecnicos (modo desarrollo)</label>
                            <pre id="payloadPreview" class="border rounded p-2 small mb-0 text-black">-</pre>
                            <div class="form-text">Solo visible para pruebas.</div>
                        </div>

                    </div>
                </div>
            </div>

            <div class="col-lg-5">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex align-items-center justify-content-between mb-3">
                            <div class="fw-semibold">Estado del lector</div>
                            <span id="statusBadge" class="badge bg-secondary">Desconectado</span>
                        </div>

                        <div class="mb-3 d-none" aria-hidden="true">
                            <label class="form-label" for="readerSelect">Lector</label>
                            <div class="input-group">
                                <select id="readerSelect" class="form-select"></select>
                                <button id="refreshReaders" class="btn btn-outline-secondary" type="button">
                                    Actualizar
                                </button>
                            </div>
                        </div>

                        <div class="enroll-progress-panel mt-3">
                            <div class="d-flex justify-content-between small text-muted mb-1">
                                <span>Progreso de captura</span>
                                <span id="progressText">0/4</span>
                            </div>
                            <div class="progress enroll-progress-bar">
                                <div id="progressBar" class="progress-bar bg-success" style="width: 0%"></div>
                            </div>
                            <div class="enroll-step-hint mt-3">
                                <div id="stepHint" class="fw-semibold">Coloca el mismo dedo 4 veces.</div>
                                <div class="small text-muted">Paso <span id="currentStep">1</span> de 4</div>
                            </div>
                            <div class="enroll-stepper mt-3" id="stepper">
                                <div class="step-item border rounded px-2 py-1 d-flex align-items-center gap-2" data-step="1">
                                    <span class="step-icon" aria-hidden="true">
                                        <i class="fas fa-fingerprint"></i>
                                    </span>
                                    <div class="step-copy">
                                        <div class="step-title">Primera muestra</div>
                                        <!-- <div class="step-subtitle">Primera toma</div> -->
                                    </div>
                                </div>
                                <div class="step-item border rounded px-2 py-1 d-flex align-items-center gap-2" data-step="2">
                                    <span class="step-icon" aria-hidden="true">
                                        <i class="fas fa-fingerprint"></i>
                                    </span>
                                    <div class="step-copy">
                                        <div class="step-title">Segunda muestra</div>
                                        <!-- <div class="step-subtitle">Segunda toma</div> -->
                                    </div>
                                </div>
                                <div class="step-item border rounded px-2 py-1 d-flex align-items-center gap-2" data-step="3">
                                    <span class="step-icon" aria-hidden="true">
                                        <i class="fas fa-fingerprint"></i>
                                    </span>
                                    <div class="step-copy">
                                        <div class="step-title">Tercera muestra</div>
                                        <!-- <div class="step-subtitle">Tercera toma</div> -->
                                    </div>
                                </div>
                                <div class="step-item border rounded px-2 py-1 d-flex align-items-center gap-2" data-step="4">
                                    <span class="step-icon" aria-hidden="true">
                                        <i class="fas fa-fingerprint"></i>
                                    </span>
                                    <div class="step-copy">
                                        <div class="step-title">Cuarta muestra</div>
                                        <!-- <div class="step-subtitle">Ultima toma</div> -->
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="d-grid gap-2 mt-4">
                            <button id="startCapture" class="btn btn-primary btn-lg" type="button">Iniciar captura</button>
                            <button id="resetCapture" class="btn btn-outline-secondary" type="button">Reiniciar proceso</button>
                            <button id="sendPayload" class="btn btn-success" type="button" disabled>Enviar huella</button>
                            <div class="form-text">Cuando completes las 4 muestras, podras enviar el enrolamiento.</div>
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
            statusBadge: document.getElementById('statusBadge'),
            readerSelect: document.getElementById('readerSelect'),
            startCapture: document.getElementById('startCapture'),
            resetCapture: document.getElementById('resetCapture'),
            sendPayload: document.getElementById('sendPayload'),
            personSelect: document.getElementById('personSelect'),
            selectedPerson: document.getElementById('selectedPerson'),
            fingerSelect: document.getElementById('fingerSelect'),
            fingerLabel: document.getElementById('selectedFingerLabel'),
            progressText: document.getElementById('progressText'),
            progressBar: document.getElementById('progressBar'),
            stepHint: document.getElementById('stepHint'),
            currentStep: document.getElementById('currentStep'),
            payloadPreview: document.getElementById('payloadPreview')
        };

        var client = new FingerprintClient();
        var register = false;
        var samples = [];
        var lastQuality = null;
        var targetCount = 4;
        var selectedPerson = null;
        var sendInProgress = false;
        var initialLoadAt = Date.now();
        var commFailedCount = 0;
          var commRetryTimer = null;
          var userInitiated = false;
          var useMockResponse = false;
          var idCreacion = @json($idCreacion);
          var fingerMap = {};
        var fingerContainer = document.querySelector('.fingerprint-hands');
        if (fingerContainer) {
            var fingersJson = fingerContainer.getAttribute('data-fingers') || '';
            if (fingersJson) {
                try {
                    fingerMap = JSON.parse(fingersJson);
                } catch (err) {
                    fingerMap = {};
                }
            }
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
            ui.statusBadge.className = 'badge ' + cfg.cls;
            ui.statusBadge.textContent = cfg.text;
        }

        function showAlert(type, message) {
            if (!message) return;
            var icon = type === 'danger' ? 'error' : type;
            if (window.Swal && typeof window.Swal.fire === 'function') {
                window.Swal.fire({
                    icon: icon,
                    text: message,
                    confirmButtonColor: '#3366CC',
                    customClass: { popup: 'swalAlert' }
                });
                return;
            }
            alert(message);
        }

        function setButtons(isCapturing) {
            ui.startCapture.disabled = isCapturing;
            if (ui.sendPayload) {
                ui.sendPayload.disabled = isCapturing || samples.length !== targetCount || sendInProgress;
            }
        }

        function selectedPersonId() {
            return selectedPerson ? selectedPerson.id : null;
        }

        function selectedPersonIdentificacion() {
            return selectedPerson ? selectedPerson.documento : null;
        }

        function selectedPersonEvento() {
            if (selectedPerson && selectedPerson.evento) {
                return Number(selectedPerson.evento) || 1;
            }
            return 1;
        }

        function selectedFinger() {
            return ui.fingerSelect.value;
        }

        function updateFingerLabel() {
            if (!ui.fingerLabel) return;
            var value = selectedFinger();
            if (!value) {
                ui.fingerLabel.textContent = 'Sin dedo seleccionado';
                ui.fingerLabel.classList.add('is-empty');
                return;
            }
            var key = String(value);
            var label = (fingerMap && Object.prototype.hasOwnProperty.call(fingerMap, key)) ? fingerMap[key] : value;
            ui.fingerLabel.textContent = label;
            ui.fingerLabel.classList.remove('is-empty');
        }

        function highlightFinger(value) {
            var nodes = document.querySelectorAll('.finger-hotspot');
            nodes.forEach(function(node) {
                if (node.getAttribute('data-dedo') === value) {
                    node.classList.add('active');
                } else {
                    node.classList.remove('active');
                }
            });
        }

        function updateSelectedPerson() {
            if (!selectedPerson) {
                ui.selectedPerson.textContent = '-';
                return;
            }
            ui.selectedPerson.textContent = selectedPerson.documento + ' - ' + selectedPerson.nombre;
        }

        function updatePayloadPreview() {
            var identificacion = selectedPersonIdentificacion();
            var dedo = selectedFinger();
            if (!identificacion || !dedo) {
                ui.payloadPreview.textContent = '-';
                return;
            }
              var payload = {
                  identificacion: String(identificacion),
                  dedo: dedo,
                  huellas: samples.slice(),
                  idCreacion: idCreacion,
                  tipo: selectedPersonEvento()
              };
              ui.payloadPreview.textContent = JSON.stringify(payload, null, 2);
          }

        function resetSamples() {
            samples = [];
            sendInProgress = false;
            updateProgress();
        }

        function updateProgress() {
            var count = samples.length;
            var percent = Math.min(100, Math.round((count / targetCount) * 100));
            ui.progressText.textContent = count + '/' + targetCount;
            ui.progressBar.style.width = percent + '%';
            if (ui.sendPayload) {
                ui.sendPayload.disabled = count !== targetCount || sendInProgress;
            }
            updateStepIndicator();
            updateStepHint();
            updatePayloadPreview();
        }

        function requireSelection() {
            if (!selectedPersonIdentificacion()) {
                showAlert('warning', 'Seleccione una persona antes de capturar.');
                ui.personSelect.focus();
                return false;
            }
            if (!selectedFinger()) {
                showAlert('warning', 'Seleccione el dedo antes de capturar.');
                var hands = document.querySelector('.fingerprint-hands');
                if (hands) {
                    hands.scrollIntoView({ behavior: 'smooth', block: 'center' });
                }
                return false;
            }
            return true;
        }

        function getQualityLabel(quality) {
            if (quality === null || typeof quality === 'undefined') return '-';
            if (window.Fingerprint && Fingerprint.QualityCode && typeof Fingerprint.QualityCode[quality] === 'string') {
                return Fingerprint.QualityCode[quality];
            }
            return String(quality);
        }

        function updateStepIndicator() {
            var count = samples.length;
            var current = Math.min(count + 1, targetCount);
            if (count >= targetCount) current = targetCount;
            if (ui.currentStep) ui.currentStep.textContent = String(current);

            var items = document.querySelectorAll('[data-step]');
            items.forEach(function(item) {
                var step = parseInt(item.getAttribute('data-step'), 10);
                var badge = item.querySelector('.step-badge');
                item.classList.remove('active', 'completed');
                if (badge) badge.className = 'badge step-badge bg-secondary';

                if (step <= count) {
                    item.classList.add('completed');
                    if (badge) badge.className = 'badge step-badge bg-success';
                } else if (step === current && count < targetCount) {
                    item.classList.add('active');
                    if (badge) badge.className = 'badge step-badge bg-primary';
                }
            });
        }

        function updateStepHint() {
            if (!ui.stepHint) return;
            var count = samples.length;
            if (count === 0) {
                ui.stepHint.textContent = 'Coloca el mismo dedo 4 veces.';
                return;
            }
            if (count < targetCount) {
                ui.stepHint.textContent = 'Muestra ' + count + ' lista. Coloca nuevamente el dedo.';
                return;
            }
            ui.stepHint.textContent = 'Enroll completo.';
        }

        function initPersonSelect() {
            $('#personSelect').select2({
                theme: 'bootstrap-5',
                language: {
                    inputTooShort: () => 'Introduce mas caracteres',
                    noResults: () => 'No se encontraron resultados',
                    searching: () => 'Buscando...',
                    loadingMore: () => 'Cargando mas resultados...',
                },
                placeholder: 'Buscar por nombre o documento',
                minimumInputLength: 4,
                ajax: {
                    url: '{{ route("fingerprint.personas") }}',
                    dataType: 'json',
                    delay: 250,
                    data: function(params) {
                        return {
                            query: params.term
                        };
                    },
                    processResults: function(data) {
                        return {
                            results: data
                        };
                    },
                    cache: true
                },
                width: '100%',
                allowClear: true,
                dropdownPosition: 'below'
            });

              $('#personSelect').on('select2:select', function(event) {
                  var data = event.params.data || {};
                  var evento = parseInt(data.evento, 10);
                  selectedPerson = {
                      id: data.id || '',
                      documento: data.documento || '',
                      nombre: data.nombre || data.text || '',
                      evento: Number.isFinite(evento) ? evento : 1
                  };
                  updateSelectedPerson();
                  resetSamples();
              });

            $('#personSelect').on('select2:clear', function() {
                selectedPerson = null;
                updateSelectedPerson();
                resetSamples();
            });
        }

        async function refreshReaders(autoSelect) {
            ui.readerSelect.innerHTML = '';
            var option = document.createElement('option');
            option.value = '';
            option.textContent = 'Seleccione lector';
            ui.readerSelect.appendChild(option);

            try {
                var readers = await client.enumerateReaders();
                if (!readers || readers.length === 0) {
                    setStatus('disconnected');
                    showAlert('warning', 'No se detectaron lectores.');
                    return;
                }
                readers.forEach(function(reader) {
                    var opt = document.createElement('option');
                    opt.value = reader;
                    opt.textContent = 'DigitalPersona (' + reader + ')';
                    ui.readerSelect.appendChild(opt);
                });
                if (readers.length > 0) {
                    ui.readerSelect.value = readers[0];
                }
            } catch (err) {
                setStatus('error');
                showAlert('danger', 'No se pudo enumerar lectores.');
            }
        }

        async function handleSample(sampleData) {
            if (!register || samples.length >= targetCount) return;
            if (lastQuality !== 0) {
                setStatus('waiting');
                var qualityValue = (lastQuality === null || typeof lastQuality === 'undefined') ? '-' : lastQuality;
                showAlert('warning', 'Muestra rechazada. Calidad: ' + qualityValue + ' (' + getQualityLabel(lastQuality) + ').');
                return;
            }
            if (!sampleData) {
                showAlert('warning', 'Muestra vacia.');
                return;
            }
            samples.push(sampleData);
            lastQuality = null;
            updateProgress();

            if (samples.length >= targetCount) {
                register = false;
                client.stopCapture().finally(function() {
                    setButtons(false);
                    setStatus('completed');
                });
            } else {
                setStatus('waiting');
            }
        }

        async function startCapture() {
            userInitiated = true;
            if (sendInProgress) {
                showAlert('warning', 'El enrolamiento se esta enviando. Espere un momento.');
                return;
            }
            if (samples.length >= targetCount) {
                showAlert('warning', 'Ya tienes 4 muestras. Envia o reinicia el proceso.');
                return;
            }
            if (!requireSelection()) return;
            if (!ui.readerSelect.value) {
                await refreshReaders(true);
                if (!ui.readerSelect.value) {
                    showAlert('warning', 'No se detecto un lector disponible.');
                    return;
                }
            }
            if (client.acquisitionStarted) return;
            lastQuality = null;
            register = true;
            setStatus('capturing');
            setButtons(true);
            try {
                await client.startCaptureIntermediate(ui.readerSelect.value);
            } catch (err) {
                register = false;
                setButtons(false);
                setStatus('error');
                showAlert('danger', 'No se pudo iniciar la captura.');
            }
        }

        function resetCapture() {
            register = false;
            client.stopCapture().finally(function() {
                setButtons(false);
                setStatus('connected');
            });
            samples = [];
            sendInProgress = false;
            updateProgress();
        }

        function resetForNextFinger() {
            register = false;
            client.stopCapture().finally(function() {
                setButtons(false);
                setStatus('connected');
            });
            samples = [];
            sendInProgress = false;
            ui.fingerSelect.value = '';
            updateFingerLabel();
            highlightFinger('');
            updateProgress();
        }

        function showSuccessModal() {
            if (window.Swal && typeof window.Swal.fire === 'function') {
                window.Swal.fire({
                    icon: 'success',
                    title: 'Enroll completado',
                    text: '¿Deseas enrolar otro dedo para la misma persona?',
                    showDenyButton: true,
                    confirmButtonColor: '#3366CC',
                    denyButtonColor: '#6c757d',
                    confirmButtonText: 'Otro dedo',
                    denyButtonText: 'Finalizar',
                    customClass: {
                        popup: 'swalAlert'
                    }
                }).then(function(result) {
                    if (result.isConfirmed) {
                        resetForNextFinger();
                    } else if (result.isDenied) {
                        window.location.reload();
                    }
                });
                return;
            }

            if (confirm('Enroll completado. ¿Deseas enrolar otro dedo?')) {
                resetForNextFinger();
                return;
            }
            window.location.reload();
        }

        async function sendPayload() {
            if (sendInProgress) return;
            sendInProgress = true;
            updateProgress();
            if (!requireSelection()) {
                sendInProgress = false;
                updateProgress();
                return;
            }
            if (samples.length !== targetCount) {
                showAlert('warning', 'Aun faltan muestras para completar el enroll.');
                sendInProgress = false;
                updateProgress();
                return;
            }
              var payload = {
                  identificacion: String(selectedPersonIdentificacion()),
                  dedo: selectedFinger(),
                  huellas: samples.slice(),
                  idCreacion: idCreacion,
                  tipo: selectedPersonEvento()
              };

            if (useMockResponse) {
                sendInProgress = false;
                updateProgress();
                showSuccessModal();
                return;
            }

            var csrfToken = document.querySelector('meta[name="csrf-token"]');
            var token = csrfToken ? csrfToken.getAttribute('content') : '';

            try {
                var response = await fetch("{{ route('api.fingerprint.enroll') }}", {
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

                if (!response.ok || !data || data.ok !== true) {
                    showAlert('warning', (data && data.error) ? data.error : 'Servicio no disponible.');
                    sendInProgress = false;
                    updateProgress();
                    return;
                }

                sendInProgress = false;
                updateProgress();
                showSuccessModal();
            } catch (err) {
                showAlert('warning', 'No se pudo contactar el servicio. Payload guardado.');
                sendInProgress = false;
                updateProgress();
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
        };
        client.onDeviceDisconnected = function() {
            setStatus('disconnected');
            setButtons(false);
        };
        client.onCommunicationFailed = function() {
            commFailedCount += 1;
            var elapsed = Date.now() - initialLoadAt;
            if (elapsed < 5000 && commFailedCount <= 2) {
                setStatus('disconnected');
                if (commRetryTimer) {
                    clearTimeout(commRetryTimer);
                }
                commRetryTimer = setTimeout(function() {
                    refreshReaders(true);
                }, 900);
                return;
            }
            setStatus('error');
            if (userInitiated) {
                showAlert('danger', 'Fallo la comunicación con el servicio WebAPI.');
            }
        };
        client.onErrorOccurred = function() {
            setStatus('error');
            showAlert('danger', 'Error del lector.');
        };
        client.onQualityReported = function(e) {
            lastQuality = e ? e.quality : null;
        };
        client.onSamplesAcquired = function(normalized) {
            if (!normalized || normalized.length === 0) return;
            handleSample(normalized[0]);
        };

        ui.fingerSelect.addEventListener('change', function() {
            resetSamples();
            updateFingerLabel();
            highlightFinger(selectedFinger());
        });
        ui.startCapture.addEventListener('click', startCapture);
        ui.resetCapture.addEventListener('click', resetCapture);
        ui.sendPayload.addEventListener('click', sendPayload);

        setStatus('disconnected');
        setButtons(false);
        setTimeout(function() {
            refreshReaders(true);
        }, 900);
        initPersonSelect();
        updateSelectedPerson();
        updateFingerLabel();
        highlightFinger(selectedFinger());
        updateProgress();

        document.querySelectorAll('.finger-hotspot').forEach(function(node) {
            node.addEventListener('click', function() {
                var value = node.getAttribute('data-dedo');
                if (!value) return;
                ui.fingerSelect.value = value;
                resetSamples();
                updateFingerLabel();
                highlightFinger(value);
            });
        });
    });
</script>
@endpush
