@extends('layouts.app')

@section('title', 'Dashboard - Sistema de Huellas Dactilares')

@push('styles')
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
<style>
    .card-stats {
        transition: transform 0.2s;
    }
    .card-stats:hover {
        transform: translateY(-2px);
    }
    .status-indicator {
        width: 12px;
        height: 12px;
        border-radius: 50%;
        display: inline-block;
        margin-right: 8px;
    }
    .status-online {
        background-color: #28a745;
        animation: pulse 2s infinite;
    }
    .status-offline {
        background-color: #dc3545;
    }
    @keyframes pulse {
        0% { opacity: 1; }
        50% { opacity: 0.5; }
        100% { opacity: 1; }
    }
    .event-timeline {
        max-height: 400px;
        overflow-y: auto;
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
                        Sistema de Huellas Dactilares
                    </h1>
                    <p class="text-muted mb-0">Dashboard de control y monitoreo</p>
                </div>
                <div class="d-flex gap-2">
                    <a href="{{ route('huellero.captura.index') }}" class="btn btn-primary">
                        <i class="bi bi-plus-circle me-1"></i>
                        Capturar Huella
                    </a>
                    <a href="{{ route('huellero.verificacion.index') }}" class="btn btn-success">
                        <i class="bi bi-check-circle me-1"></i>
                        Verificar Acceso
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Estado del Servicio -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="alert {{ $servicioDisponible ? 'alert-success' : 'alert-danger' }} d-flex align-items-center" role="alert">
                <span class="status-indicator {{ $servicioDisponible ? 'status-online' : 'status-offline' }}"></span>
                <div>
                    <strong>Estado del Servicio Digital Persona:</strong>
                    {{ $servicioDisponible ? 'Conectado y funcionando' : 'Desconectado o no disponible' }}
                    @if(!$servicioDisponible)
                        <br><small>Verifique que el Digital Persona Lite Client esté instalado y ejecutándose.</small>
                    @endif
                </div>
                <button type="button" class="btn btn-sm btn-outline-{{ $servicioDisponible ? 'success' : 'danger' }} ms-auto" onclick="verificarEstadoServicio()">
                    <i class="bi bi-arrow-clockwise me-1"></i>
                    Verificar
                </button>
            </div>
        </div>
    </div>

    <!-- Estadísticas principales -->
    <div class="row mb-4">
        {{-- <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-primary shadow h-100 py-2 card-stats">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                Personas Dentro Hoy
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800" id="stat-personas-dentro">
                                {{ $estadisticasEventos['personas_actualmente_dentro'] ?? 0 }}
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="bi bi-people-fill fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div> --}}

        {{-- <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-success shadow h-100 py-2 card-stats">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                Eventos Hoy
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800" id="stat-eventos-hoy">
                                {{ $estadisticasEventos['total_eventos_hoy'] ?? 0 }}
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="bi bi-calendar-check fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div> --}}

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-info shadow h-100 py-2 card-stats">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                Huellas Registradas
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                {{ $estadisticasHuellas['total_huellas_registradas'] ?? 0 }}
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="bi bi-fingerprint fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-warning shadow h-100 py-2 card-stats">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                Eventos Manuales Hoy
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800" id="stat-eventos-manuales">
                                {{ $estadisticasEventos['eventos_manuales'] ?? 0 }}
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="bi bi-exclamation-triangle fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Gráficos y eventos recientes -->
    <div class="row">
        <!-- Eventos recientes -->
        <div class="col-lg-6 mb-4">
            <div class="card shadow">
                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="bi bi-clock-history me-2"></i>
                        Eventos Recientes
                    </h6>
                    <div class="dropdown no-arrow">
                        <a class="dropdown-toggle" href="#" role="button" id="dropdownMenuLink" data-bs-toggle="dropdown">
                            <i class="bi bi-three-dots-vertical text-gray-400"></i>
                        </a>
                        <div class="dropdown-menu dropdown-menu-end shadow">
                            <a class="dropdown-item" href="{{ route('huellero.eventos.index') }}">Ver todos</a>
                            <div class="dropdown-divider"></div>
                            <a class="dropdown-item" href="#" onclick="actualizarEventosRecientes()">Actualizar</a>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <div class="event-timeline" id="eventos-recientes">
                        @forelse($eventosRecientes as $evento)
                            <div class="d-flex align-items-center mb-3 evento-item">
                                <div class="flex-shrink-0">
                                    <i class="{{ $evento->icono_evento }} fa-lg"></i>
                                </div>
                                <div class="flex-grow-1 ms-3">
                                    <div class="d-flex justify-content-between">
                                        <div>
                                            <h6 class="mb-0">{{ $evento->persona->nombreCompleto() }}</h6>
                                            <small class="text-muted">{{ $evento->persona->identificacion }}</small>
                                        </div>
                                        <div class="text-end">
                                            <span class="{{ $evento->clase_registro }}">{{ $evento->tipo_registro_texto }}</span>
                                            <br>
                                            <small class="text-muted">{{ $evento->fechaevento->format('H:i:s') }}</small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @empty
                            <div class="text-center text-muted py-4">
                                <i class="bi bi-inbox fa-3x mb-3"></i>
                                <p>No hay eventos recientes</p>
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>

        <!-- Dispositivos conectados -->
        <div class="col-lg-6 mb-4">
            <div class="card shadow">
                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="bi bi-usb-symbol me-2"></i>
                        Dispositivos Conectados
                    </h6>
                    <button class="btn btn-sm btn-outline-primary" onclick="actualizarDispositivos()">
                        <i class="bi bi-arrow-clockwise me-1"></i>
                        Actualizar
                    </button>
                </div>
                <div class="card-body">
                    <div id="dispositivos-lista">
                        <div class="text-center">
                            <div class="spinner-border text-primary" role="status">
                                <span class="visually-hidden">Cargando...</span>
                            </div>
                            <p class="mt-2 text-muted">Verificando dispositivos...</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Estadísticas adicionales -->
    <div class="row">
        <div class="col-lg-4 mb-4">
            <div class="card shadow">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="bi bi-bar-chart me-2"></i>
                        Entradas vs Salidas Hoy
                    </h6>
                </div>
                <div class="card-body">
                    <div class="chart-container">
                        <canvas id="entradasSalidasChart" width="100" height="50"></canvas>
                    </div>
                    <div class="mt-3">
                        <div class="row text-center">
                            <div class="col">
                                <span class="text-success">
                                    <i class="bi bi-box-arrow-in-right"></i>
                                    Entradas: <strong id="total-entradas">{{ $estadisticasEventos['entradas_hoy'] ?? 0 }}</strong>
                                </span>
                            </div>
                            <div class="col">
                                <span class="text-warning">
                                    <i class="bi bi-box-arrow-right"></i>
                                    Salidas: <strong id="total-salidas">{{ $estadisticasEventos['salidas_hoy'] ?? 0 }}</strong>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- <div class="col-lg-8 mb-4">
            <div class="card shadow">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="bi bi-exclamation-triangle me-2"></i>
                        Tipos de Errores Hoy
                    </h6>
                </div>
                <div class="card-body">
                    <div id="errores-lista">
                        @if(isset($estadisticasEventos['errores_por_tipo']) && count($estadisticasEventos['errores_por_tipo']) > 0)
                            @foreach($estadisticasEventos['errores_por_tipo'] as $tipoError => $cantidad)
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <span class="text-muted">{{ $tipoError }}</span>
                                    <span class="badge bg-warning text-dark">{{ $cantidad }}</span>
                                </div>
                            @endforeach
                        @else
                            <div class="text-center text-muted py-3">
                                <i class="bi bi-check-circle fa-2x mb-2 text-success"></i>
                                <p>No hay errores registrados hoy</p>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div> --}}
    </div>
</div>
@endsection

@push('script')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Actualizar datos en tiempo real cada 30 segundos
    setInterval(actualizarDatosRealTime, 30000);

    // Cargar dispositivos al iniciar
    actualizarDispositivos();

    // Inicializar gráfico
    inicializarGraficoEntradaSalida();
});

function actualizarDatosRealTime() {
    fetch('{{ route("huellero.dashboard.datos") }}')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Actualizar estadísticas
               //  document.getElementById('stat-personas-dentro').textContent = data.estadisticas.personas_actualmente_dentro;
               //  document.getElementById('stat-eventos-hoy').textContent = data.estadisticas.total_eventos_hoy;
                document.getElementById('stat-eventos-manuales').textContent = data.estadisticas.eventos_manuales;

                // Actualizar eventos recientes
                actualizarEventosRecientesDOM(data.eventos_recientes);

                // Actualizar gráfico
                actualizarGrafico(data.estadisticas.entradas_hoy, data.estadisticas.salidas_hoy);
            }
        })
        .catch(error => console.error('Error actualizando datos:', error));
}

