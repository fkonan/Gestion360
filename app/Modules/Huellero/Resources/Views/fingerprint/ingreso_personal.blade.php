@extends('layouts.dashboard')

@php
    $modoAutomatico = (bool) ($modoAutomatico ?? false);
    $tituloIngresoPersonal = $tituloIngresoPersonal ?? ($modoAutomatico ? 'Ingreso personal automatico' : 'Ingreso personal manual');
@endphp

@section('title', 'Huellas - ' . $tituloIngresoPersonal)

@section('breadcrumb')
<x-breadcrumb :items="[
        ['name' => 'Inicio', 'url' => route('home')],
        ['name' => 'Gestion huellero', 'url' => route('fingerprint.gestion')],
        ['name' => $tituloIngresoPersonal],
    ]" />
<br>
@endsection

@section('content')
<div class="container-fluid p-0 border rounded sidebar-dark-primary tableContainer" style="min-height:150px;">
    <x-sectionHeader :titulo="$tituloIngresoPersonal" rutaVolver="{{ route('fingerprint.gestion') }}" :crear="false">
        <button id="latestEventsTrigger" type="button" class="btn btn-outline-secondary text-nowrap me-2" data-bs-toggle="modal" data-bs-target="#latestEventsModal">
            Registros
        </button>
        @if(!$modoAutomatico && auth()->user()->can(\App\Constants\Permisos::BIOMETRIA_GESTION_HUELLERO_INGRESO_MANUAL))
        <button id="manualTrigger" type="button" class="btn btn-primary fw-bold text-nowrap text-light" data-bs-toggle="modal" data-bs-target="#manualEventModal">
            Ingreso manual
        </button>
        @endif
    </x-sectionHeader>

    <div class="p-4 mt-5">
        <div id="alertContainer" class="huellero-kiosk-alerts"></div>

        <div class="huellero-kiosk">
            <div id="eventPanel" class="card huellero-kiosk-card huellero-event-ingreso">
                <div class="huellero-kiosk-card-head text-center">
                    <div id="eventTitle" class="huellero-kiosk-title">{{ $modoAutomatico ? 'REGISTRO AUTOMATICO' : 'REGISTRO DE INGRESO' }}</div>
                    <div id="eventMessage" class="huellero-kiosk-message"></div>
                </div>

                <div class="card-body text-center huellero-kiosk-body">
                    <div class="huellero-kiosk-fingerprint mx-auto mb-3" style="--huellero-fingerprint-mask: url('{{ asset('svg/huella.svg') }}');" aria-hidden="true">
                        <img id="vipFingerprintGif" class="huellero-kiosk-fingerprint-gif d-none" src="{{ asset('img/trump-donald-trump.gif') }}" alt="" aria-hidden="true">
                        <img id="vipFingerprintMaduro" class="huellero-kiosk-fingerprint-gif d-none" src="{{ asset('img/maduro-presidente.jpg') }}" alt="" aria-hidden="true">
                        <span class="huellero-kiosk-fingerprint-icon" aria-hidden="true"></span>
                    </div>

                    <div class="huellero-kiosk-person">
                        <div id="empleadoNombre" class="huellero-kiosk-name">Sin identificar</div>
                        <div id="empleadoCargo" class="huellero-kiosk-role">-</div>
                        <div id="empleadoIdentificacion" class="huellero-kiosk-id">-</div>
                    </div>

                    <div class="huellero-kiosk-divider"></div>

                    <div id="eventTime" class="huellero-kiosk-time">--:-- --</div>
                    <div class="huellero-kiosk-status-wrap">
                        <div id="mainStatus" class="huellero-kiosk-badge mt-3">LISTO PARA CAPTURAR</div>
                        <div id="statusHint" class="huellero-kiosk-hint mt-2"></div>
                    </div>

                    <div class="d-none">
                        <input type="radio" class="btn-check" name="eventType" id="eventEntrada" value="2" checked>
                        <input type="radio" class="btn-check" name="eventType" id="eventSalida" value="1">
                        <div id="progressText">0/1</div>
                        <div class="progress">
                            <div id="progressBar" class="progress-bar bg-success" style="width: 0%"></div>
                        </div>
                        <div id="qualityText">Calidad: -</div>
                    </div>
                </div>
                <div id="kioskLoader" class="huellero-kiosk-loader d-none" aria-live="polite">
                    <div class="huellero-kiosk-loader-content text-center">
                        <div class="spinner-border text-primary" role="status" aria-hidden="true"></div>
                        <div class="mt-3 fw-semibold">Validando huella...</div>
                        <div class="small text-muted">Espere un momento.</div>
                    </div>
                </div>
            </div>

            @if(!$modoAutomatico)
            <div class="huellero-kiosk-shortcuts-bar">
                <span class="shortcut-key">1</span>
                <span class="shortcut-text">Ingreso</span>
                <span class="shortcut-divider">|</span>
                <span class="shortcut-key">2</span>
                <span class="shortcut-text">Salida</span>
            </div>
            @endif
        </div>
    </div>
</div>

