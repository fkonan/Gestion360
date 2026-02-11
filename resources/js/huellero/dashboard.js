/**
 * Funciones para el dashboard del sistema de huellas
 */

// Variables del dashboard
let dashboardRefreshInterval = null;
let chartInstances = {};
let realTimeUpdates = true;

/**
 * Inicializar dashboard
 */
document.addEventListener('DOMContentLoaded', function() {
    if (document.getElementById('huellero-dashboard')) {
        initDashboard();
    }
});

function initDashboard() {
    loadDashboardData();
    setupCharts();
    setupRealTimeUpdates();
    setupControlButtons();

    // Actualizar cada 30 segundos
    dashboardRefreshInterval = setInterval(loadDashboardData, 30000);
}

/**
 * Cargar datos del dashboard
 */
async function loadDashboardData() {
    try {
        const response = await fetch('/huellero/dashboard/datos', {
            headers: {
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            }
        });

        const data = await response.json();

        if (data.success) {
            updateDashboardStats(data.estadisticas);
            updateDeviceStatus(data.dispositivos);
            updateRecentActivity(data.actividad_reciente);
            updateCharts(data.graficos);
        }

    } catch (error) {
        console.error('Error cargando datos del dashboard:', error);
        showDashboardError('Error cargando datos del dashboard');
    }
}

/**
 * Actualizar estadísticas principales
 */
function updateDashboardStats(stats) {
    // Total personas registradas
    updateStatCard('total-personas', stats.total_personas, 'personas registradas');

    // Total huellas registradas
    updateStatCard('total-huellas', stats.total_huellas, 'huellas registradas');

    // Accesos hoy
    updateStatCard('accesos-hoy', stats.accesos_hoy, 'accesos hoy');

    // Tasa de éxito
    const tasaExito = stats.total_verificaciones > 0
        ? Math.round((stats.verificaciones_exitosas / stats.total_verificaciones) * 100)
        : 0;
    updateStatCard('tasa-exito', tasaExito + '%', 'tasa de éxito');

    // Actualizar tendencias
    updateTrend('personas-trend', stats.tendencia_personas);
    updateTrend('huellas-trend', stats.tendencia_huellas);
    updateTrend('accesos-trend', stats.tendencia_accesos);
    updateTrend('exito-trend', stats.tendencia_exito);
}

/**
 * Actualizar tarjeta de estadística
 */
function updateStatCard(cardId, value, label) {
    const valueElement = document.getElementById(cardId + '-value');
    const labelElement = document.getElementById(cardId + '-label');

    if (valueElement) {
        // Animación de número
        animateNumber(valueElement, value);
    }

    if (labelElement) {
        labelElement.textContent = label;
    }
}

/**
 * Actualizar indicador de tendencia
 */
function updateTrend(trendId, trendData) {
    const trendElement = document.getElementById(trendId);
    if (!trendElement || !trendData) return;

    const { porcentaje, direccion } = trendData;

    trendElement.className = `trend-indicator ${direccion === 'up' ? 'trend-up' : direccion === 'down' ? 'trend-down' : 'trend-neutral'}`;

    const icon = direccion === 'up' ? 'bi-arrow-up' : direccion === 'down' ? 'bi-arrow-down' : 'bi-dash';

    trendElement.innerHTML = `
        <i class="bi ${icon}"></i>
        <span>${Math.abs(porcentaje)}%</span>
    `;
}

/**
 * Actualizar estado de dispositivos
 */
