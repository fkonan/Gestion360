<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title')</title>

    <script>
      // Dark Mode Toggle
      document.addEventListener("DOMContentLoaded", () => {
         themeManager.initTheme();
         // Configurar listener para cambios del sistema
         themeManager.setupSystemThemeListener();
         // Configurar botón toggle si existe
         const toggleButton = document.querySelector("[data-theme-toggle]");
         if (toggleButton) {
            toggleButton.addEventListener("click", () => {
               themeManager.toggleTheme();
            });
         }
      });
   </script>

    <link rel="icon" href="{{ asset('favicon.png') }}" type="image/png">
    <link rel="preload" href="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/css/adminlte.min.css" as="style" onload="this.onload=null;this.rel='stylesheet'">
    <noscript>
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/css/adminlte.min.css">
    </noscript>

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-duallistbox/dist/bootstrap-duallistbox.min.css">

    <style>
        body {
            visibility: hidden;
        }
        body.show {
            visibility: visible;
        }
    </style>

     <!--Vite -->
     @vite(['resources/css/app.css', 'resources/js/app.js','resources/css/custom.css','resources/css/mobile.css','resources/css/darkmode.css'])
</head>

<body class="sidebar-mini sidebar-collapse layout-fixed">
    <!-- Toast componente (para AJAX) -->
    <div class="toast-container position-fixed top-0 end-0 p-3" id="toastContainer"></div>

    <div class="wrapper">
        <!-- Pantalla de carga -->
        <div class="preloader flex-column justify-content-center align-items-center">
            <div class="spinner-border text-secondary mt-2" role="status"></div>
            <p class="mt-1 fw-medium text-muted">Cargando...</p>
        </div>

        <!-- Sidebar -->
        <aside class="main-sidebar sidebar-dark-primary elevation-4">
            <div class="sidebar">
            <!-- Usuario -->
                <div class="user-panel mt-3 pt-1 pb-0 d-flex flex-column" style="border:none">

                    <!-- <div style="width: 2em; display: flex; align-items: center; justify-content: flex-start;" class="ms-3">
                        <i id="toggleDarkMode" class="light-icon text-dark" style="cursor: pointer;"></i>
                    </div> -->

                    {{-- Icono cambiar theme --}}
                    <button class="btn rounded-circle me-3" title="Cambiar modo oscuro" style="width: 50px" data-theme-toggle>
                       <i class="light-icon"></i>
                    </button>

                    <!-- <div class="info d-flex flex-column align-items-center" style="width: 235px; margin: 0 auto; user-select: none;">
                        <img src="{{ asset('img/LogoCope.png') }}" class="logoCope" style="width:100%; height: auto;" alt="Logo Cope">
                        <img src="{{ asset('img/LogoCopeBlancoFull.png') }}" class="logoCopeBlanco py-3" style="width:80%; height: auto; display:none" alt="Logo Cope ">
                    </div> -->

                    <div class="info d-flex flex-column align-items-center logo-container">
                        <img class="logo-responsive" alt="Logo Cope">
                     </div>

                    <div class="info text-muted" style="user-select: none; font-size: 0.75em;">
                        <hr class="mb-2">
                        <span class="fw-bold">Nombre:</span>
                        <span class="fw-medium">
                            {{ $user->persona?->PerNombres }} {{ $user->persona?->PerApellidos }}
                        </span>
                        <br>

                        <span class="fw-bold"> Rol:</span>
                        <span class="fw-medium">
                            {{ $user->rol }}
                        </span>
                        <br>

                        <span class="fw-bold"> Ultima sesión:</span>
                        <span class="fw-medium">
                            {{ $user->ultimaSesion->first()->SesionFechReg }}
                            {{ $user->ultimaSesion->first()->SesionHorReg }}
                        </span>
                    </div>

                    <div class="horaComponent info text-primary">
                        <span id="horas"></span>:<span id="minutos"></span>:<span id="segundos"></span>&nbsp;<span id="ampm"></span>
                        <hr class="mb-0 mt-2 text-muted">
                    </div>
                </div>

                <!-- Menú de Navegación -->
                @include('menu')
            </div>
        </aside>

        <!-- Contenido Principal -->
        <div class="content-wrapper">
            <section class="content-header mb-4 p-2 py-1 d-flex justify-content-between align-items-center bg-primary"
                style="position: sticky; top: 0; z-index: 1030;">
                <div class="container-fluid my-2">
                    <a class="pushmenu btn btn-sm text-light navbar-toggler"
                        data-widget="pushmenu"
                        data-enable-remember="true"
                        href="#"
                        role="button">
                        <i class="fas fa-bars"></i>
                    </a>
                    <img src="{{ asset('img/LogoCopeBlanco.png') }}" style="width:70px; height: 15px;" alt="Logo Cope" class="d-md-none">
                </div>
            </section>

            <section class="content">
                <div class="container-fluid mb-4">
                    @yield('breadcrumb')
                    @yield('content')
                </div>
            </section>
        </div>
    </div>

    <!-- Renderizado de componentes-->
    <x-alert />
    <x-modal />
    <x-toast />
    <x-loader />

    <script src="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/js/adminlte.min.js" defer></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap-duallistbox/dist/jquery.bootstrap-duallistbox.min.js" defer></script>

    <script>
        //Modal (evita errores de focus)
        document.addEventListener('hidden.bs.modal', function (event) {
            if (document.activeElement) {
                document.activeElement.blur();
            }
        });

        // Función para mostrar el toast
        window.addEventListener('DOMContentLoaded', function () {
            const title = sessionStorage.getItem('toastTitle');
            const type = sessionStorage.getItem('toastType');

            if (title && type) {
                mostrarToast(title, type);
                sessionStorage.removeItem('toastTitle');
                sessionStorage.removeItem('toastType');
            }
        });

        //dark mode toggle
        document.addEventListener('DOMContentLoaded', () => {
            darkModeEnable()
        });

        // Mostrar el cuerpo del documento después de cargar todo
        window.addEventListener('load', function () {
            document.body.classList.add('show');
        });

    </script>

    @stack('script')
</body>
</html>