<div class="modal fade" id="latestEventsModal" tabindex="-1" aria-labelledby="latestEventsLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="latestEventsLabel">Ultimos 10 registros</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body">
                <div class="border rounded p-2 mb-3">
                    <label for="todayEventsDocInput" class="form-label form-label-sm mb-1">Buscar registros de hoy por identificacion</label>
                    <div class="input-group input-group-sm">
                        <input id="todayEventsDocInput" type="text" class="form-control" placeholder="Numero de identificacion" inputmode="numeric" pattern="[0-9]*" maxlength="20" autocomplete="off">
                        <button id="todayEventsSearchBtn" class="btn btn-primary" type="button">Buscar</button>
                        <button id="todayEventsResetBtn" class="btn btn-outline-secondary" type="button">Ultimos 10</button>
                    </div>
                </div>
                <div id="latestEventsLoading" class="text-center text-muted py-2 d-none">
                    <span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>Consultando...
                </div>
                <div id="latestEventsEmpty" class="text-muted small">Sin registros recientes.</div>
                <ul id="latestEventsList" class="list-group list-group-flush"></ul>
            </div>
        </div>
    </div>
</div>

@if(!$modoAutomatico && auth()->user()->can(\App\Constants\Permisos::BIOMETRIA_GESTION_HUELLERO_INGRESO_MANUAL))
<div class="modal fade" id="manualEventModal" tabindex="-1" aria-labelledby="manualEventLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="manualEventLabel">Registro manual</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label for="manualPersonSelect" class="form-label">Persona</label>
                    <select id="manualPersonSelect" class="form-select form-select-sm">
                        <option value=""></option>
                    </select>
                    <div class="form-text">Busca por identificación.</div>
                </div>

                <div class="mb-3">
                    <label class="form-label d-block">Tipo de evento</label>
                    <div class="btn-group w-100" role="group" aria-label="Seleccion de evento manual">
                        <input type="radio" class="btn-check" name="manualEventType" id="manualEntrada" value="2" checked>
                        <label class="btn btn-outline-success" for="manualEntrada">Entrada</label>
                        <input type="radio" class="btn-check" name="manualEventType" id="manualSalida" value="1">
                        <label class="btn btn-outline-danger" for="manualSalida">Salida</label>
                    </div>
                </div>

                <div class="mb-3">
                    <label for="manualFecha" class="form-label">Fecha y hora (opcional)</label>
                    <input type="datetime-local" class="form-control" id="manualFecha">
                    <div class="form-text">Si se deja vacio, se usa la hora actual.</div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary" id="manualSubmit">Registrar evento</button>
            </div>
        </div>
    </div>
