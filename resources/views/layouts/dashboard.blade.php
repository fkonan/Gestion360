<!DOCTYPE html>
<html lang="es">

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="csrf-token" content="{{ csrf_token() }}">

  <title>@yield('title')</title>

  <script>
    // Dark Mode Toggle
    document.addEventListener("DOMContentLoaded", () => {
      themeManager.initTheme();
      themeManager.setupSystemThemeListener();
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
    body { visibility: hidden; }
    body.show { visibility: visible; }
  </style>
  <!--Vite -->
  @vite(['resources/css/app.css', 'resources/js/app.js','resources/css/custom.css','resources/css/mobile.css'])
  @stack('css')
</head>

<body class="sidebar-mini sidebar-collapse layout-fixed">
  <!-- Toast componente (para AJAX) -->
  <div class="toast-container position-fixed top-0 end-0 p-3" id="toastContainer"></div>

  <div class="wrapper">

    <!-- Pantalla de carga -->
    <div id="mainLoader" class="preloader sidebar-dark-primary flex-column justify-content-center align-items-center">
      <div class="spinner-border text-secondary mt-2" role="status"></div>
      <p class="mt-1 fw-medium text-muted">Cargando...</p>
    </div>

    <!-- Sidebar -->
    <aside class="main-sidebar sidebar-dark-primary elevation-4">
      <div class="sidebar">
        <!-- Usuario -->
        <div class="user-panel mt-3 pt-1 pb-0 d-flex flex-column" style="border:none">
          <button class="btn rounded-circle me-3" title="Cambiar modo oscuro" style="width: 50px" data-theme-toggle>
            <i class="light-icon"></i>
          </button>

          <div class="info d-flex flex-column align-items-center logo-container">
            <img class="logo-responsive" alt="Logo Cope">
          </div>

          <div class="info text-muted" style="user-select: none; font-size: 0.75em;">
            <hr class="mb-2">
            <span class="fw-bold">Nombre:</span>
            <span class="fw-medium" title="{{ $user->persona?->PerNombres }} {{ $user->persona?->PerApellidos }}">
              {{ $user->persona?->PerNombres }} {{ $user->persona?->PerApellidos }}
            </span>
            <br>

            <span class="fw-bold">Rol:</span>
            <span class="fw-medium">
              {{ $user->rol[0] ?? 'SIN ROL' }}
            </span>

            @if (count($user->rol) > 1)
            <span class="fw-medium d-block">
              @foreach (array_slice($user->rol, 1) as $rol)
              {{ $rol }}<br>
              @endforeach
            </span>
            @endif

            <br>

            <span class="fw-bold"> Centro costo:</span>
            <span class="fw-medium" title="{{ $user->obtenerDescripcionCentroCosto() }}">
              {{ $user->obtenerDescripcionCentroCosto() }}
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
      <section class="content-header mb-4 p-2 py-1 bg-primary-subtle"
        style="position: sticky; top: 0; z-index: 1030;">
        <div class="container-fluid my-2 d-flex align-items-center gap-3">
          <a class="pushmenu btn btn-sm text-light navbar-toggler" data-widget="pushmenu" data-enable-remember="true"
            href="#" role="button">
            <i class="fas fa-bars"></i>
          </a>
          <div class="header-toolbar ms-auto">
            <div class="header-info-slot">
              @yield('headerInfo')
            </div>
            <div class="header-actions">
              <div class="position-relative">
                <button id="btnNotificaciones" class="btn btn-sm btn-notificaciones">
                  <i class="fas fa-bell text-white"></i>
                  <span id="badgeNotificaciones" class="badge-notificaciones">3</span>
                </button>
                <div id="dropdownNotificaciones"
                  class="dropdown-noti bg-body text-dark shadow rounded position-absolute mt-2"
                  style="right:0; min-width:300px; display:none; max-height:360px; overflow-y:auto; z-index:1050;">
                  <div class="p-2 border-bottom fw-semibold bg-light text-dark d-flex justify-content-between align-items-center">
                    <span>Notificaciones</span>
                    <i class="fas fa-bell text-primary"></i>
                  </div>
                  <div id="listaNotificaciones" class="list-group list-group-flush small"></div>
                  <div id="sinNotificaciones" class="p-3 text-muted small text-center" style="display:none;">Sin notificaciones.</div>
                </div>
              </div>
            </div>
          </div>
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
  <script src="https://cdn.jsdelivr.net/npm/bootstrap-duallistbox/dist/jquery.bootstrap-duallistbox.min.js"
    defer></script>
  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

  @include('components.notificaciones-js')
  @stack('script')
</body>

</html>
