@extends('huellero.layouts.app')

@section('title', 'Registro de Eventos')

@section('content')
<div class="container-fluid">
    <div class="row">
        <!-- Panel de Filtros -->
        <div class="col-md-3">
            <div class="card shadow-sm">
                <div class="card-header bg-info text-white">
                    <h6 class="mb-0">
                        <i class="bi bi-funnel me-2"></i>
                        Filtros de Búsqueda
                    </h6>
                </div>
                <div class="card-body">
                    <form id="filters-form">
                        <!-- Rango de Fechas -->
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Rango de Fechas</label>
                            <div class="row">
                                <div class="col-12 mb-2">
                                    <input type="date" class="form-control form-control-sm"
                                           id="fecha_inicio" name="fecha_inicio"
                                           value="{{ now()->startOfDay()->format('Y-m-d') }}">
                                    <small class="text-muted">Fecha inicio</small>
                                </div>
                                <div class="col-12">
                                    <input type="date" class="form-control form-control-sm"
                                           id="fecha_fin" name="fecha_fin"
                                           value="{{ now()->endOfDay()->format('Y-m-d') }}">
                                    <small class="text-muted">Fecha fin</small>
                                </div>
                            </div>
                        </div>

                        <!-- Tipo de Evento -->
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Tipo de Evento</label>
                            <select class="form-select form-select-sm" id="tipo_evento" name="tipo_evento">
                                <option value="">Todos los tipos</option>
                                <option value="49">Entrada</option>
                                <option value="50">Salida</option>
                            </select>
                        </div>

                        <!-- Tipo de Registro -->
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Tipo de Registro</label>
                            <select class="form-select form-select-sm" id="tipo_registro" name="tipo_registro">
                                <option value="">Todos los registros</option>
                                <option value="0">Automático</option>
                                <option value="1">Manual</option>
                            </select>
                        </div>

                        <!-- Búsqueda por Persona -->
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Persona</label>
                            <input type="text" class="form-control form-control-sm"
                                   id="buscar_persona" name="buscar_persona"
                                   placeholder="Nombre o código...">
                        </div>

                        <!-- Botones de Acción -->
                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary btn-sm">
                                <i class="bi bi-search me-1"></i>
                                Buscar
                            </button>
                            <button type="button" class="btn btn-outline-secondary btn-sm" id="clear-filters">
                                <i class="bi bi-x-circle me-1"></i>
                                Limpiar
                            </button>
                            <button type="button" class="btn btn-success btn-sm" id="export-events">
                                <i class="bi bi-download me-1"></i>
                                Exportar
                            </button>
                        </div>
                    </form>

                    <hr>

                    <!-- Filtros Rápidos -->
                    <div class="quick-filters">
                        <h6 class="fw-semibold mb-3">Filtros Rápidos</h6>
                        <div class="d-grid gap-1">
                            <button type="button" class="btn btn-outline-primary btn-sm quick-filter"
                                    data-filter="hoy">
                                Hoy
                            </button>
                            <button type="button" class="btn btn-outline-primary btn-sm quick-filter"
                                    data-filter="ayer">
                                Ayer
                            </button>
                            <button type="button" class="btn btn-outline-primary btn-sm quick-filter"
                                    data-filter="semana">
                                Esta Semana
                            </button>
                            <button type="button" class="btn btn-outline-primary btn-sm quick-filter"
                                    data-filter="mes">
                                Este Mes
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Resumen de Resultados -->
            <div class="card shadow-sm mt-3">
                <div class="card-header">
                    <h6 class="mb-0">
                        <i class="bi bi-bar-chart me-2"></i>
                        Resumen
                    </h6>
                </div>
                <div class="card-body">
                    <div class="row text-center">
                        <div class="col-6">
                            <div class="border-end">
                                <h5 class="text-success mb-1" id="total-entradas">0</h5>
                                <small class="text-muted">Entradas</small>
                            </div>
                        </div>
                        <div class="col-6">
                            <h5 class="text-primary mb-1" id="total-salidas">0</h5>
                            <small class="text-muted">Salidas</small>
                        </div>
                    </div>
                    <hr>
                    <div class="row text-center">
                        <div class="col-6">
                            <div class="border-end">
                                <h6 class="text-info mb-1" id="total-automaticos">0</h6>
                                <small class="text-muted">Automáticos</small>
                            </div>
                        </div>
                        <div class="col-6">
                            <h6 class="text-warning mb-1" id="total-manuales">0</h6>
                            <small class="text-muted">Manuales</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Lista de Eventos -->
        <div class="col-md-9">
            <div class="card shadow-sm">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        <i class="bi bi-clock-history me-2"></i>
                        Registro de Eventos
                    </h5>
                    <div class="d-flex align-items-center">
                        <div class="me-3">
                            <small class="text-muted">Total de registros: </small>
                            <span class="badge bg-primary" id="total-registros">0</span>
                        </div>
                        <button type="button" class="btn btn-outline-primary btn-sm" id="refresh-events">
                            <i class="bi bi-arrow-clockwise"></i>
                        </button>
                    </div>
                </div>
                <div class="card-body p-0">
                    <!-- Tabla de Eventos -->
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th width="10%">Fecha/Hora</th>
                                    <th width="25%">Persona</th>
                                    <th width="15%">Evento</th>
                                    <th width="15%">Tipo Registro</th>
                                    <th width="20%">Observación</th>
                                    <th width="15%">Acciones</th>
                                </tr>
                            </thead>
                            <tbody id="events-table-body">
                                <tr>
                                    <td colspan="6" class="text-center py-4">
                                        <div class="spinner-border spinner-border-sm me-2" role="status"></div>
                                        Cargando eventos...
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Paginación -->
                    <div class="d-flex justify-content-between align-items-center p-3 border-top">
                        <div>
                            <small class="text-muted">
                                Mostrando <span id="showing-from">0</span> a <span id="showing-to">0</span>
                                de <span id="total-events">0</span> eventos
                            </small>
                        </div>
                        <nav>
                            <ul class="pagination pagination-sm mb-0" id="pagination-controls">
                                <!-- Paginación se genera dinámicamente -->
                            </ul>
                        </nav>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal de Detalles de Evento -->
