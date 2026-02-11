@extends('huellero::layouts.app')

@section('title', 'Verificación de Huellas')

@section('content')
<div class="container-fluid">
    <div class="row">
        <!-- Panel de Control de Verificación -->
        <div class="col-md-4">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">
                        <i class="bi bi-shield-check me-2"></i>
                        Control de Verificación
                    </h5>
                </div>
                <div class="card-body">
                    <!-- Selección de Modo -->
                    <div class="mb-4">
                        <h6 class="fw-semibold mb-3">Modo de Verificación</h6>
                        <div class="form-check mb-2">
                            <input class="form-check-input" type="radio" name="verification-mode" id="mode-access" value="access" checked>
                            <label class="form-check-label" for="mode-access">
                                <strong>Control de Acceso</strong>
                                <br><small class="text-muted">Registra entrada/salida automáticamente</small>
                            </label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="verification-mode" id="mode-search" value="search">
                            <label class="form-check-label" for="mode-search">
                                <strong>Búsqueda de Persona</strong>
                                <br><small class="text-muted">Identifica persona sin registrar evento</small>
                            </label>
                        </div>
                    </div>

                    <!-- Información del Modo -->
                    <div id="access-mode-info" class="alert alert-info">
                        <i class="bi bi-info-circle me-2"></i>
                        <strong>Modo Acceso:</strong> Al verificar una huella, se registrará automáticamente el evento de entrada o salida según corresponda.
                    </div>

                    <div id="search-mode-info" class="alert alert-secondary d-none">
                        <i class="bi bi-search me-2"></i>
                        <strong>Modo Búsqueda:</strong> Solo se mostrará la información de la persona identificada sin registrar eventos.
                    </div>

                    <!-- Selección de Dispositivo -->
                    <div class="mb-4">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h6 class="fw-semibold mb-0">Dispositivos Disponibles</h6>
                            <button type="button" class="btn btn-outline-primary btn-sm" id="refresh-verification-devices">
                                <i class="bi bi-arrow-clockwise"></i>
                            </button>
                        </div>
                        <div id="verification-devices-list">
                            <div class="text-center text-muted py-3">
                                <div class="spinner-border spinner-border-sm me-2" role="status"></div>
                                Cargando dispositivos...
                            </div>
                        </div>
                    </div>

                    <!-- Controles de Verificación -->
                    <div class="d-grid gap-2">
                        <button type="button" class="btn btn-success" id="start-verification-btn">
                            <i class="bi bi-play-fill me-2"></i>
                            Iniciar Verificación
                        </button>
                        <button type="button" class="btn btn-danger" id="stop-verification-btn" disabled>
                            <i class="bi bi-stop-fill me-2"></i>
                            Detener Verificación
                        </button>
                    </div>

                    <!-- Estado de Verificación -->
                    <div id="verification-status" class="mt-3 d-none"></div>
                </div>
            </div>

            <!-- Panel de Estadísticas Rápidas -->
            <div class="card shadow-sm mt-4">
                <div class="card-header">
                    <h6 class="mb-0">
                        <i class="bi bi-graph-up me-2"></i>
                        Estadísticas Hoy
                    </h6>
                </div>
                <div class="card-body">
                    <div class="row text-center">
                        <div class="col-6">
                            <div class="border-end">
                                <h4 class="text-success mb-1" id="entradas-hoy">0</h4>
                                <small class="text-muted">Entradas</small>
                            </div>
                        </div>
                        <div class="col-6">
                            <h4 class="text-primary mb-1" id="salidas-hoy">0</h4>
                            <small class="text-muted">Salidas</small>
                        </div>
                    </div>
                    <hr>
                    <div class="text-center">
                        <h5 class="text-info mb-1" id="verificaciones-hoy">0</h5>
                        <small class="text-muted">Total Verificaciones</small>
                    </div>
                </div>
            </div>
        </div>

        <!-- Panel Principal de Resultados -->
        <div class="col-md-8">
            <div class="row">
                <!-- Área de Resultados de Acceso -->
                <div class="col-12 mb-4">
                    <div class="card shadow-sm">
                        <div class="card-header">
                            <h5 class="mb-0">
                                <i class="bi bi-person-check me-2"></i>
                                Resultado de Verificación
                            </h5>
                        </div>
                        <div class="card-body">
                            <div id="verification-panel">
                                <div class="text-center text-muted py-5">
                                    <i class="bi bi-fingerprint" style="font-size: 4rem; opacity: 0.3;"></i>
                                    <p class="mt-3 mb-0">Inicie la verificación para comenzar</p>
                                    <small>Seleccione un dispositivo y presione "Iniciar Verificación"</small>
                                </div>
                            </div>

                            <!-- Resultado de Control de Acceso -->
                            <div id="access-result"></div>

                            <!-- Resultado de Búsqueda -->
                            <div id="search-result"></div>
                        </div>
                    </div>
                </div>

                <!-- Log de Accesos Recientes -->
                <div class="col-12">
                    <div class="card shadow-sm">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h6 class="mb-0">
                                <i class="bi bi-clock-history me-2"></i>
                                Accesos Recientes
                            </h6>
                            <button type="button" class="btn btn-outline-primary btn-sm" onclick="loadRecentAccess()">
                                <i class="bi bi-arrow-clockwise"></i>
                            </button>
                        </div>
                        <div class="card-body">
                            <div id="recent-access-list" style="max-height: 400px; overflow-y: auto;">
                                <div class="text-center text-muted py-4">
                                    <div class="spinner-border spinner-border-sm me-2" role="status"></div>
                                    Cargando accesos recientes...
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal de Detalles de Persona -->
<div class="modal fade" id="personDetailsModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="bi bi-person me-2"></i>
                    Detalles de Persona
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="person-details-content">
                    <div class="text-center py-4">
                        <div class="spinner-border" role="status"></div>
                        <p class="mt-2">Cargando información...</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
    .verification-status {
        padding: 0.75rem;
        border-radius: 0.375rem;
        font-weight: 500;
    }

    .access-result {
        margin-top: 1rem;
    }

    .device-card {
        border: 1px solid #dee2e6;
        border-radius: 0.375rem;
        padding: 0.75rem;
        margin-bottom: 0.5rem;
        transition: all 0.2s ease;
    }

    .device-card:hover {
        background-color: #f8f9fa;
    }

    .activity-item {
        border-bottom: 1px solid #f0f0f0;
    }

    .activity-item:last-child {
        border-bottom: none;
    }

    .activity-icon {
        width: 40px;
        text-align: center;
    }

    .activity-description {
        font-weight: 500;
        color: #495057;
    }

    .activity-time {
        min-width: 80px;
        text-align: right;
    }

    .stats-quick {
        background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
        border-radius: 10px;
        padding: 1rem;
        text-align: center;
    }

    .pulse-animation {
        animation: pulse 2s infinite;
    }

    @keyframes pulse {
        0% {
            transform: scale(1);
        }
        50% {
            transform: scale(1.05);
        }
        100% {
            transform: scale(1);
        }
    }

    .verification-ready {
        border: 3px solid #28a745;
        background: rgba(40, 167, 69, 0.1);
        border-radius: 10px;
        padding: 2rem;
        text-align: center;
        animation: pulse-border 2s infinite;
    }

    @keyframes pulse-border {
        0% {
            border-color: #28a745;
        }
        50% {
            border-color: #20c997;
        }
        100% {
            border-color: #28a745;
        }
    }
