<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title')</title>

    <link rel="preload" href="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/css/adminlte.min.css" as="style" onload="this.onload=null;this.rel='stylesheet'">
    <noscript>
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/css/adminlte.min.css">
    </noscript>

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-duallistbox/dist/bootstrap-duallistbox.min.css">

     <!--Vite -->
     @vite(['resources/css/app.css', 'resources/js/app.js','resources/css/custom.css','resources/css/mobile.css'])
</head>

<body class="sidebar-mini sidebar-collapse layout-fixed">
    <div class="wrapper">
        <!-- Pantalla de carga -->
        <div class="preloader flex-column justify-content-center align-items-center">
            <div class="spinner-border text-primary mt-2" role="status"></div>
            <p class="mt-1 fw-medium text-muted">Cargando...</p>
        </div>
        
        <!-- Sidebar -->
        <aside class="main-sidebar sidebar-dark-primary elevation-4 text-dark bg-light">

            <div class="sidebar">
            <!-- Usuario -->
                <div class="user-panel my-3 pt-1 pb-2 d-flex flex-column" style="border-bottom: 1px solid rgba(157, 159, 161, 0.7);">
                    <div class="info" style="width: 235px; margin: 0 auto;">
                        <img src="{{ asset('img/LogoCope.png') }}" style="width:100%; height: auto;" alt="Logo Cope">
                    </div>
                    
                    <div class="info" style="user-select: none; font-size: 0.75em">
                        <span class="fw-bold text-dark">Nombre:</span>
                        <span class="fw-medium">
                            {{ auth()->user()->persona?->PerNombres }} {{ auth()->user()->persona?->PerApellidos }}
                        </span>
                        <br>

                        <span class="fw-bold text-dark"> Rol:</span>
                        <span class="fw-medium">
                            {{ auth()->user()->rol }}
                        </span>
                        <br>

                        <span class="fw-bold text-dark"> Ultima sesión:</span>
                        <span class="fw-medium">
                            {{ auth()->user()->ultimaSesion->first()->SesionFechReg }} 
                            {{ auth()->user()->ultimaSesion->first()->SesionHorReg }} 
                        </span>
                    </div>

                    <div class="info text-primary">
                        <span id="horas"></span>:<span id="minutos"></span>:<span id="segundos"></span>&nbsp;<span id="ampm"></span>
                    </div>
                </div>

                <!-- Menú de Navegación -->
                @include('menu')
            </div>
        </aside>

        <!-- Contenido Principal -->
        <div class="content-wrapper">
            <section class="content-header mb-4 p-2 py-1 d-flex justify-content-between align-items-center bg-secondary">
                <div class="container-fluid my-1">
                    <a class="pushmenu btn btn-sm text-light" 
                        data-widget="pushmenu" 
                        data-enable-remember="true" 
                        href="#" 
                        role="button">
                        <i class="fas fa-bars"></i>
                    </a>      
                </div>
            </section>

            <section class="content">
                <div class="container-fluid pb-4">
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


        
        
</script>

    </script>

    @stack('script')
</body>
</html>