<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>@yield('title')</title>

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/css/adminlte.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-duallistbox/dist/bootstrap-duallistbox.min.css">

     <!--Vite -->
     @vite(['resources/css/app.css', 'resources/js/app.js','resources/css/custom.css'])
</head>

<body class="sidebar-mini layout-fixed">
    <div class="wrapper">
        <!-- Sidebar -->
        <aside class="main-sidebar sidebar-dark-primary elevation-4 text-dark bg-light">

            <div class="sidebar">
            <!-- Usuario -->
                <div class="user-panel my-3 pt-3 pb-2 d-flex flex-column text-left">
                    <div class="info">
                        <span style="user-select: none" class="fw-medium text-dark">
                            {{ auth()->user()->persona->PerNombres }} {{ auth()->user()->persona->PerApellidos }}
                        </span>
                    </div>
                    <div class="info text-primary fw-medium">
                        <span id="horas"></span>:<span id="minutos"></span>:<span id="segundos"></span>&nbsp;<span id="ampm"></span>
                    </div>
                </div>

                <!-- Menú de Navegación -->
                @include('menu')
            </div>
        </aside>

        <!-- Contenido Principal -->
        <div class="content-wrapper">
            <section class="content-header mb-4 p-2 d-flex justify-content-between align-items-center" style="background-color: #0B5ED7;">
                <div class="container-fluid">
                    <button class="btn btn-sm text-light" onclick="toggleSidebar()">
                        ☰ 
                    </button>
                </div>
                <div class="info pe-4 text-light">
                    <span>Ultima sesión</span><br>
                    <span style="font-size: .8em;" class="text-nowrap">
                        {{ auth()->user()->ultimaSesion->first()->SesionFechReg }}  /
                        {{ auth()->user()->ultimaSesion->first()->SesionHorReg }} 
                    </span>                       
                </div>
            </section>

            <section class="content">
                <div class="container-fluid pb-4">
                    @yield('content')
                </div>
            </section>
        </div>
    </div>

    <!-- Renderizado de componentes-->
    <x-alert /> 
    <x-modal />

    <script src="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/js/adminlte.min.js" defer></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap-duallistbox/dist/jquery.bootstrap-duallistbox.min.js" defer></script>
    
    <script>
        function toggleSidebar() {
            document.body.classList.toggle('sidebar-collapse');
        }

        //Modal (evita errores de focus)
        document.addEventListener('hidden.bs.modal', function (event) {
            if (document.activeElement) {
                document.activeElement.blur();
            }
        });
    </script>

    @stack('script')
</body>
</html>