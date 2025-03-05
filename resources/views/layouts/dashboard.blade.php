<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>@yield('title')</title>
    
    <!-- Iconos -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">

    <!-- AdminLTE CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/css/adminlte.min.css">

    <!--Vite -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        body {
            height: 100vh;
            overflow: hidden;
        }
        .content-wrapper {
            display: flex;
            flex-direction: column;
            min-height: 100vh;
            background-color: #f4f6f9; 
        }
        .content {
            flex: 1; 
        }
        .user-panel {
            text-align: center;
        }
        .nav-treeview {
            background-color: #f8f9fa; 
        }
    </style>
</head>

<body class="sidebar-mini layout-fixed">
    <div class="wrapper">
        <!-- Sidebar -->
        <aside class="main-sidebar sidebar-dark-primary elevation-4 text-dark bg-light">

            <div class="sidebar">
            <!-- Usuario -->
                <div class="user-panel mt-3 pb-3 mb-3 d-flex flex-column align-items-center">
                    <div class="info">
                        <span style="user-select: none; color: #000; font-weight: bold;">
                            {{ auth()->user()->persona->PerNombres }} <br>
                            {{ auth()->user()->persona->PerApellidos }}
                        </span>
                    </div>
                    <div class="info">
                        <span id="horas"></span>:<span id="minutos"></span>:<span id="segundos"></span>&nbsp;<span id="ampm"></span>
                    </div>
                </div>

                <!-- Menú de Navegación -->
                <nav class="mt-2">
                    <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu">
                        <li class="nav-item">
                            <a style="background-color:#EAE9E9; color:#6c757d;" href="{{ route('formatos.index') }}" class="nav-link">
                                <i class="nav-icon fas fa-map" style="color: #0E2146;"></i>
                                <p><b>Procesos</b></p>
                            </a>
                        </li>
                       
                        <li class="nav-item has-treeview">
                            <a style="background-color:#EAE9E9; color:#6c757d; font-size:16px;" href="#" class="nav-link">
                                <i class="nav-icon fas fa-user-tie" style="color: #0E2146;"></i>
                                <p><b>Administración</b><i class="right fas fa-angle-left"></i></p>
                            </a>
                            <ul class="nav nav-treeview">
                                <li class="nav-item">
                                    <a style="color:#6c757d;" href="{{ route('persona.index') }}" class="nav-link">
                                        <i class="nav-icon fas fa-people-arrows"></i>
                                        <p><b>Personas</b></p>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a style="color:#6c757d;" href="{{ route('usuarios.index') }}" class="nav-link">
                                        <i class="nav-icon fas fa-user-tie"></i>
                                        <p><b>Usuarios</b></p>
                                    </a>
                                </li>
                            </ul>
                        </li>

                        
                        <li class="nav-item has-treeview">
                            <a style="background-color:#D0CCFA; color:#000000; font-size:16px;" href="#" class="nav-link">
                                <i class="nav-icon fas fa-cloud"></i>
                                <p><b>Sesión</b><i class="right fas fa-angle-left"></i></p>
                            </a>
                            <ul class="nav nav-treeview">
                                <li class="nav-item">
                                    <a href="#" onclick="document.getElementById('logoutForm').submit();" class="nav-link bg-danger">
                                        <i class="fas fa-sign-out-alt"></i>
                                        <p>Cerrar sesión</p>
                                    </a>
                                    <form id="logoutForm" action="{{ route('logout') }}" method="POST" style="display: none;">
                                        @csrf
                                    </form>
                                </li>
                            </ul>
                        </li>
                    </ul>
                </nav>
            </div>
        </aside>

        <!-- Contenido Principal -->
        <div class="content-wrapper">
            <section class="content-header mb-4" style="background-color: #005EB8;">
                <div class="container-fluid">
                    <button class="btn btn-sm text-light" onclick="toggleSidebar()">
                        ☰ 
                    </button>
                </div>
            </section>

            <section class="content">
                <div class="container-fluid">
                    @yield('content')
                </div>
            </section>
        </div>

    </div>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <!-- jQuery (requerido para AdminLTE) -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.4/jquery.min.js"></script>

    <!-- AdminLTE JS (asegura funcionalidad de treeview y sidebar) -->
    <script src="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/js/adminlte.min.js"></script>

    <!-- Script adicional -->
    <script>
        function toggleSidebar() {
            document.body.classList.toggle('sidebar-collapse');
        }
    </script>

    <!-- Alertas de swal (components/alert)-->
    <x-alert /> 

    @yield('script')
</body>
</html>
