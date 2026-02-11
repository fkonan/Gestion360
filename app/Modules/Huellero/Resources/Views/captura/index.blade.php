@extends('layouts.app')

@section('title', 'Captura de Huellas - Sistema Huellero')

@push('styles')
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
<style>
    .fingerprint-preview {
        width: 300px;
        height: 300px;
        border: 2px dashed #ddd;
        border-radius: 8px;
        display: flex;
        align-items: center;
        justify-content: center;
        background-color: #f8f9fa;
        margin: 0 auto;
        overflow: hidden;
    }

    .fingerprint-preview img {
        max-width: 100%;
        max-height: 100%;
        object-fit: contain;
    }

    .fingerprint-preview.active {
        border-color: #007bff;
        background-color: #e3f2fd;
    }

    .quality-indicator {
        width: 100%;
        height: 8px;
        background-color: #e9ecef;
        border-radius: 4px;
        overflow: hidden;
    }

    .quality-bar {
        height: 100%;
        transition: width 0.3s ease, background-color 0.3s ease;
    }

    .quality-excellent { background-color: #28a745; }
    .quality-good { background-color: #17a2b8; }
    .quality-fair { background-color: #ffc107; }
    .quality-poor { background-color: #dc3545; }

    .device-selector {
        border: 1px solid #dee2e6;
        border-radius: 8px;
        padding: 15px;
        margin-bottom: 20px;
    }

    .device-selector.selected {
        border-color: #007bff;
        background-color: #f8f9fa;
    }

    .capture-controls {
        display: flex;
        gap: 10px;
        justify-content: center;
        margin: 20px 0;
    }

    .finger-selection {
        display: grid;
        grid-template-columns: repeat(5, 1fr);
        gap: 8px;
        margin: 15px 0;
    }

    .finger-btn {
        padding: 8px 4px;
        font-size: 12px;
        text-align: center;
        border: 1px solid #dee2e6;
        background: white;
        border-radius: 4px;
        cursor: pointer;
        transition: all 0.2s;
    }

    .finger-btn:hover {
        background-color: #e9ecef;
    }

    .finger-btn.selected {
        background-color: #007bff;
        color: white;
        border-color: #007bff;
    }

    .capture-status {
        text-align: center;
        padding: 10px;
        margin: 10px 0;
        border-radius: 4px;
        font-weight: 500;
    }

    .status-waiting {
        background-color: #fff3cd;
        color: #856404;
        border: 1px solid #ffeaa7;
    }

    .status-capturing {
        background-color: #d1ecf1;
        color: #0c5460;
        border: 1px solid #bee5eb;
    }

    .status-success {
        background-color: #d4edda;
        color: #155724;
        border: 1px solid #c3e6cb;
    }

    .status-error {
        background-color: #f8d7da;
        color: #721c24;
        border: 1px solid #f5c6cb;
    }
</style>
@endpush

@section('content')
<div class="container-fluid">
    <!-- Header -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h1 class="h3 mb-0 text-gray-800">
                        <i class="bi bi-fingerprint me-2"></i>
                        Captura de Huellas Dactilares
                    </h1>
                    <p class="text-muted mb-0">Registrar nuevas huellas en el sistema</p>
                </div>
                <div class="d-flex gap-2">
                    <a href="{{ route('huellero.dashboard.index') }}" class="btn btn-outline-secondary">
                        <i class="bi bi-arrow-left me-1"></i>
                        Volver al Dashboard
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Estado del servicio -->
    @if(!$servicioDisponible)
        <div class="row mb-4">
            <div class="col-12">
                <div class="alert alert-danger" role="alert">
                    <i class="bi bi-exclamation-triangle me-2"></i>
                    <strong>Servicio no disponible:</strong>
                    El Digital Persona Lite Client no está disponible. Verifique que esté instalado y ejecutándose.
                </div>
            </div>
        </div>
    @endif

    <div class="row">
        <!-- Panel de control -->
        <div class="col-lg-4">
            <!-- Selección de persona -->
            <div class="card shadow mb-4">
                <div class="card-header">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="bi bi-person me-2"></i>
                        1. Seleccionar Persona
                    </h6>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label for="persona-selector" class="form-label">Buscar persona por identificación:</label>
                        <div class="input-group">
                            <input type="text" class="form-control" id="persona-search"
                                   placeholder="Número de identificación">
                            <button class="btn btn-outline-secondary" type="button" onclick="buscarPersona()">
                                <i class="bi bi-search"></i>
                            </button>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="persona-selector" class="form-label">O seleccionar de la lista:</label>
                        <select class="form-select" id="persona-selector" onchange="seleccionarPersona()">
                            <option value="">-- Seleccione una persona --</option>
                            @foreach($personas as $persona)
                                <option value="{{ $persona->id }}"
                                        data-identificacion="{{ $persona->identificacion }}"
                                        data-nombre="{{ $persona->nombreCompleto() }}">
                                    {{ $persona->identificacion }} - {{ $persona->nombreCompleto() }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div id="persona-info" class="d-none">
                        <div class="border rounded p-3 bg-light">
                            <h6 class="mb-2">Persona Seleccionada:</h6>
                            <p class="mb-1"><strong>Nombre:</strong> <span id="persona-nombre"></span></p>
                            <p class="mb-1"><strong>Identificación:</strong> <span id="persona-identificacion"></span></p>
                            <div id="huellas-existentes" class="mt-2"></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Selección de dedo -->
            <div class="card shadow mb-4">
                <div class="card-header">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="bi bi-hand-index me-2"></i>
                        2. Seleccionar Dedo
                    </h6>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-6">
                            <h6 class="text-center mb-2">Mano Derecha</h6>
                            <div class="finger-selection">
                                @foreach(['01' => 'Pulgar', '02' => 'Índice', '03' => 'Medio', '04' => 'Anular', '05' => 'Meñique'] as $codigo => $nombre)
                                    <button type="button" class="finger-btn" data-dedo="{{ $codigo }}" onclick="seleccionarDedo('{{ $codigo }}')">
                                        {{ $nombre }}
                                    </button>
                                @endforeach
                            </div>
                        </div>
                        <div class="col-6">
                            <h6 class="text-center mb-2">Mano Izquierda</h6>
                            <div class="finger-selection">
                                @foreach(['06' => 'Pulgar', '07' => 'Índice', '08' => 'Medio', '09' => 'Anular', '10' => 'Meñique'] as $codigo => $nombre)
                                    <button type="button" class="finger-btn" data-dedo="{{ $codigo }}" onclick="seleccionarDedo('{{ $codigo }}')">
                                        {{ $nombre }}
                                    </button>
                                @endforeach
                            </div>
                        </div>
                    </div>

                    <div id="dedo-seleccionado" class="d-none mt-3">
                        <div class="alert alert-info">
                            <strong>Dedo seleccionado:</strong> <span id="dedo-nombre"></span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Configuración de captura -->
            <div class="card shadow mb-4">
                <div class="card-header">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="bi bi-gear me-2"></i>
                        3. Configuración
                    </h6>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label for="formato-captura" class="form-label">Formato de captura:</label>
                        <select class="form-select" id="formato-captura">
                            @foreach($formatos as $codigo => $nombre)
                                <option value="{{ $codigo }}" {{ $codigo === 'Intermediate' ? 'selected' : '' }}>
                                    {{ $nombre }}
                                </option>
                            @endforeach
                        </select>
                        <small class="form-text text-muted">
                            Recomendado: Feature Set (Intermediate) para máxima compatibilidad
                        </small>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Calidad mínima:</label>
                        <div class="d-flex align-items-center">
                            <input type="range" class="form-range me-2" id="calidad-minima"
                                   min="30" max="100" value="50" onchange="actualizarCalidadMinima()">
                            <span id="calidad-valor">50%</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Panel de captura -->
        <div class="col-lg-8">
            <!-- Dispositivos -->
            <div class="card shadow mb-4">
                <div class="card-header">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="bi bi-usb-symbol me-2"></i>
                        Dispositivos Conectados
                    </h6>
                </div>
                <div class="card-body">
                    <div id="dispositivos-container">
                        <div class="text-center">
                            <div class="spinner-border text-primary" role="status">
                                <span class="visually-hidden">Cargando dispositivos...</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Captura de huella -->
            <div class="card shadow">
                <div class="card-header">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="bi bi-camera me-2"></i>
                        Captura de Huella
                    </h6>
                </div>
                <div class="card-body">
                    <!-- Estado de captura -->
                    <div id="capture-status" class="capture-status status-waiting d-none">
                        Esperando inicio de captura...
                    </div>

                    <!-- Preview de huella -->
                    <div class="fingerprint-preview" id="fingerprint-preview">
                        <div class="text-center text-muted">
                            <i class="bi bi-fingerprint" style="font-size: 4rem;"></i>
                            <p class="mt-2">Coloque el dedo en el lector</p>
                        </div>
                    </div>

                    <!-- Indicador de calidad -->
                    <div class="mt-3">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <label class="form-label mb-0">Calidad de la muestra:</label>
                            <span id="quality-percentage" class="badge bg-secondary">0%</span>
                        </div>
                        <div class="quality-indicator">
                            <div class="quality-bar" id="quality-bar" style="width: 0%;"></div>
                        </div>
                    </div>

                    <!-- Controles de captura -->
                    <div class="capture-controls">
                        <button type="button" class="btn btn-success" id="btn-iniciar"
                                onclick="iniciarCaptura()" disabled>
                            <i class="bi bi-play me-1"></i>
                            Iniciar Captura
                        </button>
                        <button type="button" class="btn btn-warning" id="btn-detener"
                                onclick="detenerCaptura()" disabled>
                            <i class="bi bi-stop me-1"></i>
                            Detener
                        </button>
                        <button type="button" class="btn btn-info" id="btn-limpiar"
                                onclick="limpiarCaptura()">
                            <i class="bi bi-arrow-clockwise me-1"></i>
                            Limpiar
                        </button>
                        <button type="button" class="btn btn-primary" id="btn-guardar"
                                onclick="guardarHuella()" disabled>
                            <i class="bi bi-save me-1"></i>
                            Guardar Huella
                        </button>
                    </div>

                    <!-- Información adicional -->
                    <div class="row mt-4">
                        <div class="col-md-6">
                            <div class="card border-info">
                                <div class="card-body">
                                    <h6 class="card-title">
                                        <i class="bi bi-info-circle me-1"></i>
                                        Instrucciones
                                    </h6>
                                    <ul class="list-unstyled mb-0 small">
                                        <li>• Coloque el dedo completamente sobre el sensor</li>
                                        <li>• Mantenga presión constante</li>
                                        <li>• Evite movimientos durante la captura</li>
                                        <li>• La calidad debe ser superior al 50%</li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card border-warning" id="capture-info" style="display: none;">
                                <div class="card-body">
                                    <h6 class="card-title">
                                        <i class="bi bi-clipboard-data me-1"></i>
                                        Datos de Captura
                                    </h6>
                                    <div id="capture-details" class="small">
                                        <!-- Se llenará dinámicamente -->
                                    </div>
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

@push('script')
<script>
// Variables globales
let currentDevice = null;
let isCapturing = false;
let capturedData = null;
let selectedPersona = null;
let selectedDedo = null;

document.addEventListener('DOMContentLoaded', function() {
    cargarDispositivos();

    // Event listeners para buscar persona en tiempo real
    document.getElementById('persona-search').addEventListener('input', function() {
        if (this.value.length >= 3) {
            buscarPersonaEnTiempoReal(this.value);
        }
    });
});

function cargarDispositivos() {
    const container = document.getElementById('dispositivos-container');
    container.innerHTML = '<div class="text-center"><div class="spinner-border text-primary" role="status"></div></div>';

    fetch('{{ route("huellero.dashboard.dispositivos") }}')
        .then(response => response.json())
        .then(data => {
            if (data.success && data.dispositivos.length > 0) {
                container.innerHTML = data.dispositivos.map(dispositivo => `
                    <div class="device-selector" data-device="${dispositivo}" onclick="seleccionarDispositivo('${dispositivo}')">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="mb-1">Digital Persona U.are.U 4500</h6>
                                <small class="text-muted">ID: ${dispositivo}</small>
                            </div>
                            <div>
                                <span class="badge bg-success">Conectado</span>
                            </div>
                        </div>
                    </div>
                `).join('');

                // Seleccionar el primer dispositivo automáticamente
                if (data.dispositivos.length === 1) {
                    seleccionarDispositivo(data.dispositivos[0]);
                }
            } else {
                container.innerHTML = `
                    <div class="text-center text-muted py-3">
                        <i class="bi bi-usb-plug fa-2x mb-2"></i>
                        <p>No hay dispositivos conectados</p>
                        <small>Conecte un lector Digital Persona y actualice la página</small>
                    </div>
                `;
            }
        })
        .catch(error => {
            container.innerHTML = `
                <div class="alert alert-danger">
                    <i class="bi bi-exclamation-triangle me-2"></i>
                    Error al cargar dispositivos: ${error.message}
                </div>
            `;
        });
}

function seleccionarDispositivo(deviceId) {
    // Remover selección anterior
    document.querySelectorAll('.device-selector').forEach(el => {
        el.classList.remove('selected');
    });

    // Seleccionar nuevo dispositivo
    document.querySelector(`[data-device="${deviceId}"]`).classList.add('selected');
    currentDevice = deviceId;

    verificarEstadoCaptura();
}

function buscarPersona() {
    const identificacion = document.getElementById('persona-search').value.trim();
    if (!identificacion) {
        mostrarToast('Ingrese un número de identificación', 'warning');
        return;
    }

    fetch(`{{ route("huellero.verificacion.buscar_persona") }}?identificacion=${identificacion}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                seleccionarPersonaPorDatos(data.persona);
            } else {
                mostrarToast('Persona no encontrada', 'error');
                limpiarSeleccionPersona();
            }
        })
        .catch(error => {
            mostrarToast('Error en la búsqueda', 'error');
        });
}

function buscarPersonaEnTiempoReal(identificacion) {
    // Buscar en el select existente
    const select = document.getElementById('persona-selector');
    const option = Array.from(select.options).find(opt =>
        opt.dataset.identificacion && opt.dataset.identificacion.includes(identificacion)
    );

    if (option) {
        select.value = option.value;
        seleccionarPersona();
    }
}

function seleccionarPersona() {
    const select = document.getElementById('persona-selector');
    const selectedOption = select.options[select.selectedIndex];

    if (selectedOption.value) {
        selectedPersona = {
            id: selectedOption.value,
            identificacion: selectedOption.dataset.identificacion,
            nombre: selectedOption.dataset.nombre
        };

        mostrarInfoPersona();
        cargarHuellasExistentes(selectedPersona.id);
        verificarEstadoCaptura();
    } else {
        limpiarSeleccionPersona();
    }
}

function seleccionarPersonaPorDatos(persona) {
    selectedPersona = persona;

    // Actualizar el select si existe la opción
    const select = document.getElementById('persona-selector');
    const option = Array.from(select.options).find(opt => opt.value == persona.id);
    if (option) {
        select.value = persona.id;
    }

    // Actualizar campo de búsqueda
    document.getElementById('persona-search').value = persona.identificacion;

    mostrarInfoPersona();
    cargarHuellasExistentes(persona.id);
    verificarEstadoCaptura();
}

function mostrarInfoPersona() {
    if (!selectedPersona) return;

    document.getElementById('persona-nombre').textContent = selectedPersona.nombre;
    document.getElementById('persona-identificacion').textContent = selectedPersona.identificacion;
    document.getElementById('persona-info').classList.remove('d-none');
}

function limpiarSeleccionPersona() {
    selectedPersona = null;
    document.getElementById('persona-info').classList.add('d-none');
    document.getElementById('persona-selector').value = '';
    document.getElementById('persona-search').value = '';
    verificarEstadoCaptura();
}

function cargarHuellasExistentes(personaId) {
    fetch(`{{ route("huellero.captura.huellas_persona") }}?persona_id=${personaId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const container = document.getElementById('huellas-existentes');
                if (data.huellas.length > 0) {
                    container.innerHTML = `
                        <h6 class="mt-3 mb-2">Huellas Registradas (${data.huellas.length}):</h6>
                        <div class="small">
                            ${data.huellas.map(huella => `
                                <span class="badge bg-info me-1">${huella.nombre_dedo}</span>
                            `).join('')}
                        </div>
                    `;
                } else {
                    container.innerHTML = `
                        <div class="mt-3">
                            <span class="badge bg-warning">Sin huellas registradas</span>
                        </div>
                    `;
                }
            }
        });
}

function seleccionarDedo(dedo) {
    // Remover selección anterior
    document.querySelectorAll('.finger-btn').forEach(btn => {
        btn.classList.remove('selected');
    });

    // Seleccionar nuevo dedo
    document.querySelector(`[data-dedo="${dedo}"]`).classList.add('selected');
    selectedDedo = dedo;

    // Mostrar información del dedo
    const dedosConfig = @json(config('fingerprint.dedos'));
    document.getElementById('dedo-nombre').textContent = dedosConfig[dedo];
    document.getElementById('dedo-seleccionado').classList.remove('d-none');

    verificarEstadoCaptura();
}

function verificarEstadoCaptura() {
    const puedeCapturar = currentDevice && selectedPersona && selectedDedo;
    document.getElementById('btn-iniciar').disabled = !puedeCapturar || isCapturing;
}

function actualizarCalidadMinima() {
    const valor = document.getElementById('calidad-minima').value;
    document.getElementById('calidad-valor').textContent = valor + '%';
}

function iniciarCaptura() {
    if (!currentDevice || !selectedPersona || !selectedDedo) {
        mostrarToast('Complete todos los campos requeridos', 'warning');
        return;
    }

    const formato = document.getElementById('formato-captura').value;

    fetch('{{ route("huellero.captura.iniciar") }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        },
        body: JSON.stringify({
            device_id: currentDevice,
            format: formato
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            isCapturing = true;
            actualizarEstadoCaptura('Capturando huella... Coloque el dedo en el lector', 'capturing');
            document.getElementById('btn-iniciar').disabled = true;
            document.getElementById('btn-detener').disabled = false;

            // Activar preview
            document.getElementById('fingerprint-preview').classList.add('active');

            // Simular proceso de captura (en implementación real, esto vendría del SDK)
            simularCaptura();
        } else {
            mostrarToast(data.message, 'error');
        }
    })
    .catch(error => {
        mostrarToast('Error al iniciar captura', 'error');
    });
}

function detenerCaptura() {
    fetch('{{ route("huellero.captura.detener") }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        },
        body: JSON.stringify({
            device_id: currentDevice
        })
    })
    .then(response => response.json())
    .then(data => {
        finalizarCaptura();
        actualizarEstadoCaptura('Captura detenida', 'waiting');
    });
}

function finalizarCaptura() {
    isCapturing = false;
    document.getElementById('btn-iniciar').disabled = false;
    document.getElementById('btn-detener').disabled = true;
    document.getElementById('fingerprint-preview').classList.remove('active');
}

function limpiarCaptura() {
    finalizarCaptura();
    capturedData = null;

    // Limpiar preview
    const preview = document.getElementById('fingerprint-preview');
    preview.innerHTML = `
        <div class="text-center text-muted">
            <i class="bi bi-fingerprint" style="font-size: 4rem;"></i>
            <p class="mt-2">Coloque el dedo en el lector</p>
        </div>
    `;

    // Limpiar calidad
    actualizarCalidad(0);

    // Ocultar estado
    document.getElementById('capture-status').classList.add('d-none');
    document.getElementById('capture-info').style.display = 'none';
    document.getElementById('btn-guardar').disabled = true;

    actualizarEstadoCaptura('', 'waiting');
}

function simularCaptura() {
    // Esta función simula el proceso de captura
    // En la implementación real, esto se manejaría con eventos del SDK
    setTimeout(() => {
        if (isCapturing) {
            // Simular datos de captura
            const mockData = {
                samples: ['iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNkYPhfDwAChwGA60e6kgAAAABJRU5ErkJggg=='],
                quality: Math.floor(Math.random() * 40) + 60, // 60-100%
                format: document.getElementById('formato-captura').value
            };

            procesarCapturaCompleta(mockData);
        }
    }, 3000);
}

function procesarCapturaCompleta(sampleData) {
    fetch('{{ route("huellero.captura.procesar") }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        },
        body: JSON.stringify(sampleData)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            capturedData = data.data;

            // Mostrar imagen si está disponible
            if (capturedData.image_data) {
                document.getElementById('fingerprint-preview').innerHTML =
                    `<img src="${capturedData.image_data}" alt="Huella capturada">`;
            }

            // Actualizar calidad
            actualizarCalidad(capturedData.quality);

            // Mostrar información de captura
            mostrarInfoCaptura(capturedData);

            // Verificar si la calidad es aceptable
            const calidadMinima = parseInt(document.getElementById('calidad-minima').value);
            if (capturedData.quality >= calidadMinima) {
                actualizarEstadoCaptura('Huella capturada exitosamente', 'success');
                document.getElementById('btn-guardar').disabled = false;
            } else {
                actualizarEstadoCaptura(`Calidad insuficiente (${capturedData.quality}%). Intente nuevamente.`, 'error');
                document.getElementById('btn-guardar').disabled = true;
            }

            finalizarCaptura();
        } else {
            actualizarEstadoCaptura('Error al procesar la huella', 'error');
            finalizarCaptura();
        }
    })
    .catch(error => {
        actualizarEstadoCaptura('Error en el procesamiento', 'error');
        finalizarCaptura();
    });
}

function actualizarCalidad(quality) {
    const percentage = document.getElementById('quality-percentage');
    const bar = document.getElementById('quality-bar');

    percentage.textContent = quality + '%';
    bar.style.width = quality + '%';

    // Cambiar color según calidad
    bar.className = 'quality-bar';
    if (quality >= 80) bar.classList.add('quality-excellent');
    else if (quality >= 60) bar.classList.add('quality-good');
    else if (quality >= 40) bar.classList.add('quality-fair');
    else bar.classList.add('quality-poor');

    // Actualizar badge
    percentage.className = 'badge';
    if (quality >= 80) percentage.classList.add('bg-success');
    else if (quality >= 60) percentage.classList.add('bg-info');
    else if (quality >= 40) percentage.classList.add('bg-warning');
    else percentage.classList.add('bg-danger');
}

function mostrarInfoCaptura(data) {
    const container = document.getElementById('capture-details');
    container.innerHTML = `
        <p class="mb-1"><strong>Formato:</strong> ${data.format}</p>
        <p class="mb-1"><strong>Calidad:</strong> ${data.quality}%</p>
        <p class="mb-1"><strong>Timestamp:</strong> ${new Date(data.timestamp).toLocaleString()}</p>
        <p class="mb-0"><strong>Tamaño:</strong> ${data.raw_data ? data.raw_data.length : 'N/A'} bytes</p>
    `;
    document.getElementById('capture-info').style.display = 'block';
}

function actualizarEstadoCaptura(mensaje, tipo) {
    const status = document.getElementById('capture-status');
    if (mensaje) {
        status.textContent = mensaje;
        status.className = `capture-status status-${tipo}`;
        status.classList.remove('d-none');
    } else {
        status.classList.add('d-none');
    }
}

function guardarHuella() {
    if (!capturedData || !selectedPersona || !selectedDedo) {
        mostrarToast('Faltan datos para guardar la huella', 'warning');
        return;
    }

    const templateHuella = capturedData.processed_template || capturedData.raw_data;

    fetch('{{ route("huellero.captura.registrar") }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        },
        body: JSON.stringify({
            persona_id: selectedPersona.id,
            dedo: selectedDedo,
            template_huella: templateHuella,
            quality: capturedData.quality
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            mostrarToast('Huella registrada exitosamente', 'success');

            // Limpiar formulario
            limpiarCaptura();

            // Actualizar huellas existentes
            cargarHuellasExistentes(selectedPersona.id);

            // Limpiar selección de dedo
            document.querySelectorAll('.finger-btn').forEach(btn => {
                btn.classList.remove('selected');
            });
            document.getElementById('dedo-seleccionado').classList.add('d-none');
            selectedDedo = null;

        } else {
            mostrarToast(data.message, 'error');
        }
    })
    .catch(error => {
        mostrarToast('Error al guardar la huella', 'error');
    });
}

// Función auxiliar para mostrar toasts
function mostrarToast(mensaje, tipo) {
    // Implementar según el sistema de toasts del proyecto
    console.log(`${tipo.toUpperCase()}: ${mensaje}`);
    alert(mensaje); // Temporal, reemplazar con el sistema de toasts del proyecto
}
</script>
@endpush