function actualizarEventosRecientes() {
    const container = document.getElementById('eventos-recientes');
    container.innerHTML = '<div class="text-center"><div class="spinner-border text-primary" role="status"></div></div>';

    actualizarDatosRealTime();
}

function actualizarEventosRecientesDOM(eventos) {
    const container = document.getElementById('eventos-recientes');

    if (eventos.length === 0) {
        container.innerHTML = `
            <div class="text-center text-muted py-4">
                <i class="bi bi-inbox fa-3x mb-3"></i>
                <p>No hay eventos recientes</p>
            </div>
        `;
        return;
    }

    container.innerHTML = eventos.map(evento => `
        <div class="d-flex align-items-center mb-3 evento-item">
            <div class="flex-shrink-0">
                <i class="${evento.icono} fa-lg"></i>
            </div>
            <div class="flex-grow-1 ms-3">
                <div class="d-flex justify-content-between">
                    <div>
                        <h6 class="mb-0">${evento.persona}</h6>
                        <small class="text-muted">${evento.identificacion}</small>
                    </div>
                    <div class="text-end">
                        <span class="${evento.clase_registro}">${evento.tipo_registro}</span>
                        <br>
                        <small class="text-muted">${evento.fecha_evento}</small>
                    </div>
                </div>
            </div>
        </div>
    `).join('');
}