function updateDeviceStatus(dispositivos) {
    const container = document.getElementById('devices-status');

    if (dispositivos.length === 0) {
        container.innerHTML = `
            <div class="alert alert-warning">
                <i class="bi bi-exclamation-triangle me-2"></i>
                No hay dispositivos conectados
            </div>
        `;
        return;
    }

    let html = '';
    dispositivos.forEach(device => {
        const statusClass = device.conectado ? 'bg-success' : 'bg-danger';
        const statusText = device.conectado ? 'Conectado' : 'Desconectado';
        const statusIcon = device.conectado ? 'bi-check-circle' : 'bi-x-circle';

        html += `
            <div class="device-card mb-3">
                <div class="d-flex align-items-center">
                    <div class="device-icon me-3">
                        <i class="bi bi-fingerprint text-primary" style="font-size: 1.5rem;"></i>
                    </div>
                    <div class="flex-grow-1">
                        <div class="fw-semibold">${device.id}</div>
                        <small class="text-muted">${device.tecnologia || 'Desconocida'}</small>
                    </div>
                    <div class="text-end">
                        <span class="badge ${statusClass}">
                            <i class="bi ${statusIcon} me-1"></i>
                            ${statusText}
                        </span>
                        ${device.conectado ? `<div><small class="text-muted">Última actividad: ${device.ultima_actividad}</small></div>` : ''}
                    </div>
                </div>
            </div>
        `;
    });

    container.innerHTML = html;
}

/**
 * Actualizar actividad reciente
 */
function updateRecentActivity(actividad) {
    const container = document.getElementById('recent-activity');

    if (actividad.length === 0) {
        container.innerHTML = `
            <div class="text-center text-muted py-4">
                <i class="bi bi-clock-history" style="font-size: 2rem;"></i>
                <p class="mt-2">No hay actividad reciente</p>
            </div>
        `;
        return;
    }

    let html = '';
    actividad.forEach(evento => {
        const tipoIcon = {
            'entrada': 'bi-box-arrow-in-right text-success',
            'salida': 'bi-box-arrow-right text-primary',
            'registro': 'bi-person-plus text-info',
            'error': 'bi-exclamation-triangle text-warning'
        };

        const icon = tipoIcon[evento.tipo] || 'bi-circle text-muted';
        const tiempo = new Date(evento.fecha_hora).toLocaleTimeString();

        html += `
            <div class="activity-item d-flex align-items-center py-2">
                <div class="activity-icon me-3">
                    <i class="bi ${icon}" style="font-size: 1.2rem;"></i>
                </div>
                <div class="flex-grow-1">
                    <div class="activity-description">${evento.descripcion}</div>
                    <small class="text-muted">${evento.usuario || 'Sistema'}</small>
                </div>
                <div class="activity-time text-muted">
                    <small>${tiempo}</small>
                </div>
            </div>
        `;
    });

    container.innerHTML = html;
}

/**
 * Configurar gráficos
 */
function setupCharts() {
    // Configurar Chart.js globalmente
    Chart.defaults.responsive = true;
    Chart.defaults.maintainAspectRatio = false;
    Chart.defaults.plugins.legend.display = true;
    Chart.defaults.plugins.tooltip.enabled = true;
}

/**
 * Actualizar gráficos
 */
function updateCharts(datosGraficos) {
    if (datosGraficos.accesos_por_dia) {
        updateAccessChart(datosGraficos.accesos_por_dia);
    }

    if (datosGraficos.accesos_por_hora) {
        updateHourlyChart(datosGraficos.accesos_por_hora);
    }

    if (datosGraficos.top_usuarios) {
        updateTopUsersChart(datosGraficos.top_usuarios);
    }

    if (datosGraficos.calidad_huellas) {
        updateQualityChart(datosGraficos.calidad_huellas);
    }
}

/**
 * Actualizar gráfico de accesos por día
 */