<div class="modal fade" id="eventDetailsModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="bi bi-info-circle me-2"></i>
                    Detalles del Evento
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="event-details-content">
                    <!-- Contenido se carga dinámicamente -->
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal de Registro Manual -->
<div class="modal fade" id="manualEventModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="bi bi-plus-circle me-2"></i>
                    Registro Manual de Evento
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="manual-event-form">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="persona_codigo" class="form-label">Persona *</label>
                        <input type="text" class="form-control" id="persona_codigo" name="persona_codigo"
                               placeholder="Buscar por código o nombre..." required>
                        <div class="form-text">Ingrese el código o comience a escribir el nombre</div>
                    </div>

                    <div class="mb-3">
                        <label for="evento_tipo" class="form-label">Tipo de Evento *</label>
                        <select class="form-select" id="evento_tipo" name="evento_tipo" required>
                            <option value="">Seleccionar evento</option>
                            <option value="49">Entrada</option>
                            <option value="50">Salida</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="fecha_evento" class="form-label">Fecha y Hora *</label>
                        <input type="datetime-local" class="form-control" id="fecha_evento" name="fecha_evento"
                               value="{{ now()->format('Y-m-d\TH:i') }}" required>
                    </div>

                    <div class="mb-3">
                        <label for="motivo" class="form-label">Motivo del Registro Manual *</label>
                        <select class="form-select" id="motivo" name="motivo" required>
                            <option value="">Seleccionar motivo</option>
                            <option value="sistema_no_disponible">Sistema no disponible</option>
                            <option value="error_lector">Error en el lector</option>
                            <option value="huella_no_leida">Huella no reconocida</option>
                            <option value="error_comunicacion">Error de comunicación</option>
                            <option value="otro">Otro motivo</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="observacion" class="form-label">Observación</label>
                        <textarea class="form-control" id="observacion" name="observacion" rows="3"
                                  placeholder="Detalles adicionales del evento..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-save me-1"></i>
                        Registrar Evento
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
    .quick-filter.active {
        background-color: var(--bs-primary);
        color: white;
    }

    .event-row:hover {
        background-color: rgba(0, 123, 255, 0.05);
    }

    .event-type-badge {
        font-size: 0.75rem;
        padding: 0.25rem 0.5rem;
    }

    .event-status {
        width: 10px;
        height: 10px;
        border-radius: 50%;
        display: inline-block;
        margin-right: 8px;
    }

    .event-status.automatico {
        background-color: #28a745;
    }

    .event-status.manual {
        background-color: #ffc107;
    }

    .pagination-sm .page-link {
        padding: 0.25rem 0.5rem;
        font-size: 0.875rem;
    }

    .table th {
        background-color: #f8f9fa;
        border-bottom: 2px solid #dee2e6;
        font-weight: 600;
        font-size: 0.875rem;
    }

    .table td {
        vertical-align: middle;
        font-size: 0.875rem;
    }

    .filters-card {
        position: sticky;
        top: 20px;
    }