</style>
@endpush

@push('scripts')
<script>
    // Variables específicas de la página de verificación
    let currentVerificationStats = {
        entradas: 0,
        salidas: 0,
        total: 0
    };

    // Inicializar página
    document.addEventListener('DOMContentLoaded', function() {
        loadVerificationStats();

        // Actualizar estadísticas cada 30 segundos
        setInterval(loadVerificationStats, 30000);
    });

    // Cargar estadísticas de verificación
    async function loadVerificationStats() {
        try {
            const response = await fetch('/huellero/verificacion/estadisticas-hoy', {
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                }
            });

            const data = await response.json();

            if (data.success) {
                updateVerificationStats(data.estadisticas);
            }
        } catch (error) {
            console.error('Error cargando estadísticas:', error);
        }
    }

    // Actualizar estadísticas en UI
    function updateVerificationStats(stats) {
        document.getElementById('entradas-hoy').textContent = stats.entradas || 0;
        document.getElementById('salidas-hoy').textContent = stats.salidas || 0;
        document.getElementById('verificaciones-hoy').textContent = stats.total || 0;

        currentVerificationStats = stats;
    }

    // Ver detalles de persona (función global necesaria para verificacion.js)
    window.viewPersonDetails = async function(codigoPersona) {
        const modal = new bootstrap.Modal(document.getElementById('personDetailsModal'));
        const content = document.getElementById('person-details-content');

        // Mostrar modal con loading
        modal.show();
        content.innerHTML = `
            <div class="text-center py-4">
                <div class="spinner-border" role="status"></div>
                <p class="mt-2">Cargando información...</p>
            </div>
        `;

        try {
            const response = await fetch(`/huellero/personas/${codigoPersona}`, {
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                }
            });

            const data = await response.json();

            if (data.success) {
                const persona = data.persona;
                const huellas = data.huellas || [];
                const eventosRecientes = data.eventos_recientes || [];

                content.innerHTML = `
                    <div class="row">
                        <div class="col-md-6">
                            <h6 class="fw-semibold mb-3">Información Personal</h6>
                            <table class="table table-sm">
                                <tr>
                                    <td><strong>Código:</strong></td>
                                    <td>${persona.codigo}</td>
                                </tr>
                                <tr>
                                    <td><strong>Nombre:</strong></td>
                                    <td>${persona.nombre}</td>
                                </tr>
                                <tr>
                                    <td><strong>Cargo:</strong></td>
                                    <td>${persona.cargo || 'Sin cargo definido'}</td>
                                </tr>
                                <tr>
                                    <td><strong>Estado:</strong></td>
                                    <td>
                                        <span class="badge ${persona.activo ? 'bg-success' : 'bg-secondary'}">
                                            ${persona.activo ? 'Activo' : 'Inactivo'}
                                        </span>
                                    </td>
                                </tr>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <h6 class="fw-semibold mb-3">Huellas Registradas</h6>
                            ${huellas.length > 0 ? `
                                <div class="list-group list-group-flush">
                                    ${huellas.map(huella => `
                                        <div class="list-group-item d-flex justify-content-between align-items-center">
                                            <span>
                                                <i class="bi bi-fingerprint me-2"></i>
                                                ${huella.nombre_dedo}
                                            </span>
                                            <span class="badge bg-primary rounded-pill">${huella.calidad}%</span>
                                        </div>
                                    `).join('')}
                                </div>
                            ` : `
                                <div class="alert alert-warning">
                                    <i class="bi bi-exclamation-triangle me-2"></i>
                                    No hay huellas registradas
                                </div>
                            `}
                        </div>
                    </div>

                    ${eventosRecientes.length > 0 ? `
                        <hr>
                        <h6 class="fw-semibold mb-3">Eventos Recientes</h6>
                        <div style="max-height: 200px; overflow-y: auto;">
                            ${eventosRecientes.map(evento => `
                                <div class="d-flex justify-content-between align-items-center py-2 border-bottom">
                                    <div>
                                        <i class="bi ${evento.icono_evento} me-2"></i>
                                        <strong>${evento.tipo_evento}</strong>
                                        ${evento.es_manual ? `<span class="badge bg-warning text-dark ms-2">Manual</span>` : ''}
                                    </div>
                                    <small class="text-muted">${new Date(evento.fechaevento).toLocaleString()}</small>
                                </div>
                            `).join('')}
                        </div>
                    ` : ''}
                `;
            } else {
                content.innerHTML = `
                    <div class="alert alert-danger">
                        <i class="bi bi-exclamation-triangle me-2"></i>
                        Error cargando información: ${data.message}
                    </div>
                `;
            }
        } catch (error) {
            content.innerHTML = `
                <div class="alert alert-danger">
                    <i class="bi bi-exclamation-triangle me-2"></i>
                    Error de conexión: ${error.message}
                </div>
            `;
        }
    };
</script>
@endpush
