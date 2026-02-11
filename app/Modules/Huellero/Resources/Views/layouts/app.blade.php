<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Sistema de Huellas') - {{ config('app.name') }}</title>

    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">

    <!-- Chart.js para gráficos del dashboard -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <!-- Digital Persona SDK -->
    <script src="{{ asset('vendor/digital-persona/es6-shim.js') }}"></script>
    <script src="{{ asset('vendor/digital-persona/websdk.client.bundle.min.js') }}"></script>
    <script src="{{ asset('vendor/digital-persona/fingerprint.sdk.min.js') }}"></script>

    <!-- Assets principales de Laravel -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <!-- Assets específicos del huellero -->
    @vite([
        'resources/js/huellero/laravel-fingerprint-sdk.js',
        'resources/js/huellero/integration-bridge.js',
        'resources/js/huellero/dashboard.js',
        'resources/js/huellero/captura.js',
        'resources/js/huellero/verificacion.js'
    ])

    <!-- Estilos específicos del huellero -->
    <style>
        .huellero-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 1rem 0;
            margin-bottom: 2rem;
        }

        .navbar-huellero {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-bottom: 1px solid rgba(0, 0, 0, 0.1);
        }

        .fingerprint-icon {
            background: linear-gradient(45deg, #667eea, #764ba2);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .device-status-indicator {
            width: 12px;
            height: 12px;
            border-radius: 50%;
            display: inline-block;
            margin-right: 8px;
        }

        .device-status-connected {
            background-color: #28a745;
            box-shadow: 0 0 8px rgba(40, 167, 69, 0.6);
        }

        .device-status-disconnected {
            background-color: #dc3545;
            box-shadow: 0 0 8px rgba(220, 53, 69, 0.6);
        }

        .capture-preview {
            border: 2px dashed #dee2e6;
            border-radius: 8px;
            min-height: 300px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #f8f9fa;
        }

        .capture-preview.active {
            border-color: #007bff;
            background: rgba(0, 123, 255, 0.05);
        }

        .quality-bar {
            height: 20px;
            border-radius: 10px;
            transition: all 0.3s ease;
        }

        .quality-excellent { background: linear-gradient(90deg, #28a745, #20c997); }
        .quality-good { background: linear-gradient(90deg, #ffc107, #fd7e14); }
        .quality-fair { background: linear-gradient(90deg, #fd7e14, #dc3545); }
        .quality-poor { background: linear-gradient(90deg, #dc3545, #6f42c1); }

        .step-indicator {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            border: 2px solid #dee2e6;
            color: #6c757d;
            font-weight: 600;
        }

        .step-indicator.active {
            background: #007bff;
            border-color: #007bff;
            color: white;
        }

        .step-indicator.completed {
            background: #28a745;
            border-color: #28a745;
            color: white;
        }

        .persona-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
        }

        .finger-option {
            border: 2px solid #dee2e6;
            border-radius: 8px;
            padding: 1rem;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .finger-option:hover {
            border-color: #007bff;
            background: rgba(0, 123, 255, 0.05);
        }

        .finger-option.selected {
            border-color: #007bff;
            background: rgba(0, 123, 255, 0.1);
            color: #007bff;
        }

        .access-result {
            animation: slideIn 0.5s ease-out;
        }

        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .stats-card {
            border-radius: 15px;
            border: none;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease;
        }

        .stats-card:hover {
            transform: translateY(-5px);
        }

        .trend-indicator {
            font-size: 0.8rem;
            font-weight: 600;
        }

        .trend-up { color: #28a745; }
        .trend-down { color: #dc3545; }
        .trend-neutral { color: #6c757d; }

        .sidebar-huellero {
            background: linear-gradient(180deg, #f8f9fa 0%, #e9ecef 100%);
            border-right: 1px solid #dee2e6;
            min-height: calc(100vh - 100px);
        }

        .main-content {
            background: #ffffff;
            min-height: calc(100vh - 100px);
        }

        .loading-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 9999;
        }

        .loading-spinner {
            background: white;
            padding: 2rem;
            border-radius: 10px;
            text-align: center;
        }
    </style>

    @stack('styles')
</head>
<body>
    <!-- Header Principal -->
    <header class="huellero-header">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <h1 class="h2 mb-0">
                        <i class="bi bi-fingerprint fingerprint-icon me-2"></i>
                        Sistema de Control de Acceso
                    </h1>
                    <p class="mb-0 opacity-75">Gestión integral de huellas dactilares</p>
                </div>
                <div class="col-md-6 text-end">
                    <div class="d-flex align-items-center justify-content-end">
                        <div class="me-3">
                            <small class="opacity-75">Estado del Sistema:</small>
                            <div id="system-status" class="d-flex align-items-center">
                                <span class="device-status-indicator device-status-connected"></span>
                                <span>Operativo</span>
                            </div>
                        </div>
                        <a href="{{ route('home') }}" class="btn btn-outline-light btn-sm">
                            <i class="bi bi-house me-1"></i>
                            Inicio
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <!-- Navegación secundaria -->
    <nav class="navbar navbar-expand-lg navbar-huellero sticky-top">
        <div class="container">
            <div class="navbar-nav">
                <a class="nav-link {{ request()->routeIs('huellero.dashboard') ? 'active' : '' }}"
                   href="{{ route('huellero.dashboard') }}">
                    <i class="bi bi-speedometer2 me-1"></i>
                    Dashboard
                </a>
                <a class="nav-link {{ request()->routeIs('huellero.captura*') ? 'active' : '' }}"
                   href="{{ route('huellero.captura.index') }}">
                    <i class="bi bi-person-plus me-1"></i>
                    Captura
                </a>
                <a class="nav-link {{ request()->routeIs('huellero.verificacion*') ? 'active' : '' }}"
                   href="{{ route('huellero.verificacion.index') }}">
                    <i class="bi bi-shield-check me-1"></i>
                    Verificación
                </a>
                <a class="nav-link {{ request()->routeIs('huellero.eventos*') ? 'active' : '' }}"
                   href="{{ route('huellero.eventos.index') }}">
                    <i class="bi bi-clock-history me-1"></i>
                    Eventos
                </a>
            </div>

            <div class="ms-auto">
                <div class="d-flex align-items-center">
                    <div class="me-3">
                        <small class="text-muted">Usuario:</small>
                        <strong>{{ auth()->user()->name ?? 'Sistema' }}</strong>
                    </div>
                    <div class="me-3">
                        <small class="text-muted">Fecha:</small>
                        <strong>{{ now()->format('d/m/Y H:i') }}</strong>
                    </div>
                </div>
            </div>
        </div>
    </nav>

    <!-- Contenido principal -->
    <main class="py-4">
        <!-- Alertas globales -->
        <div id="global-alerts" class="container">
            @if(session('success'))
                <div class="alert alert-success alert-dismissible fade show">
                    <i class="bi bi-check-circle me-2"></i>
                    {{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            @if(session('error'))
                <div class="alert alert-danger alert-dismissible fade show">
                    <i class="bi bi-exclamation-triangle me-2"></i>
                    {{ session('error') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            @if(session('warning'))
                <div class="alert alert-warning alert-dismissible fade show">
                    <i class="bi bi-exclamation-triangle me-2"></i>
                    {{ session('warning') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif
        </div>

        @yield('content')
    </main>

    <!-- Footer -->
    <footer class="bg-light border-top py-3 mt-auto">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <small class="text-muted">
                        © {{ date('Y') }} {{ config('app.name') }}.
                        Sistema de Control de Acceso v2.0
                    </small>
                </div>
                <div class="col-md-6 text-end">
                    <small class="text-muted">
                        Desarrollado con Laravel {{ app()->version() }}
                    </small>
                </div>
            </div>
        </div>
    </footer>

    <!-- Overlay de carga global -->
    <div id="loading-overlay" class="loading-overlay d-none">
        <div class="loading-spinner">
            <div class="spinner-border text-primary mb-3" role="status">
                <span class="visually-hidden">Cargando...</span>
            </div>
            <div>Procesando...</div>
        </div>
    </div>

    <!-- Scripts adicionales -->
    <script>
        // Configuración global para el módulo huellero
        window.huelleroConfig = {
            routes: {
                dashboard: '{{ route("huellero.dashboard") }}',
                captura: '{{ route("huellero.captura.index") }}',
                verificacion: '{{ route("huellero.verificacion.index") }}',
                eventos: '{{ route("huellero.eventos.index") }}'
            },
            csrf: '{{ csrf_token() }}',
            usuario: '{{ auth()->user()->name ?? "Sistema" }}'
        };

        // Función global para mostrar mensajes
        window.showStatusMessage = function(message, type = 'info') {
            const alertDiv = document.createElement('div');
            alertDiv.className = `alert alert-${type === 'error' ? 'danger' : type} alert-dismissible fade show`;
            alertDiv.innerHTML = `
                <i class="bi bi-${type === 'success' ? 'check-circle' : type === 'error' ? 'exclamation-triangle' : 'info-circle'} me-2"></i>
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            `;

            const container = document.getElementById('global-alerts');
            container.appendChild(alertDiv);

            // Auto-remover después de 5 segundos
            setTimeout(() => {
                if (alertDiv.parentNode) {
                    alertDiv.remove();
                }
            }, 5000);
        };

        // Función global para overlay de carga
        window.showGlobalLoading = function() {
            document.getElementById('loading-overlay').classList.remove('d-none');
        };

        window.hideGlobalLoading = function() {
            document.getElementById('loading-overlay').classList.add('d-none');
        };

        // Actualizar estado del sistema cada minuto
        setInterval(function() {
            const statusElement = document.getElementById('system-status');
            const now = new Date();
            const timeSpan = statusElement.querySelector('span:last-child');

            if (window.laravelFingerprintSDK) {
                window.laravelFingerprintSDK.getDevices().then(devices => {
                    const connected = devices.length > 0;
                    const indicator = statusElement.querySelector('.device-status-indicator');

                    indicator.className = `device-status-indicator ${connected ? 'device-status-connected' : 'device-status-disconnected'}`;
                    timeSpan.textContent = connected ? 'Operativo' : 'Sin dispositivos';
                }).catch(() => {
                    const indicator = statusElement.querySelector('.device-status-indicator');
                    indicator.className = 'device-status-indicator device-status-disconnected';
                    timeSpan.textContent = 'Error de SDK';
                });
            }
        }, 60000);
    </script>

    @stack('scripts')
</body>
</html>