function actualizarDispositivos() {
    const container = document.getElementById('dispositivos-lista');
    container.innerHTML = '<div class="text-center"><div class="spinner-border text-primary" role="status"></div></div>';

    fetch('{{ route("huellero.dashboard.dispositivos") }}')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                if (data.dispositivos.length === 0) {
                    container.innerHTML = `
                        <div class="text-center text-muted py-3">
                            <i class="bi bi-usb-plug fa-2x mb-2"></i>
                            <p>No hay dispositivos conectados</p>
                            <small>Conecte un lector Digital Persona</small>
                        </div>
                    `;
                } else {
                    container.innerHTML = data.dispositivos.map(dispositivo => `
                        <div class="d-flex justify-content-between align-items-center mb-2 p-2 border rounded">
                            <div>
                                <strong>Digital Persona (${dispositivo})</strong>
                                <br>
                                <small class="text-muted">ID: ${dispositivo}</small>
                            </div>
                            <div>
                                <span class="badge bg-success">Conectado</span>
                                <button class="btn btn-sm btn-outline-info ms-1" onclick="verInfoDispositivo('${dispositivo}')">
                                    <i class="bi bi-info-circle"></i>
                                </button>
                            </div>
                        </div>
                    `).join('');
                }
            } else {
                container.innerHTML = `
                    <div class="text-center text-muted py-3">
                        <i class="bi bi-exclamation-triangle fa-2x mb-2 text-warning"></i>
                        <p>Error al obtener dispositivos</p>
                        <small>${data.message}</small>
                    </div>
                `;
            }
        })
        .catch(error => {
            container.innerHTML = `
                <div class="text-center text-muted py-3">
                    <i class="bi bi-wifi-off fa-2x mb-2 text-danger"></i>
                    <p>Error de conexión</p>
                    <small>Verifique el servicio Digital Persona</small>
                </div>
            `;
        });
}

function verInfoDispositivo(deviceId) {
    // Implementar modal con información del dispositivo
    fetch('{{ route("huellero.dashboard.info_dispositivo") }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        },
        body: JSON.stringify({ device_id: deviceId })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            mostrarModalInfoDispositivo(data.info);
        }
    });
}

function verificarEstadoServicio() {
    location.reload();
}

let entradasSalidasChart;

function inicializarGraficoEntradaSalida() {
    const ctx = document.getElementById('entradasSalidasChart').getContext('2d');
    const entradas = parseInt(document.getElementById('total-entradas').textContent);
    const salidas = parseInt(document.getElementById('total-salidas').textContent);

    entradasSalidasChart = new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: ['Entradas', 'Salidas'],
            datasets: [{
                data: [entradas, salidas],
                backgroundColor: ['#28a745', '#ffc107'],
                borderWidth: 0
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
                }
            }
        }
    });
}

function actualizarGrafico(entradas, salidas) {
    if (entradasSalidasChart) {
        entradasSalidasChart.data.datasets[0].data = [entradas, salidas];
        entradasSalidasChart.update();

        document.getElementById('total-entradas').textContent = entradas;
        document.getElementById('total-salidas').textContent = salidas;
    }
}
</script>
@endpush