function updateAccessChart(data) {
    const ctx = document.getElementById('accessChart');
    if (!ctx) return;

    // Destruir gráfico existente
    if (chartInstances.accessChart) {
        chartInstances.accessChart.destroy();
    }

    chartInstances.accessChart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: data.labels,
            datasets: [{
                label: 'Entradas',
                data: data.entradas,
                borderColor: 'rgb(75, 192, 192)',
                backgroundColor: 'rgba(75, 192, 192, 0.1)',
                tension: 0.1
            }, {
                label: 'Salidas',
                data: data.salidas,
                borderColor: 'rgb(255, 99, 132)',
                backgroundColor: 'rgba(255, 99, 132, 0.1)',
                tension: 0.1
            }]
        },
        options: {
            responsive: true,
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        stepSize: 1
                    }
                }
            },
            plugins: {
                title: {
                    display: true,
                    text: 'Accesos por Día (Últimos 7 días)'
                }
            }
        }
    });
}

/**
 * Actualizar gráfico de accesos por hora
 */
function updateHourlyChart(data) {
    const ctx = document.getElementById('hourlyChart');
    if (!ctx) return;

    if (chartInstances.hourlyChart) {
        chartInstances.hourlyChart.destroy();
    }

    chartInstances.hourlyChart = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: data.labels,
            datasets: [{
                label: 'Accesos',
                data: data.accesos,
                backgroundColor: 'rgba(54, 162, 235, 0.5)',
                borderColor: 'rgba(54, 162, 235, 1)',
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        stepSize: 1
                    }
                }
            },
            plugins: {
                title: {
                    display: true,
                    text: 'Distribución de Accesos por Hora (Hoy)'
                }
            }
        }
    });
}

/**
 * Actualizar gráfico de usuarios más activos
 */
function updateTopUsersChart(data) {
    const ctx = document.getElementById('topUsersChart');
    if (!ctx) return;

    if (chartInstances.topUsersChart) {
        chartInstances.topUsersChart.destroy();
    }

    chartInstances.topUsersChart = new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: data.nombres,
            datasets: [{
                data: data.accesos,
                backgroundColor: [
                    'rgba(255, 99, 132, 0.8)',
                    'rgba(54, 162, 235, 0.8)',
                    'rgba(255, 205, 86, 0.8)',
                    'rgba(75, 192, 192, 0.8)',
                    'rgba(153, 102, 255, 0.8)'
                ]
            }]
        },
        options: {
            responsive: true,
            plugins: {
                title: {
                    display: true,
                    text: 'Top 5 Usuarios más Activos'
                },
                legend: {
                    position: 'bottom'
                }
            }
        }
    });
}

/**
 * Actualizar gráfico de calidad de huellas
 */
function updateQualityChart(data) {
    const ctx = document.getElementById('qualityChart');
    if (!ctx) return;

    if (chartInstances.qualityChart) {
        chartInstances.qualityChart.destroy();
    }

    chartInstances.qualityChart = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: ['Excelente (80-100%)', 'Buena (60-79%)', 'Regular (40-59%)', 'Pobre (0-39%)'],
            datasets: [{
                label: 'Cantidad de Huellas',
                data: [data.excelente, data.buena, data.regular, data.pobre],
                backgroundColor: [
                    'rgba(40, 167, 69, 0.8)',
                    'rgba(255, 193, 7, 0.8)',
                    'rgba(255, 152, 0, 0.8)',
                    'rgba(220, 53, 69, 0.8)'
                ]
            }]
        },
        options: {
            responsive: true,
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        stepSize: 1
                    }
                }
            },
            plugins: {
                title: {
                    display: true,
                    text: 'Distribución de Calidad de Huellas'
                }
            }
        }
    });
}

/**
 * Configurar actualizaciones en tiempo real
 */
function setupRealTimeUpdates() {
    const toggleButton = document.getElementById('toggle-realtime');

    if (toggleButton) {
        toggleButton.addEventListener('click', () => {
            realTimeUpdates = !realTimeUpdates;

            if (realTimeUpdates) {
                toggleButton.innerHTML = '<i class="bi bi-pause-fill me-2"></i>Pausar';
                toggleButton.className = 'btn btn-warning btn-sm';

                if (!dashboardRefreshInterval) {
                    dashboardRefreshInterval = setInterval(loadDashboardData, 30000);
                }
            } else {
                toggleButton.innerHTML = '<i class="bi bi-play-fill me-2"></i>Reanudar';
                toggleButton.className = 'btn btn-success btn-sm';

                if (dashboardRefreshInterval) {
                    clearInterval(dashboardRefreshInterval);
                    dashboardRefreshInterval = null;
                }
            }
        });
    }
}