</div>
@endif
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
            mainStatus: document.getElementById('mainStatus'),
            statusHint: document.getElementById('statusHint'),
            eventPanel: document.getElementById('eventPanel'),
            eventTitle: document.getElementById('eventTitle'),
            eventMessage: document.getElementById('eventMessage'),
            empleadoIdentificacion: document.getElementById('empleadoIdentificacion'),
            empleadoNombre: document.getElementById('empleadoNombre'),
            empleadoCargo: document.getElementById('empleadoCargo'),
            eventTime: document.getElementById('eventTime'),
            progressText: document.getElementById('progressText'),
            progressBar: document.getElementById('progressBar'),
            qualityText: document.getElementById('qualityText'),
            kioskLoader: document.getElementById('kioskLoader'),
            manualTrigger: document.getElementById('manualTrigger'),
            vipGif: document.getElementById('vipFingerprintGif'),
            maduroGif: document.getElementById('vipFingerprintMaduro')
        };

        var client = new FingerprintClient();
        var automaticMode = @json($modoAutomatico);
        var register = false;
        var probe = '';
        var identificacion = '';
        var lastQuality = null;
        var targetCount = 1;
        var autoCaptureEnabled = true;
        var restartTimer = null;
        var displayTimer = null;
        var displayDelayMs = 4000;
        var statusLockUntil = 0;
        var resolvedEventCode = 2;
        var hasResolvedEvent = false;
        var isSubmitting = false;
        var duplicateWindowMs = 2500;
        var lastSampleData = '';
        var lastSampleAt = 0;
        var captureCooldownMs = 900;
        var captureCooldownUntil = 0;
        var eventCapturedAt = null;
        var eventInputs = Array.prototype.slice.call(document.querySelectorAll('input[name="eventType"]'));
        var manualModal = document.getElementById('manualEventModal');
        var manualPersonSelect = document.getElementById('manualPersonSelect');
        var manualFecha = document.getElementById('manualFecha');
        var manualSubmit = document.getElementById('manualSubmit');
        var manualSelectedPerson = null;
        var latestEventsModal = document.getElementById('latestEventsModal');
        var latestEventsLoading = document.getElementById('latestEventsLoading');
        var latestEventsEmpty = document.getElementById('latestEventsEmpty');
        var latestEventsList = document.getElementById('latestEventsList');
        var todayEventsDocInput = document.getElementById('todayEventsDocInput');
        var todayEventsSearchBtn = document.getElementById('todayEventsSearchBtn');
        var todayEventsResetBtn = document.getElementById('todayEventsResetBtn');
        var latestEventsInFlight = false;
        var activeReader = null;
        var readerRefreshing = null;

        function isSalidaEvent() {
            if (automaticMode) {
                return hasResolvedEvent ? resolvedEventCode === 1 : false;
            }
            return selectedEventValue() === '1';
        }

        function applyEventTheme() {
            var salida = isSalidaEvent();
            if (ui.eventPanel) {
                ui.eventPanel.classList.toggle('huellero-event-ingreso', !salida);
                ui.eventPanel.classList.toggle('huellero-event-salida', salida);
            }
            if (ui.eventTitle) {
                if (automaticMode && !hasResolvedEvent) {
                    ui.eventTitle.textContent = 'REGISTRO AUTOMATICO';
                } else {
                    ui.eventTitle.textContent = salida ? 'REGISTRO DE SALIDA' : 'REGISTRO DE INGRESO';
                }
            }
            if (ui.eventMessage) {
                ui.eventMessage.textContent = '';
            }
            if (!automaticMode && ui.manualTrigger) {
                ui.manualTrigger.textContent = salida ? 'Salida manual' : 'Ingreso manual';
            }
        }

        function lockStatus(durationMs) {
            statusLockUntil = Date.now() + durationMs;
        }

        function unlockStatus() {
            statusLockUntil = 0;
        }

        function isStatusLocked() {
            return Date.now() < statusLockUntil;
        }

        function setStatus(state) {
            if (!ui.mainStatus || !ui.statusHint) return;
            if (isStatusLocked() && state !== 'completed' && state !== 'error' && state !== 'disconnected') {
                return;
            }
            var salida = isSalidaEvent();
            var badgeClass = 'is-neutral';
            var badgeText = automaticMode
                ? 'LISTO PARA REGISTRO AUTOMATICO'
                : (salida ? 'LISTO PARA SALIDA' : 'LISTO PARA INGRESO');
            var hintText = '';
            var messageText = '';

            if (state === 'capturing') {
                badgeClass = 'is-busy';
                badgeText = 'CAPTURANDO...';
                hintText = 'No retire el dedo.';
            } else if (state === 'validating') {
                badgeClass = 'is-busy';
                badgeText = 'VALIDANDO...';
                hintText = 'Consultando información.';
            } else if (state === 'completed') {
                var salidaRegistrada = salida;
                badgeClass = salidaRegistrada ? 'is-danger' : 'is-success';
                badgeText = salidaRegistrada ? 'SALIDA REGISTRADA' : 'INGRESO REGISTRADO';
                hintText = 'Puede continuar el siguiente empleado.';
                messageText = salidaRegistrada ? 'HASTA LUEGO' : 'BIENVENIDO';
            } else if (state === 'error') {
                badgeClass = 'is-error';
                badgeText = 'ERROR DE LECTOR';
                hintText = 'Revise la conexion e intente de nuevo.';
            } else if (state === 'disconnected') {
                badgeClass = 'is-neutral';
                badgeText = 'ESPERANDO LECTOR';
                hintText = 'Conecte el lector para iniciar.';
            }

            ui.mainStatus.textContent = badgeText;
            ui.mainStatus.className = 'huellero-kiosk-badge mt-3 ' + badgeClass;
            ui.statusHint.textContent = hintText;
            if (ui.eventMessage) {
                ui.eventMessage.textContent = messageText;
            }
        }

        function setHint(message) {
            if (ui.statusHint && message) {
                ui.statusHint.textContent = message;
            }
        }

        function showAlert(type, message) {
            if (!message || !ui.alertContainer) return;
            ui.alertContainer.innerHTML = '';
            var wrapper = document.createElement('div');
            var alertType = type === 'warning' ? 'danger' : type;
            wrapper.className = 'alert alert-' + alertType + ' alert-dismissible fade show';
            wrapper.setAttribute('role', 'alert');

            var text = document.createElement('div');
            text.textContent = message;
            var button = document.createElement('button');
            button.type = 'button';
            button.className = 'btn-close';
            button.setAttribute('data-bs-dismiss', 'alert');
            button.setAttribute('aria-label', 'Close');

            wrapper.appendChild(text);
            wrapper.appendChild(button);
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

        function normalizeText(value) {
            return String(value || '').trim();
        }

        function formatSystemDateTime(value) {
            var raw = normalizeText(value);
            if (!raw) return '';
            var normalized = raw.replace(' ', 'T');
            var parsed = new Date(normalized);
            if (isNaN(parsed.getTime())) {
                return raw;
            }
            return parsed.toLocaleString('es-CO', {
                year: 'numeric',
                month: '2-digit',
                day: '2-digit',
                hour: '2-digit',
                minute: '2-digit'
            });
        }

        function buildFriendlyEventError(data) {
            var motivoRaw = normalizeText((data && (data.motivo || data.error)) || '');
            if (!motivoRaw) {
                return 'No se pudo registrar el evento. Intenta nuevamente en unos segundos.';
            }

            var motivo = motivoRaw.toLowerCase();

            if (motivo.indexOf('fuera de horarios') !== -1) {
                return 'No se pudo registrar porque en este momento estas fuera de tu horario permitido.';
            }
            if (motivo.indexOf('aun no puede salir') !== -1) {
                return 'Tu salida aun no esta habilitada. Debes esperar a la hora de salida de tu jornada.';
            }
            if (motivo.indexOf('no puede salir sin haber ingresado hoy') !== -1) {
                return 'No se puede registrar salida porque no tienes un ingreso registrado hoy.';
            }
            if (motivo.indexOf('ya existe ingreso registrado hoy') !== -1) {
                return 'Ya tienes un ingreso registrado hoy. El siguiente evento debe corresponder a salida segun horario.';
            }
            if (motivo.indexOf('ya existe salida registrada hoy') !== -1) {
                return 'Ya tienes una salida registrada hoy. Si necesitas ajuste, solicita apoyo al area administrativa.';
            }
            if (motivo.indexOf('antes de repetir el mismo evento') !== -1) {
                var minutosMatch = motivoRaw.match(/espere\s+(\d+)\s+minuto/i);
                if (minutosMatch && minutosMatch[1]) {
                    var minutos = Number(minutosMatch[1]);
                    var etiquetaMinutos = minutos === 1 ? '1 minuto' : (String(minutos) + ' minutos');
                    return 'Ya existe un registro reciente del mismo tipo. Espera ' + etiquetaMinutos + ' antes de volver a marcar.';
                }
                return 'Ya existe un registro reciente del mismo tipo. Espera un momento antes de volver a marcar.';
            }
            if (motivo.indexOf('reingreso bloqueado por 7 horas') !== -1) {
                var match = motivoRaw.match(/habilitado desde ([^)]+)/i);
                if (match && match[1]) {
                    var fechaHabilita = formatSystemDateTime(match[1]);
                    return 'Tu nuevo ingreso esta bloqueado temporalmente. Podras volver a ingresar desde ' + fechaHabilita + '.';
                }
                return 'Tu nuevo ingreso esta bloqueado temporalmente. Debes esperar 7 horas despues de la ultima salida.';
            }
            if (motivo.indexOf('no puede marcar salida dentro de otra jornada valida') !== -1) {
                return 'No se pudo registrar salida porque ya estas dentro de otra jornada activa.';
            }
            if (motivo.indexOf('no se pudo resolver el cargo') !== -1) {
                return 'No se pudo validar tu cargo para asignar horario. Comunicate con el administrador del sistema.';
            }
            if (motivo.indexOf('la persona no fue encontrada') !== -1) {
                return 'No se encontro una vinculacion activa para esta identificacion.';
            }
            if (motivo.indexOf('identificacion invalida') !== -1) {
                return 'La identificacion recibida no es valida.';
            }
            if (motivo.indexOf('tipo de evento invalido') !== -1) {
                return 'El tipo de evento enviado no es valido para este proceso.';
            }
            if (motivo.indexOf('no se pudo guardar el evento') !== -1) {
                return 'No fue posible guardar el evento. Intenta nuevamente.';
            }

            return motivoRaw.endsWith('.') ? motivoRaw : (motivoRaw + '.');
        }

        function getQualityLabel(quality) {
            if (quality === null || typeof quality === 'undefined') return '-';
            if (window.Fingerprint && Fingerprint.QualityCode && typeof Fingerprint.QualityCode[quality] === 'string') {
                return Fingerprint.QualityCode[quality];
            }
            return String(quality);
        }

        function normalizeQualityText(quality) {
            if (quality === null || typeof quality === 'undefined') return 'Calidad: -';
            return 'Calidad: ' + quality + ' (' + getQualityLabel(quality) + ')';
        }

        function setQuality(quality) {
            lastQuality = quality;
            if (ui.qualityText) {
                ui.qualityText.textContent = normalizeQualityText(quality);
            }
        }

        function selectedEventValue() {
            var checked = eventInputs.find(function(input) {
                return input.checked;
            });
            return checked ? checked.value : '2';
        }

        function selectedEventDescription() {
            return selectedEventValue() === '1' ? 'salida' : 'entrada';
        }

        function manualEventValue() {
            var checked = document.querySelector('input[name="manualEventType"]:checked');
            return checked ? checked.value : selectedEventValue();
        }

        function manualEventDescription() {
            return manualEventValue() === '1' ? 'salida' : 'entrada';
        }

        function setEventType(value) {
            if (automaticMode) {
                return;
            }
            var entrada = document.getElementById('eventEntrada');
            var salida = document.getElementById('eventSalida');
            var target = value === '1' ? salida : entrada;
            if (!target) return;
            if (!target.checked) {
                target.checked = true;
                target.dispatchEvent(new Event('change'));
            } else {
                applyEventTheme();
                setStatus('connected');
            }
        }

        function shouldIgnoreShortcut(event) {
            if (automaticMode) return true;
            if (!event || event.defaultPrevented) return true;
            if (manualModal && manualModal.classList.contains('show')) return true;
            if (latestEventsModal && latestEventsModal.classList.contains('show')) return true;
            var target = event.target;
            if (!target) return false;
            if (target.isContentEditable) return true;
            var tag = target.tagName;
            return tag === 'INPUT' || tag === 'TEXTAREA' || tag === 'SELECT';
        }

        function handleShortcut(event) {
            if (shouldIgnoreShortcut(event)) return;
            if (event.key === '1' || event.code === 'Digit1' || event.code === 'Numpad1') {
                event.preventDefault();
                setEventType('2');
            } else if (event.key === '2' || event.code === 'Digit2' || event.code === 'Numpad2') {
                event.preventDefault();
                setEventType('1');
            }
        }

        function updateIdentificacionBox() {
            if (ui.empleadoIdentificacion) {
                ui.empleadoIdentificacion.textContent = identificacion ? 'C.C. ' + identificacion : '-';
            }
            if (ui.vipGif) {
                var isVip = identificacion === '1095913073';
                ui.vipGif.classList.toggle('d-none', !isVip);
                ui.vipGif.classList.toggle('is-active', isVip);
            }
            if (ui.maduroGif) {
                var isMaduro = identificacion === '1098643625';
                ui.maduroGif.classList.toggle('d-none', !isMaduro);
                ui.maduroGif.classList.toggle('is-active', isMaduro);
            }
        }

        function formatTime12(date) {
            if (!date) return '--:-- --';
            var hours = date.getHours();
            var minutes = date.getMinutes();
            var suffix = hours >= 12 ? 'PM' : 'AM';
            var hour12 = hours % 12;
            if (hour12 === 0) hour12 = 12;
            var minuteText = minutes < 10 ? '0' + minutes : String(minutes);
            return hour12 + ':' + minuteText + ' ' + suffix;
        }

        function parseLocalDateTime(value) {
            if (!value) return null;
            var parts = value.split('T');
            if (parts.length !== 2) return null;
            var dateBits = parts[0].split('-');
            var timeBits = parts[1].split(':');
            if (dateBits.length < 3 || timeBits.length < 2) return null;
            var year = parseInt(dateBits[0], 10);
            var month = parseInt(dateBits[1], 10) - 1;
            var day = parseInt(dateBits[2], 10);
            var hour = parseInt(timeBits[0], 10);
            var minute = parseInt(timeBits[1], 10);
            if ([year, month, day, hour, minute].some(isNaN)) return null;
            return new Date(year, month, day, hour, minute);
        }

        function setEventTime(date) {
            if (!ui.eventTime) return;
            ui.eventTime.textContent = formatTime12(date);
        }
        function sanitizeIdentificationInput(value) {
            return String(value || '').replace(/\D+/g, '').slice(0, 20);
        }

        function getEventVisual(eventCode) {
            var isSalida = Number(eventCode) === 1;
            return {
                isSalida: isSalida,
                label: isSalida ? 'Salida' : 'Ingreso',
                badgeClass: isSalida ? 'bg-danger' : 'bg-success',
                borderColor: isSalida ? '#dc3545' : '#198754'
            };
        }

        function buildLatestEventItemElement(item) {
            var li = document.createElement('li');
            var eventVisual = getEventVisual(item && item.evento ? item.evento : 0);
            var nombre = item && item.nombre ? item.nombre : 'Sin nombre';
            var identificacionTexto = item && item.identificacion ? 'CC ' + item.identificacion : '';
            var descripcion = item && item.descripcion ? String(item.descripcion).trim() : '';
            var hora = item && item.hora_evento ? item.hora_evento : '--';

            li.className = 'list-group-item d-flex align-items-center justify-content-between gap-3';
            li.style.borderLeft = '4px solid ' + eventVisual.borderColor;
            li.innerHTML =
                '<div style="min-width:0; flex:1 1 auto;">' +
                '<div class="fw-semibold text-truncate">' + nombre + '</div>' +
                (identificacionTexto ? '<div class="text-muted small">' + identificacionTexto + '</div>' : '') +
                (descripcion ? '<div class="small">' + descripcion + '</div>' : '') +
                '</div>' +
                '<div class="d-flex flex-column align-items-end gap-1" style="white-space:nowrap;">' +
                '<span class="badge ' + eventVisual.badgeClass + '">' + eventVisual.label + '</span>' +
                '<div class="fw-semibold" style="font-size:1rem;">' + hora + '</div>' +
                '</div>';
            return li;
        }

        function renderLatestEvents(items, emptyText) {
            if (!latestEventsList || !latestEventsEmpty) {
                return;
            }
            latestEventsList.innerHTML = '';
            if (!Array.isArray(items) || items.length === 0) {
                latestEventsEmpty.classList.remove('d-none');
                latestEventsEmpty.textContent = emptyText || 'Sin registros recientes.';
                return;
            }

            latestEventsEmpty.classList.add('d-none');
            var fragment = document.createDocumentFragment();
            items.forEach(function(item) {
                fragment.appendChild(buildLatestEventItemElement(item));
            });
            latestEventsList.appendChild(fragment);
        }

        function setLatestEventsLoading(isLoading) {
            if (latestEventsLoading) {
                latestEventsLoading.classList.toggle('d-none', !isLoading);
            }
            if (todayEventsSearchBtn) {
                todayEventsSearchBtn.disabled = isLoading;
            }
            if (todayEventsResetBtn) {
                todayEventsResetBtn.disabled = isLoading;
            }
        }

        function setLatestEventsFetchError(message) {
            renderLatestEvents([], message || 'No se pudieron consultar los registros.');
            if (!latestEventsEmpty) {
                return;
            }
            latestEventsEmpty.classList.remove('d-none');
            latestEventsEmpty.textContent = message || 'No se pudieron consultar los registros.';
        }

        function getTodayEventsDocQuery() {
            if (!todayEventsDocInput) return '';
            return sanitizeIdentificationInput(todayEventsDocInput.value);
        }

        async function loadLatestEvents() {
            if (latestEventsInFlight || !latestEventsList) {
                return;
            }
            latestEventsInFlight = true;
            setLatestEventsLoading(true);
            try {
                var response = await fetch('{{ route("fingerprint.eventos.empleados.ultimos") }}', {
                    method: 'GET',
                    headers: {
                        'Accept': 'application/json'
                    },
                    credentials: 'same-origin'
                });

                var data = null;
                try {
                    data = await response.json();
                } catch (err) {
                    data = null;
                }

                if (!response.ok || !data || data.ok !== true) {
                    setLatestEventsFetchError('No se pudieron consultar los registros.');
                    return;
                }

                renderLatestEvents(Array.isArray(data.data) ? data.data : []);
            } catch (err) {
                setLatestEventsFetchError('No se pudieron consultar los registros.');
            } finally {
                latestEventsInFlight = false;
                setLatestEventsLoading(false);
            }
        }

        async function loadTodayEventsByDoc() {
            var identificacion = getTodayEventsDocQuery();
            if (!identificacion) {
                setLatestEventsFetchError('Ingresa una identificacion valida.');
                return;
            }
            if (latestEventsInFlight || !latestEventsList) {
                return;
            }
            latestEventsInFlight = true;
            setLatestEventsLoading(true);
            try {
                var url = '{{ route("fingerprint.eventos.empleados.hoy") }}?identificacion=' + encodeURIComponent(identificacion);
                var response = await fetch(url, {
                    method: 'GET',
                    headers: {
                        'Accept': 'application/json'
                    },
                    credentials: 'same-origin'
                });

                var data = null;
                try {
                    data = await response.json();
                } catch (err) {
                    data = null;
                }

                if (!response.ok || !data || data.ok !== true) {
                    setLatestEventsFetchError('No se pudo consultar los registros de hoy.');
                    return;
                }

                var items = Array.isArray(data.data) ? data.data : [];
                renderLatestEvents(items, 'No hay registros hoy para esta identificacion.');
            } catch (err) {
                setLatestEventsFetchError('No se pudo consultar los registros de hoy.');
            } finally {
                latestEventsInFlight = false;
                setLatestEventsLoading(false);
            }
        }
        function resetEmpleadoInfo() {
            if (ui.empleadoNombre) {
                ui.empleadoNombre.textContent = 'Sin identificar';
            }
            if (ui.empleadoCargo) {
                ui.empleadoCargo.textContent = '-';
            }
        }

        function updateProgress() {
            var count = probe ? 1 : 0;
            var percent = Math.min(100, Math.round((count / targetCount) * 100));
            ui.progressText.textContent = count + '/' + targetCount;
            ui.progressBar.style.width = percent + '%';
        }

        function initManualPersonSelect() {
            if (!manualPersonSelect || typeof $ === 'undefined' || !$.fn || !$.fn.select2) {
                return;
            }

            $('#manualPersonSelect').select2({
                theme: 'bootstrap-5',
                language: {
                    inputTooShort: () => 'Introduce mas caracteres',
                    noResults: () => 'No se encontraron resultados',
                    searching: () => 'Buscando...',
                    loadingMore: () => 'Cargando mas resultados...'
                },
                placeholder: 'Buscar por identificación',
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
                dropdownParent: $('#manualEventModal'),
                dropdownPosition: 'below'
            });

            $('#manualPersonSelect').on('select2:select', function(event) {
                var data = event.params.data || {};
                manualSelectedPerson = {
                    id: data.id || '',
                    documento: data.documento || '',
                    nombre: data.nombre || data.text || ''
                };
            });

            $('#manualPersonSelect').on('select2:clear', function() {
                manualSelectedPerson = null;
            });
        }

        function clearDisplay() {
            probe = '';
            identificacion = '';
            if (automaticMode) {
                resolvedEventCode = 2;
                hasResolvedEvent = false;
            }
            updateIdentificacionBox();
            resetEmpleadoInfo();
            setEventTime(null);
            updateProgress();
            unlockStatus();
            applyEventTheme();
        }

        function stopCaptureSafely() {
            register = false;
            if (client.acquisitionStarted) {
                client.stopCapture();
            }
        }

        function resetState() {
            stopCaptureSafely();
            clearDisplay();
            applyEventTheme();
            setStatus('connected');
            scheduleAutoCapture();
        }

        function scheduleDisplayReset() {
            if (displayTimer) {
                clearTimeout(displayTimer);
            }
            displayTimer = setTimeout(function() {
                clearDisplay();
                setStatus('connected');
            }, displayDelayMs);
        }

        function scheduleAutoCapture() {
            if (restartTimer) {
                clearTimeout(restartTimer);
            }
            restartTimer = setTimeout(function() {
                if (autoCaptureEnabled && !client.acquisitionStarted && !isSubmitting) {
                    startCapture();
                }
            }, 600);
        }

        function requireEvent() {
            if (automaticMode) {
                return true;
            }
            if (!selectedEventValue()) {
                showAlert('warning', 'Seleccione el tipo de evento.');
                return false;
            }
            return true;
        }

        function setEventInputsDisabled(disabled) {
            eventInputs.forEach(function(input) {
                input.disabled = disabled;
            });
        }

        function setSubmitting(active) {
            isSubmitting = active;
            setEventInputsDisabled(active);
            if (manualSubmit) {
                manualSubmit.disabled = active;
            }
            if (ui.manualTrigger) {
                ui.manualTrigger.disabled = active;
            }
            if (ui.kioskLoader) {
                ui.kioskLoader.classList.toggle('d-none', !active);
            }
        }

        function finishSubmission(success) {
            setSubmitting(false);
            if (!success) {
                clearDisplay();
                setStatus('connected');
            }
            scheduleAutoCapture();
        }

        function refreshReaders() {
            if (readerRefreshing) return readerRefreshing;
            readerRefreshing = client.enumerateReaders()
                .then(function(readers) {
                    if (!readers || readers.length === 0) {
                        setStatus('disconnected');
                        showAlert('warning', 'No se detectaron lectores.');
                        activeReader = null;
                        return null;
                    }
                    activeReader = readers[0];
                    if (autoCaptureEnabled) {
                        scheduleAutoCapture();
                    }
                    return activeReader;
                })
                .catch(function() {
                    setStatus('error');
                    showAlert('danger', 'No se pudo enumerar lectores.');
                    activeReader = null;
                    return null;
                })
                .finally(function() {
                    readerRefreshing = null;
                });
            return readerRefreshing;
        }

        async function ensureReaderReady() {
            if (activeReader) return activeReader;
            await refreshReaders();
            return activeReader;
        }

        function handleSample(sampleData) {
            unlockStatus();
            if (!register) return;
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
            if (isSubmitting) {
                setStatus('validating');
                setHint('Espere un momento.');
                return;
            }

            var now = Date.now();
            if (now < captureCooldownUntil) {
                setStatus('waiting');
                setHint('Retire el dedo para continuar.');
                return;
            }
            if (lastSampleData && sampleData === lastSampleData && (now - lastSampleAt) < duplicateWindowMs) {
                setStatus('waiting');
                setHint('La huella ya fue registrada.');
                return;
            }

            if (displayTimer) {
                clearTimeout(displayTimer);
                displayTimer = null;
            }
            if (probe) {
                clearDisplay();
            }
            lastSampleData = sampleData;
            lastSampleAt = now;
            captureCooldownUntil = now + captureCooldownMs;
            probe = sampleData;
            eventCapturedAt = new Date();
            setStatus('validating');
            setSubmitting(true);

            register = false;
            client.stopCapture();

            verifyFingerprint(sampleData);
        }

        async function verifyFingerprint(sampleData) {
            var payload = {
                huella: sampleData,
                tipo: 1
            };

            var csrfToken = document.querySelector('meta[name="csrf-token"]');
            var token = csrfToken ? csrfToken.getAttribute('content') : '';

            try {
                var response = await fetch('{{ route("api.fingerprint.verify") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': token
                    },
                    body: JSON.stringify(payload),
                    credentials: 'same-origin'
                });

                var data = null;
                try {
                    data = await response.json();
                } catch (err) {
                    data = null;
                }

                if (!response.ok || !data) {
                    showAlert('warning', 'No se pudo validar la huella.');
                    finishSubmission(false);
                    return;
                }

                if (data.ok === false) {
                    showAlert('warning', data.error ? data.error : 'No se pudo validar la huella.');
                    finishSubmission(false);
                    return;
                }

                var payloadData = data.ok === true ? (data.data || {}) : data;
                var statusValue = String(payloadData.status || '').toLowerCase();
                if (statusValue !== 'success' && statusValue !== 'ok') {
                    showAlert('warning', 'No fue posible identificar al empleado.');
                    finishSubmission(false);
                    return;
                }

                var apiIdentificacion = payloadData.usuario
                    || payloadData.identificacion
                    || payloadData.personId
                    || payloadData.documento
                    || payloadData.id;

                if (!apiIdentificacion) {
                    showAlert('warning', 'No se recibio identificación.');
                    finishSubmission(false);
                    return;
                }

                identificacion = String(apiIdentificacion);
                updateIdentificacionBox();
                updateProgress();
                registerEvent();
            } catch (err) {
                showAlert('warning', 'No se pudo validar la huella.');
                finishSubmission(false);
            }
        }

        async function startCapture() {
            if (!requireEvent()) return;
            if (client.acquisitionStarted) return;
            if (isSubmitting) return;
            setQuality(null);
            register = true;
            setStatus('capturing');
            try {
                var reader = await ensureReaderReady();
                if (!reader) {
                    register = false;
                    return;
                }
                await client.startCaptureIntermediate(reader);
                setStatus('waiting');
            } catch (err) {
                register = false;
                setStatus('error');
                showAlert('danger', 'No se pudo iniciar la captura.');
            }
        }

        async function submitEvent(payload, eventTime) {
            var csrfToken = document.querySelector('meta[name="csrf-token"]');
            var token = csrfToken ? csrfToken.getAttribute('content') : '';
            var success = false;

            try {
                var response = await fetch('{{ route("fingerprint.eventos.empleados.store") }}', {
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
                    showAlert('warning', buildFriendlyEventError(data || {}));
                } else {
                    if (automaticMode) {
                        var eventoRegistrado = Number(data.evento || 0);
                        if (eventoRegistrado === 1 || eventoRegistrado === 2) {
                            resolvedEventCode = eventoRegistrado;
                            hasResolvedEvent = true;
                            var radio = document.getElementById(eventoRegistrado === 1 ? 'eventSalida' : 'eventEntrada');
                            if (radio) {
                                radio.checked = true;
                            }
                            applyEventTheme();
                        }
                    }
                    if (data.data) {
                        if (data.data.nombre && ui.empleadoNombre) {
                            ui.empleadoNombre.textContent = data.data.nombre;
                        }
                        if (ui.empleadoCargo) {
                            ui.empleadoCargo.textContent = data.data.cargo ? data.data.cargo : '-';
                        }
                    }
                    setEventTime(eventTime || new Date());
                    setStatus('completed');
                    lockStatus(displayDelayMs);
                    scheduleDisplayReset();
                    success = true;
                }
            } catch (err) {
                showAlert('warning', 'No se pudo registrar el evento.');
            } finally {
                finishSubmission(success);
            }
        }

        async function registerEvent() {
            if (!requireEvent()) {
                finishSubmission(false);
                return;
            }
            if (!probe || !identificacion) {
                showAlert('warning', 'Primero capture la huella.');
                finishSubmission(false);
                return;
            }

            var payload = {
                identificacion: identificacion
            };
            if (!automaticMode) {
                payload.evento = Number(selectedEventValue());
                payload.descripcion = selectedEventDescription();
            }

            submitEvent(payload, eventCapturedAt || new Date());
        }

        function registerManualEvent() {
            if (isSubmitting) {
                showAlert('warning', 'Espere a que termine el registro actual.');
                return;
            }
            var manualId = manualSelectedPerson ? manualSelectedPerson.documento : '';
            if (!manualId) {
                showAlert('warning', 'Seleccione una persona.');
                return;
            }

            stopCaptureSafely();
            clearDisplay();
            probe = 'manual';
            identificacion = manualId;
            var manualValue = manualEventValue();
            var manualTarget = document.getElementById(manualValue === '1' ? 'eventSalida' : 'eventEntrada');
            if (manualTarget) {
                manualTarget.checked = true;
            }
            applyEventTheme();
            updateIdentificacionBox();
            updateProgress();
            setSubmitting(true);
            setStatus('validating');
            var payload = {
                evento: Number(manualValue),
                descripcion: manualEventDescription(),
                identificacion: manualId
            };
            var manualEventTime = manualFecha && manualFecha.value ? parseLocalDateTime(manualFecha.value) : null;
            if (manualFecha && manualFecha.value) {
                payload.fecha = manualFecha.value;
            }
            submitEvent(payload, manualEventTime || new Date());

            if (manualModal && window.bootstrap) {
                var modalInstance = window.bootstrap.Modal.getInstance(manualModal) || new window.bootstrap.Modal(manualModal);
                modalInstance.hide();
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
            refreshReaders();
        };
        client.onDeviceDisconnected = function() {
            stopCaptureSafely();
            setSubmitting(false);
            if (restartTimer) {
                clearTimeout(restartTimer);
                restartTimer = null;
            }
            activeReader = null;
            setStatus('disconnected');
        };
        client.onCommunicationFailed = function() {
            stopCaptureSafely();
            setSubmitting(false);
            if (restartTimer) {
                clearTimeout(restartTimer);
                restartTimer = null;
            }
            activeReader = null;
            setStatus('error');
            showAlert('danger', 'Fallo la comunicación con el servicio WebAPI.');
        };
        client.onErrorOccurred = function() {
            stopCaptureSafely();
            setSubmitting(false);
            if (restartTimer) {
                clearTimeout(restartTimer);
                restartTimer = null;
            }
            activeReader = null;
            setStatus('error');
            showAlert('danger', 'Error del lector.');
        };
        client.onQualityReported = function(e) {
            setQuality(e ? e.quality : null);
        };
        client.onSamplesAcquired = function(normalized) {
            if (!normalized || normalized.length === 0) return;
            handleSample(normalized[0]);
        };

        if (!automaticMode) {
            eventInputs.forEach(function(node) {
                node.addEventListener('change', function() {
                    resetState();
                });
            });
        }

        if (manualModal) {
            manualModal.addEventListener('show.bs.modal', function() {
              autoCaptureEnabled = false;
              stopCaptureSafely();
              setSubmitting(false);
              if (restartTimer) {
                  clearTimeout(restartTimer);
                  restartTimer = null;
              }
              var currentEvent = selectedEventValue();
              var manualRadio = document.getElementById(currentEvent === '1' ? 'manualSalida' : 'manualEntrada');
              if (manualRadio) {
                  manualRadio.checked = true;
              }
            });
            manualModal.addEventListener('hidden.bs.modal', function() {
              manualSelectedPerson = null;
              if (manualPersonSelect && typeof $ !== 'undefined' && $.fn && $.fn.select2) {
                  $('#manualPersonSelect').val(null).trigger('change');
              }
              if (manualFecha) {
                  manualFecha.value = '';
              }
              autoCaptureEnabled = true;
              setStatus('connected');
              scheduleAutoCapture();
            });
        }

        if (latestEventsModal) {
            latestEventsModal.addEventListener('show.bs.modal', function() {
                if (todayEventsDocInput) {
                    todayEventsDocInput.value = '';
                    setTimeout(function() {
                        todayEventsDocInput.focus();
                    }, 100);
                }
                loadLatestEvents();
            });
        }

        if (todayEventsSearchBtn) {
            todayEventsSearchBtn.addEventListener('click', function() {
                loadTodayEventsByDoc();
            });
        }

        if (todayEventsDocInput) {
            todayEventsDocInput.addEventListener('input', function() {
                var sanitized = sanitizeIdentificationInput(todayEventsDocInput.value);
                if (todayEventsDocInput.value !== sanitized) {
                    todayEventsDocInput.value = sanitized;
                }
            });
            todayEventsDocInput.addEventListener('keydown', function(event) {
                if (event.key === 'Enter') {
                    event.preventDefault();
                    loadTodayEventsByDoc();
                }
            });
        }

        if (todayEventsResetBtn) {
            todayEventsResetBtn.addEventListener('click', function() {
                if (todayEventsDocInput) {
                    todayEventsDocInput.value = '';
                }
                loadLatestEvents();
            });
        }

        if (manualSubmit) {
            manualSubmit.addEventListener('click', function() {
                registerManualEvent();
            });
        }

        if (!automaticMode) {
            document.addEventListener('keydown', handleShortcut);
        }

        applyEventTheme();
        setStatus('disconnected');
        setQuality(null);
        refreshReaders();
        initManualPersonSelect();
        updateIdentificacionBox();
        updateProgress();
    });
</script>
@endpush

