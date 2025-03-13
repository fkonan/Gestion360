<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>@yield('title')</title>
    
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/css/adminlte.min.css">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
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
                    <div class="info fw-bold">
                        <span style="user-select: none; color: #000;">
                            {{ auth()->user()->persona->PerNombres }} {{ auth()->user()->persona->PerApellidos }}
                        </span>
                    </div>
                    <div class="info text-primary fw-bold">
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

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>


    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.4/jquery.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery-validate/1.19.5/jquery.validate.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/js/adminlte.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap-duallistbox/dist/jquery.bootstrap-duallistbox.min.js"></script>

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