/**
 * Configurar botones de control
 */
function setupControlButtons() {
    // Botón de actualizar manualmente
    const refreshButton = document.getElementById('refresh-dashboard');
    if (refreshButton) {
        refreshButton.addEventListener('click', () => {
            showLoading('refresh-dashboard');
            loadDashboardData().finally(() => {
                hideLoading('refresh-dashboard');
            });
        });
    }

    // Botón de exportar datos
    const exportButton = document.getElementById('export-data');
    if (exportButton) {
        exportButton.addEventListener('click', exportDashboardData);
    }

    // Botón de configuración de dispositivos
    const devicesButton = document.getElementById('manage-devices');
    if (devicesButton) {
        devicesButton.addEventListener('click', () => {
            window.location.href = '/huellero/dispositivos';
        });
    }
}

/**
 * Exportar datos del dashboard
 */
async function exportDashboardData() {
    try {
        showLoading('export-data');

        const response = await fetch('/huellero/dashboard/exportar', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            },
            body: JSON.stringify({
                formato: 'excel',
                periodo: '7_dias'
            })
        });

        if (response.ok) {
            const blob = await response.blob();
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = `reporte_huellero_${new Date().toISOString().split('T')[0]}.xlsx`;
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
            window.URL.revokeObjectURL(url);

            showAlert('Reporte exportado exitosamente', 'success');
        } else {
            throw new Error('Error exportando datos');
        }

    } catch (error) {
        showAlert('Error exportando datos: ' + error.message, 'error');
    } finally {
        hideLoading('export-data');
    }
}

/**
 * Animación de números
 */
function animateNumber(element, targetValue) {
    const currentValue = parseInt(element.textContent) || 0;
    const increment = Math.ceil((targetValue - currentValue) / 20);

    if (currentValue === targetValue) return;

    let current = currentValue;
    const timer = setInterval(() => {
        current += increment;

        if ((increment > 0 && current >= targetValue) || (increment < 0 && current <= targetValue)) {
            current = targetValue;
            clearInterval(timer);
        }

        element.textContent = current;
    }, 50);
}

/**
 * Mostrar error en dashboard
 */
function showDashboardError(message) {
    const container = document.getElementById('dashboard-alerts');
    if (container) {
        container.innerHTML = `
            <div class="alert alert-danger alert-dismissible fade show">
                <i class="bi bi-exclamation-triangle me-2"></i>
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        `;
    }
}

/**
 * Limpiar recursos al salir
 */
window.addEventListener('beforeunload', () => {
    if (dashboardRefreshInterval) {
        clearInterval(dashboardRefreshInterval);
    }

    // Destruir gráficos
    Object.values(chartInstances).forEach(chart => {
        if (chart) {
            chart.destroy();
        }
    });
});

/**
 * Funciones de utilidad reutilizadas
 */
function showLoading(buttonId) {
    const button = document.getElementById(buttonId);
    if (button) {
        button.disabled = true;
        const originalText = button.textContent;
        button.dataset.originalText = originalText;
        button.innerHTML = '<i class="bi bi-hourglass-split me-2"></i>Cargando...';
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
    const container = document.getElementById('dashboard-alerts') || document.querySelector('.container');

    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${type === 'error' ? 'danger' : type} alert-dismissible fade show`;
    alertDiv.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;

    container.insertBefore(alertDiv, container.firstChild);

    setTimeout(() => {
        if (alertDiv.parentNode) {
            alertDiv.remove();
        }
    }, 5000);
}
