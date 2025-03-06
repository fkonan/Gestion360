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
                <div class="user-panel my-3 pt-3 pb-2 d-flex flex-column text-left">
                    <div class="info fw-bold">
                        <span style="user-select: none; color: #000;">
                            {{ auth()->user()->persona->PerNombres }} {{ auth()->user()->persona->PerApellidos }}
                        </span>
                    </div>
                    <div class="info">
                        <span style="font-size:.8em">Ultima sesión</span><br>
                        <span style="font-size:.8em">Fecha: {{ auth()->user()->ultimaSesion->first()->SesionFechReg }}  {{ auth()->user()->ultimaSesion->first()->SesionHorReg }} </span>                       
                    </div>
                    <div class="info text-primary fw-bold">
                        <span id="horas"></span>:<span id="minutos"></span>:<span id="segundos"></span>&nbsp;<span id="ampm"></span>
                    </div>
                </div>

                <!-- Menú de Navegación -->
                @include('menu2')
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