</style>
@endpush

@push('scripts')
<script>
    // Variables globales para eventos
    let currentPage = 1;
    let currentFilters = {};
    let isLoading = false;

    // Inicializar página
    document.addEventListener('DOMContentLoaded', function() {
        setupEventHandlers();
        loadEvents();
    });

    // Configurar manejadores de eventos
    function setupEventHandlers() {
        // Formulario de filtros
        document.getElementById('filters-form').addEventListener('submit', function(e) {
            e.preventDefault();
            currentPage = 1;
            loadEvents();
        });

        // Limpiar filtros
        document.getElementById('clear-filters').addEventListener('click', clearFilters);

        // Filtros rápidos
        document.querySelectorAll('.quick-filter').forEach(btn => {
            btn.addEventListener('click', function() {
                applyQuickFilter(this.dataset.filter);
            });
        });

        // Refrescar eventos
        document.getElementById('refresh-events').addEventListener('click', function() {
            loadEvents();
        });

        // Exportar eventos
        document.getElementById('export-events').addEventListener('click', exportEvents);

        // Registro manual
        document.getElementById('manual-event-form').addEventListener('submit', submitManualEvent);
    }

    // Cargar eventos con filtros actuales
    async function loadEvents(page = 1) {
        if (isLoading) return;

        isLoading = true;
        currentPage = page;

        try {
            showLoading(true);

            const formData = new FormData(document.getElementById('filters-form'));
            const params = new URLSearchParams(formData);
            params.append('page', page);

            const response = await fetch(`/huellero/eventos/listar?${params}`, {
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                }
            });

            const data = await response.json();

            if (data.success) {
                displayEvents(data.eventos);
                updatePagination(data.pagination);
                updateSummary(data.resumen);
            } else {
                showError('Error cargando eventos: ' + data.message);
            }
        } catch (error) {
            showError('Error de conexión: ' + error.message);
        } finally {
            isLoading = false;
            showLoading(false);
        }
    }

    // Mostrar eventos en la tabla
    function displayEvents(eventos) {
        const tbody = document.getElementById('events-table-body');

        if (eventos.length === 0) {
            tbody.innerHTML = `
                <tr>
                    <td colspan="6" class="text-center py-4 text-muted">
                        <i class="bi bi-inbox me-2"></i>
                        No se encontraron eventos con los filtros seleccionados
                    </td>
                </tr>
            `;
            return;
        }

        tbody.innerHTML = eventos.map(evento => `
            <tr class="event-row">
                <td>
                    <div class="small">
                        <div>${formatDateTime(evento.fechaevento).fecha}</div>
                        <div class="text-muted">${formatDateTime(evento.fechaevento).hora}</div>
                    </div>
                </td>
                <td>
                    <div>
                        <div class="fw-semibold">${evento.persona.nombre}</div>
                        <small class="text-muted">${evento.persona.codigo}</small>
                    </div>
                </td>
                <td>
                    <span class="badge ${evento.evento === '49' ? 'bg-success' : 'bg-primary'} event-type-badge">
                        <i class="bi ${evento.evento === '49' ? 'bi-box-arrow-in-right' : 'bi-box-arrow-right'} me-1"></i>
                        ${evento.tipo_evento}
                    </span>
                </td>
                <td>
                    <div class="d-flex align-items-center">
                        <span class="event-status ${evento.tiporegistro === 0 ? 'automatico' : 'manual'}"></span>
                        <span class="badge ${evento.tiporegistro === 0 ? 'bg-success' : 'bg-warning text-dark'} small">
                            ${evento.tipo_registro_texto}
                        </span>
                    </div>
                </td>
                <td>
                    <div class="small">
                        ${evento.mensaje_error ? `
                            <div class="text-warning">
                                <i class="bi bi-exclamation-triangle me-1"></i>
                                ${evento.mensaje_error}
                            </div>
                        ` : (evento.observacion ?
                            `<span class="text-muted">${evento.observacion.substring(0, 50)}${evento.observacion.length > 50 ? '...' : ''}</span>`
                            : '<span class="text-muted">-</span>')}
                    </div>
                </td>
                <td>
                    <div class="btn-group" role="group">
                        <button type="button" class="btn btn-outline-primary btn-sm"
                                onclick="viewEventDetails(${evento.id})">
                            <i class="bi bi-eye"></i>
                        </button>
                        <button type="button" class="btn btn-outline-info btn-sm"
                                onclick="viewPersonDetails('${evento.persona.codigo}')">
                            <i class="bi bi-person"></i>
                        </button>
                    </div>
                </td>
            </tr>
        `).join('');
    }

    // Actualizar paginación
    function updatePagination(pagination) {
        const container = document.getElementById('pagination-controls');
        const { current_page, last_page, per_page, total } = pagination;

        // Actualizar información de registros
        document.getElementById('showing-from').textContent = ((current_page - 1) * per_page) + 1;
        document.getElementById('showing-to').textContent = Math.min(current_page * per_page, total);
        document.getElementById('total-events').textContent = total;
        document.getElementById('total-registros').textContent = total;

        if (last_page <= 1) {
            container.innerHTML = '';
            return;
        }

        let paginationHTML = '';

        // Botón anterior
        paginationHTML += `
            <li class="page-item ${current_page === 1 ? 'disabled' : ''}">
                <a class="page-link" href="#" onclick="loadEvents(${current_page - 1}); return false;">
                    <i class="bi bi-chevron-left"></i>
                </a>
            </li>
        `;

        // Páginas
        const start = Math.max(1, current_page - 2);
        const end = Math.min(last_page, current_page + 2);

        for (let i = start; i <= end; i++) {
            paginationHTML += `
                <li class="page-item ${i === current_page ? 'active' : ''}">
                    <a class="page-link" href="#" onclick="loadEvents(${i}); return false;">${i}</a>
                </li>
            `;
        }

        // Botón siguiente
        paginationHTML += `
            <li class="page-item ${current_page === last_page ? 'disabled' : ''}">
                <a class="page-link" href="#" onclick="loadEvents(${current_page + 1}); return false;">
                    <i class="bi bi-chevron-right"></i>
                </a>
            </li>
        `;

        container.innerHTML = paginationHTML;
    }

    // Actualizar resumen
    function updateSummary(resumen) {
        document.getElementById('total-entradas').textContent = resumen.entradas || 0;
        document.getElementById('total-salidas').textContent = resumen.salidas || 0;
        document.getElementById('total-automaticos').textContent = resumen.automaticos || 0;
        document.getElementById('total-manuales').textContent = resumen.manuales || 0;
    }

    // Aplicar filtro rápido
    function applyQuickFilter(filter) {
        // Limpiar filtros activos
        document.querySelectorAll('.quick-filter').forEach(btn => btn.classList.remove('active'));

        const now = new Date();
        let startDate, endDate;

        switch (filter) {
            case 'hoy':
                startDate = endDate = now.toISOString().split('T')[0];
                break;
            case 'ayer':
                const yesterday = new Date(now);
                yesterday.setDate(yesterday.getDate() - 1);
                startDate = endDate = yesterday.toISOString().split('T')[0];
                break;
            case 'semana':
                const weekStart = new Date(now);
                weekStart.setDate(weekStart.getDate() - weekStart.getDay());
                startDate = weekStart.toISOString().split('T')[0];
                endDate = now.toISOString().split('T')[0];
                break;
            case 'mes':
                startDate = new Date(now.getFullYear(), now.getMonth(), 1).toISOString().split('T')[0];
                endDate = now.toISOString().split('T')[0];
                break;
        }

        document.getElementById('fecha_inicio').value = startDate;
        document.getElementById('fecha_fin').value = endDate;

        // Marcar botón como activo
        event.target.classList.add('active');

        // Cargar eventos
        currentPage = 1;
        loadEvents();
    }

    // Limpiar filtros
    function clearFilters() {
        document.getElementById('filters-form').reset();
        document.getElementById('fecha_inicio').value = new Date().toISOString().split('T')[0];
        document.getElementById('fecha_fin').value = new Date().toISOString().split('T')[0];
        document.querySelectorAll('.quick-filter').forEach(btn => btn.classList.remove('active'));

        currentPage = 1;
        loadEvents();
    }

    // Ver detalles de evento
    window.viewEventDetails = async function(eventId) {
        const modal = new bootstrap.Modal(document.getElementById('eventDetailsModal'));
        const content = document.getElementById('event-details-content');

        modal.show();
        content.innerHTML = `
            <div class="text-center py-4">
                <div class="spinner-border" role="status"></div>
                <p class="mt-2">Cargando detalles...</p>
            </div>
        `;

        try {
            const response = await fetch(`/huellero/eventos/${eventId}`, {
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                }
            });

            const data = await response.json();

            if (data.success) {
                const evento = data.evento;
                content.innerHTML = `
                    <div class="row">
                        <div class="col-md-6">
                            <h6 class="fw-semibold mb-3">Información del Evento</h6>
                            <table class="table table-sm">
                                <tr>
                                    <td><strong>ID:</strong></td>
                                    <td>${evento.id}</td>
                                </tr>
                                <tr>
                                    <td><strong>Fecha/Hora:</strong></td>
                                    <td>${new Date(evento.fechaevento).toLocaleString()}</td>
                                </tr>
                                <tr>
                                    <td><strong>Tipo:</strong></td>
                                    <td>
                                        <span class="badge ${evento.evento === '49' ? 'bg-success' : 'bg-primary'}">
                                            ${evento.tipo_evento}
                                        </span>
                                    </td>
                                </tr>
                                <tr>
                                    <td><strong>Registro:</strong></td>
                                    <td>
                                        <span class="badge ${evento.tiporegistro === 0 ? 'bg-success' : 'bg-warning text-dark'}">
                                            ${evento.tipo_registro_texto}
                                        </span>
                                    </td>
                                </tr>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <h6 class="fw-semibold mb-3">Información de la Persona</h6>
                            <table class="table table-sm">
                                <tr>
                                    <td><strong>Código:</strong></td>
                                    <td>${evento.persona.codigo}</td>
                                </tr>
                                <tr>
                                    <td><strong>Nombre:</strong></td>
                                    <td>${evento.persona.nombre}</td>
                                </tr>
                                <tr>
                                    <td><strong>Cargo:</strong></td>
                                    <td>${evento.persona.cargo || 'Sin cargo'}</td>
                                </tr>
                            </table>
                        </div>
                    </div>

                    ${evento.observacion ? `
                        <hr>
                        <h6 class="fw-semibold mb-2">Observación</h6>
                        <p class="text-muted">${evento.observacion}</p>
                    ` : ''}

                    <hr>
                    <small class="text-muted">
                        Creado por: ${evento.usrcreacion || 'Sistema'} |
                        Fecha creación: ${new Date(evento.feccreacion).toLocaleString()}
                    </small>
                `;
            } else {
                content.innerHTML = `
                    <div class="alert alert-danger">
                        <i class="bi bi-exclamation-triangle me-2"></i>
                        Error cargando detalles: ${data.message}
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

    // Exportar eventos
    async function exportEvents() {
        try {
            showLoading(true);

            const formData = new FormData(document.getElementById('filters-form'));

            const response = await fetch('/huellero/eventos/exportar', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                },
                body: formData
            });

            if (response.ok) {
                const blob = await response.blob();
                const url = window.URL.createObjectURL(blob);
                const a = document.createElement('a');
                a.href = url;
                a.download = `eventos_huellero_${new Date().toISOString().split('T')[0]}.xlsx`;
                document.body.appendChild(a);
                a.click();
                document.body.removeChild(a);
                window.URL.revokeObjectURL(url);

                showStatusMessage('Eventos exportados exitosamente', 'success');
            } else {
                throw new Error('Error en la exportación');
            }
        } catch (error) {
            showStatusMessage('Error exportando eventos: ' + error.message, 'error');
        } finally {
            showLoading(false);
        }
    }

    // Funciones de utilidad
    function formatDateTime(dateTimeString) {
        const date = new Date(dateTimeString);
        return {
            fecha: date.toLocaleDateString(),
            hora: date.toLocaleTimeString()
        };
    }

    function showLoading(show) {
        const tbody = document.getElementById('events-table-body');
        if (show) {
            tbody.innerHTML = `
                <tr>
                    <td colspan="6" class="text-center py-4">
                        <div class="spinner-border spinner-border-sm me-2" role="status"></div>
                        Cargando eventos...
                    </td>
                </tr>
            `;
        }
    }

    function showError(message) {
        const tbody = document.getElementById('events-table-body');
        tbody.innerHTML = `
            <tr>
                <td colspan="6" class="text-center py-4 text-danger">
                    <i class="bi bi-exclamation-triangle me-2"></i>
                    ${message}
                </td>
            </tr>
        `;
    }
</script>
@endpush
